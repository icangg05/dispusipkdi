<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Support\Format;
use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One line of the guest register.
 */
#[Fillable(['visit_date', 'nomor', 'nama', 'asal', 'kategori', 'keperluan', 'jenis_kelamin', 'jumlah', 'whatsapp', 'foto', 'sumber', 'ip_address', 'user_agent'])]
#[Hidden(['ip_address', 'user_agent', 'whatsapp', 'foto'])]
class Guest extends Model
{
    /** @use HasFactory<GuestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visit_date' => DateOnly::class,
            'nomor' => 'integer',
            'jumlah' => 'integer',
        ];
    }

    /**
     * Given name plus initials ("La Ode F.") so the public register never shows a full name.
     */
    public function maskedName(): string
    {
        if ($this->keperluan === 'Kunjungan rombongan' && filled($this->asal)) {
            return $this->asal;
        }

        $given = Format::givenName($this->nama);
        $rest = preg_split('/\s+/', trim(mb_substr(trim($this->nama), mb_strlen($given)))) ?: [];
        $initials = array_map(fn (string $w): string => mb_strtoupper(mb_substr($w, 0, 1)).'.', array_filter($rest));

        return trim($given.' '.implode(' ', $initials));
    }

    public function nomorLabel(): string
    {
        return str_pad((string) $this->nomor, 3, '0', STR_PAD_LEFT);
    }

    public function keperluanLabel(): string
    {
        return $this->keperluan.($this->jumlah > 1 ? ' ('.$this->jumlah.' orang)' : '');
    }
}
