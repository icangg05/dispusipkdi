<?php

use App\Models\Category;
use App\Models\Post;
use Livewire\Component;

/**
 * Berita & Pengumuman on the home page: category chips, one lead story, two side stories and three compact rows.
 */
new class extends Component
{
    public string $kategori = 'semua';

    public function pilih(string $slug): void
    {
        $this->kategori = $slug === 'semua' || Category::query()->where('slug', $slug)->exists() ? $slug : 'semua';
    }

    public function with(): array
    {
        $posts = Post::query()
            ->published()
            ->newest()
            ->with('category')
            ->when($this->kategori !== 'semua', fn ($q) => $q->whereRelation('category', 'slug', $this->kategori))
            ->limit(6)
            ->get();

        return [
            'categories' => Category::query()->ordered()->withCount(['posts' => fn ($q) => $q->published()])->get(),
            'total' => Post::query()->published()->count(),
            'first' => $posts->first(),
            'rest' => $posts->slice(1)->values(),
        ];
    }
};
?>

<div class="dk-newssec">
    <x-dk.section-heading eyebrow="Berita Terbaru" eyebrow-icon="newspaper" title="Berita &" accent="Pengumuman" id="berita"
        description="Kabar kegiatan, pengumuman layanan, gerakan literasi, dan informasi kearsipan dari Dispusip Kota Kendari." />

    <div class="dk-chips" role="tablist" aria-label="Filter kategori">
        <button type="button" role="tab" @class(['dk-chip', 'is-active' => $kategori === 'semua']) aria-selected="{{ $kategori === 'semua' ? 'true' : 'false' }}" wire:click="pilih('semua')">
            <x-dk.icon name="newspaper" :size="15" :stroke="2.2" /> Semua <span class="dk-chip__n">{{ $total }}</span>
        </button>
        @foreach ($categories as $category)
            <button type="button" role="tab" wire:key="cat-{{ $category->id }}" @class(['dk-chip', 'is-active' => $kategori === $category->slug])
                aria-selected="{{ $kategori === $category->slug ? 'true' : 'false' }}" wire:click="pilih('{{ $category->slug }}')">
                <x-dk.icon :name="$category->icon" :size="15" :stroke="2.2" /> {{ $category->name }} <span class="dk-chip__n">{{ $category->posts_count }}</span>
            </button>
        @endforeach
    </div>

    <div wire:loading.class="opacity-60" class="transition-opacity">
        @if ($first)
            <div @class(['dk-newsgrid', 'dk-newsgrid--single' => $rest->isEmpty()])>
                <x-dk.news-card :post="$first" variant="featured" wire:key="post-{{ $first->id }}" data-aos="fade-up" />
                @if ($rest->isNotEmpty())
                    <div class="dk-newsgrid__side">
                        @foreach ($rest->take(2) as $post)
                            <x-dk.news-card :post="$post" wire:key="post-{{ $post->id }}" data-aos="fade-up" data-aos-delay="{{ ($loop->index + 1) * 100 }}" />
                        @endforeach
                    </div>
                @endif
            </div>
            @if ($rest->count() > 2)
                <div class="dk-newslist">
                    @foreach ($rest->slice(2, 3) as $post)
                        <x-dk.news-card :post="$post" variant="compact" wire:key="post-c-{{ $post->id }}" data-aos="fade-up" data-aos-delay="{{ $loop->index * 80 }}" />
                    @endforeach
                </div>
            @endif
        @else
            <p class="dk-empty">Belum ada berita di kategori ini.</p>
        @endif
    </div>

    <div class="dk-center">
        <x-dk.button variant="secondary" icon-right="arrow-right" :href="route('berita.index', $kategori !== 'semua' ? ['kategori' => $kategori] : [])" wire:navigate>Lihat semua berita</x-dk.button>
    </div>
</div>
