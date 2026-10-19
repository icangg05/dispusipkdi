<?php

use App\Models\Category;
use App\Models\Post;
use App\Support\Media;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] #[Title('Berita')] class extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $kategori = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $cari = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function hapus(int $id): void
    {
        $post = Post::query()->findOrFail($id);
        Media::delete($post->image);
        $post->delete();
        $this->dispatch('toast', message: 'Berita dihapus.');
    }

    public function terbitkan(int $id, bool $terbit): void
    {
        $post = Post::query()->findOrFail($id);
        $post->update(['is_published' => $terbit, 'published_at' => $terbit ? ($post->published_at ?? now()) : $post->published_at]);
        $this->dispatch('toast', message: $terbit ? 'Berita diterbitkan.' : 'Berita dijadikan draf.');
    }

    public function paginationView(): string
    {
        return 'partials.pagination';
    }

    public function with(): array
    {
        return [
            'categories' => Category::query()->ordered()->get(),
            'posts' => Post::query()
                ->with('category')
                ->when($this->kategori, fn ($q) => $q->where('category_id', $this->kategori))
                ->when($this->status === 'terbit', fn ($q) => $q->published())
                ->when($this->status === 'draf', fn ($q) => $q->where('is_published', false))
                ->when($this->status === 'terjadwal', fn ($q) => $q->where('is_published', true)->where('published_at', '>', now()))
                ->when(trim($this->cari) !== '', fn ($q) => $q->where('title', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], trim($this->cari)).'%'))
                ->orderByRaw('published_at is null desc')
                ->latest('published_at')
                ->latest('id')
                ->paginate(15),
        ];
    }
};
?>

<div class="pnl-grid" style="gap: var(--space-6)">
    <div class="pnl-pagehead">
        <div>
            <h1>Berita</h1>
            <p>Berita, pengumuman, dan kabar kegiatan yang tampil di situs. Setiap berita memakai satu kategori.</p>
        </div>
        <div class="pnl-pagehead__actions">
            <x-dk.button variant="secondary" icon="tag" :href="route('admin.kategori')" wire:navigate>Kategori</x-dk.button>
            <x-dk.button variant="primary" icon="plus" :href="route('admin.berita.create')" wire:navigate>Tulis berita</x-dk.button>
        </div>
    </div>

    <div class="pnl-card">
        <div class="pnl-toolbar">
            <x-ad.field label="Kategori" for="f-kategori">
                <select id="f-kategori" class="pnl-select pnl-select--sm" wire:model.live="kategori">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                </select>
            </x-ad.field>
            <x-ad.field label="Status" for="f-status">
                <select id="f-status" class="pnl-select pnl-select--sm" wire:model.live="status">
                    <option value="">Semua status</option>
                    <option value="terbit">Terbit</option>
                    <option value="terjadwal">Terjadwal</option>
                    <option value="draf">Draf</option>
                </select>
            </x-ad.field>
            <x-ad.field label="Cari judul" for="f-cari" class="pnl-field--grow">
                <input id="f-cari" type="search" class="pnl-input pnl-input--sm" wire:model.live.debounce.400ms="cari" placeholder="Judul berita…">
            </x-ad.field>
        </div>

        @if ($posts->isEmpty())
            <x-ad.empty icon="newspaper" title="Belum ada berita" text="Tulis berita pertama Anda.">
                <x-dk.button variant="primary" icon="plus" :href="route('admin.berita.create')" wire:navigate>Tulis berita</x-dk.button>
            </x-ad.empty>
        @else
            <div class="pnl-tablewrap">
                <table class="pnl-table">
                    <thead><tr><th>Berita</th><th>Kategori</th><th>Status</th><th>Tanggal</th><th class="num">Dibaca</th><th><span class="dk-sr">Aksi</span></th></tr></thead>
                    <tbody>
                        @foreach ($posts as $post)
                            @php($visible = $post->isVisible())
                            <tr wire:key="post-{{ $post->id }}">
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if ($post->image_url)
                                            <img class="pnl-thumb" src="{{ $post->image_url }}" alt="" loading="lazy">
                                        @else
                                            <span class="pnl-thumb grid place-items-center text-ink-muted"><x-dk.icon name="image-off" :size="16" /></span>
                                        @endif
                                        <span class="min-w-0"><strong class="line-clamp-2">{{ $post->title }}</strong><small>{{ $post->author ?: '—' }}</small></span>
                                    </div>
                                </td>
                                <td><x-dk.badge :category="$post->category" size="sm" /></td>
                                <td>
                                    @if ($visible)
                                        <span class="pnl-tag pnl-tag--on">Terbit</span>
                                    @elseif ($post->is_published)
                                        <span class="pnl-tag pnl-tag--new">Terjadwal</span>
                                    @else
                                        <span class="pnl-tag">Draf</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">{{ $post->published_at ? \App\Support\Format::shortDate($post->published_at) : '—' }}</td>
                                <td class="num">{{ \App\Support\Format::number($post->views) }}</td>
                                <td>
                                    <div class="pnl-actions">
                                        @if ($visible)
                                            <a class="dk-iconbtn" href="{{ route('berita.show', $post) }}" target="_blank" aria-label="Lihat di situs"><x-dk.icon name="external-link" :size="17" /></a>
                                        @endif
                                        <a class="dk-iconbtn" href="{{ route('admin.berita.edit', $post) }}" wire:navigate aria-label="Ubah {{ $post->title }}"><x-dk.icon name="pencil" :size="17" /></a>
                                        <button type="button" class="dk-iconbtn pnl-iconbtn--danger" wire:click="hapus({{ $post->id }})" wire:confirm="Hapus berita “{{ $post->title }}”?" aria-label="Hapus"><x-dk.icon name="trash-2" :size="17" /></button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pnl-pager">{{ $posts->links() }}</div>
        @endif
    </div>
</div>
