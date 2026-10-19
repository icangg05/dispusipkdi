@props(['model', 'name' => null])
@php($name ??= str_replace('.', '-', $model))
<div class="pnl-icons" role="radiogroup">
    @foreach (config('dispusip.ikon_pilihan') as $icon)
        <label title="{{ $icon }}">
            <input type="radio" name="{{ $name }}" value="{{ $icon }}" wire:model="{{ $model }}" aria-label="{{ $icon }}">
            <x-dk.icon :name="$icon" :size="18" />
        </label>
    @endforeach
</div>
