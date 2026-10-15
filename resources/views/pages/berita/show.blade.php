<?php

use App\Models\Category;
use App\Models\Post;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component
{
    public Post $post;

    public function mount(Post $post): void
    {
        abort_unless($post->isVisible(), 404);

        $this->post = $post->load('category');

        $viewed = session()->get('berita_dibaca', []);

        if (! in_array($post->id, $viewed, true)) {
            $post->increment('views');
            session()->push('berita_dibaca', $post->id);
        }
    }

    public function render()
    {
        return $this->view([
            'related' => Post::query()->published()->with('category')
                ->whereKeyNot($this->post->id)
                ->orderByRaw('category_id = ? desc', [$this->post->category_id])
                ->newest()
                ->limit(4)->get(),
            'categories' => Category::query()->ordered()->withCount(['posts' => fn ($q) => $q->published()])->get(),
            'shareUrl' => route('berita.show', $this->post),
        ])->title($this->post->title)->layoutData([
            'description' => $this->post->summary,
            'ogImage' => $this->post->image_url,
            'ogType' => 'article',
        ]);
    }
};
?>

<div>
    <x-dk.page-top :crumbs="[['label' => 'Beranda', 'href' => route('beranda')], ['label' => 'Berita', 'href' => route('berita.index')], ['label' => $post->category->name, 'href' => route('berita.index', ['kategori' => $post->category->slug])], ['label' => \Illuminate\Support\Str::limit($post->title, 48)]]"
        :title="$post->title" />

    <x-dk.section tight>
        <div class="dk-article">
            <article>
                <header class="dk-article__head">
                    <x-dk.badge :category="$post->category" />
                    <div class="dk-article__meta">
                        <span><x-dk.icon name="calendar" :size="16" /> {{ \App\Support\Format::longDate($post->published_at) }}</span>
                        @if ($post->author)<span><x-dk.icon name="user" :size="16" /> {{ $post->author }}</span>@endif
                        <span><x-dk.icon name="eye" :size="16" /> {{ \App\Support\Format::number($post->views) }} kali dibaca</span>
                    </div>
                </header>
                @if ($post->image_url)
                    <figure class="dk-article__cover"><img src="{{ $post->image_url }}" alt="{{ $post->title }}"></figure>
                @endif
                @if ($post->excerpt)
                    <p class="dk-article__lede">{{ $post->excerpt }}</p>
                @endif
                <div class="dk-prose">{!! $post->body_html !!}</div>

                <div class="dk-share" x-data="{ copied: false }">
                    <span class="dk-share__label">Bagikan</span>
                    <x-dk.button variant="secondary" size="sm" icon="whatsapp" :href="'https://wa.me/?text='.rawurlencode($post->title.' '.$shareUrl)" target="_blank" rel="noopener">WhatsApp</x-dk.button>
                    <x-dk.button variant="secondary" size="sm" icon="facebook" :href="'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($shareUrl)" target="_blank" rel="noopener">Facebook</x-dk.button>
                    <x-dk.button variant="ghost" size="sm" icon="link-2" @click="navigator.clipboard?.writeText({{ \Illuminate\Support\Js::from($shareUrl) }}).then(() => { copied = true; setTimeout(() => copied = false, 2500) })">
                        <span x-text="copied ? 'Tautan disalin' : 'Salin tautan'">Salin tautan</span>
                    </x-dk.button>
                </div>
            </article>

            <aside class="dk-aside" aria-label="Berita lain">
                @if ($related->isNotEmpty())
                    <div class="dk-aside__box">
                        <h2 class="dk-aside__title">Berita lainnya</h2>
                        @foreach ($related as $item)
                            <x-dk.news-card :post="$item" variant="compact" wire:key="rel-{{ $item->id }}" />
                        @endforeach
                    </div>
                @endif
                <div class="dk-aside__box">
                    <h2 class="dk-aside__title">Kategori</h2>
                    <ul class="dk-aside__cats" role="list">
                        @foreach ($categories as $category)
                            <li><a href="{{ route('berita.index', ['kategori' => $category->slug]) }}" wire:navigate><span>{{ $category->name }}</span><span class="dk-chip__n">{{ $category->posts_count }}</span></a></li>
                        @endforeach
                    </ul>
                </div>
                <x-dk.button variant="secondary" icon="arrow-left" :href="route('berita.index')" wire:navigate>Semua berita</x-dk.button>
            </aside>
        </div>
    </x-dk.section>
</div>
