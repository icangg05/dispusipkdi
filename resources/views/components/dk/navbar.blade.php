@props(['overlay' => false])
@php
    $nav = site()->nav();
    $kontak = site()->kontak();
    $status = site()->status();
    $pageRoute = page_route_name();
    $isActive = fn (array $item): bool => $item['active'] && $pageRoute && \Illuminate\Support\Str::is($item['active'], $pageRoute);
@endphp
<header {{ $attributes->class(['dk-nav', 'dk-nav--overlay' => $overlay]) }} x-data="{ open: false }" @keydown.escape.window="open = false">
    <div class="dk-topbar">
        <div class="dk-container dk-topbar__in">
            <div class="dk-topbar__info">
                <span @class(['dk-dot', $status['open'] ? 'dk-dot--open' : 'dk-dot--closed']) aria-hidden="true"></span>
                <span><strong>{{ $status['label'] }}</strong><span class="dk-hide-sm"> · {{ $status['detail'] }}</span></span>
                <span class="dk-topbar__sep" aria-hidden="true"></span>
                @if ($kontak['whatsappLink'])
                    <a href="{{ $kontak['whatsappLink'] }}" class="dk-topbar__link" target="_blank" rel="noopener"><x-dk.icon name="whatsapp" :size="14" /> {{ $kontak['whatsapp'] }}</a>
                @endif
                <a href="mailto:{{ $kontak['email'] }}" class="dk-topbar__link dk-hide-sm"><x-dk.icon name="mail" :size="14" /> {{ $kontak['email'] }}</a>
            </div>
            <span class="dk-hide-sm"><x-dk.social-links dark size="sm" /></span>
        </div>
    </div>
    <div class="dk-container">
        <nav class="dk-navbar" aria-label="Menu utama">
            <x-dk.brand />
            <ul class="dk-navbar__menu" role="list">
                @foreach ($nav as $item)
                    <li>
                        <a href="{{ $item['href'] }}" @class(['dk-navlink', 'is-active' => $isActive($item)]) @if ($isActive($item)) aria-current="page" @endif wire:navigate>{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ul>
            <div class="dk-navbar__end">
                <x-dk.button variant="lentera" size="sm" icon="notebook-pen" :href="route('buku-tamu')" class="dk-hide-sm" wire:navigate>Buku Tamu</x-dk.button>
                <button type="button" class="dk-navbar__burger" :aria-expanded="open" :aria-label="open ? 'Tutup menu' : 'Buka menu'" aria-label="Buka menu" @click="open = !open">
                    <span x-show="!open"><x-dk.icon name="menu" :size="22" /></span>
                    <span x-show="open" x-cloak><x-dk.icon name="x" :size="22" /></span>
                </button>
            </div>
        </nav>
        <div class="dk-navdrawer" x-show="open" x-cloak @click.outside="open = false">
            @foreach ($nav as $item)
                <a href="{{ $item['href'] }}" @class(['dk-navdrawer__link', 'is-active' => $isActive($item)]) @click="open = false" wire:navigate>
                    <x-dk.icon :name="$item['icon']" :size="18" /> {{ $item['label'] }}
                </a>
            @endforeach
            <x-dk.button variant="lentera" icon="notebook-pen" :href="route('buku-tamu')" block wire:navigate>Buku Tamu</x-dk.button>
        </div>
    </div>
</header>
