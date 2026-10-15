{{-- Open/closed label: always colour + icon or dot + word. --}}
@props(['status' => null, 'variant' => 'full'])
@php($status ??= site()->status())
@if ($variant === 'sm')
    <span {{ $attributes->class(['dk-status', 'dk-status--sm', $status['open'] ? 'dk-status--open' : 'dk-status--closed']) }}>
        <span @class(['dk-dot', $status['open'] ? 'dk-dot--open' : 'dk-dot--closed']) aria-hidden="true"></span>{{ $status['label'] }}
    </span>
@elseif ($variant === 'dot')
    <span {{ $attributes->class(['dk-status', $status['open'] ? 'dk-status--open' : 'dk-status--closed']) }}>
        <span @class(['dk-dot', $status['open'] ? 'dk-dot--open' : 'dk-dot--closed']) aria-hidden="true"></span><strong>{{ $status['label'] }}</strong>@if ($status['detail']) · {{ $status['detail'] }}@endif
    </span>
@else
    <div {{ $attributes->class(['dk-status', $status['open'] ? 'dk-status--open' : 'dk-status--closed']) }}>
        <x-dk.icon :name="$status['open'] ? 'door-open' : 'clock'" :size="18" />
        <span><strong>{{ $status['label'] }}</strong>@if ($status['detail']) · {{ $status['detail'] }}@endif</span>
    </div>
@endif
