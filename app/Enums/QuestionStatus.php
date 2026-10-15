<?php

namespace App\Enums;

enum QuestionStatus: string
{
    case Baru = 'baru';
    case Dijawab = 'dijawab';

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Belum dijawab',
            self::Dijawab => 'Sudah dijawab',
        };
    }
}
