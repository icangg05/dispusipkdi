@props(['id', 'first' => false, 'last' => false, 'action' => 'pindah'])
<span class="pnl-sortbtns">
    <button type="button" wire:click="{{ $action }}({{ $id }}, -1)" @disabled($first) aria-label="Naikkan urutan"><x-dk.icon name="chevron-down" :size="16" class="rotate-180" /></button>
    <button type="button" wire:click="{{ $action }}({{ $id }}, 1)" @disabled($last) aria-label="Turunkan urutan"><x-dk.icon name="chevron-down" :size="16" /></button>
</span>
