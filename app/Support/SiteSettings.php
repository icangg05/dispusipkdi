<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Site-wide content edited from the admin "Pengaturan" page: identity, profile, contact,
 * social media, service hours, headline numbers and guestbook options.
 *
 * The defaults are the design system's sample content — placeholders to be replaced with official data.
 */
class SiteSettings
{
    public const CACHE_KEY = 'site-settings';

    public const GROUPS = ['identitas', 'profil', 'kontak', 'sosmed', 'jadwal', 'angka', 'bukutamu'];

    public const PLATFORMS = [
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
    ];

    /** @var array<string, mixed>|null */
    protected ?array $values = null;

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'identitas' => [
                'nama' => 'Dinas Perpustakaan dan Kearsipan Kota Kendari',
                'singkatan' => 'DISPUSIP',
                'kota' => 'Kota Kendari',
                'deskripsi' => 'Dinas Perpustakaan dan Kearsipan Kota Kendari melayani kebutuhan baca, informasi, dan arsip warga Kota Kendari.',
                'logo' => null,
            ],
            'profil' => [
                'ringkas' => 'Dinas Perpustakaan dan Kearsipan (Dispusip) Kota Kendari menyelenggarakan layanan perpustakaan umum, pembinaan literasi, dan pengelolaan arsip daerah. Kami membuka ruang baca, layanan pinjam, perpustakaan keliling, dan penelusuran arsip bagi seluruh warga tanpa biaya.',
                'sejarah' => "Perpustakaan umum Kota Kendari tumbuh dari sebuah ruang baca kecil di kantor pemerintah kota menjadi dinas yang mengelola layanan perpustakaan dan kearsipan daerah.\n\nKini kami melayani warga lewat ruang baca umum, pojok baca anak, perpustakaan keliling yang menyambangi sekolah dan kelurahan, serta depo arsip statis yang menyimpan ingatan kota.",
                'visi' => 'Terwujudnya masyarakat Kota Kendari yang gemar membaca, berpengetahuan, dan tertib arsip.',
                'misi' => [
                    'Meningkatkan mutu dan jangkauan layanan perpustakaan hingga ke tingkat kelurahan.',
                    'Menumbuhkan budaya baca melalui gerakan literasi bersama sekolah dan komunitas.',
                    'Menyelamatkan dan melestarikan arsip sebagai memori kolektif daerah.',
                    'Membangun layanan berbasis digital yang mudah diakses warga.',
                ],
                'tugas' => [
                    ['icon' => 'library', 'title' => 'Perpustakaan Umum', 'text' => 'Pengembangan koleksi, layanan sirkulasi, dan ruang baca.'],
                    ['icon' => 'archive', 'title' => 'Kearsipan Daerah', 'text' => 'Pembinaan arsip perangkat daerah dan layanan arsip statis.'],
                    ['icon' => 'heart-handshake', 'title' => 'Pembinaan Literasi', 'text' => 'Gerakan membaca bersama sekolah, kelurahan, dan komunitas.'],
                ],
                'gambar_utama' => null,
                'gambar_kedua' => null,
                'struktur' => null,
            ],
            'kontak' => [
                'whatsapp' => '0812-0000-0000',
                'email' => 'info@dispusip.contoh.go.id',
                'telepon' => '(0401) 000-000',
                'alamat' => 'Jl. Contoh Raya No. 12, Kota Kendari, Sulawesi Tenggara 93111',
                'catatan_alamat' => 'Ruang baca di lantai 1',
                'maps_link' => 'https://maps.google.com/?q=Kota+Kendari',
                'maps_embed' => null,
            ],
            'sosmed' => [
                'instagram' => ['handle' => '@dispusip.kendari', 'url' => 'https://instagram.com/', 'followers' => '12,4 rb pengikut'],
                'facebook' => ['handle' => 'Dispusip Kota Kendari', 'url' => 'https://facebook.com/', 'followers' => '8,9 rb pengikut'],
                'tiktok' => ['handle' => '@dispusip.kendari', 'url' => 'https://tiktok.com/', 'followers' => '5,2 rb pengikut'],
                'youtube' => ['handle' => 'Dispusip Kendari', 'url' => 'https://youtube.com/', 'followers' => '1,7 rb pelanggan'],
            ],
            'jadwal' => [
                ['hari' => 'Minggu', 'sesi' => []],
                ['hari' => 'Senin', 'sesi' => [[480, 930]]],
                ['hari' => 'Selasa', 'sesi' => [[480, 930]]],
                ['hari' => 'Rabu', 'sesi' => [[480, 930]]],
                ['hari' => 'Kamis', 'sesi' => [[480, 930]]],
                ['hari' => 'Jumat', 'sesi' => [[480, 690], [810, 960]]],
                ['hari' => 'Sabtu', 'sesi' => [[540, 840]]],
            ],
            'angka' => [
                ['icon' => 'book-copy', 'value' => '32.500+', 'label' => 'Eksemplar koleksi'],
                ['icon' => 'archive', 'value' => '14.200', 'label' => 'Berkas arsip terdata'],
                ['icon' => 'id-card', 'value' => '6.850', 'label' => 'Anggota aktif'],
                ['icon' => 'bus', 'value' => '48', 'label' => 'Titik layanan keliling'],
            ],
            'bukutamu' => [
                'foto_wajib' => true,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        try {
            $stored = Cache::rememberForever(self::CACHE_KEY, fn (): array => Setting::query()->pluck('value', 'key')->all());
        } catch (Throwable) {
            $stored = [];
        }

        $values = static::defaults();

        foreach ($stored as $key => $value) {
            if (is_array($value)) {
                $values[$key] = $key === 'jadwal' ? $value : array_replace($values[$key] ?? [], $value);
            }
        }

        return $this->values = $values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->all(), $key, $default);
    }

    /**
     * Save one settings group and refresh the cache.
     *
     * @param  array<mixed>  $value
     */
    public function put(string $group, array $value): void
    {
        Setting::query()->updateOrCreate(['key' => $group], ['value' => $value]);
        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->values = null;
    }

    /**
     * @return array{nama: string, singkatan: string, kota: string, deskripsi: string, logo: ?string, logo_url: ?string}
     */
    public function identitas(): array
    {
        $identitas = $this->get('identitas');
        $identitas['logo_url'] = Media::url($identitas['logo'] ?? null);

        return $identitas;
    }

    /**
     * @return array<string, mixed>
     */
    public function profil(): array
    {
        return $this->get('profil');
    }

    /**
     * @return array{whatsapp: string, whatsappLink: ?string, email: string, telepon: ?string, alamat: string, catatan_alamat: ?string, maps_link: ?string, maps_embed: ?string}
     */
    public function kontak(): array
    {
        $kontak = $this->get('kontak');
        $kontak['whatsappLink'] = Format::whatsappLink($kontak['whatsapp'] ?? null);

        return $kontak;
    }

    /**
     * Social accounts that have a link, in the fixed platform order.
     *
     * @return list<array{platform: string, label: string, handle: string, href: string, followers: ?string}>
     */
    public function sosmed(): array
    {
        $items = [];

        foreach (self::PLATFORMS as $platform => $label) {
            $row = $this->get('sosmed.'.$platform, []);

            if (blank($row['url'] ?? null)) {
                continue;
            }

            $items[] = [
                'platform' => $platform,
                'label' => $label,
                'handle' => $row['handle'] ?? $label,
                'href' => $row['url'],
                'followers' => $row['followers'] ?? null,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array{hari: string, sesi: array<int, array{0: int, 1: int}>}>
     */
    public function jadwal(): array
    {
        $jadwal = $this->get('jadwal');

        foreach (Jadwal::HARI as $i => $hari) {
            $jadwal[$i] = [
                'hari' => $hari,
                'sesi' => array_values(array_map(fn (array $s): array => [(int) $s[0], (int) $s[1]], $jadwal[$i]['sesi'] ?? [])),
            ];
        }

        ksort($jadwal);

        return $jadwal;
    }

    /**
     * @return array{open: bool, label: string, detail: string, day: int}
     */
    public function status(): array
    {
        return Jadwal::status($this->jadwal());
    }

    /**
     * @return list<array{icon: string, value: string, label: string}>
     */
    public function angka(): array
    {
        return array_values(array_filter($this->get('angka', []), fn (array $a): bool => filled($a['value'] ?? null)));
    }

    public function fotoTamuWajib(): bool
    {
        return (bool) $this->get('bukutamu.foto_wajib', true);
    }

    /**
     * Main menu. `active` is the route pattern that highlights the item.
     *
     * @return list<array{label: string, href: string, icon: string, active: ?string}>
     */
    public function nav(): array
    {
        return [
            ['label' => 'Beranda', 'href' => route('beranda'), 'icon' => 'house', 'active' => 'beranda'],
            ['label' => 'Profil', 'href' => route('profil'), 'icon' => 'landmark', 'active' => 'profil'],
            ['label' => 'Layanan', 'href' => route('beranda').'#layanan', 'icon' => 'layers', 'active' => null],
            ['label' => 'Berita', 'href' => route('berita.index'), 'icon' => 'newspaper', 'active' => 'berita.*'],
            ['label' => 'Galeri', 'href' => route('galeri'), 'icon' => 'image', 'active' => 'galeri'],
            ['label' => 'Statistik', 'href' => route('statistik'), 'icon' => 'chart-column', 'active' => 'statistik'],
            ['label' => 'Kontak', 'href' => route('kontak'), 'icon' => 'phone', 'active' => 'kontak'],
        ];
    }
}
