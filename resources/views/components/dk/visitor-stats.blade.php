{{-- The Statistik band (laut-800): headline tiles, a chart switchable by source and period, and guest categories. --}}
@props(['stats', 'id' => 'statistik', 'showReportLink' => true])
@use('App\Services\StatistikService')
@use('App\Support\Format')
@php
    $tamu = $stats['tamu'];
    $web = $stats['web'];
    $kategori = $stats['kategoriTamu'];
    $totalKat = array_sum(array_column($kategori, 'value'));
    $maxKat = max([1, ...array_column($kategori, 'value')]);
@endphp
<section {{ $attributes->class('dk-statsec') }} id="{{ $id }}" aria-labelledby="{{ $id }}-title" x-data="visitorStats(@js(['tamu' => $tamu, 'web' => $web]))">
    <div class="dk-container">
        <x-dk.section-heading eyebrow="Statistik" eyebrow-icon="chart-column" title="Statistik" accent="Pengunjung" align="center" dark :id="$id.'-title'"
            description="Data kunjungan dari buku tamu digital dan pengunjung website, diperbarui otomatis setiap hari." />

        <div class="dk-statsec__tiles" data-aos="fade-up">
            <x-dk.stat-tile dark highlight icon="user-check" label="Tamu hari ini" :value="$tamu['hariIni']" :delta="StatistikService::pct($tamu['hariIni'], $tamu['kemarinSamaJam'])" delta-label="vs jam yang sama kemarin" />
            <x-dk.stat-tile dark icon="users" label="Tamu bulan ini" :value="$tamu['bulanIni']" :delta="StatistikService::pct($tamu['bulanIni'], $tamu['bulanLaluSamaPeriode'])" delta-label="vs periode sama bulan lalu" />
            <x-dk.stat-tile dark icon="globe" label="Pengunjung web hari ini" :value="$web['hariIni']" :delta="StatistikService::pct($web['hariIni'], $web['kemarinSamaJam'])" delta-label="vs jam yang sama kemarin" />
            <x-dk.stat-tile dark icon="monitor-smartphone" label="Total pengunjung web" :value="$web['total']" :caption="'sejak '.$web['sejak']" />
        </div>

        <div class="dk-statsec__panel" data-aos="fade-up" data-aos-delay="100">
            <div class="dk-statsec__chart">
                <div class="dk-statsec__filters">
                    <div class="dk-seg" role="tablist" aria-label="Sumber data">
                        @foreach (['tamu' => ['Buku tamu', 'notebook-pen'], 'web' => ['Website', 'globe']] as $key => [$label, $icon])
                            <button type="button" role="tab" class="dk-seg__btn {{ $loop->first ? 'is-active' : '' }}" :class="{ 'is-active': sumber === '{{ $key }}' }"
                                :aria-selected="sumber === '{{ $key }}'" @click="sumber = '{{ $key }}'"><x-dk.icon :name="$icon" :size="15" /> {{ $label }}</button>
                        @endforeach
                    </div>
                    <div class="dk-seg" role="tablist" aria-label="Periode">
                        @foreach (['harian' => 'Harian', 'bulanan' => 'Bulanan', 'semua' => 'Semua'] as $key => $label)
                            <button type="button" role="tab" class="dk-seg__btn {{ $loop->first ? 'is-active' : '' }}" :class="{ 'is-active': periode === '{{ $key }}' }"
                                :aria-selected="periode === '{{ $key }}'" @click="periode = '{{ $key }}'">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="dk-statsec__headline" aria-live="polite">
                    <span class="dk-statsec__k" x-text="S.label + ' · ' + ringkas.judul">{{ $tamu['label'] }} · Hari ini</span>
                    <span class="dk-statsec__big" x-ref="big" x-text="fmt(ringkas.nilai)">{{ Format::number($tamu['hariIni']) }}</span>
                    <span class="dk-statsec__cmp">
                        <template x-if="ringkas.banding !== null">
                            <span class="dk-delta" :class="ringkas.banding >= 0 ? 'dk-delta--up' : 'dk-delta--down'">
                                <span x-show="ringkas.banding >= 0"><x-dk.icon name="trending-up" :size="14" /></span>
                                <span x-show="ringkas.banding < 0"><x-dk.icon name="arrow-down-right" :size="14" /></span>
                                <span x-text="delta(ringkas.banding)"></span>
                            </span>
                        </template>
                        <span x-text="ringkas.bandingLbl + ' · sumber: ' + S.sumber"></span>
                    </span>
                </div>
                <x-dk.chart source="ringkas.seri" title-expr="chartTitle" unit-expr="unit" />
            </div>

            <div class="dk-statsec__kat">
                <h3 class="dk-statsec__kh">Kategori tamu bulan ini</h3>
                <p class="dk-statsec__ks">{{ Format::number($totalKat) }} tamu dari buku tamu</p>
                <ul class="dk-hbars" role="list">
                    @foreach ($kategori as $k)
                        <li>
                            <span class="dk-hbars__row"><span>{{ $k['label'] }}</span><strong>{{ Format::number($k['value']) }} <small>({{ $totalKat ? round($k['value'] / $totalKat * 100) : 0 }}%)</small></strong></span>
                            <span class="dk-hbars__track"><span style="width: {{ round($k['value'] / $maxKat * 100, 1) }}%"></span></span>
                        </li>
                    @endforeach
                </ul>
                @if ($showReportLink)
                    <x-dk.button variant="on-dark" size="sm" icon-right="arrow-right" :href="route('statistik')" block wire:navigate>Laporan statistik lengkap</x-dk.button>
                @endif
            </div>
        </div>
    </div>
</section>
