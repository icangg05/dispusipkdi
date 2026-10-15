<?php

namespace App\Models;

use App\Casts\DateOnly;
use Database\Factories\GuestCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The 4-digit visit code for one day. A code is drawn at random the first time a day's code is needed,
 * and an admin can draw a new one (or set one) at any time.
 */
#[Fillable(['date', 'code', 'user_id'])]
class GuestCode extends Model
{
    /** @use HasFactory<GuestCodeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => DateOnly::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Today's code (WITA), created on first use.
     */
    public static function today(): self
    {
        $date = today();
        $find = fn (): ?self => static::query()->whereDate('date', $date)->first();

        // Two first visitors of the day may race to create it; the unique index keeps one.
        return $find() ?? rescue(
            fn (): self => static::query()->create(['date' => $date, 'code' => static::randomCode()]),
            fn (): self => $find() ?? throw new \RuntimeException('Kode kunjungan hari ini tidak dapat dibuat.'),
            report: false,
        );
    }

    /**
     * Draw a new random code for today, different from the current one.
     */
    public static function regenerate(?User $by = null): self
    {
        $current = static::today();

        do {
            $code = static::randomCode();
        } while ($code === $current->code);

        $current->update(['code' => $code, 'user_id' => $by?->id]);

        return $current;
    }

    /**
     * Set today's code to a chosen 4-digit value.
     */
    public static function setToday(string $code, ?User $by = null): self
    {
        $current = static::today();
        $current->update(['code' => $code, 'user_id' => $by?->id]);

        return $current;
    }

    public static function matchesToday(string $input): bool
    {
        return hash_equals(static::today()->code, $input);
    }

    public static function randomCode(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
