<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Service hours. A schedule is seven entries, index 0 = Minggu, each with sessions of
 * [opening minute, closing minute] counted from midnight WITA.
 */
class Jadwal
{
    public const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    /** Display order starting Monday. */
    public const URUTAN = [1, 2, 3, 4, 5, 6, 0];

    /**
     * Open/closed status at a moment (now by default).
     *
     * @param  array<int, array{hari: string, sesi: array<int, array{0: int, 1: int}>}>  $jadwal
     * @return array{open: bool, label: string, detail: string, day: int}
     */
    public static function status(array $jadwal, ?CarbonInterface $at = null): array
    {
        $at = ($at ?? now())->copy()->setTimezone(config('app.timezone'));
        $day = $at->dayOfWeek;
        $minutes = $at->hour * 60 + $at->minute;
        $today = $jadwal[$day]['sesi'] ?? [];

        foreach ($today as [$open, $close]) {
            if ($minutes >= $open && $minutes < $close) {
                return ['open' => true, 'label' => 'Buka sekarang', 'detail' => 'tutup '.Format::clock($close).' WITA', 'day' => $day];
            }
        }

        foreach ($today as [$open]) {
            if ($minutes < $open) {
                return ['open' => false, 'label' => 'Tutup', 'detail' => 'buka lagi '.Format::clock($open).' WITA', 'day' => $day];
            }
        }

        for ($k = 1; $k <= 7; $k++) {
            $d = ($day + $k) % 7;

            if (! empty($jadwal[$d]['sesi'])) {
                return ['open' => false, 'label' => 'Tutup', 'detail' => 'buka '.$jadwal[$d]['hari'].' '.Format::clock($jadwal[$d]['sesi'][0][0]), 'day' => $day];
            }
        }

        return ['open' => false, 'label' => 'Tutup', 'detail' => '', 'day' => $day];
    }

    /**
     * "08.00–15.30" or "08.00–11.30, 13.30–16.00" or "Tutup".
     *
     * @param  array<int, array{0: int, 1: int}>  $sesi
     */
    public static function sesiText(array $sesi): string
    {
        if ($sesi === []) {
            return 'Tutup';
        }

        return implode(', ', array_map(fn (array $s): string => Format::clock($s[0]).'–'.Format::clock($s[1]), $sesi));
    }

    /**
     * One-line summary for the footer: "Sen–Kam 08.00–15.30 · Jum 08.00–11.30, 13.30–16.00 · Sab 09.00–14.00 WITA".
     *
     * @param  array<int, array{hari: string, sesi: array<int, array{0: int, 1: int}>}>  $jadwal
     */
    public static function ringkas(array $jadwal): string
    {
        $groups = [];

        foreach (self::URUTAN as $d) {
            $text = self::sesiText($jadwal[$d]['sesi'] ?? []);

            if ($text === 'Tutup') {
                continue;
            }

            $last = array_key_last($groups);

            if ($last !== null && $groups[$last]['text'] === $text && $groups[$last]['to'] === self::prev($d)) {
                $groups[$last]['to'] = $d;
            } else {
                $groups[] = ['from' => $d, 'to' => $d, 'text' => $text];
            }
        }

        $short = fn (int $d): string => Format::HARI_PENDEK[$d];

        return implode(' · ', array_map(
            fn (array $g): string => ($g['from'] === $g['to'] ? $short($g['from']) : $short($g['from']).'–'.$short($g['to'])).' '.$g['text'],
            $groups,
        )).($groups ? ' WITA' : '');
    }

    protected static function prev(int $day): int
    {
        $i = array_search($day, self::URUTAN, true);

        return self::URUTAN[$i - 1] ?? -1;
    }

    /**
     * True when the given weekday has no sessions.
     *
     * @param  array<int, array{hari: string, sesi: array<int, array{0: int, 1: int}>}>  $jadwal
     */
    public static function closedOn(array $jadwal, int $dayOfWeek): bool
    {
        return empty($jadwal[$dayOfWeek]['sesi']);
    }
}
