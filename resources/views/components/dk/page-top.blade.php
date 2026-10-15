{{-- Inner-page top: regular navbar + PageHeader sharing the surface-sunken band. --}}
@props(['crumbs' => [], 'title', 'text' => null])
<div class="dk-pagetop">
    <x-dk.navbar />
    <div class="dk-container dk-pagehead">
        <div class="dk-pagehead__main">
            @if (count($crumbs))
                <nav class="dk-crumbs" aria-label="Lokasi halaman">
                    <ol>
                        @foreach ($crumbs as $crumb)
                            <li>
                                @if (! $loop->last && ! empty($crumb['href']))
                                    <a href="{{ $crumb['href'] }}" wire:navigate>{{ $crumb['label'] }}</a>
                                    <x-dk.icon name="chevron-right" :size="14" />
                                @else
                                    <span aria-current="page">{{ $crumb['label'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endif
            <h1 class="dk-pagehead__title">{{ $title }}</h1>
            @if ($text)
                <p class="dk-pagehead__text">{{ $text }}</p>
            @endif
        </div>
        @isset($aside)
            <div class="dk-pagehead__aside">{{ $aside }}</div>
        @endisset
    </div>
</div>
