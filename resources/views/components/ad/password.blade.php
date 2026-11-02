{{-- Password input with a show/hide button. `id` and `model` name the field; other attributes (autocomplete, placeholder, required) go on the input. --}}
@props(['id', 'model'])
<div class="pnl-password" x-data="{ show: false }">
    <input {{ $attributes->merge(['id' => $id, 'type' => 'password', 'class' => 'pnl-input']) }} :type="show ? 'text' : 'password'" wire:model="{{ $model }}">
    {{-- wire:ignore stops a re-render (after a failed submit) from desyncing the icon from the field. --}}
    <button type="button" class="pnl-password__toggle" @click="show = ! show" aria-controls="{{ $id }}" :aria-pressed="show" :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" aria-label="Tampilkan kata sandi" wire:ignore x-cloak>
        <span x-show="! show"><x-dk.icon name="eye" :size="20" /></span>
        <span x-show="show" x-cloak><x-dk.icon name="eye-off" :size="20" /></span>
    </button>
</div>
