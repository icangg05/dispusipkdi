@php
    $user = auth()->user();
    $pertanyaanBaru = \App\Models\Question::query()->where('status', \App\Enums\QuestionStatus::Baru)->count();
    $menu = [
        'Utama' => [
            ['label' => 'Dasbor', 'icon' => 'layout-dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
            ['label' => 'Buku tamu', 'icon' => 'notebook-pen', 'route' => 'admin.buku-tamu', 'active' => 'admin.buku-tamu*'],
            ['label' => 'Kode kunjungan', 'icon' => 'key-round', 'route' => 'admin.kode', 'active' => 'admin.kode'],
            ['label' => 'Pertanyaan', 'icon' => 'inbox', 'route' => 'admin.pertanyaan', 'active' => 'admin.pertanyaan', 'count' => $pertanyaanBaru],
        ],
        'Konten' => [
            ['label' => 'Berita', 'icon' => 'newspaper', 'route' => 'admin.berita.index', 'active' => 'admin.berita.*'],
            ['label' => 'Kategori berita', 'icon' => 'tag', 'route' => 'admin.kategori', 'active' => 'admin.kategori'],
            ['label' => 'Galeri', 'icon' => 'images', 'route' => 'admin.galeri', 'active' => 'admin.galeri'],
            ['label' => 'Slider beranda', 'icon' => 'clapperboard', 'route' => 'admin.slider', 'active' => 'admin.slider'],
            ['label' => 'Layanan', 'icon' => 'layers', 'route' => 'admin.layanan', 'active' => 'admin.layanan'],
            ['label' => 'Tanya jawab', 'icon' => 'message-circle', 'route' => 'admin.faq', 'active' => 'admin.faq'],
        ],
        'Situs' => [
            ['label' => 'Pengaturan', 'icon' => 'settings', 'route' => 'admin.pengaturan', 'active' => 'admin.pengaturan'],
            ['label' => 'Akun saya', 'icon' => 'user-cog', 'route' => 'admin.akun', 'active' => 'admin.akun'],
        ],
    ];
    $initials = collect(preg_split('/\s+/', trim($user->name)))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => ($title ?? 'Admin').' | Admin'])
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
    <a class="dk-skip" href="#konten">Langsung ke konten</a>
    <div class="ad-shell" x-data="{ side: false }" @keydown.escape.window="side = false">
        <aside class="ad-side" :class="{ 'is-open': side }" aria-label="Menu admin">
            <x-dk.brand dark />
            <nav class="ad-nav" aria-label="Menu admin">
                @foreach ($menu as $group => $items)
                    <span class="ad-nav__label">{{ $group }}</span>
                    @foreach ($items as $item)
                        @php($active = request()->routeIs($item['active']))
                        <a href="{{ route($item['route']) }}" @class(['ad-nav__link', 'is-active' => $active]) @if ($active) aria-current="page" @endif wire:navigate>
                            <x-dk.icon :name="$item['icon']" :size="18" />
                            <span>{{ $item['label'] }}</span>
                            @if (! empty($item['count']))
                                <span class="ad-nav__count" aria-label="{{ $item['count'] }} baru">{{ $item['count'] }}</span>
                            @endif
                        </a>
                    @endforeach
                @endforeach
            </nav>
            <div class="ad-side__foot">
                <div class="ad-user">
                    <span class="ad-user__avatar" aria-hidden="true">{{ $initials }}</span>
                    <span class="ad-user__text"><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></span>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="ad-nav__link w-full cursor-pointer border-0 bg-transparent text-left">
                        <x-dk.icon name="log-out" :size="18" /> <span>Keluar</span>
                    </button>
                </form>
            </div>
        </aside>
        <div class="ad-backdrop" :class="{ 'is-open': side }" @click="side = false"></div>

        <div class="ad-main">
            <header class="ad-top">
                <button type="button" class="dk-iconbtn ad-burger" @click="side = true" aria-label="Buka menu admin"><x-dk.icon name="menu" /></button>
                <div class="ad-top__crumb">
                    <span>Admin</span>
                    <x-dk.icon name="chevron-right" :size="14" />
                    <strong>{{ $title ?? 'Dasbor' }}</strong>
                </div>
                <div class="ad-top__end">
                    <x-dk.button variant="ghost" size="sm" icon="external-link" :href="route('beranda')" target="_blank" rel="noopener"><span class="dk-hide-sm">Lihat situs</span></x-dk.button>
                </div>
            </header>
            <main class="ad-content" id="konten">
                {{ $slot }}
            </main>
        </div>
    </div>

    @if (session('status'))
        <div class="ad-toast" role="status" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)">
            <x-dk.icon name="circle-check" :size="18" /> {{ session('status') }}
        </div>
    @endif
    <div x-data="{ show: false, text: '' }" x-on:toast.window="text = $event.detail.message ?? $event.detail[0]?.message ?? ''; show = true; clearTimeout(window.__toast); window.__toast = setTimeout(() => show = false, 4000)">
        <div class="ad-toast" role="status" x-show="show" x-cloak>
            <x-dk.icon name="circle-check" :size="18" /> <span x-text="text"></span>
        </div>
    </div>
    @livewireScriptConfig
</body>
</html>
