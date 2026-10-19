{{-- Server-rendered dialog: render it inside @if ($showForm) and close with the given Livewire action.
     The first field gets focus only with a mouse: on touch screens that would pop the keyboard open. --}}
@props(['title', 'size' => null, 'close' => 'tutup'])
<div class="pnl-modal" role="dialog" aria-modal="true" aria-labelledby="pnl-modal-title" wire:keydown.escape.window="{{ $close }}"
    x-data x-init="matchMedia('(pointer: fine)').matches && $nextTick(() => $el.querySelector('input:not([type=hidden]), select, textarea')?.focus())">
    <div @class(['pnl-modal__box', 'pnl-modal__box--'.$size => $size]) @click.outside="$wire.{{ $close }}()">
        <div class="pnl-modal__head">
            <h2 id="pnl-modal-title">{{ $title }}</h2>
            <button type="button" class="dk-iconbtn dk-iconbtn--sm" wire:click="{{ $close }}" aria-label="Tutup"><x-dk.icon name="x" :size="18" /></button>
        </div>
        <div class="pnl-modal__body">{{ $slot }}</div>
        @isset($footer)
            <div class="pnl-modal__foot">{{ $footer }}</div>
        @endisset
    </div>
</div>
