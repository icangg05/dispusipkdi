@props(['service'])
<a {{ $attributes->class(['dk-svc', 'dk-svc--featured' => $service->is_featured]) }} href="{{ $service->href ?: '#kontak' }}" @if (is_internal_page($service->href)) wire:navigate @endif>
    <span @class(['dk-tile', 'dk-tile--lentera' => $service->is_featured])><x-dk.icon :name="$service->icon" :size="24" /></span>
    <strong class="dk-svc__title">{{ $service->title }}</strong>
    <span class="dk-svc__text">{{ $service->text }}</span>
    <span class="dk-svc__link">{{ $service->link_label ?: 'Selengkapnya' }} <x-dk.icon name="arrow-right" :size="16" /></span>
</a>
