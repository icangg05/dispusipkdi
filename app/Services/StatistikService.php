<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\SiteVisit;
use App\Support\Format;
use App\Support\Jadwal;
use App\Support\SiteSettings;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Visit statistics for guests (from the guestbook) and website visitors (unique per day),
 * shaped like the design system's `Seri`: today, yesterday, this/last month, total and
 * daily (7 days), monthly (12 months) and yearly series.
 */
class StatistikService
{
    public const CACHE_KEY = 'statistik';

    public const CACHE_SECONDS = 120;

    public function __construct(protected SiteSettings $settings) {}

    /**
     * @return array{tamu: array<string, mixed>, web: array<string, mixed>, kategoriTamu: list<array{label: string, value: int}>, keperluanTamu: list<array{label: string, value: int}>}
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, fn (): array => [
            'tamu' => $this->seri(
                $this->dailyCounts(Guest::query(), 'visit_date'),
                $this->yesterdayUntilNow(Guest::query()),
                'Tamu perpustakaan',
                'Buku tamu',
                closedDays: true,
            ),
            'web' => $this->seri(
                $this->dailyCounts(SiteVisit::query(), 'visit_date'),
                $this->yesterdayUntilNow(SiteVisit::query()),
                'Pengunjung website',
                'Analitik situs',
            ),
            'kategoriTamu' => $this->breakdown('kategori', config('dispusip.kategori_tamu')),
            'keperluanTamu' => $this->breakdown('keperluan', config('dispusip.keperluan')),
        ]);
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function tamuHariIni(): int
    {
        return Guest::query()->whereDate('visit_date', today())->count();
    }

    /**
     * @param  Builder<Model>  $query
     * @return array<string, int> keyed by Y-m-d
     */
    protected function dailyCounts(Builder $query, string $column): array
    {
        $counts = [];

        $rows = $query->toBase()
            ->selectRaw($column.' as d, count(*) as c')
            ->groupBy($column)
            ->get();

        foreach ($rows as $row) {
            $key = substr((string) $row->d, 0, 10);
            $counts[$key] = ($counts[$key] ?? 0) + (int) $row->c;
        }

        return $counts;
    }

    /**
     * Yesterday's count up to the current time of day, so "today so far" compares like with like.
     *
     * @param  Builder<Model>  $query
     */
    protected function yesterdayUntilNow(Builder $query): int
    {
        return $query->whereDate('visit_date', today()->subDay())
            ->where('created_at', '<=', now()->subDay())
            ->count();
    }

    /**
     * @param  array<string, int>  $daily
     * @return array<string, mixed>
     */
    protected function seri(array $daily, int $kemarinSamaJam, string $label, string $sumber, bool $closedDays = false): array
    {
        $today = CarbonImmutable::today();
        $jadwal = $this->settings->jadwal();
        $day = fn (CarbonImmutable $d): int => $daily[$d->toDateString()] ?? 0;
        $month = function (CarbonImmutable $d) use ($daily): int {
            $prefix = $d->format('Y-m');
            $sum = 0;

            foreach ($daily as $date => $count) {
                if (str_starts_with($date, $prefix)) {
                    $sum += $count;
                }
            }

            return $sum;
        };

        $harian = [];

        for ($i = 6; $i >= 0; $i--) {
            $d = $today->subDays($i);
            $value = $day($d);
            $point = ['label' => Format::HARI_PENDEK[$d->dayOfWeek], 'value' => $value, 'current' => $i === 0];

            if ($closedDays && $value === 0 && Jadwal::closedOn($jadwal, $d->dayOfWeek)) {
                $point['note'] = 'Tutup';
            }

            $harian[] = $point;
        }

        $bulanan = [];

        for ($i = 11; $i >= 0; $i--) {
            $d = $today->startOfMonth()->subMonthsNoOverflow($i);
            $bulanan[] = ['label' => Format::BULAN_PENDEK[$d->month - 1], 'value' => $month($d), 'current' => $i === 0];
        }

        $years = [];

        foreach ($daily as $date => $count) {
            $y = (int) substr($date, 0, 4);
            $years[$y] = ($years[$y] ?? 0) + $count;
        }

        $firstYear = $years ? min(array_keys($years)) : $today->year;
        $semua = [];

        for ($y = $firstYear; $y <= $today->year; $y++) {
            $point = ['label' => (string) $y, 'value' => $years[$y] ?? 0, 'current' => $y === $today->year];

            if ($y === $today->year && $today->month < 12) {
                $point['note'] = 's.d. '.Format::BULAN_PENDEK[$today->month - 1];
            }

            $semua[] = $point;
        }

        // Last month's days 1…today, to compare with this month so far.
        $lastMonth = $today->startOfMonth()->subMonthNoOverflow();
        $bulanLaluSamaPeriode = 0;

        for ($d = 1; $d <= min($today->day, $lastMonth->daysInMonth); $d++) {
            $bulanLaluSamaPeriode += $day($lastMonth->setDay($d));
        }

        return [
            'label' => $label,
            'sumber' => $sumber,
            'hariIni' => $day($today),
            'kemarin' => $day($today->subDay()),
            'kemarinSamaJam' => $kemarinSamaJam,
            'bulanIni' => $month($today),
            'bulanLalu' => $month($lastMonth),
            'bulanLaluSamaPeriode' => $bulanLaluSamaPeriode,
            'total' => array_sum($daily),
            'sejak' => $firstYear,
            'harian' => $harian,
            'bulanan' => $bulanan,
            'semua' => $semua,
        ];
    }

    /**
     * Guest counts for the current month grouped by a column, in the configured order.
     *
     * @param  list<string>  $order
     * @return list<array{label: string, value: int}>
     */
    protected function breakdown(string $column, array $order): array
    {
        $counts = Guest::query()
            ->whereBetween('visit_date', [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()])
            ->selectRaw($column.' as k, count(*) as c')
            ->groupBy($column)
            ->pluck('c', 'k')
            ->map(fn ($c): int => (int) $c)
            ->all();

        $result = [];

        foreach ($order as $label) {
            $result[] = ['label' => $label, 'value' => $counts[$label] ?? 0];
            unset($counts[$label]);
        }

        foreach ($counts as $label => $value) {
            $result[] = ['label' => (string) $label, 'value' => $value];
        }

        return $result;
    }

    /**
     * Change in percent between two values, or null when there is nothing to compare with.
     */
    public static function pct(int|float $now, int|float $before): ?float
    {
        return $before > 0 ? (($now - $before) / $before) * 100 : null;
    }
}
