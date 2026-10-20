<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::bare')] #[Title('Masuk Admin')] class extends Component
{
    /** Wrong passwords allowed (per email and IP, within ten minutes) before sign-in locks. */
    private const MAX_SALAH = 3;

    /** Seconds sign-in stays locked once the limit is reached. */
    private const LAMA_KUNCI = 60;

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function masuk(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Tulis alamat email Anda.',
            'email.email' => 'Periksa lagi alamat email ini.',
            'password.required' => 'Tulis kata sandi Anda.',
        ]);

        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key.':kunci', 1)) {
            $this->reset('password');

            return;
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->reset('password');
            $salah = RateLimiter::hit($key, 10 * 60);

            if ($salah >= self::MAX_SALAH) {
                RateLimiter::clear($key);
                RateLimiter::hit($key.':kunci', self::LAMA_KUNCI);

                return;
            }

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak cocok. Sisa '.(self::MAX_SALAH - $salah).' kali percobaan sebelum dikunci 1 menit.',
            ]);
        }

        RateLimiter::clear($key);
        session()->regenerate();

        $this->redirectIntended(route('admin.dashboard'));
    }

    protected function throttleKey(): string
    {
        return 'masuk:'.Str::lower($this->email).'|'.request()->ip();
    }

    public function with(): array
    {
        $kunci = $this->throttleKey().':kunci';

        return [
            'background' => \App\Models\Slide::query()->active()->first()?->image_url ?? asset('images/contoh/ruang-baca.jpg'),
            'sisaKunci' => RateLimiter::tooManyAttempts($kunci, 1) ? max(1, RateLimiter::availableIn($kunci)) : 0,
            'maxSalah' => self::MAX_SALAH,
        ];
    }
};
?>

<div class="pnl-login">
    <div class="pnl-login__art" style="background-image: url('{{ $background }}')">
        <x-dk.brand dark />
        <div>
            <x-dk.eyebrow icon="shield-check" dark>Panel admin</x-dk.eyebrow>
            <h1>Kelola layanan <em class="dk-accent">Dispusip</em></h1>
            <p>Kelola buku tamu, kode kunjungan harian, berita, galeri, dan informasi dinas di satu tempat.</p>
        </div>
        <small class="text-on-laut-muted">{{ site()->get('identitas.nama') }}</small>
    </div>
    <div class="pnl-login__panel">
        <form class="pnl-login__form" wire:submit="masuk" novalidate>
            <h2>Masuk</h2>
            <p>Khusus petugas Dispusip.</p>
            <x-ad.field label="Email" for="email" error="email">
                <input id="email" type="email" class="pnl-input" wire:model="email" autocomplete="username" placeholder="Alamat email akun Anda" autofocus required>
            </x-ad.field>
            <x-ad.field label="Kata sandi" for="password" error="password">
                <div class="pnl-password" x-data="{ show: false }">
                    <input id="password" type="password" :type="show ? 'text' : 'password'" class="pnl-input" wire:model="password" autocomplete="current-password" placeholder="Kata sandi akun Anda" required>
                    {{-- wire:ignore stops a re-render (after a failed sign-in) from desyncing the icon from the field. --}}
                    <button type="button" class="pnl-password__toggle" @click="show = ! show" aria-controls="password" :aria-pressed="show" :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" aria-label="Tampilkan kata sandi" wire:ignore x-cloak>
                        <span x-show="! show"><x-dk.icon name="eye" :size="20" /></span>
                        <span x-show="show" x-cloak><x-dk.icon name="eye-off" :size="20" /></span>
                    </button>
                </div>
            </x-ad.field>
            <x-ad.check model="remember">Ingat saya di perangkat ini</x-ad.check>
            @if ($sisaKunci > 0)
                {{-- A fresh key each render restarts the countdown with the server's remaining time. --}}
                <p class="pnl-lock" role="alert" wire:key="kunci-{{ now()->getTimestampMs() }}" x-data="lockCountdown({ seconds: {{ $sisaKunci }} })">
                    <x-dk.icon name="lock" :size="18" />
                    <span>Email atau kata sandi salah {{ $maxSalah }} kali. Coba lagi dalam <strong x-text="left">{{ $sisaKunci }}</strong> detik.</span>
                </p>
            @endif
            {{-- The fieldset, not the button, carries the lock, so wire:loading can still toggle the button. --}}
            <fieldset class="pnl-login__submit" @disabled($sisaKunci > 0)>
                <x-dk.button type="submit" variant="primary" size="lg" block wire:loading.attr="disabled" wire:target="masuk">Masuk</x-dk.button>
            </fieldset>
            <x-dk.button variant="ghost" size="sm" icon="arrow-left" :href="route('beranda')" wire:navigate>Kembali ke situs</x-dk.button>
        </form>
    </div>
</div>
