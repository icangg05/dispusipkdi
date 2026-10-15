{{-- Floating accessibility menu, bottom-left on every public page. --}}
@php
    $options = [
        ['id' => 'kontras', 'icon' => 'contrast', 'label' => 'Kontras tinggi'],
        ['id' => 'abu', 'icon' => 'droplet', 'label' => 'Skala abu-abu'],
        ['id' => 'tautan', 'icon' => 'link', 'label' => 'Sorot tautan'],
        ['id' => 'spasi', 'icon' => 'space', 'label' => 'Spasi teks'],
        ['id' => 'diam', 'icon' => 'circle-pause', 'label' => 'Hentikan animasi'],
        ['id' => 'kursor', 'icon' => 'mouse-pointer-2', 'label' => 'Kursor besar'],
        ['id' => 'suara', 'icon' => 'volume-2', 'label' => 'Bacakan teks'],
    ];
@endphp
<div class="dk-a11y" x-data="a11yMenu()" @keydown.escape.window="open && hide()">
    <div class="dk-a11y__panel" role="dialog" aria-label="Pengaturan aksesibilitas" id="dk-a11y-panel" x-ref="panel" x-show="open" x-cloak>
        <div class="dk-a11y__head">
            <span class="dk-tile dk-tile--sm"><x-dk.icon name="accessibility" :size="20" /></span>
            <span class="dk-a11y__title"><strong>Mode aksesibilitas</strong><small>Sesuaikan tampilan agar nyaman dibaca</small></span>
            <button type="button" class="dk-iconbtn dk-iconbtn--sm" @click="hide()" aria-label="Tutup pengaturan aksesibilitas"><x-dk.icon name="x" :size="18" /></button>
        </div>
        <div class="dk-a11y__size">
            <span class="dk-a11y__sizel"><x-dk.icon name="a-large-small" :size="18" /> Ukuran teks</span>
            <span class="dk-a11y__steps" role="group" aria-label="Ukuran teks">
                <button type="button" @click="size(-1)" :disabled="st.teks === 0" aria-label="Perkecil teks"><x-dk.icon name="minus" :size="16" /></button>
                <output aria-live="polite" x-text="skala[st.teks] + '%'">100%</output>
                <button type="button" @click="size(1)" :disabled="st.teks === 2" aria-label="Perbesar teks"><x-dk.icon name="plus" :size="16" /></button>
            </span>
        </div>
        <div class="dk-a11y__grid">
            @foreach ($options as $o)
                <button type="button" class="dk-a11y__opt" :class="{ 'is-on': st.{{ $o['id'] }} }" :aria-pressed="!!st.{{ $o['id'] }}"
                    @if ($o['id'] === 'suara') :disabled="!tts" @endif @click="toggle('{{ $o['id'] }}')">
                    <x-dk.icon :name="$o['icon']" :size="22" />
                    <span class="dk-a11y__optl">{{ $o['label'] }}</span>
                    <span class="dk-a11y__state" x-text="{{ $o['id'] === 'suara' ? "!tts ? 'Tidak didukung' : " : '' }}(st.{{ $o['id'] }} ? 'Aktif' : 'Mati')">Mati</span>
                </button>
            @endforeach
        </div>
        <p class="dk-a11y__hint" x-show="st.suara" x-cloak><x-dk.icon name="info" :size="15" /> Klik judul, paragraf, atau tombol untuk dibacakan.</p>
        <div class="dk-a11y__foot">
            <x-dk.button variant="secondary" size="sm" icon="rotate-ccw" @click="reset()" x-bind:disabled="!aktif">Atur ulang</x-dk.button>
            <small>Tersimpan di perangkat ini</small>
        </div>
    </div>
    <button type="button" x-ref="fab" class="dk-a11y__fab" :class="{ 'is-open': open }" :aria-expanded="open" aria-controls="dk-a11y-panel"
        aria-label="Mode aksesibilitas" title="Mode aksesibilitas" @click="open ? hide() : show()">
        <span x-show="!open"><x-dk.icon name="accessibility" :size="28" :stroke="2.2" /></span>
        <span x-show="open" x-cloak><x-dk.icon name="x" :size="28" :stroke="2.2" /></span>
        <span class="dk-a11y__count" aria-hidden="true" x-show="aktif > 0 && !open" x-text="aktif" x-cloak></span>
    </button>
</div>
