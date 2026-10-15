<?php

namespace App\Models;

use App\Enums\QuestionStatus;
use App\Support\Format;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['nama', 'kontak', 'topik', 'isi', 'status', 'catatan', 'answered_at', 'ip_address'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    /** Placeholders a reply template can use, with what each one is filled with. */
    public const PLACEHOLDERS = [
        '{nama}' => 'nama panggilan penanya',
        '{topik}' => 'topik pertanyaan',
        '{pertanyaan}' => 'isi pertanyaan',
        '{dinas}' => 'nama singkat dinas',
        '{petugas}' => 'nama petugas yang membalas',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuestionStatus::class,
            'answered_at' => 'datetime',
        ];
    }

    /**
     * The question, always starting with a capital letter.
     */
    protected function isi(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value === null ? null : Str::ucfirst($value),
            set: fn (string $value): string => Str::ucfirst(trim($value)),
        );
    }

    /**
     * The reply sent to the asker (or a staff note), always starting with a capital letter.
     */
    protected function catatan(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => blank($value) ? null : Str::ucfirst($value),
            set: fn (?string $value): ?string => blank($value) ? null : Str::ucfirst(trim($value)),
        );
    }

    /**
     * Older questions were answered by email; new ones always leave a WhatsApp number.
     */
    public function isEmail(): bool
    {
        return (bool) filter_var($this->kontak, FILTER_VALIDATE_EMAIL);
    }

    /**
     * A wa.me link when the contact is a phone number, with an optional prefilled message.
     */
    public function whatsappLink(?string $text = null): ?string
    {
        $link = $this->isEmail() ? null : Format::whatsappLink($this->kontak);

        if ($link === null || blank($text)) {
            return $link;
        }

        return $link.'?text='.rawurlencode($text);
    }

    /**
     * Fill a reply template with this question's details.
     */
    public function fillTemplate(string $template, string $petugas): string
    {
        $identitas = site()->identitas();

        return strtr($template, [
            '{nama}' => Format::givenName($this->nama),
            '{topik}' => mb_strtolower($this->topik),
            '{pertanyaan}' => (string) $this->isi,
            '{dinas}' => $identitas['singkatan'].' '.$identitas['kota'],
            '{petugas}' => $petugas,
        ]);
    }
}
