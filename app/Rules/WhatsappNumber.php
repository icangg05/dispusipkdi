<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * An Indonesian mobile number WhatsApp can reach: 0812…, 62812… or +62 812…, with spaces, dots or dashes allowed.
 */
class WhatsappNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;
        $digits = preg_replace('/\D/', '', $value);

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        if (! preg_match('/^[0-9+\s().-]+$/', $value) || ! preg_match('/^628\d{7,12}$/', $digits)) {
            $fail('Tulis nomor WhatsApp yang aktif, misalnya 081234567890.');
        }
    }
}
