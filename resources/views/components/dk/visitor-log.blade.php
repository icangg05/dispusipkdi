{{-- Right-hand page of the register: today's guests with masked names, blank ruled rows below. --}}
@props(['entries', 'total', 'max' => 7, 'highlight' => null])
@php($rows = $entries->take($max))
<div {{ $attributes->class('dk-log') }}>
    <div class="dk-log__head">
        <h2 class="dk-log__title">Tercatat hari ini</h2>
        <span class="dk-log__n">{{ \App\Support\Format::number($total) }} tamu</span>
    </div>
    <table class="dk-ledger">
        <thead>
            <tr><th scope="col">No.</th><th scope="col">Jam</th><th scope="col">Nama</th><th scope="col" class="dk-ledger__kep">Keperluan</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $guest)
                <tr wire:key="tamu-{{ $guest->id }}" @class(['is-new' => $highlight === $guest->id])>
                    <td class="dk-ledger__no">{{ $guest->nomorLabel() }}</td>
                    <td class="dk-ledger__jam">{{ $guest->created_at?->format('H.i') }}</td>
                    <td>
                        <strong>{{ $guest->maskedName() }}</strong>
                        <small>{{ $guest->kategori }}</small>
                        <small class="dk-ledger__kep-sm">{{ $guest->keperluanLabel() }}</small>
                    </td>
                    <td class="dk-ledger__kep">{{ $guest->keperluanLabel() }}</td>
                </tr>
            @endforeach
            @for ($k = 0; $k < max(0, $max - $rows->count()); $k++)
                <tr class="dk-ledger__blank" aria-hidden="true"><td>{{ str_pad((string) ($total + $k + 1), 3, '0', STR_PAD_LEFT) }}</td><td></td><td></td><td class="dk-ledger__kep"></td></tr>
            @endfor
        </tbody>
    </table>
    @if ($rows->isEmpty())
        <p class="dk-log__note">Belum ada tamu hari ini. Jadilah yang pertama mengisi.</p>
    @else
        <p class="dk-log__note">Nama ditampilkan sebagian untuk menjaga privasi tamu.</p>
    @endif
</div>
