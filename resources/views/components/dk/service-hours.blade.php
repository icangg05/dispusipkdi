@props(['jadwal' => null, 'dark' => false])
@php
    $jadwal ??= site()->jadwal();
    $status = \App\Support\Jadwal::status($jadwal);
@endphp
<div {{ $attributes->class(['dk-hours', 'dk-hours--dark' => $dark]) }}>
    <div class="dk-hours__head">
        <span class="dk-hours__t"><x-dk.icon name="clock" :size="18" /> Jam layanan</span>
        <x-dk.status :status="$status" variant="sm" />
    </div>
    <table class="dk-hours__table">
        <tbody>
            @foreach (\App\Support\Jadwal::URUTAN as $d)
                <tr @class(['is-today' => $d === $status['day']])>
                    <th scope="row">{{ $jadwal[$d]['hari'] }}@if ($d === $status['day'])<span class="dk-today">Hari ini</span>@endif</th>
                    <td>{{ \App\Support\Jadwal::sesiText($jadwal[$d]['sesi']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="dk-hours__note">Waktu Indonesia Tengah (WITA). Tutup pada hari libur nasional.</p>
</div>
