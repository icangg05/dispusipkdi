<?php

use App\Enums\MediaType;
use App\Models\MediaItem;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Galeri Foto & Video')] class extends Component
{
    #[Url(as: 'jenis', except: 'semua')]
    public string $jenis = 'semua';

    /** First tile is 2×2, so 1 + 12n items fill whole rows of four. */
    public int $tampil = 13;

    public function pilih(string $jenis): void
    {
        $this->jenis = in_array($jenis, ['foto', 'video'], true) ? $jenis : 'semua';
        $this->tampil = 13;
    }

    public function lagi(): void
    {
        $this->tampil += 12;
    }

    public function with(): array
    {
        $query = MediaItem::query()->published()->when($this->jenis !== 'semua', fn ($q) => $q->where('type', $this->jenis));
        $total = (clone $query)->count();

        return [
            'items' => $query->limit($this->tampil)->get()->map->toGalleryItem()->all(),
            'total' => $total,
            'counts' => [
                'semua' => MediaItem::query()->published()->count(),
                'foto' => MediaItem::query()->published()->where('type', MediaType::Foto)->count(),
                'video' => MediaItem::query()->published()->where('type', MediaType::Video)->count(),
            ],
            'youtube' => collect(site()->sosmed())->firstWhere('platform', 'youtube'),
        ];
    }
};
?>

<div>
    <x-dk.page-top :crumbs="[['label' => 'Beranda', 'href' => route('beranda')], ['label' => 'Galeri']]" title="Galeri Foto & Video"
        text="Dokumentasi kegiatan, fasilitas, dan video layanan Dispusip Kota Kendari.">
        @if ($youtube)
            <x-slot:aside>
                <x-dk.button variant="secondary" icon="youtube" :href="$youtube['href']" target="_blank" rel="noopener">Kanal YouTube</x-dk.button>
            </x-slot:aside>
        @endif
    </x-dk.page-top>

    <x-dk.section tight>
        <div class="dk-gallery">
            <div class="dk-chips" role="tablist" aria-label="Jenis media">
                @foreach (['semua' => ['Semua', 'layers'], 'foto' => ['Foto', 'image'], 'video' => ['Video', 'film']] as $id => [$label, $icon])
                    <button type="button" role="tab" @class(['dk-chip', 'is-active' => $jenis === $id]) aria-selected="{{ $jenis === $id ? 'true' : 'false' }}" wire:click="pilih('{{ $id }}')">
                        <x-dk.icon :name="$icon" :size="15" :stroke="2.2" /> {{ $label }} <span class="dk-chip__n">{{ $counts[$id] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <div wire:key="galeri-{{ $jenis }}-{{ count($items) }}">
            <x-dk.media-gallery :items="$items" :tabs="false" />
        </div>

        @if ($total > count($items))
            <div class="dk-center">
                <x-dk.button variant="secondary" icon="plus" wire:click="lagi" wire:loading.attr="disabled" wire:target="lagi">Tampilkan lebih banyak</x-dk.button>
            </div>
        @endif
    </x-dk.section>
</div>
