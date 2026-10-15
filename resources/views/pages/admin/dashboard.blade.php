<?php

use App\Enums\QuestionStatus;
use App\Models\Guest;
use App\Models\GuestCode;
use App\Models\Post;
use App\Models\Question;
use App\Services\StatistikService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin')] #[Title('Dasbor')] class extends Component
{
    public function acakKode(): void
    {
        GuestCode::regenerate(auth()->user());
        $this->dispatch('toast', message: 'Kode kunjungan hari ini sudah diganti.');
    }

    public function with(): array
    {
        $jam = (int) now()->format('G');

        return [
            'sapaan' => match (true) {
                $jam < 11 => 'Selamat pagi',
                $jam < 15 => 'Selamat siang',
                $jam < 18 => 'Selamat sore',
                default => 'Selamat malam',
            },
            'kode' => GuestCode::today(),
            'stats' => app(StatistikService::class)->all(),
            'tamuTerbaru' => Guest::query()->whereDate('visit_date', today())->orderByDesc('nomor')->limit(8)->get(),
            'pertanyaan' => Question::query()->where('status', QuestionStatus::Baru)->latest()->limit(5)->get(),
            'beritaTerbaru' => Post::query()->with('category')->latest('updated_at')->limit(4)->get(),
        ];
    }
};
?>

@php($tamu = $stats['tamu'])
@php($web = $stats['web'])
<div class="ad-grid" style="gap: var(--space-6)">
    <div class="ad-pagehead">
        <div>
            <h1>{{ $sapaan }}, {{ \App\Support\Format::givenName(auth()->user()->name) }}</h1>
            <p>{{ \App\Support\Format::longDate() }} · <x-dk.status variant="sm" class="align-middle" /></p>
        </div>
        <div class="ad-pagehead__actions">
            <x-dk.button variant="secondary" icon="square-pen" :href="route('admin.berita.create')" wire:navigate>Tulis berita</x-dk.button>
            <x-dk.button variant="primary" icon="notebook-pen" :href="route('admin.buku-tamu')" wire:navigate>Buku tamu hari ini</x-dk.button>
        </div>
    </div>

    <div class="ad-grid ad-grid--main">
        <div class="ad-codecard">
            <span class="ad-codecard__k">Kode kunjungan hari ini</span>
            <div class="ad-code" aria-label="Kode {{ implode(' ', str_split($kode->code)) }}">
                @foreach (str_split($kode->code) as $digit)
                    <span>{{ $digit }}</span>
                @endforeach
            </div>
            <small>Pengunjung memasukkan kode ini sebelum mengirim buku tamu. Kode berganti otomatis setiap hari{{ $kode->user_id ? ', terakhir diganti '.$kode->updated_at->format('H.i').' WITA' : '' }}.</small>
            <div class="flex flex-wrap gap-2">
                <x-dk.button variant="lentera" icon="shuffle" wire:click="acakKode" wire:confirm="Ganti kode hari ini? Kode lama langsung tidak berlaku.">Acak ulang kode</x-dk.button>
                <x-dk.button variant="on-dark" icon="monitor" :href="route('admin.kode.layar')" target="_blank">Layar meja layanan</x-dk.button>
                <x-dk.button variant="on-dark" icon="settings" :href="route('admin.kode')" wire:navigate>Atur</x-dk.button>
            </div>
        </div>
        <div class="ad-grid ad-grid--2">
            <x-dk.stat-tile icon="user-check" label="Tamu hari ini" :value="$tamu['hariIni']" :delta="\App\Services\StatistikService::pct($tamu['hariIni'], $tamu['kemarinSamaJam'])" delta-label="vs jam yang sama kemarin" />
            <x-dk.stat-tile icon="users" label="Tamu bulan ini" :value="$tamu['bulanIni']" :delta="\App\Services\StatistikService::pct($tamu['bulanIni'], $tamu['bulanLaluSamaPeriode'])" delta-label="vs periode sama bulan lalu" />
            <x-dk.stat-tile icon="globe" label="Pengunjung web hari ini" :value="$web['hariIni']" :delta="\App\Services\StatistikService::pct($web['hariIni'], $web['kemarinSamaJam'])" delta-label="vs jam yang sama kemarin" />
            <x-dk.stat-tile icon="monitor-smartphone" label="Total pengunjung web" :value="$web['total']" :caption="'sejak '.$web['sejak']" />
        </div>
    </div>

    <div class="ad-card" x-data="visitorStats(@js(['tamu' => $tamu, 'web' => $web]))">
        <div class="ad-card__head">
            <h2 class="ad-card__title"><x-dk.icon name="chart-column" :size="18" /> Statistik kunjungan</h2>
            <div class="flex flex-wrap gap-2">
                <div class="dk-tabs !mb-0" role="tablist" aria-label="Sumber data">
                    <button type="button" role="tab" class="dk-tab is-active" :class="{ 'is-active': sumber === 'tamu' }" :aria-selected="sumber === 'tamu'" @click="sumber = 'tamu'">Buku tamu</button>
                    <button type="button" role="tab" class="dk-tab" :class="{ 'is-active': sumber === 'web' }" :aria-selected="sumber === 'web'" @click="sumber = 'web'">Website</button>
                </div>
                <div class="dk-tabs !mb-0" role="tablist" aria-label="Periode">
                    @foreach (['harian' => 'Harian', 'bulanan' => 'Bulanan', 'semua' => 'Semua'] as $key => $label)
                        <button type="button" role="tab" class="dk-tab {{ $loop->first ? 'is-active' : '' }}" :class="{ 'is-active': periode === '{{ $key }}' }" :aria-selected="periode === '{{ $key }}'" @click="periode = '{{ $key }}'">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="ad-card__body">
            <div class="flex flex-wrap items-end gap-x-6 gap-y-2">
                <div>
                    <div class="text-[13px] font-semibold text-ink-muted" x-text="S.label + ' · ' + ringkas.judul"></div>
                    <div class="text-[40px] leading-[44px] font-extrabold tracking-tight" x-ref="big" x-text="fmt(ringkas.nilai)">{{ \App\Support\Format::number($tamu['hariIni']) }}</div>
                </div>
                <div class="pb-1 text-[13px] text-ink-muted">
                    <template x-if="ringkas.banding !== null">
                        <span class="dk-delta" :class="ringkas.banding >= 0 ? 'dk-delta--up' : 'dk-delta--down'"><span x-text="delta(ringkas.banding)"></span></span>
                    </template>
                    <span x-text="ringkas.bandingLbl"></span>
                </div>
            </div>
            <x-dk.chart source="ringkas.seri" title-expr="chartTitle" unit-expr="unit" :dark="false" :height="240" />
        </div>
    </div>

    <div class="ad-grid ad-grid--main">
        <div class="ad-card">
            <div class="ad-card__head">
                <h2 class="ad-card__title"><x-dk.icon name="notebook-pen" :size="18" /> Tamu terbaru hari ini</h2>
                <x-dk.button variant="ghost" size="sm" icon-right="arrow-right" :href="route('admin.buku-tamu')" wire:navigate>Semua tamu</x-dk.button>
            </div>
            @if ($tamuTerbaru->isEmpty())
                <x-ad.empty icon="notebook-pen" title="Belum ada tamu hari ini" text="Tamu yang mengisi buku tamu akan muncul di sini." />
            @else
                <div class="ad-tablewrap">
                    <table class="ad-table">
                        <thead><tr><th>No.</th><th>Jam</th><th>Tamu</th><th>Keperluan</th></tr></thead>
                        <tbody>
                            @foreach ($tamuTerbaru as $guest)
                                <tr wire:key="g-{{ $guest->id }}">
                                    <td class="no">{{ $guest->nomorLabel() }}</td>
                                    <td class="num">{{ $guest->created_at->format('H.i') }}</td>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            @if ($guest->foto)
                                                <img class="ad-thumb ad-thumb--portrait" src="{{ route('admin.buku-tamu.foto', $guest) }}" alt="" loading="lazy">
                                            @endif
                                            <span><strong>{{ $guest->nama }}</strong><small>{{ $guest->kategori }}{{ $guest->asal ? ' · '.$guest->asal : '' }}</small></span>
                                        </div>
                                    </td>
                                    <td>{{ $guest->keperluanLabel() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="ad-grid" style="align-content: start">
            <div class="ad-card">
                <div class="ad-card__head">
                    <h2 class="ad-card__title"><x-dk.icon name="inbox" :size="18" /> Pertanyaan baru</h2>
                    <x-dk.button variant="ghost" size="sm" icon-right="arrow-right" :href="route('admin.pertanyaan')" wire:navigate>Kotak masuk</x-dk.button>
                </div>
                @forelse ($pertanyaan as $q)
                    <a href="{{ route('admin.pertanyaan', ['buka' => $q->id]) }}" wire:navigate class="block border-b border-line px-5 py-3 text-ink no-underline last:border-b-0 hover:bg-laut-50">
                        <strong class="block text-[14px]">{{ $q->nama }} <span class="ad-tag ad-tag--new ml-1">{{ $q->topik }}</span></strong>
                        <span class="block truncate text-[13px] text-ink-muted">{{ $q->isi }}</span>
                    </a>
                @empty
                    <x-ad.empty icon="mail-open" title="Semua pertanyaan sudah dijawab" />
                @endforelse
            </div>
            <div class="ad-card">
                <div class="ad-card__head">
                    <h2 class="ad-card__title"><x-dk.icon name="newspaper" :size="18" /> Berita terakhir diubah</h2>
                </div>
                @foreach ($beritaTerbaru as $post)
                    <a href="{{ route('admin.berita.edit', $post) }}" wire:navigate class="flex items-center gap-3 border-b border-line px-5 py-3 text-ink no-underline last:border-b-0 hover:bg-laut-50">
                        @if ($post->image_url)<img class="ad-thumb" src="{{ $post->image_url }}" alt="" loading="lazy">@endif
                        <span class="min-w-0">
                            <strong class="block truncate text-[14px]">{{ $post->title }}</strong>
                            <small class="text-[12.5px] text-ink-muted">{{ $post->category->name }} · {{ $post->is_published ? $post->date_label : 'Draf' }}</small>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
