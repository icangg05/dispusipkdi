<?php

use App\Services\GuestbookService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::kiosk')] #[Title('Buku Tamu | Meja Layanan')] class extends Component
{
    #[On('tamu-tercatat')]
    public function refreshCount(): void
    {
        // Re-render to update today's guest count.
    }

    public function with(): array
    {
        return [
            'total' => app(GuestbookService::class)->nextNumber() - 1,
            'background' => \App\Models\Slide::query()->active()->first()?->image_url ?? asset('images/contoh/ruang-baca.jpg'),
        ];
    }
};
?>

<div class="dk-kiosk">
    <aside class="dk-kiosk__side" style="background-image: url('{{ $background }}')">
        <div class="dk-kiosk__scrim" aria-hidden="true"></div>
        <div class="dk-kiosk__sidein">
            <x-dk.brand dark />
            <div>
                <x-dk.eyebrow icon="sparkles" dark>Selamat datang</x-dk.eyebrow>
                <h1 class="dk-kiosk__title">Silakan isi <em class="dk-accent">buku tamu</em> sebelum masuk</h1>
                <p class="dk-kiosk__text">Data kunjungan membantu kami menambah koleksi dan jam layanan yang sesuai kebutuhan warga.</p>
            </div>
            <div class="dk-kiosk__facts">
                <div x-data="clock()"><span class="dk-kiosk__clock"><span x-text="time">{{ now()->format('H.i') }}</span> <small>WITA</small></span><small>{{ \App\Support\Format::longDate() }}</small></div>
                <div><span class="dk-kiosk__num">{{ \App\Support\Format::number($total) }}</span><small>tamu hari ini</small></div>
            </div>
            <x-dk.service-hours dark />
        </div>
    </aside>
    <main class="dk-kiosk__main">
        <div class="dk-kiosk__formhead">
            <h2>Buku Tamu Digital</h2>
            <p>Isi satu lembar untuk setiap kunjungan. Butuh bantuan? Tanyakan petugas di meja layanan.</p>
        </div>
        <livewire:guestbook variant="kiosk" />
    </main>
</div>
