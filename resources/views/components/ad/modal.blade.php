{{-- Server-rendered dialog: render it inside @if ($showForm) and close with the given Livewire action. --}}
@props(['title', 'size' => null, 'close' => 'tutup'])
<div class="ad-modal" role="dialog" aria-modal="true" aria-labelledby="ad-modal-title" wire:keydown.escape.window="{{ $close }}"
    x-data x-init="$nextTick(() => $el.querySelector('input:not([type=hidden]), select, textarea')?.focus())">
    <div @class(['ad-modal__box', 'ad-modal__box--'.$size => $size]) @click.outside="$wire.{{ $close }}()">
        <div class="ad-modal__head">
            <h2 id="ad-modal-title">{{ $title }}</h2>
            <button type="button" class="dk-iconbtn dk-iconbtn--sm" wire:click="{{ $close }}" aria-label="Tutup"><x-dk.icon name="x" :size="18" /></button>
        </div>
        <div class="ad-modal__body">{{ $slot }}</div>
        @isset($footer)
            <div class="ad-modal__foot">{{ $footer }}</div>
        @endisset
    </div>
</div>
