{{-- Image picker with preview. `file` is the Livewire temporary upload, `current` the saved image URL. --}}
@props(['model', 'file' => null, 'current' => null, 'square' => false])
@php
    $preview = null;

    if ($file && method_exists($file, 'isPreviewable') && $file->isPreviewable()) {
        $preview = $file->temporaryUrl();
    }

    $preview ??= $current;
@endphp
<div class="ad-upload">
    <div @class(['ad-upload__preview', 'ad-upload__preview--square' => $square])>
        @if ($preview)
            <img src="{{ $preview }}" alt="Pratinjau gambar">
        @else
            <x-dk.icon name="image-plus" :size="28" :stroke="1.6" />
        @endif
    </div>
    <div class="ad-upload__side">
        <label class="dk-btn dk-btn--secondary dk-btn--sm">
            <x-dk.icon name="upload" :size="16" /><span>{{ $preview ? 'Ganti gambar' : 'Pilih gambar' }}</span>
            <input type="file" accept="image/png,image/jpeg,image/webp" class="dk-sr" wire:model="{{ $model }}" id="{{ $attributes->get('id', str_replace('.', '-', $model)) }}">
        </label>
        @if ($file && method_exists($file, 'getClientOriginalName'))
            <span class="ad-help">{{ \Illuminate\Support\Str::limit($file->getClientOriginalName(), 40) }}</span>
        @endif
        <span class="ad-help" wire:loading wire:target="{{ $model }}">Mengunggah…</span>
        <span class="ad-help">JPG, PNG, atau WebP, paling besar 4 MB. Gambar otomatis dikompres dan disimpan sebagai WebP.</span>
        {{ $slot }}
    </div>
</div>
