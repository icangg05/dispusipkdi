<?php

use App\Models\Category;
use App\Models\Post;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::public')] #[Title('Berita & Pengumuman')] class extends Component
{
    use WithPagination;

    #[Url(as: 'kategori', except: '')]
    public string $kategori = '';

    #[Url(as: 'cari', except: '')]
    public string $cari = '';

    public function pilih(string $slug): void
    {
        $this->kategori = $slug;
        $this->resetPage();
    }

    public function updatedCari(): void
    {
        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'partials.pagination';
    }

    public function with(): array
    {
        $categories = Category::query()->ordered()->withCount(['posts' => fn ($q) => $q->published()])->get();
        $active = $categories->firstWhere('slug', $this->kategori);

        $posts = Post::query()
            ->published()
            ->newest()
            ->with('category')
            ->when($active, fn ($q) => $q->where('category_id', $active->id))
            ->when(trim($this->cari) !== '', function ($q) {
                $term = '%'.str_replace(['%', '_'], ['\%', '\_'], trim($this->cari)).'%';
                $q->where(fn ($w) => $w->where('title', 'like', $term)->orWhere('excerpt', 'like', $term)->orWhere('body', 'like', $term));
            })
            ->paginate(9);

        return [
            'categories' => $categories,
            'active' => $active,
            'posts' => $posts,
            'total' => Post::query()->published()->count(),
        ];
    }
};
?>

<div>
    <x-dk.page-top :crumbs="[['label' => 'Beranda', 'href' => route('beranda')], ['label' => 'Berita']]" title="Berita & Pengumuman"
        text="Kabar kegiatan, pengumuman layanan, gerakan literasi, dan informasi kearsipan dari Dispusip Kota Kendari." />

    <x-dk.section tight>
        <div class="dk-toolbar">
            <div class="dk-chips" role="tablist" aria-label="Filter kategori">
                <button type="button" role="tab" @class(['dk-chip', 'is-active' => ! $active]) aria-selected="{{ $active ? 'false' : 'true' }}" wire:click="pilih('')">
                    <x-dk.icon name="newspaper" :size="15" :stroke="2.2" /> Semua <span class="dk-chip__n">{{ $total }}</span>
                </button>
                @foreach ($categories as $category)
                    <button type="button" role="tab" wire:key="cat-{{ $category->id }}" @class(['dk-chip', 'is-active' => $active?->is($category)])
                        aria-selected="{{ $active?->is($category) ? 'true' : 'false' }}" wire:click="pilih('{{ $category->slug }}')">
                        <x-dk.icon :name="$category->icon" :size="15" :stroke="2.2" /> {{ $category->name }} <span class="dk-chip__n">{{ $category->posts_count }}</span>
                    </button>
                @endforeach
            </div>
            <label class="dk-search">
                <span class="dk-sr">Cari berita</span>
                <x-dk.icon name="search" :size="18" />
                <input type="search" wire:model.live.debounce.400ms="cari" placeholder="Cari berita…">
            </label>
        </div>

        @if (trim($cari) !== '')
            <p class="dk-result">{{ $posts->total() }} berita untuk <strong>“{{ $cari }}”</strong>@if ($active) di kategori {{ $active->name }}@endif.</p>
        @endif

        <div wire:loading.class="opacity-60" class="transition-opacity">
            @if ($posts->isEmpty())
                <p class="dk-empty">Belum ada berita yang cocok.</p>
            @else
                <div class="dk-newscols">
                    @foreach ($posts as $post)
                        <x-dk.news-card :post="$post" wire:key="post-{{ $post->id }}" />
                    @endforeach
                </div>
            @endif
        </div>

        {{ $posts->links() }}
    </x-dk.section>
</div>
