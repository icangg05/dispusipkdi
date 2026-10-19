<?php

use App\Models\Guest;
use App\Services\GuestbookService;
use App\Services\StatistikService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] #[Title('Buku tamu')] class extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $dari = '';

    #[Url(except: '')]
    public string $sampai = '';

    #[Url(except: '')]
    public string $kategori = '';

    #[Url(except: '')]
    public string $cari = '';

    public ?int $lihat = null;

    public function mount(): void
    {
        $this->dari = $this->dari ?: today()->toDateString();
        $this->sampai = $this->sampai ?: today()->toDateString();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['dari', 'sampai', 'kategori', 'cari'], true)) {
            $this->resetPage();
        }
    }

    public function rentang(string $preset): void
    {
        [$this->dari, $this->sampai] = match ($preset) {
            'kemarin' => [today()->subDay()->toDateString(), today()->subDay()->toDateString()],
            'minggu' => [today()->subDays(6)->toDateString(), today()->toDateString()],
            'bulan' => [today()->startOfMonth()->toDateString(), today()->toDateString()],
            default => [today()->toDateString(), today()->toDateString()],
        };
        $this->resetPage();
    }

    public function buka(int $id): void
    {
        $this->lihat = $id;
    }

    public function tutup(): void
    {
        $this->lihat = null;
    }

    public function hapus(int $id, GuestbookService $guestbook): void
    {
        $guest = Guest::query()->findOrFail($id);
        $guestbook->deletePhoto($guest);
        $guest->delete();
        StatistikService::flush();

        $this->lihat = null;
        $this->dispatch('toast', message: 'Data tamu dihapus.');
    }

    public function paginationView(): string
    {
        return 'partials.pagination';
    }

    protected function query()
    {
        $dari = $this->validDate($this->dari) ?? today()->toDateString();
        $sampai = $this->validDate($this->sampai) ?? today()->toDateString();

        return Guest::query()
            ->whereBetween('visit_date', [min($dari, $sampai), max($dari, $sampai)])
            ->when($this->kategori, fn ($q) => $q->where('kategori', $this->kategori))
            ->when(trim($this->cari) !== '', function ($q) {
                $term = '%'.str_replace(['%', '_'], ['\%', '\_'], trim($this->cari)).'%';
                $q->where(fn ($w) => $w->where('nama', 'like', $term)->orWhere('asal', 'like', $term));
            });
    }

    protected function validDate(string $value): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) ? $value : null;
    }

    public function with(): array
    {
        $summary = (clone $this->query())->toBase()->selectRaw('count(*) as lembar, coalesce(sum(jumlah), 0) as orang')->first();

        return [
            'guests' => $this->query()->orderByDesc('visit_date')->orderByDesc('nomor')->paginate(25),
            'lembar' => (int) $summary->lembar,
            'orang' => (int) $summary->orang,
            'detail' => $this->lihat ? Guest::query()->find($this->lihat) : null,
            'kategoriList' => config('dispusip.kategori_tamu'),
        ];
    }
};
?>

<div class="pnl-grid" style="gap: var(--space-6)">
    <div class="pnl-pagehead">
        <div>
            <h1>Buku tamu</h1>
            <p>Semua lembar yang diisi pengunjung dari website maupun tablet meja layanan. Foto tamu hanya dapat dilihat di sini.</p>
        </div>
        <div class="pnl-pagehead__actions">
            <x-dk.button variant="secondary" icon="download" :href="route('admin.buku-tamu.ekspor', ['dari' => $dari, 'sampai' => $sampai, 'kategori' => $kategori ?: null])">Unduh CSV</x-dk.button>
            <x-dk.button variant="secondary" icon="key-round" :href="route('admin.kode')" wire:navigate>Kode kunjungan</x-dk.button>
        </div>
    </div>

    <div class="pnl-card">
        <div class="pnl-toolbar">
            <x-ad.field label="Dari tanggal" for="dari"><input id="dari" type="date" class="pnl-input pnl-input--sm" wire:model.live="dari" max="{{ today()->toDateString() }}"></x-ad.field>
            <x-ad.field label="Sampai tanggal" for="sampai"><input id="sampai" type="date" class="pnl-input pnl-input--sm" wire:model.live="sampai" max="{{ today()->toDateString() }}"></x-ad.field>
            <x-ad.field label="Datang sebagai" for="kategori">
                <select id="kategori" class="pnl-select pnl-select--sm" wire:model.live="kategori">
                    <option value="">Semua</option>
                    @foreach ($kategoriList as $k)<option value="{{ $k }}">{{ $k }}</option>@endforeach
                </select>
            </x-ad.field>
            <x-ad.field label="Cari nama atau asal" for="cari" class="pnl-field--grow"><input id="cari" type="search" class="pnl-input pnl-input--sm" wire:model.live.debounce.400ms="cari" placeholder="Nama tamu, sekolah, instansi…"></x-ad.field>
            <div class="flex flex-wrap gap-1">
                @foreach (['hari' => 'Hari ini', 'kemarin' => 'Kemarin', 'minggu' => '7 hari', 'bulan' => 'Bulan ini'] as $key => $label)
                    <x-dk.button variant="ghost" size="sm" wire:click="rentang('{{ $key }}')">{{ $label }}</x-dk.button>
                @endforeach
            </div>
        </div>
        <div class="flex flex-wrap gap-x-6 gap-y-1 border-b border-line px-5 py-3 text-[14px] text-ink-muted">
            <span><strong class="text-ink">{{ \App\Support\Format::number($lembar) }}</strong> lembar</span>
            <span><strong class="text-ink">{{ \App\Support\Format::number($orang) }}</strong> orang (termasuk rombongan)</span>
        </div>

        @if ($guests->isEmpty())
            <x-ad.empty icon="notebook-pen" title="Tidak ada tamu pada rentang ini" text="Ubah tanggal atau kata pencarian." />
        @else
            <div class="pnl-tablewrap" wire:loading.class="opacity-60">
                <table class="pnl-table">
                    <thead>
                        <tr><th>No.</th><th>Waktu</th><th>Tamu</th><th>Datang sebagai</th><th>Keperluan</th><th class="num">Orang</th><th>Sumber</th><th><span class="dk-sr">Aksi</span></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($guests as $guest)
                            <tr wire:key="guest-{{ $guest->id }}">
                                <td class="no">{{ $guest->nomorLabel() }}</td>
                                <td class="whitespace-nowrap"><strong>{{ \App\Support\Format::shortDate($guest->visit_date) }}</strong><small>{{ $guest->created_at?->format('H.i') }} WITA</small></td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if ($guest->foto)
                                            <img class="pnl-thumb pnl-thumb--portrait" src="{{ route('admin.buku-tamu.foto', $guest) }}" alt="" loading="lazy">
                                        @else
                                            <span class="pnl-thumb pnl-thumb--portrait grid place-items-center text-ink-muted"><x-dk.icon name="image-off" :size="16" /></span>
                                        @endif
                                        <span><strong>{{ $guest->nama }}</strong><small>{{ $guest->asal ?: '—' }}</small></span>
                                    </div>
                                </td>
                                <td>{{ $guest->kategori }}</td>
                                <td>{{ $guest->keperluan }}</td>
                                <td class="num">{{ $guest->jumlah }}</td>
                                <td><span @class(['pnl-tag', 'pnl-tag--on' => $guest->sumber === 'kios'])>{{ $guest->sumber === 'kios' ? 'Kios' : 'Web' }}</span></td>
                                <td>
                                    <div class="pnl-actions">
                                        <button type="button" class="dk-iconbtn" wire:click="buka({{ $guest->id }})" aria-label="Lihat {{ $guest->nama }}"><x-dk.icon name="eye" :size="18" /></button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pnl-pager">{{ $guests->links() }}</div>
        @endif
    </div>

    @if ($detail)
        <x-ad.modal title="Tamu no. {{ $detail->nomorLabel() }} · {{ \App\Support\Format::shortDate($detail->visit_date) }}" size="wide">
            <div class="grid gap-6 md:grid-cols-[220px_minmax(0,1fr)]">
                <div class="pnl-photo">
                    @if ($detail->foto)
                        <img src="{{ route('admin.buku-tamu.foto', $detail) }}" alt="Foto {{ $detail->nama }}">
                    @else
                        <span class="grid justify-items-center gap-2 text-[13px]"><x-dk.icon name="image-off" :size="28" /> Tanpa foto</span>
                    @endif
                </div>
                <dl class="pnl-kv">
                    <dt>Nama</dt><dd>{{ $detail->nama }}</dd>
                    <dt>Asal</dt><dd>{{ $detail->asal ?: '—' }}</dd>
                    <dt>Datang sebagai</dt><dd>{{ $detail->kategori }}</dd>
                    <dt>Keperluan</dt><dd>{{ $detail->keperluan }}</dd>
                    <dt>Jenis kelamin</dt><dd>{{ $detail->jenis_kelamin ?: '—' }}</dd>
                    <dt>Jumlah orang</dt><dd>{{ $detail->jumlah }}</dd>
                    <dt>WhatsApp</dt>
                    <dd>
                        @if ($detail->whatsapp)
                            <a class="text-laut-700 underline" href="{{ \App\Support\Format::whatsappLink($detail->whatsapp) }}" target="_blank" rel="noopener">{{ $detail->whatsapp }}</a>
                        @else
                            —
                        @endif
                    </dd>
                    <dt>Waktu</dt><dd>{{ \App\Support\Format::longDate($detail->visit_date) }}, {{ $detail->created_at?->format('H.i') }} WITA</dd>
                    <dt>Diisi dari</dt><dd>{{ $detail->sumber === 'kios' ? 'Tablet meja layanan (kios)' : 'Website' }}</dd>
                </dl>
            </div>
            <x-slot:footer>
                <x-dk.button variant="ghost" icon="trash-2" class="mr-auto !text-danger" wire:click="hapus({{ $detail->id }})" wire:confirm="Hapus data tamu ini beserta fotonya? Tindakan ini tidak dapat dibatalkan.">Hapus</x-dk.button>
                <x-dk.button variant="secondary" wire:click="tutup">Tutup</x-dk.button>
            </x-slot:footer>
        </x-ad.modal>
    @endif
</div>
