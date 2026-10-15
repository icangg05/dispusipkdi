<?php

namespace Database\Seeders;

use App\Enums\MediaType;
use App\Enums\QuestionStatus;
use App\Enums\Tone;
use App\Models\Category;
use App\Models\Faq;
use App\Models\MediaItem;
use App\Models\Post;
use App\Models\Question;
use App\Models\Service;
use App\Models\Slide;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Sample content from the design system (slides, services, news, gallery, FAQ).
 * Every name, number and address here is placeholder copy — replace it with official data.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->slides();
        $this->services();
        $categories = $this->categories();
        $this->posts($categories);
        $this->gallery();
        $this->faqs();
        $this->questions();
    }

    protected function slides(): void
    {
        $slides = [
            ['image' => '/images/contoh/ruang-baca.jpg', 'caption' => 'Ruang Baca Umum', 'kicker' => 'Selamat datang di Dispusip Kota Kendari', 'title' => 'Ruang baca untuk', 'accent' => 'seluruh warga Kendari', 'text' => 'Pinjam buku, baca di tempat, dan telusuri arsip daerah. Gratis, nyaman, dan dekat dengan Anda.'],
            ['image' => '/images/contoh/rak.jpg', 'caption' => 'Koleksi Umum', 'kicker' => 'Koleksi perpustakaan', 'title' => 'Ribuan judul siap', 'accent' => 'dibaca', 'text' => 'Fiksi, pengetahuan umum, buku anak, hingga koleksi lokal Sulawesi Tenggara bisa Anda pinjam.'],
            ['image' => '/images/contoh/teluk.jpg', 'caption' => 'Teluk Kendari', 'kicker' => 'Kearsipan daerah', 'title' => 'Menjaga ingatan', 'accent' => 'Kota Kendari', 'text' => 'Arsip dan dokumen daerah dirawat agar sejarah kota tetap bisa dibaca oleh generasi berikutnya.'],
            ['image' => '/images/contoh/pusling.jpg', 'caption' => 'Perpustakaan Keliling', 'kicker' => 'Layanan jemput bola', 'title' => 'Perpustakaan datang', 'accent' => 'ke kelurahan Anda', 'text' => 'Mobil pustaka menyambangi sekolah, taman, dan kelurahan setiap pekan sesuai jadwal.'],
        ];

        foreach ($slides as $i => $slide) {
            Slide::query()->create($slide + ['sort' => $i + 1, 'is_active' => true]);
        }
    }

    protected function services(): void
    {
        $services = [
            ['icon' => 'notebook-pen', 'title' => 'Buku Tamu Digital', 'text' => 'Catat kunjungan Anda dalam 30 detik lewat ponsel atau tablet di meja layanan.', 'link_label' => 'Isi buku tamu', 'href' => '/buku-tamu', 'is_featured' => true],
            ['icon' => 'armchair', 'title' => 'Baca di Tempat', 'text' => 'Ruang baca sejuk, Wi‑Fi gratis, dan meja belajar terbuka untuk umum.', 'link_label' => 'Lihat fasilitas', 'href' => '/galeri'],
            ['icon' => 'book-copy', 'title' => 'Peminjaman Buku', 'text' => 'Pinjam hingga 3 buku selama 14 hari dengan kartu anggota.', 'link_label' => 'Syarat pinjam', 'href' => '/kontak#tanya'],
            ['icon' => 'bus', 'title' => 'Perpustakaan Keliling', 'text' => 'Mobil pustaka menyambangi sekolah dan kelurahan sesuai jadwal pekanan.', 'link_label' => 'Lihat jadwal', 'href' => '/berita?kategori=kegiatan'],
            ['icon' => 'archive', 'title' => 'Layanan Kearsipan', 'text' => 'Penelusuran dan peminjaman arsip statis untuk riset dan keperluan dinas.', 'link_label' => 'Ajukan penelusuran', 'href' => '/kontak'],
            ['icon' => 'baby', 'title' => 'Pojok Baca Anak', 'text' => 'Buku bergambar dan sesi mendongeng setiap Sabtu pagi untuk pembaca cilik.', 'link_label' => 'Jadwal dongeng', 'href' => '/berita?kategori=literasi'],
            ['icon' => 'id-card', 'title' => 'Kartu Anggota', 'text' => 'Daftar cukup dengan KTP atau kartu pelajar; kartu jadi di hari yang sama.', 'link_label' => 'Cara daftar', 'href' => '/kontak#tanya'],
            ['icon' => 'users', 'title' => 'Kunjungan Rombongan', 'text' => 'Sekolah dan komunitas dapat menjadwalkan tur perpustakaan dan kelas literasi.', 'link_label' => 'Jadwalkan', 'href' => '/kontak'],
        ];

        foreach ($services as $i => $service) {
            Service::query()->create($service + ['sort' => $i + 1, 'is_active' => true]);
        }
    }

    /**
     * @return array<string, Category>
     */
    protected function categories(): array
    {
        $rows = [
            ['name' => 'Pengumuman', 'slug' => 'pengumuman', 'tone' => Tone::Lentera, 'icon' => 'megaphone'],
            ['name' => 'Kegiatan', 'slug' => 'kegiatan', 'tone' => Tone::Laut, 'icon' => 'calendar-days'],
            ['name' => 'Literasi', 'slug' => 'literasi', 'tone' => Tone::Daun, 'icon' => 'book-open'],
            ['name' => 'Kearsipan', 'slug' => 'kearsipan', 'tone' => Tone::Terakota, 'icon' => 'archive'],
        ];

        $categories = [];

        foreach ($rows as $i => $row) {
            $categories[$row['slug']] = Category::query()->create($row + ['sort' => $i + 1]);
        }

        return $categories;
    }

    /**
     * @param  array<string, Category>  $categories
     */
    protected function posts(array $categories): void
    {
        $admin = User::query()->first();
        $today = Carbon::today();

        $posts = [
            ['pengumuman', 'tumpukan-buku', 3, 'Jadwal Layanan Perpustakaan Selama Libur Akhir Tahun', 'Ruang baca tetap buka pada 22 sampai 24 Desember dengan jam layanan khusus pukul 08.00 sampai 12.00 WITA. Pengembalian buku dapat dilakukan lewat kotak pengembalian di lobi.', 'Admin Dispusip', 1284],
            ['kegiatan', 'pusling', 6, 'Perpustakaan Keliling Sambangi 12 Kelurahan Bulan Ini', 'Mobil pustaka hadir di sekolah dasar dan taman kelurahan dengan lebih dari 600 judul buku anak dan remaja.', 'Bidang Layanan', 864],
            ['literasi', 'pojok-anak', 10, 'Gerakan 15 Menit Membaca Diikuti 40 Sekolah se-Kota Kendari', 'Program pembiasaan membaca sebelum pelajaran dimulai kini menjangkau 40 sekolah dasar dan menengah.', 'Bidang Pembinaan', 732],
            ['kearsipan', 'arsip', 13, 'Sosialisasi Pengelolaan Arsip Dinamis bagi Perangkat Daerah', 'Petugas arsip dari 28 perangkat daerah mengikuti pelatihan penataan dan pemberkasan arsip aktif.', 'Bidang Kearsipan', 418],
            ['pengumuman', 'buku-terbuka', 17, 'Pendaftaran Kartu Anggota Kini Bisa Dimulai dari Rumah', 'Isi formulir daring, lalu ambil kartu anggota di meja layanan dengan menunjukkan KTP atau kartu pelajar.', 'Admin Dispusip', 2051],
            ['kegiatan', 'teluk', 21, 'Bedah Buku: Kisah Pesisir Teluk Kendari', 'Diskusi bersama penulis lokal tentang kehidupan nelayan dan kampung pesisir di sekitar Teluk Kendari.', 'Bidang Layanan', 596],
            ['literasi', 'rak', 28, 'Koleksi Baru: 350 Judul Buku Lokal Sulawesi Tenggara', 'Rak koleksi lokal kini memuat sejarah, budaya, dan cerita rakyat dari berbagai kabupaten di Sulawesi Tenggara.', 'Bidang Layanan', 389],
            ['kearsipan', 'arsip', 35, 'Depo Arsip Statis Terima Hibah Dokumen Foto Lama Kota Kendari', 'Ratusan foto kota dari era 1970-an diserahkan keluarga pensiunan ASN untuk dirawat dan didigitalkan.', 'Bidang Kearsipan', 271],
            ['pengumuman', 'ruang-baca', 42, 'Ruang Baca Lantai 1 Kini Buka Hingga Pukul 15.30 WITA', 'Penyesuaian jam layanan berlaku Senin sampai Kamis untuk memberi waktu lebih bagi pelajar sepulang sekolah.', 'Admin Dispusip', 1167],
        ];

        foreach ($posts as [$cat, $image, $daysAgo, $title, $excerpt, $author, $views]) {
            Post::query()->create([
                'category_id' => $categories[$cat]->id,
                'user_id' => $admin?->id,
                'title' => $title,
                'slug' => Str::slug($title),
                'excerpt' => $excerpt,
                'body' => $this->body($cat),
                'image' => '/images/contoh/'.$image.'.jpg',
                'author' => $author,
                'views' => $views,
                'is_published' => true,
                'published_at' => $today->copy()->subDays($daysAgo)->setTime(9, 0),
            ]);
        }
    }

    /**
     * Article body in Markdown; the excerpt is shown above it as the lede.
     */
    protected function body(string $category): string
    {
        return match ($category) {
            'pengumuman' => "## Yang perlu Anda ketahui\n\n- Layanan ruang baca dan peminjaman berjalan seperti biasa pada hari kerja.\n- Bawa kartu anggota atau KTP saat meminjam buku.\n- Informasi perubahan jadwal juga kami umumkan lewat WhatsApp dan media sosial resmi.\n\nPertanyaan seputar pengumuman ini dapat disampaikan melalui halaman **Kontak** atau langsung di meja layanan.",
            'kegiatan' => "## Rangkaian kegiatan\n\nKegiatan ini terbuka untuk umum dan tidak dipungut biaya. Peserta diminta mengisi **buku tamu digital** saat tiba di lokasi.\n\n1. Registrasi peserta dan pengisian buku tamu.\n2. Sesi utama bersama narasumber.\n3. Diskusi dan tanya jawab.\n\n> Dokumentasi kegiatan dapat dilihat di halaman Galeri.",
            'literasi' => "## Mengapa membaca setiap hari?\n\nKebiasaan membaca yang dibangun sejak dini membantu anak memahami bacaan, menambah kosakata, dan menumbuhkan rasa ingin tahu.\n\nSekolah dan komunitas yang ingin bergabung dapat menghubungi Bidang Pembinaan melalui WhatsApp pada jam layanan.",
            default => "## Tentang layanan kearsipan\n\nArsip statis yang telah ditata dapat ditelusuri di ruang baca arsip dengan mengisi permohonan penelusuran. Beberapa arsip memiliki ketentuan akses khusus.\n\nPerangkat daerah yang membutuhkan pendampingan penataan arsip dapat mengajukan jadwal melalui surat resmi.",
        };
    }

    protected function gallery(): void
    {
        $now = Carbon::today();
        $items = [
            ['type' => MediaType::Foto, 'image' => '/images/contoh/ruang-baca.jpg', 'title' => 'Ruang Baca Umum lantai 1', 'taken_at' => $now->copy()->subDays(4)],
            ['type' => MediaType::Video, 'image' => '/images/contoh/rak.jpg', 'title' => 'Tur virtual koleksi perpustakaan', 'youtube_url' => null, 'duration' => '3:24', 'taken_at' => $now->copy()->subDays(9)],
            ['type' => MediaType::Foto, 'image' => null, 'title' => 'Poster Bedah Buku', 'poster_line1' => 'Bedah', 'poster_line2' => 'Buku', 'poster_sub' => 'Kisah Pesisir Teluk Kendari', 'poster_tone' => 'lentera', 'taken_at' => $now->copy()->subDays(21)],
            ['type' => MediaType::Foto, 'image' => '/images/contoh/pojok-anak.jpg', 'title' => 'Pojok Baca Anak setiap Sabtu', 'taken_at' => $now->copy()->subDays(12)],
            ['type' => MediaType::Video, 'image' => '/images/contoh/pusling.jpg', 'title' => 'Perpustakaan Keliling edisi bulan ini', 'youtube_url' => null, 'duration' => '2:10', 'taken_at' => $now->copy()->subDays(6)],
            ['type' => MediaType::Foto, 'image' => '/images/contoh/arsip.jpg', 'title' => 'Depo arsip statis', 'taken_at' => $now->copy()->subDays(30)],
            ['type' => MediaType::Foto, 'image' => null, 'title' => 'Kampanye Jelajah Literasi', 'poster_line1' => 'Jelajah', 'poster_line2' => 'Literasi', 'poster_sub' => 'Gerakan 15 Menit Membaca', 'poster_tone' => 'laut', 'taken_at' => $now->copy()->subDays(33)],
            ['type' => MediaType::Video, 'image' => '/images/contoh/buku-terbuka.jpg', 'title' => 'Cara mengisi buku tamu digital', 'youtube_url' => null, 'duration' => '1:05', 'taken_at' => $now->copy()->subDays(50)],
            ['type' => MediaType::Foto, 'image' => '/images/contoh/teluk.jpg', 'title' => 'Bedah buku di tepi Teluk Kendari', 'taken_at' => $now->copy()->subDays(21)],
            ['type' => MediaType::Foto, 'image' => '/images/contoh/tumpukan-buku.jpg', 'title' => 'Koleksi baru siap dipinjam', 'taken_at' => $now->copy()->subDays(28)],
        ];

        foreach ($items as $i => $item) {
            MediaItem::query()->create($item + ['sort' => $i + 1, 'is_published' => true]);
        }
    }

    protected function faqs(): void
    {
        $faqs = [
            ['Apakah meminjam buku dikenakan biaya?', 'Tidak. Peminjaman gratis untuk anggota. Mendaftar anggota cukup membawa KTP atau kartu pelajar, dan kartu jadi di hari yang sama.'],
            ['Berapa lama masa pinjam dan berapa buku yang boleh dipinjam?', 'Paling banyak 3 buku selama 14 hari. Masa pinjam bisa diperpanjang satu kali lewat WhatsApp sebelum jatuh tempo.'],
            ['Di mana saya mendapat kode kunjungan untuk buku tamu?', 'Kode empat angka tertera di meja layanan dan berganti setiap hari. Tanyakan kepada petugas bila belum melihatnya.'],
            ['Bagaimana mengatur kunjungan rombongan sekolah?', 'Hubungi kami paling lambat 3 hari sebelum kunjungan dengan menyebut nama sekolah, jumlah peserta, dan tanggal yang diinginkan. Kami siapkan ruang dan pemandu.'],
            ['Apakah arsip daerah bisa dilihat oleh umum?', 'Arsip statis dapat ditelusuri di ruang baca arsip dengan mengisi permohonan penelusuran. Beberapa arsip memiliki ketentuan akses khusus.'],
            ['Apakah perpustakaan buka pada hari Sabtu?', 'Ya, pukul 09.00 sampai 14.00 WITA untuk ruang baca umum dan pojok baca anak. Hari Minggu dan hari libur nasional tutup.'],
        ];

        foreach ($faqs as $i => [$q, $a]) {
            Faq::query()->create(['question' => $q, 'answer' => $a, 'sort' => $i + 1, 'is_active' => true]);
        }
    }

    protected function questions(): void
    {
        Question::query()->create([
            'nama' => 'Rina Kartika',
            'kontak' => '0812-3456-7890',
            'topik' => 'Keanggotaan',
            'isi' => 'Apakah mahasiswa dari luar Kendari bisa mendaftar kartu anggota? Saya kuliah di sini tetapi KTP saya dari Kolaka.',
            'status' => QuestionStatus::Baru,
            'created_at' => now()->subHours(3),
        ]);

        Question::query()->create([
            'nama' => 'Guru SD Negeri Contoh',
            'kontak' => 'sdcontoh@contoh.sch.id',
            'topik' => 'Kunjungan rombongan',
            'isi' => 'Kami ingin membawa 30 siswa kelas 4 untuk kunjungan perpustakaan minggu depan. Hari apa yang paling tepat?',
            'status' => QuestionStatus::Dijawab,
            'catatan' => 'Dijadwalkan Kamis pukul 09.00, pemandu: Bidang Layanan.',
            'answered_at' => now()->subDay(),
            'created_at' => now()->subDays(2),
        ]);
    }
}
