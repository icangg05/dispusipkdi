@props(['overlay' => false])
@php
    $nav = site()->nav();
    $kontak = site()->kontak();
    $status = site()->status();
    $pageRoute = page_route_name();
    $isActive = fn (array $item): bool => $item['active'] && $pageRoute && \Illuminate\Support\Str::is($item['active'], $pageRoute);
    $survei = config('dispusip.survei_url');
@endphp
<header {{ $attributes->class(['dk-nav', 'dk-nav--overlay' => $overlay]) }} x-data="navbar" @keydown.escape.window="open = false; closeSurvey()">
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
                        <a href="{{ $item['href'] }}" @class(['dk-navlink', 'is-active' => $isActive($item)]) @if ($isActive($item)) aria-current="page" @endif wire:navigate.hover>{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ul>
            <div class="dk-navbar__end">
                @if ($survei)
                    <x-dk.button variant="secondary" size="sm" icon="clipboard-list" class="dk-hide-sm" @click="openSurvey()">Survei Kepuasan</x-dk.button>
                @endif
                <x-dk.button variant="lentera" size="sm" icon="notebook-pen" :href="route('buku-tamu')" class="dk-hide-sm" wire:navigate.hover>Buku Tamu</x-dk.button>
                <button type="button" class="dk-navbar__burger" :aria-expanded="open" :aria-label="open ? 'Tutup menu' : 'Buka menu'" aria-label="Buka menu" @click="open = !open">
                    <span x-show="!open"><x-dk.icon name="menu" :size="22" /></span>
                    <span x-show="open" x-cloak><x-dk.icon name="x" :size="22" /></span>
                </button>
            </div>
        </nav>
        <div class="dk-navdrawer" x-show="open" x-cloak @click.outside="open = false">
            @foreach ($nav as $item)
                <a href="{{ $item['href'] }}" @class(['dk-navdrawer__link', 'is-active' => $isActive($item)]) @click="open = false" wire:navigate.hover>
                    <x-dk.icon :name="$item['icon']" :size="18" /> {{ $item['label'] }}
                </a>
            @endforeach
            @if ($survei)
                <x-dk.button variant="secondary" icon="clipboard-list" block @click="openSurvey()">Survei Kepuasan</x-dk.button>
            @endif
            <x-dk.button variant="lentera" icon="notebook-pen" :href="route('buku-tamu')" block wire:navigate.hover>Buku Tamu</x-dk.button>
        </div>
    </div>

    @if ($survei)
        <template x-teleport="body">
            <div class="dk-lightbox dk-survei" role="dialog" aria-modal="true" aria-labelledby="judul-survei" x-show="survey" x-cloak @click.self="closeSurvey()">
                <div class="dk-survei__box">
                    <div class="dk-survei__head">
                        <h2 id="judul-survei">Survei Kepuasan Masyarakat</h2>
                        <a class="dk-survei__open" href="{{ $survei }}" target="_blank" rel="noopener" aria-label="Buka survei di tab baru"><x-dk.icon name="external-link" :size="16" /> <span>Buka di tab baru</span></a>
                        <button type="button" class="dk-iconbtn" x-ref="closeSurvey" @click="closeSurvey()" aria-label="Tutup survei"><x-dk.icon name="x" /></button>
                    </div>
                    <template x-if="surveyLoaded">
                        <iframe src="{{ $survei }}" title="Survei Kepuasan Masyarakat"></iframe>
                    </template>
                </div>
            </div>
        </template>
    @endif
</header>
