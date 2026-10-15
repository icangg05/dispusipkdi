<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\GuestCode;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Writes lines to the guest register and guards it with the daily visit code.
 */
class GuestbookService
{
    /** Guest photos are personal data: they live on the private disk and are only served to admins. */
    public const PHOTO_DISK = 'local';

    public function nextNumber(): int
    {
        return (int) Guest::query()->whereDate('visit_date', today())->max('nomor') + 1;
    }

    /**
     * Today's register, newest first.
     *
     * @return Collection<int, Guest>
     */
    public function today(int $limit = 12): Collection
    {
        return Guest::query()
            ->whereDate('visit_date', today())
            ->orderByDesc('nomor')
            ->limit($limit)
            ->get();
    }

    /**
     * Check a visit code, counting wrong attempts per client.
     *
     * @return array{ok: bool, message: ?string}
     */
    public function checkCode(string $code, string $clientKey): array
    {
        $key = 'kode-tamu:'.$clientKey;
        $max = (int) config('dispusip.kode.max_attempts');

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            return ['ok' => false, 'message' => 'Terlalu banyak percobaan kode. Coba lagi dalam '.$minutes.' menit.'];
        }

        if (! preg_match('/^\d{4}$/', $code) || ! GuestCode::matchesToday($code)) {
            RateLimiter::hit($key, (int) config('dispusip.kode.decay_seconds'));

            return ['ok' => false, 'message' => 'Kode tidak cocok. Minta kode kunjungan hari ini di meja layanan.'];
        }

        RateLimiter::clear($key);

        return ['ok' => true, 'message' => null];
    }

    /**
     * Add a guest to today's register with the next number.
     *
     * @param  array{nama: string, asal?: ?string, kategori: string, keperluan: string, jenis_kelamin?: ?string, jumlah?: int, whatsapp?: ?string}  $data
     */
    public function record(array $data, ?UploadedFile $photo, string $source = 'web', ?string $ip = null, ?string $userAgent = null): Guest
    {
        $path = $photo ? $this->storePhoto($photo) : null;

        try {
            $guest = DB::transaction(function () use ($data, $path, $source, $ip, $userAgent): Guest {
                // Lock today's rows so two guests submitting together never share a number.
                $last = Guest::query()->whereDate('visit_date', today())->lockForUpdate()->max('nomor');

                return Guest::query()->create([
                    'visit_date' => today(),
                    'nomor' => (int) $last + 1,
                    'nama' => Str::squish($data['nama']),
                    'asal' => filled($data['asal'] ?? null) ? Str::squish($data['asal']) : null,
                    'kategori' => $data['kategori'],
                    'keperluan' => $data['keperluan'],
                    'jenis_kelamin' => filled($data['jenis_kelamin'] ?? null) ? $data['jenis_kelamin'] : null,
                    'jumlah' => max(1, (int) ($data['jumlah'] ?? 1)),
                    'whatsapp' => filled($data['whatsapp'] ?? null) ? preg_replace('/[^\d+]/', '', $data['whatsapp']) : null,
                    'foto' => $path,
                    'sumber' => $source,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent ? Str::limit($userAgent, 250, '') : null,
                ]);
            }, attempts: 3);
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk(self::PHOTO_DISK)->delete($path);
            }

            throw $e;
        }

        StatistikService::flush();

        return $guest;
    }

    protected function storePhoto(UploadedFile $photo): string
    {
        $extension = $photo->guessExtension() ?: 'jpg';

        return $photo->storeAs(
            'tamu/'.today()->format('Y/m'),
            today()->format('d').'-'.Str::lower(Str::random(24)).'.'.$extension,
            self::PHOTO_DISK,
        );
    }

    public function deletePhoto(Guest $guest): void
    {
        if ($guest->foto) {
            Storage::disk(self::PHOTO_DISK)->delete($guest->foto);
        }
    }
}
