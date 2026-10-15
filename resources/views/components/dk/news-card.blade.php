@props(['post', 'variant' => 'default'])
@php($url = route('berita.show', $post))
@if ($variant === 'compact')
    <a {{ $attributes->class(['dk-news', 'dk-news--compact']) }} href="{{ $url }}" wire:navigate>
        <span class="dk-news__thumb">@if ($post->image_url)<img src="{{ $post->image_url }}" alt="" loading="lazy">@endif</span>
        <span class="dk-news__body">
            <x-dk.badge :category="$post->category" size="sm" />
            <strong class="dk-news__title">{{ $post->title }}</strong>
            <span class="dk-news__meta"><x-dk.icon name="calendar" :size="14" /> {{ $post->date_label }}</span>
        </span>
    </a>
@else
    <article {{ $attributes->class(['dk-news', 'dk-news--featured' => $variant === 'featured']) }}>
        <a class="dk-news__media" href="{{ $url }}" tabindex="-1" aria-hidden="true" wire:navigate>
            @if ($post->image_url)<img src="{{ $post->image_url }}" alt="" loading="lazy">@endif
            <span class="dk-news__badge"><x-dk.badge :category="$post->category" on-image /></span>
            <span class="dk-news__date"><x-dk.icon name="calendar" :size="14" /> {{ $post->date_label }}</span>
        </a>
        <div class="dk-news__body">
            <h3 class="dk-news__title"><a href="{{ $url }}" wire:navigate>{{ $post->title }}</a></h3>
            <p class="dk-news__excerpt">{{ $post->summary }}</p>
            <div class="dk-news__foot">
                @if ($post->author)
                    <span class="dk-news__meta"><x-dk.icon name="user" :size="14" /> {{ $post->author }}</span>
                @endif
                <span class="dk-news__meta"><x-dk.icon name="eye" :size="14" /> {{ \App\Support\Format::number($post->views) }}<span class="dk-sr"> kali dibaca</span></span>
                @if ($variant === 'featured')
                    <x-dk.button variant="primary" size="sm" icon-right="arrow-right" :href="$url" wire:navigate>Baca selengkapnya</x-dk.button>
                @else
                    <a class="dk-more" href="{{ $url }}" wire:navigate>Baca <x-dk.icon name="arrow-right" :size="14" /><span class="dk-sr">: {{ $post->title }}</span></a>
                @endif
            </div>
        </div>
    </article>
@endif
