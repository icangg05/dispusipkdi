@props(['model', 'live' => false])
<label {{ $attributes->class('ad-check') }}>
    <input type="checkbox" @if ($live) wire:model.live="{{ $model }}" @else wire:model="{{ $model }}" @endif>
    <span class="dk-tick__box" aria-hidden="true"><x-dk.icon name="check" :size="16" :stroke="3" /></span>
    <span>{{ $slot }}</span>
</label>
