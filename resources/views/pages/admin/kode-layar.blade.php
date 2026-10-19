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

<div class="pnl-layar" wire:poll.30s>
    <div class="pnl-layar__head">
        <x-dk.brand dark />
        <x-dk.status variant="sm" />
    </div>
    <div class="pnl-layar__main">
        <h1 class="pnl-layar__title">Kode <em class="dk-accent">buku tamu</em> hari ini</h1>
        <div class="pnl-layar__code" aria-label="Kode {{ implode(' ', str_split($kode)) }}">
            @foreach (str_split($kode) as $digit)
                <span wire:key="d{{ $loop->index }}">{{ $digit }}</span>
            @endforeach
        </div>
        <div class="pnl-layar__qr">
            <div class="pnl-layar__qrbox" wire:ignore x-data="qrCode(@js($url))" role="img" aria-label="Kode QR menuju halaman buku tamu"></div>
            <p class="pnl-layar__hint">Pindai kode QR atau buka <strong>{{ preg_replace('#^https?://#', '', $url) }}</strong>,<br>isi lembar buku tamu, lalu masukkan 4 angka di atas.</p>
        </div>
    </div>
    <div class="pnl-layar__foot">
        <span>{{ \App\Support\Format::longDate() }}</span>
        <span>Kode berganti setiap hari · {{ site()->get('identitas.nama') }}</span>
    </div>
</div>
