@props([
    'title',
    'accent' => null,
    'eyebrow' => null,
    'eyebrowIcon' => 'book-marked',
    'description' => null,
    'align' => 'split',
    'dark' => false,
    'as' => 'h2',
    'id' => null,
])
<div {{ $attributes->merge(['data-aos' => 'fade-up'])->class(['dk-sh', 'dk-sh--'.$align, 'dk-sh--dark' => $dark]) }}>
    <div class="dk-sh__main">
        @if ($eyebrow)
            <x-dk.eyebrow :icon="$eyebrowIcon" :dark="$dark">{{ $eyebrow }}</x-dk.eyebrow>
        @endif
        <{{ $as }} class="dk-sh__title" @if ($id) id="{{ $id }}" @endif>
            {{ $title }}@if ($accent) <em class="dk-accent">{{ $accent }}</em>@endif
        </{{ $as }}>
    </div>
    @if ($description || isset($action))
        <div class="dk-sh__aside">
            @if ($description)
                <p class="dk-sh__desc">{{ $description }}</p>
            @endif
            {{ $action ?? '' }}
        </div>
    @endif
</div>
