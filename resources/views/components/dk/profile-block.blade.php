@props(['profil' => null, 'image' => null, 'image2' => null, 'headingId' => 'profil'])
@php
    $profil ??= site()->profil();
    $image ??= \App\Support\Media::url($profil['gambar_kedua'] ?? null) ?? asset('images/contoh/buku-terbuka.jpg');
    $image2 ??= \App\Support\Media::url($profil['gambar_utama'] ?? null) ?? asset('images/contoh/ruang-baca.jpg');
@endphp
<div class="dk-profile" x-data="{ tab: 'profil' }" data-aos="fade-up">
    <div class="dk-profile__media">
        <div class="dk-frame dk-frame--a"><img src="{{ $image2 }}" alt="Ruang baca umum Dispusip Kota Kendari" loading="lazy"></div>
        <div class="dk-frame dk-frame--b"><img src="{{ $image }}" alt="Buku terbuka di meja baca" loading="lazy"></div>
        <div class="dk-profile__badge">
            <span class="dk-tile dk-tile--lentera"><x-dk.icon name="heart-handshake" :size="22" /></span>
            <span><strong>Gratis</strong><small>untuk seluruh warga</small></span>
        </div>
    </div>
    <div class="dk-profile__body">
        <x-dk.section-heading eyebrow="Profil Dinas" eyebrow-icon="landmark" title="Tentang" accent="Dispusip" align="left" :id="$headingId" />
        <div class="dk-tabs" role="tablist" aria-label="Informasi dinas">
            @foreach (['profil' => 'Profil', 'visi' => 'Visi & Misi', 'tugas' => 'Tugas & Fungsi'] as $id => $label)
                <button type="button" role="tab" id="tab-{{ $id }}" aria-controls="panel-{{ $id }}" class="dk-tab {{ $loop->first ? 'is-active' : '' }}"
                    :class="{ 'is-active': tab === '{{ $id }}' }" :aria-selected="tab === '{{ $id }}'" @click="tab = '{{ $id }}'">{{ $label }}</button>
            @endforeach
        </div>

        <div class="dk-profile__panel" role="tabpanel" id="panel-profil" aria-labelledby="tab-profil" x-show="tab === 'profil'">
            <p class="dk-lede">{{ $profil['ringkas'] }}</p>
            <ul class="dk-points" role="list">
                @foreach ($profil['tugas'] as $tugas)
                    <li><span class="dk-tile dk-tile--sm"><x-dk.icon :name="$tugas['icon'] ?: 'book-open'" :size="18" /></span><span><strong>{{ $tugas['title'] }}</strong><small>{{ $tugas['text'] }}</small></span></li>
                @endforeach
            </ul>
        </div>

        <div class="dk-profile__panel" role="tabpanel" id="panel-visi" aria-labelledby="tab-visi" x-show="tab === 'visi'" style="display: none">
            <blockquote class="dk-visi"><x-dk.icon name="quote" :size="28" /><p>{{ $profil['visi'] }}</p></blockquote>
            <ol class="dk-misi">
                @foreach ($profil['misi'] as $misi)
                    <li><span>{{ $loop->iteration }}</span>{{ $misi }}</li>
                @endforeach
            </ol>
        </div>

        <div class="dk-profile__panel" role="tabpanel" id="panel-tugas" aria-labelledby="tab-tugas" x-show="tab === 'tugas'" style="display: none">
            <ul class="dk-points dk-points--wide" role="list">
                @foreach ($profil['tugas'] as $tugas)
                    <li><span class="dk-tile dk-tile--sm"><x-dk.icon :name="$tugas['icon'] ?: 'book-open'" :size="18" /></span><span><strong>{{ $tugas['title'] }}</strong><small>{{ $tugas['text'] }}</small></span></li>
                @endforeach
                <li><span class="dk-tile dk-tile--sm"><x-dk.icon name="monitor-smartphone" :size="18" /></span><span><strong>Layanan Digital</strong><small>Buku tamu digital, informasi layanan, dan statistik kunjungan melalui website.</small></span></li>
            </ul>
        </div>
    </div>
</div>
