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

        $key = 'masuk:'.Str::lower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key, 60);
            $this->reset('password');

            throw ValidationException::withMessages(['email' => 'Email atau kata sandi tidak cocok.']);
        }

        RateLimiter::clear($key);
        session()->regenerate();

        $this->redirectIntended(route('admin.dashboard'));
    }

    public function with(): array
    {
        return [
            'background' => \App\Models\Slide::query()->active()->first()?->image_url ?? asset('images/contoh/ruang-baca.jpg'),
        ];
    }
};
?>

<div class="ad-login">
    <div class="ad-login__art" style="background-image: url('{{ $background }}')">
        <x-dk.brand dark />
        <div>
            <x-dk.eyebrow icon="shield-check" dark>Panel admin</x-dk.eyebrow>
            <h1>Kelola layanan <em class="dk-accent">Dispusip</em></h1>
            <p>Kelola buku tamu, kode kunjungan harian, berita, galeri, dan informasi dinas di satu tempat.</p>
        </div>
        <small class="text-on-laut-muted">{{ site()->get('identitas.nama') }}</small>
    </div>
    <div class="ad-login__panel">
        <form class="ad-login__form" wire:submit="masuk" novalidate>
            <h2>Masuk</h2>
            <p>Khusus petugas Dispusip.</p>
            <x-ad.field label="Email" for="email" error="email">
                <input id="email" type="email" class="ad-input" wire:model="email" autocomplete="username" autofocus required>
            </x-ad.field>
            <x-ad.field label="Kata sandi" for="password" error="password">
                <input id="password" type="password" class="ad-input" wire:model="password" autocomplete="current-password" required>
            </x-ad.field>
            <x-ad.check model="remember">Ingat saya di perangkat ini</x-ad.check>
            <x-dk.button type="submit" variant="primary" size="lg" block wire:loading.attr="disabled" wire:target="masuk">Masuk</x-dk.button>
            <x-dk.button variant="ghost" size="sm" icon="arrow-left" :href="route('beranda')" wire:navigate>Kembali ke situs</x-dk.button>
        </form>
    </div>
</div>
