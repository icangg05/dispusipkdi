@props(['icon' => 'users', 'label', 'value', 'delta' => null, 'deltaLabel' => null, 'caption' => null, 'dark' => false, 'highlight' => false])
@php($up = $delta !== null && $delta >= 0)
<div {{ $attributes->class(['dk-stat', 'dk-stat--dark' => $dark, 'dk-stat--hl' => $highlight]) }}>
    <div class="dk-stat__head">
        <span @class(['dk-tile', 'dk-tile--glass' => $dark && ! $highlight, 'dk-tile--lentera' => $highlight])><x-dk.icon :name="$icon" :size="20" /></span>
        <span class="dk-stat__label">{{ $label }}</span>
    </div>
    @if (is_numeric($value))
        <span class="dk-stat__value" x-data="countUp({{ (int) $value }})" x-text="text">{{ \App\Support\Format::number($value) }}</span>
    @else
        <span class="dk-stat__value">{{ $value }}</span>
    @endif
    @if ($delta !== null || $caption || $deltaLabel)
        <span class="dk-stat__foot">
            @if ($delta !== null)
                <span @class(['dk-delta', $up ? 'dk-delta--up' : 'dk-delta--down'])>
                    <x-dk.icon :name="$up ? 'trending-up' : 'arrow-down-right'" :size="14" /> {{ \App\Support\Format::delta($delta) }}
                </span>
            @endif
            @if ($deltaLabel)<span>{{ $deltaLabel }}</span>@endif
            @if ($caption)<span>{{ $caption }}</span>@endif
        </span>
    @endif
</div>
