<?php

namespace App\Enums;

/**
 * The four category colours of the design system plus a neutral one.
 */
enum Tone: string
{
    case Laut = 'laut';
    case Lentera = 'lentera';
    case Daun = 'daun';
    case Terakota = 'terakota';
    case Netral = 'netral';

    public function label(): string
    {
        return match ($this) {
            self::Laut => 'Laut (teal)',
            self::Lentera => 'Lentera (kuning)',
            self::Daun => 'Daun (hijau)',
            self::Terakota => 'Terakota (jingga tanah)',
            self::Netral => 'Netral (abu)',
        };
    }
}
