<?php

namespace App\Models;

use App\Enums\QuestionStatus;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nama', 'kontak', 'topik', 'isi', 'status', 'catatan', 'answered_at', 'ip_address'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

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

    public function isEmail(): bool
    {
        return (bool) filter_var($this->kontak, FILTER_VALIDATE_EMAIL);
    }

    /**
     * A wa.me link when the contact is a phone number.
     */
    public function whatsappLink(): ?string
    {
        if ($this->isEmail()) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $this->kontak);

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return 'https://wa.me/'.$digits;
    }
}
