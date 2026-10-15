@props(['category' => null, 'tone' => 'laut', 'icon' => null, 'size' => 'md', 'onImage' => false])
@php
    $toneValue = $category ? $category->tone->value : $tone;
    $iconName = $icon ?? $category?->icon;
@endphp
<span {{ $attributes->class(['dk-badge', 'dk-badge--'.$toneValue, 'dk-badge--sm' => $size === 'sm', 'dk-badge--on-image' => $onImage]) }}>
    @if ($iconName)<x-dk.icon :name="$iconName" :size="$size === 'sm' ? 12 : 14" :stroke="2.4" />@endif
    {{ $slot->isNotEmpty() ? $slot : $category?->name }}
</span>
