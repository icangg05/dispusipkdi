<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Indonesian number and date formats used across the site: 12.480, 8,9%, 12 Okt 2026, 08.00–15.30.
 */
class Format
{
    public const BULAN_PENDEK = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public const HARI_PENDEK = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

    /** Name prefixes kept together with the given name (Buton, Bugis, Muslim honorifics, titles). */
    public const PREFIKS_NAMA = ['la', 'wa', 'ode', 'andi', 'muh', 'muh.', 'moh.', 'h.', 'hj.', 'dr.', 'ir.'];

    /**
     * The name a person is greeted by: leading prefixes plus the given name — "Wa Ode Sitti", "Andi Pratama", "Nur".
     */
    public static function givenName(string $nama): string
    {
        $words = preg_split('/\s+/', trim($nama)) ?: [];
        $head = [];

        while (count($words) > 1 && in_array(mb_strtolower($words[0]), self::PREFIKS_NAMA, true)) {
            $head[] = array_shift($words);
        }

        $head[] = array_shift($words) ?? '';

        return trim(implode(' ', $head));
    }

    public static function number(int|float|null $value, int $decimals = 0): string
    {
        return number_format((float) $value, $decimals, ',', '.');
    }

    /**
     * Signed percentage with one decimal at most, e.g. "+8,9%" or "−3%".
     */
    public static function delta(float $percent): string
    {
        $abs = round(abs($percent), 1);
        $text = fmod($abs, 1.0) === 0.0 ? self::number($abs) : self::number($abs, 1);

        return ($percent >= 0 ? '+' : '−').$text.'%';
    }

    /**
     * Short axis label: 1.500 → "1,5 rb".
     */
    public static function compact(int|float $value): string
    {
        if ($value < 1000) {
            return (string) (int) $value;
        }

        $k = round($value / 1000, 1);

        return (fmod($k, 1.0) === 0.0 ? self::number($k) : self::number($k, 1)).' rb';
    }

    public static function shortDate(?CarbonInterface $date): string
    {
        return $date ? $date->day.' '.self::BULAN_PENDEK[$date->month - 1].' '.$date->year : '';
    }

    public static function monthYear(?CarbonInterface $date): string
    {
        return $date ? self::BULAN_PENDEK[$date->month - 1].' '.$date->year : '';
    }

    /**
     * "Kamis, 15 Oktober 2026".
     */
    public static function longDate(?CarbonInterface $date = null): string
    {
        return ($date ?? now())->locale('id')->translatedFormat('l, j F Y');
    }

    /**
     * Minutes after midnight to "08.00".
     */
    public static function clock(int $minutes): string
    {
        return sprintf('%02d.%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * Indonesian phone number to an international wa.me link.
     */
    public static function whatsappLink(?string $number): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $number);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return 'https://wa.me/'.$digits;
    }
}
