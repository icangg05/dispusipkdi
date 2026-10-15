@php
    $blocks = [
        [14, 14, 62, 50], [96, 14, 80, 30], [96, 52, 38, 26], [142, 52, 34, 26], [206, 10, 84, 56], [320, 14, 70, 44], [420, 10, 86, 34], [530, 16, 60, 52],
        [14, 104, 62, 56], [96, 100, 80, 64], [206, 96, 40, 66], [254, 96, 36, 30], [254, 132, 36, 30], [420, 98, 52, 62], [480, 98, 30, 62], [530, 104, 60, 50],
        [14, 194, 62, 40], [96, 192, 80, 46], [206, 190, 84, 50], [320, 192, 70, 40], [420, 190, 86, 46], [530, 188, 60, 48],
        [14, 268, 62, 50], [96, 270, 80, 44], [206, 266, 84, 40], [320, 262, 70, 30], [420, 262, 86, 26],
    ];
@endphp
<svg class="dk-map__svg" viewBox="0 0 600 520" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
    <rect width="600" height="520" class="m-land" />
    @foreach ($blocks as [$x, $y, $w, $h])
        <rect class="m-block" x="{{ $x }}" y="{{ $y }}" width="{{ $w }}" height="{{ $h }}" rx="3" />
    @endforeach
    <rect class="m-park" x="320" y="96" width="70" height="66" rx="5" />
    <circle class="m-park2" cx="355" cy="129" r="16" />
    @foreach ([0, 86, 186, 300, 410, 520] as $x)
        <path class="m-road" d="M{{ $x }},-10 V420" />
    @endforeach
    @foreach ([86, 180, 252] as $y)
        <path class="m-road" d="M-10,{{ $y }} H610" />
    @endforeach
    <path class="m-road m-road--main" d="M-10,170 C120,176 220,150 300,178 C380,206 460,170 610,180" />
    <path class="m-sea" d="M0,350 C70,322 140,372 230,346 C320,320 350,262 446,276 C520,286 556,316 600,306 L600,520 L0,520 Z" />
    <path class="m-coast" d="M0,350 C70,322 140,372 230,346 C320,320 350,262 446,276 C520,286 556,316 600,306" />
    <path class="m-bridge-deck" d="M186,352 C260,420 360,450 470,470" />
    <path class="m-bridge" d="M186,352 C260,420 360,450 470,470" />
    <text class="m-label" x="318" y="404">Teluk Kendari</text>
</svg>
