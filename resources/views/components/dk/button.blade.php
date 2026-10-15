@props(['variant' => 'primary', 'size' => 'md', 'icon' => null, 'iconRight' => null, 'href' => null, 'block' => false])
@php
    $classes = ['dk-btn', 'dk-btn--'.$variant, 'dk-btn--'.$size, 'dk-btn--block' => $block];
    $iconSize = match ($size) { 'lg' => 20, 'sm' => 16, default => 18 };
@endphp
@if ($href)
<a href="{{ $href }}" {{ $attributes->class($classes) }}>
@else
<button {{ $attributes->merge(['type' => 'button'])->class($classes) }}>
@endif
    @if ($icon)<x-dk.icon :name="$icon" :size="$iconSize" />@endif
    @if ($slot->isNotEmpty())<span>{{ $slot }}</span>@endif
    @if ($iconRight)<x-dk.icon :name="$iconRight" :size="$iconSize" />@endif
@if ($href)
</a>
@else
</button>
@endif
