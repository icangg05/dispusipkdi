{{--
    Photo & video mosaic with a lightbox. `items` is a list of
    {id, type: foto|video, title, date, image, embed, duration, poster: {line1, line2, sub, tone}|null}.
--}}
@props(['items', 'tabs' => true])
@php($brand = site()->get('identitas.singkatan').' '.site()->get('identitas.kota'))
<div {{ $attributes->class('dk-gallery') }} x-data="gallery(@js($items))" @keydown.window="key($event)">
    @if ($tabs)
        <div class="dk-chips" role="tablist" aria-label="Jenis media">
            @foreach (['semua' => ['Semua', 'layers'], 'foto' => ['Foto', 'image'], 'video' => ['Video', 'film']] as $id => [$label, $icon])
                <button type="button" role="tab" class="dk-chip {{ $loop->first ? 'is-active' : '' }}" :class="{ 'is-active': tab === '{{ $id }}' }"
                    :aria-selected="tab === '{{ $id }}'" @click="setTab('{{ $id }}')">
                    <x-dk.icon :name="$icon" :size="15" :stroke="2.2" /> {{ $label }} <span class="dk-chip__n" x-text="count('{{ $id }}')"></span>
                </button>
            @endforeach
        </div>
    @endif

    <ul class="dk-ggrid" role="list">
        <template x-for="(g, k) in list" :key="g.id">
            <li class="dk-gitem" :class="{ 'dk-gitem--big': k === 0, 'dk-gitem--poster': !!g.poster }" data-aos="fade-up" :data-aos-delay="(k % 4) * 60">
                <button type="button" class="dk-gitem__btn" @click="show(k)" :aria-label="(g.type === 'video' ? 'Putar video: ' : 'Lihat foto: ') + g.title">
                    <template x-if="g.poster">
                        <span class="dk-poster" :class="'dk-poster--' + (g.poster.tone || 'laut')">
                            <span class="dk-poster__k">{{ $brand }}</span>
                            <span class="dk-poster__t"><span x-text="g.poster.line1"></span><em x-text="g.poster.line2"></em></span>
                            <span class="dk-poster__s" x-text="g.poster.sub"></span>
                        </span>
                    </template>
                    <template x-if="!g.poster && g.image"><img :src="g.image" alt="" loading="lazy"></template>
                    <template x-if="g.type === 'video'"><span class="dk-gitem__play"><x-dk.icon name="play" :size="22" /></span></template>
                    <span class="dk-gitem__cap">
                        <span class="dk-gitem__type">
                            <span x-show="g.type === 'video'"><x-dk.icon name="film" :size="13" /></span>
                            <span x-show="g.type !== 'video'"><x-dk.icon name="image" :size="13" /></span>
                            <span x-text="g.type === 'video' ? 'Video' + (g.duration ? ' · ' + g.duration : '') : 'Foto'"></span>
                        </span>
                        <strong x-text="g.title"></strong>
                    </span>
                </button>
            </li>
        </template>
    </ul>
    <p class="dk-empty" x-show="!list.length" x-cloak>Belum ada media di kategori ini.</p>

    {{ $slot }}

    <template x-teleport="body">
        <div class="dk-lightbox" role="dialog" aria-modal="true" :aria-label="cur ? cur.title : ''" x-show="cur" x-cloak @click.self="close()">
            <template x-if="cur">
                <div class="dk-lightbox__box">
                    <div class="dk-lightbox__media">
                        <template x-if="cur.type === 'video' && cur.embed">
                            <iframe :src="cur.embed" :title="cur.title" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </template>
                        <template x-if="!(cur.type === 'video' && cur.embed) && cur.poster">
                            <span class="dk-poster" :class="'dk-poster--' + (cur.poster.tone || 'laut')">
                                <span class="dk-poster__k">{{ $brand }}</span>
                                <span class="dk-poster__t"><span x-text="cur.poster.line1"></span><em x-text="cur.poster.line2"></em></span>
                                <span class="dk-poster__s" x-text="cur.poster.sub"></span>
                            </span>
                        </template>
                        <template x-if="!(cur.type === 'video' && cur.embed) && !cur.poster && cur.image">
                            <img :src="cur.image" :alt="cur.title">
                        </template>
                        <template x-if="cur.type === 'video' && !cur.embed">
                            <span class="dk-lightbox__video"><span class="dk-gitem__play dk-gitem__play--lg"><x-dk.icon name="play" :size="30" /></span><small>Video diputar dari kanal YouTube {{ $brand }}</small></span>
                        </template>
                    </div>
                    <div class="dk-lightbox__cap">
                        <span><strong x-text="cur.title"></strong><small x-text="cur.date + ' · ' + (open + 1) + ' / ' + list.length"></small></span>
                        <span class="dk-lightbox__ctrl">
                            <button type="button" class="dk-iconbtn" @click="step(-1)" aria-label="Sebelumnya"><x-dk.icon name="chevron-left" /></button>
                            <button type="button" class="dk-iconbtn" @click="step(1)" aria-label="Berikutnya"><x-dk.icon name="chevron-right" /></button>
                            <button type="button" class="dk-iconbtn" x-ref="close" @click="close()" aria-label="Tutup"><x-dk.icon name="x" /></button>
                        </span>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>
