<?php

use App\Models\GuestCode;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Full-screen display for the front desk: today's code and a QR code to the guestbook page.
 */
new #[Layout('layouts::bare')] #[Title('Kode kunjungan hari ini')] class extends Component
{
    public function with(): array
    {
        return [
            'kode' => GuestCode::today()->code,
            'url' => route('buku-tamu'),
        ];
    }
};
?>

<div class="ad-layar" wire:poll.30s>
    <div class="ad-layar__head">
        <x-dk.brand dark />
        <x-dk.status variant="sm" />
    </div>
    <div class="ad-layar__main">
        <h1 class="ad-layar__title">Kode <em class="dk-accent">buku tamu</em> hari ini</h1>
        <div class="ad-layar__code" aria-label="Kode {{ implode(' ', str_split($kode)) }}">
            @foreach (str_split($kode) as $digit)
                <span wire:key="d{{ $loop->index }}">{{ $digit }}</span>
            @endforeach
        </div>
        <div class="ad-layar__qr">
            <div class="ad-layar__qrbox" wire:ignore x-data="qrCode(@js($url))" role="img" aria-label="Kode QR menuju halaman buku tamu"></div>
            <p class="ad-layar__hint">Pindai kode QR atau buka <strong>{{ preg_replace('#^https?://#', '', $url) }}</strong>,<br>isi lembar buku tamu, lalu masukkan 4 angka di atas.</p>
        </div>
    </div>
    <div class="ad-layar__foot">
        <span>{{ \App\Support\Format::longDate() }}</span>
        <span>Kode berganti setiap hari · {{ site()->get('identitas.nama') }}</span>
    </div>
</div>
