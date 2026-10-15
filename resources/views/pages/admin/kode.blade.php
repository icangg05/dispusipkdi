<?php

use App\Http\Middleware\EnsureKioskDevice;
use App\Models\Guest;
use App\Models\GuestCode;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin')] #[Title('Kode kunjungan')] class extends Component
{
    public string $kodeManual = '';

    public function acak(): void
    {
        GuestCode::regenerate(auth()->user());
        $this->dispatch('toast', message: 'Kode baru sudah berlaku. Kode lama tidak bisa dipakai lagi.');
    }

    public function simpanManual(): void
    {
        $this->validate(
            ['kodeManual' => ['required', 'digits:4']],
            ['kodeManual.required' => 'Tulis 4 angka.', 'kodeManual.digits' => 'Kode harus tepat 4 angka.'],
        );

        GuestCode::setToday($this->kodeManual, auth()->user());
        $this->reset('kodeManual');
        $this->dispatch('toast', message: 'Kode hari ini diganti dengan kode pilihan Anda.');
    }

    public function with(): array
    {
        $riwayat = GuestCode::query()->with('user')->latest('date')->limit(14)->get();
        $jumlah = Guest::query()
            ->whereIn('visit_date', $riwayat->map(fn (GuestCode $c): string => $c->date->toDateString()))
            ->selectRaw('visit_date, count(*) as c')
            ->groupBy('visit_date')
            ->get()
            ->mapWithKeys(fn ($row): array => [substr((string) $row->visit_date, 0, 10) => (int) $row->c]);

        return [
            'kode' => GuestCode::today(),
            'riwayat' => $riwayat,
            'jumlah' => $jumlah,
            'perangkatKios' => EnsureKioskDevice::isKiosk(request()),
        ];
    }
};
?>

<div class="ad-grid" style="gap: var(--space-6)">
    <div class="ad-pagehead">
        <div>
            <h1>Kode kunjungan</h1>
            <p>Buku tamu di website hanya bisa dikirim dengan kode 4 angka ini. Kode diacak otomatis setiap hari (WITA) dan dapat diganti kapan saja dari sini.</p>
        </div>
        <div class="ad-pagehead__actions">
            <x-dk.button variant="primary" icon="monitor" :href="route('admin.kode.layar')" target="_blank">Buka layar meja layanan</x-dk.button>
        </div>
    </div>

    <div class="ad-grid ad-grid--main">
        <div class="ad-codecard">
            <span class="ad-codecard__k">Kode hari ini · {{ \App\Support\Format::longDate() }}</span>
            <div class="ad-code ad-code--lg" aria-label="Kode {{ implode(' ', str_split($kode->code)) }}">
                @foreach (str_split($kode->code) as $digit)
                    <span>{{ $digit }}</span>
                @endforeach
            </div>
            <small>
                @if ($kode->user)
                    Diganti oleh {{ $kode->user->name }} pukul {{ $kode->updated_at->format('H.i') }} WITA.
                @else
                    Diacak otomatis oleh sistem.
                @endif
                Salah memasukkan kode {{ config('dispusip.kode.max_attempts') }} kali membuat pengisi menunggu {{ (int) (config('dispusip.kode.decay_seconds') / 60) }} menit.
            </small>
            <div>
                <x-dk.button variant="lentera" icon="shuffle" wire:click="acak" wire:confirm="Acak kode baru? Kode lama langsung tidak berlaku.">Acak ulang kode</x-dk.button>
            </div>
        </div>

        <div class="ad-card">
            <div class="ad-card__head"><h2 class="ad-card__title"><x-dk.icon name="key-round" :size="18" /> Tetapkan kode sendiri</h2></div>
            <form class="ad-card__body ad-form" wire:submit="simpanManual">
                <x-ad.field label="Kode 4 angka" for="kodeManual" error="kodeManual" help="Berlaku untuk hari ini saja. Besok sistem kembali mengacak kode baru.">
                    <input id="kodeManual" class="ad-input max-w-[200px] text-center text-[22px] font-extrabold tracking-[.3em]" wire:model="kodeManual" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" autocomplete="off" placeholder="0000">
                </x-ad.field>
                <div><x-dk.button type="submit" variant="secondary" icon="save">Simpan kode</x-dk.button></div>
            </form>
        </div>
    </div>

    <div class="ad-grid ad-grid--2">
        <div class="ad-card">
            <div class="ad-card__head"><h2 class="ad-card__title"><x-dk.icon name="tablet-smartphone" :size="18" /> Tablet meja layanan (mode kios)</h2></div>
            <div class="ad-card__body grid gap-4">
                <p class="m-0 text-[15px] leading-6 text-ink-muted">Jadikan tablet di meja layanan sebagai kios buku tamu layar penuh. Di perangkat kios, tamu tidak perlu memasukkan kode karena tabletnya sudah diaktifkan petugas.</p>
                @if ($perangkatKios)
                    <p class="m-0"><span class="ad-tag ad-tag--on"><x-dk.icon name="circle-check" :size="14" /> Perangkat ini adalah kios</span></p>
                    <div class="flex flex-wrap gap-2">
                        <x-dk.button variant="primary" icon="external-link" :href="route('kios')" wire:navigate>Buka mode kios</x-dk.button>
                        <form method="POST" action="{{ route('admin.kios.nonaktifkan') }}">@csrf<x-dk.button type="submit" variant="secondary" icon="circle-x">Matikan mode kios</x-dk.button></form>
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.kios.aktifkan') }}" onsubmit="return confirm('Jadikan perangkat ini kios? Anda akan keluar dari admin di perangkat ini.')">
                        @csrf
                        <x-dk.button type="submit" variant="primary" icon="tablet-smartphone">Jadikan perangkat ini kios</x-dk.button>
                    </form>
                    <p class="ad-help m-0">Lakukan dari tablet meja layanan. Demi keamanan, akun admin otomatis keluar di perangkat itu.</p>
                @endif
            </div>
        </div>

        <div class="ad-card">
            <div class="ad-card__head"><h2 class="ad-card__title"><x-dk.icon name="history" :size="18" /> Riwayat 14 hari</h2></div>
            <div class="ad-tablewrap">
                <table class="ad-table">
                    <thead><tr><th>Tanggal</th><th>Kode</th><th class="num">Tamu</th><th>Diatur oleh</th></tr></thead>
                    <tbody>
                        @foreach ($riwayat as $row)
                            <tr wire:key="kode-{{ $row->id }}">
                                <td>{{ \App\Support\Format::shortDate($row->date) }}@if ($row->date->isToday()) <span class="ad-tag ad-tag--new ml-1">Hari ini</span>@endif</td>
                                <td class="font-extrabold tracking-[.2em] tabular-nums">{{ $row->code }}</td>
                                <td class="num">{{ \App\Support\Format::number($jumlah[$row->date->toDateString()] ?? 0) }}</td>
                                <td class="text-ink-muted">{{ $row->user?->name ?? 'Sistem' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
