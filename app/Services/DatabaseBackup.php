<?php

namespace App\Services;

use App\Support\Format;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Writes the whole database as an SQL script that recreates every table with its rows,
 * in plain PHP so the app container needs no mysqldump. Sessions and cache keep only their
 * structure: their rows are short-lived, and a session id in a leaked file would let anyone
 * take over an admin who is still signed in.
 */
class DatabaseBackup
{
    /** Rows written per INSERT statement. */
    protected const ROWS_PER_INSERT = 100;

    /** Rows read per query, so a large table like site_visits never sits in memory at once. */
    protected const ROWS_PER_READ = 1000;

    /**
     * Tables in the backup with their row count and size on disk (MySQL only).
     *
     * @return list<array{name: string, rows: int, size: int|null, with_data: bool}>
     */
    public function tables(): array
    {
        return array_map(fn (array $table): array => [
            'name' => $table['name'],
            'rows' => (int) $this->connection()->selectOne('select count(*) as aggregate from '.$this->quoteName($table['name']))->aggregate,
            'size' => $table['size'],
            'with_data' => $this->includesData($table['name']),
        ], $this->tableList());
    }

    public function databaseName(): string
    {
        return $this->isSqlite() ? basename((string) $this->connection()->getDatabaseName()) : (string) $this->connection()->getDatabaseName();
    }

    public function filename(bool $compressed = false): string
    {
        return 'backup-dispusip-'.now()->format('Ymd-His').($compressed ? '.sql.gz' : '.sql');
    }

    /**
     * Write the SQL script to an open stream. The rows are read in one transaction so every
     * table comes from the same moment, even while visitors keep filling in the guestbook.
     *
     * @param  resource  $stream
     */
    public function write($stream): void
    {
        $connection = $this->connection();
        $driver = $connection->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb', 'sqlite'], true)) {
            throw new RuntimeException("Backup belum mendukung database [$driver].");
        }

        $connection->beginTransaction();

        try {
            $this->writeTo($stream, $connection);
        } finally {
            $connection->commit();
        }
    }

    /**
     * @param  resource  $stream
     */
    protected function writeTo($stream, Connection $connection): void
    {
        $previousTimeZone = null;

        fwrite($stream, implode("\n", [
            '-- Backup database '.config('app.name'),
            '-- Database: '.$this->databaseName().' ('.$connection->getDriverName().')',
            '-- Dibuat: '.Format::longDate(now()).' pukul '.now()->format('H.i').' WITA',
            '-- Pulihkan dengan mengimpor berkas ini ke database kosong.',
            '',
            '',
        ]));

        if ($this->isSqlite()) {
            fwrite($stream, "PRAGMA foreign_keys = OFF;\nBEGIN TRANSACTION;\n\n");
        } else {
            // TIMESTAMP columns are read in UTC and the script restores them in UTC, as mysqldump does.
            $previousTimeZone = $connection->selectOne('select @@session.time_zone as tz')->tz;
            $connection->statement("set time_zone = '+00:00'");

            fwrite($stream, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\nSET time_zone = '+00:00';\n\n");
        }

        try {
            foreach ($this->tableList() as $table) {
                $this->writeTable($stream, $table['name']);
            }
        } finally {
            if ($previousTimeZone !== null) {
                $connection->statement('set time_zone = ?', [$previousTimeZone]);
            }
        }

        fwrite($stream, $this->isSqlite() ? "COMMIT;\nPRAGMA foreign_keys = ON;\n" : "SET FOREIGN_KEY_CHECKS = 1;\n");
    }

    /**
     * @param  resource  $stream
     */
    protected function writeTable($stream, string $table): void
    {
        $name = $this->quoteName($table);

        fwrite($stream, "-- Tabel $table\nDROP TABLE IF EXISTS $name;\n");

        foreach ($this->createStatements($table) as $sql) {
            fwrite($stream, rtrim($sql, ";\n").";\n");
        }

        fwrite($stream, "\n");

        if (! $this->includesData($table)) {
            return;
        }

        $batch = [];
        $columns = null;

        foreach ($this->rows($table) as $row) {
            $columns ??= implode(', ', array_map($this->quoteName(...), array_keys($row)));
            $batch[] = '('.implode(', ', array_map($this->value(...), $row)).')';

            if (count($batch) === self::ROWS_PER_INSERT) {
                fwrite($stream, "INSERT INTO $name ($columns) VALUES\n".implode(",\n", $batch).";\n");
                $batch = [];
            }
        }

        if ($batch !== []) {
            fwrite($stream, "INSERT INTO $name ($columns) VALUES\n".implode(",\n", $batch).";\n");
        }

        if ($columns !== null) {
            fwrite($stream, "\n");
        }
    }

    /**
     * Every row of the table, read in pages along a single-column primary key when there is one.
     *
     * @return iterable<array<string, mixed>>
     */
    protected function rows(string $table): iterable
    {
        $name = $this->quoteName($table);
        $primary = collect($this->connection()->getSchemaBuilder()->getIndexes($table))->firstWhere('primary', true);

        if (count($primary['columns'] ?? []) !== 1) {
            foreach ($this->connection()->cursor("select * from $name") as $row) {
                yield (array) $row;
            }

            return;
        }

        $key = $primary['columns'][0];
        $column = $this->quoteName($key);
        $last = null;

        do {
            $rows = $this->connection()->select(
                "select * from $name".($last === null ? '' : " where $column > ?")." order by $column limit ".self::ROWS_PER_READ,
                $last === null ? [] : [$last],
            );

            foreach ($rows as $row) {
                $row = (array) $row;
                $last = $row[$key];

                yield $row;
            }
        } while (count($rows) === self::ROWS_PER_READ);
    }

    /**
     * The CREATE TABLE statement, followed on SQLite by the table's indexes.
     *
     * @return list<string>
     */
    protected function createStatements(string $table): array
    {
        if (! $this->isSqlite()) {
            return [((array) $this->connection()->selectOne('show create table '.$this->quoteName($table)))['Create Table']];
        }

        return array_column($this->connection()->select(
            "select sql from sqlite_master where tbl_name = ? and type in ('table', 'index') and sql is not null order by type = 'index', name",
            [$table],
        ), 'sql');
    }

    /**
     * @return list<array{name: string, size: int|null}>
     */
    protected function tableList(): array
    {
        $schema = $this->isSqlite() ? 'main' : $this->connection()->getDatabaseName();

        return $this->connection()->getSchemaBuilder()->getTables($schema);
    }

    protected function includesData(string $table): bool
    {
        return ! in_array($table, [
            config('session.table', 'sessions'),
            config('cache.stores.database.table', 'cache'),
            config('cache.stores.database.lock_table') ?: 'cache_locks',
        ], true);
    }

    protected function value(mixed $value): string
    {
        return match (true) {
            $value === null => 'NULL',
            is_bool($value) => $value ? '1' : '0',
            is_int($value), is_float($value) => (string) $value,
            ! mb_check_encoding((string) $value, 'UTF-8') || str_contains((string) $value, "\0") => $this->isSqlite()
                ? "X'".bin2hex((string) $value)."'"
                : '0x'.bin2hex((string) $value),
            default => $this->connection()->getPdo()->quote((string) $value),
        };
    }

    protected function quoteName(string $name): string
    {
        return $this->isSqlite()
            ? '"'.str_replace('"', '""', $name).'"'
            : '`'.str_replace('`', '``', $name).'`';
    }

    protected function isSqlite(): bool
    {
        return $this->connection()->getDriverName() === 'sqlite';
    }

    protected function connection(): Connection
    {
        return DB::connection();
    }
}
