<?php

namespace App\Enums;

enum MediaType: string
{
    case Foto = 'foto';
    case Video = 'video';

    public function label(): string
    {
        return match ($this) {
            self::Foto => 'Foto',
            self::Video => 'Video',
        };
    }
}
