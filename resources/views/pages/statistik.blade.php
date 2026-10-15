<?php

use App\Services\StatistikService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Statistik Pengunjung')] class extends Component
{
    public function with(): array
    {
        $stats = app(StatistikService::class)->all();
        $bulan = [];

        foreach ($stats['tamu']['bulanan'] as $i => $point) {
            $bulan[] = [
                'label' => $point['label'],
                'tamu' => $point['value'],
                'web' => $stats['web']['bulanan'][$i]['value'] ?? 0,
                'current' => $point['current'] ?? false,
            ];
        }

        return [
            'stats' => $stats,
            'bulan' => array_reverse($bulan),
            'keperluan' => $stats['keperluanTamu'],
        ];
    }
};
?>

@php
    $totalKep = array_sum(array_column($keperluan, 'value'));
    $maxKep = max([1, ...array_column($keperluan, 'value')]);
@endphp
<div>
    <x-dk.page-top :crumbs="[['label' => 'Beranda', 'href' => route('beranda')], ['label' => 'Statistik']]" title="Statistik Pengunjung"
        text="Kunjungan perpustakaan dari buku tamu digital dan pengunjung website, per hari, per bulan, dan sepanjang waktu." />

    <x-dk.visitor-stats :stats="$stats" id="statistik-utama" :show-report-link="false" />

    <x-dk.section>
        <x-dk.section-heading eyebrow="Rincian" eyebrow-icon="list-checks" title="Rincian" accent="kunjungan"
            description="Angka bulan berjalan dihitung sampai hari ini. Tamu dihitung per lembar buku tamu; pengunjung website dihitung sekali per perangkat per hari." />
        <div class="dk-statgrid">
            <div class="dk-panel">
                <h3 class="dk-panel__title">Keperluan kunjungan bulan ini</h3>
                <p class="dk-panel__sub">{{ \App\Support\Format::number($totalKep) }} tamu dari buku tamu</p>
                <ul class="dk-hbars dk-hbars--light" role="list">
                    @foreach ($keperluan as $k)
                        <li>
                            <span class="dk-hbars__row"><span>{{ $k['label'] }}</span><strong>{{ \App\Support\Format::number($k['value']) }} <small>({{ $totalKep ? round($k['value'] / $totalKep * 100) : 0 }}%)</small></strong></span>
                            <span class="dk-hbars__track"><span style="width: {{ round($k['value'] / $maxKep * 100, 1) }}%"></span></span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="dk-panel">
                <h3 class="dk-panel__title">12 bulan terakhir</h3>
                <p class="dk-panel__sub">Tamu perpustakaan dan pengunjung website per bulan</p>
                <div class="dk-tablewrap">
                    <table class="dk-ledger dk-ledger--stat">
                        <thead><tr><th scope="col">Bulan</th><th scope="col" class="num">Tamu</th><th scope="col" class="num">Pengunjung web</th></tr></thead>
                        <tbody>
                            @foreach ($bulan as $row)
                                <tr @class(['is-current' => $row['current']])>
                                    <td>{{ $row['label'] }}@if ($row['current']) <span class="dk-today">Berjalan</span>@endif</td>
                                    <td class="num">{{ \App\Support\Format::number($row['tamu']) }}</td>
                                    <td class="num">{{ \App\Support\Format::number($row['web']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </x-dk.section>
</div>
