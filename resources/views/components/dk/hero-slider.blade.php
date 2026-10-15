{{-- Full-width opener: background photos cross-fade every few seconds with a slow zoom; each step bar is the timer. --}}
@props(['slides', 'tamuHariIni' => 0, 'interval' => 6000])
@php($count = max(1, $slides->count()))
<section class="dk-hero" id="beranda" aria-roledescription="carousel" aria-label="Sorotan Dispusip"
    x-data="heroSlider({ count: {{ $count }}, interval: {{ $interval }} })" :class="{ 'dk-hero--still': reduce }">
    <div class="dk-hero__bgs" aria-hidden="true">
        @foreach ($slides as $k => $slide)
            <img src="{{ $slide->image_url }}" alt="" class="dk-hero__bg {{ $k === 0 ? 'is-active' : '' }}" :class="{ 'is-active': i === {{ $k }} }"
                @if ($k === 0) fetchpriority="high" @else loading="lazy" @endif>
        @endforeach
    </div>
    <div class="dk-hero__scrim" aria-hidden="true"></div>

    {{ $slot }}

    <div class="dk-container dk-hero__in">
        {{-- Every slide stays in the layout (hidden ones invisible), so the hero is as tall as its longest slide. --}}
        <div class="dk-hero__slides">
            @foreach ($slides as $k => $slide)
                <div class="dk-hero__copy {{ $k === 0 ? 'is-active' : '' }}" :class="{ 'is-active': i === {{ $k }} }" :aria-live="paused ? 'polite' : 'off'">
                    <x-dk.eyebrow icon="sparkles" dark>{{ $slide->kicker }}</x-dk.eyebrow>
                    @if ($k === 0)
                        <h1 class="dk-hero__title">{{ $slide->title }}@if ($slide->accent) <em class="dk-accent">{{ $slide->accent }}</em>@endif</h1>
                    @else
                        <h2 class="dk-hero__title">{{ $slide->title }}@if ($slide->accent) <em class="dk-accent">{{ $slide->accent }}</em>@endif</h2>
                    @endif
                    @if ($slide->text)
                        <p class="dk-hero__text">{{ $slide->text }}</p>
                    @endif
                    <div class="dk-hero__ctas">
                        <x-dk.button variant="lentera" size="lg" icon="notebook-pen" :href="route('buku-tamu')" wire:navigate>Isi Buku Tamu</x-dk.button>
                        <x-dk.button variant="on-dark" size="lg" icon="layers" href="#layanan">Jelajahi Layanan</x-dk.button>
                    </div>
                </div>
            @endforeach
        </div>

        <aside class="dk-hero__info" aria-label="Layanan hari ini">
            <div class="dk-hero__info-top">
                <span class="dk-hero__info-k">Layanan hari ini</span>
                <span class="dk-hero__info-date">{{ \App\Support\Format::longDate() }}</span>
            </div>
            <x-dk.status />
            <div class="dk-hero__info-stat">
                <span class="dk-hero__info-num" x-data="countUp({{ (int) $tamuHariIni }})" x-text="text">{{ \App\Support\Format::number($tamuHariIni) }}</span>
                <span class="dk-hero__info-lbl">tamu tercatat hari ini<br><small>dari buku tamu digital</small></span>
            </div>
            <x-dk.button variant="lentera" icon="notebook-pen" :href="route('buku-tamu')" block wire:navigate>Catat kunjungan saya</x-dk.button>
        </aside>
    </div>

    @if ($count > 1)
        <div class="dk-container dk-hero__nav">
            <ol class="dk-hero__steps" role="list">
                @foreach ($slides as $k => $slide)
                    <li>
                        <button type="button" class="dk-hero__step {{ $k === 0 ? 'is-active' : '' }}" :class="{ 'is-active': i === {{ $k }} }" @click="go({{ $k }})"
                            aria-label="Slide {{ $k + 1 }}: {{ $slide->caption }}" :aria-current="i === {{ $k }} ? 'true' : null">
                            <span class="dk-hero__step-n">{{ str_pad((string) ($k + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="dk-hero__step-c">{{ $slide->caption }}</span>
                            <span class="dk-hero__bar"><span :style="{ animationDuration: interval + 'ms', animationPlayState: paused ? 'paused' : 'running' }" @animationend="ended({{ $k }})"></span></span>
                        </button>
                    </li>
                @endforeach
            </ol>
            <div class="dk-hero__ctrl">
                <button type="button" class="dk-iconbtn dk-iconbtn--dark" @click="prev()" aria-label="Slide sebelumnya"><x-dk.icon name="chevron-left" /></button>
                <button type="button" class="dk-iconbtn dk-iconbtn--dark" @click="toggle()" :aria-label="paused ? 'Putar slide' : 'Jeda slide'" aria-label="Jeda slide">
                    <span x-show="!paused"><x-dk.icon name="pause" :size="18" /></span>
                    <span x-show="paused" x-cloak><x-dk.icon name="play" :size="18" /></span>
                </button>
                <button type="button" class="dk-iconbtn dk-iconbtn--dark" @click="next()" aria-label="Slide berikutnya"><x-dk.icon name="chevron-right" /></button>
            </div>
        </div>
    @else
        <div class="dk-container dk-hero__nav"></div>
    @endif
</section>
