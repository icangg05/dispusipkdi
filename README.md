# Website Dispusip Kota Kendari

Website Dinas Perpustakaan dan Kearsipan Kota Kendari, dibangun mengikuti design system **Dispusip Kota Kendari** (palet laut + lentera, Figtree + Fraunces miring, komponen `dk-*`).

**Stack:** Laravel 13 · Livewire 4 (komponen satu berkas) · Alpine.js (bawaan Livewire) · Tailwind CSS 4 · Vite 8 · MySQL 8.4 · Docker Compose (PHP 8.5-FPM, nginx, Node 22) · ikon Lucide + Simple Icons.

## Fitur

**Situs publik**
- **Beranda:** hero dengan foto latar yang berganti tiap 6 detik (bisa dijeda), angka utama, profil, layanan, berita dengan filter kategori, galeri foto & video, statistik, tanya jawab + formulir pertanyaan, kontak, dan footer.
- **Buku tamu** (`/buku-tamu`) berbentuk lembar register:
  - tamu memotret diri dengan kamera depan, atau mengunggah foto bila kamera tidak tersedia;
  - sebelum dikirim, tamu wajib memasukkan **kode kunjungan 4 angka** yang berganti setiap hari;
  - setelah tercatat muncul cap *TERCATAT*, dan daftar tamu hari ini menampilkan nama yang disamarkan.
- **Halaman lain:**
  - **Profil:** visi & misi, tugas & fungsi, sejarah, struktur organisasi.
  - **Berita:** kategori, pencarian, paginasi, halaman detail, tombol bagikan.
  - **Galeri:** foto, video YouTube, dan poster tipografis.
  - **Statistik:** tamu dan pengunjung website, harian/bulanan/semua.
  - **Kontak:** WhatsApp, email, alamat, jam layanan, dan peta.
- **Mode kios** (`/kios`): buku tamu layar penuh untuk tablet di meja layanan. Tablet yang sudah diaktifkan petugas tidak perlu memasukkan kode.
- **Menu aksesibilitas** di setiap halaman: ukuran teks, kontras tinggi, huruf mudah dibaca, hentikan animasi, bacakan teks, dan lainnya.
- **Statistik pengunjung website** dihitung sekali per perangkat per hari, tanpa menyimpan IP mentah.

**Panel admin** (`/admin`)
- **Dasbor:** kode hari ini, ringkasan statistik, tamu terbaru, pertanyaan baru.
- **Kode kunjungan:** acak ulang atau tetapkan kode sendiri, riwayat 14 hari, dan *layar meja layanan* berisi kode besar + QR menuju halaman buku tamu. Dari halaman ini juga tablet diaktifkan sebagai kios.
- **Buku tamu:** filter tanggal/kategori, foto tamu (hanya untuk admin), unduh CSV.
- **Konten:** berita (Markdown, terjadwal/draf) dan kategorinya, galeri, slider beranda, layanan, tanya jawab, kotak masuk pertanyaan.
- **Pengaturan:** identitas & logo, profil dinas, kontak & sematan peta, media sosial (Instagram, Facebook, TikTok, YouTube), jam layanan, angka utama, foto tamu wajib/opsional.

## Menjalankan dengan Docker

Prasyarat: Docker dan Docker Compose.

```bash
cp .env.example .env          # sekali saja; sesuaikan bila perlu
docker compose up -d --build
```

Saat container `app` pertama kali jalan, otomatis dilakukan: `composer install`, pembuatan `APP_KEY`, migrasi, seeding data contoh, dan `storage:link`. Seeding hanya sekali; penandanya berkas `storage/app/.seeded`. Container `node` membangun aset Tailwind/Vite ke `public/build`, lalu berhenti.

| Alamat | Isi |
| --- | --- |
| http://localhost:8080 | Situs publik |
| http://localhost:8080/admin | Panel admin — `admin@dispusip.test` / `password` |
| localhost:3307 | MySQL (user `dispusip`, sandi `dispusip-secret`) |

Akun admin pertama diambil dari `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` di `.env`. **Segera ganti kata sandinya** di menu *Akun saya*.

### Pengembangan

```bash
docker compose --profile dev up -d vite      # Vite dev server + hot reload (port 5173)
docker compose exec app php artisan test     # tes otomatis (SQLite di memori, database MySQL tidak tersentuh)
docker compose exec app vendor/bin/pint      # format kode
docker compose run --rm node npm run build   # build ulang aset produksi
docker compose exec app php artisan migrate:fresh --seed   # reset data contoh (menghapus semua data!)
```

Bila membuka `npm run dev`, hapus `public/hot` setelah selesai agar situs kembali memakai aset hasil build.

## Cara kerja kode kunjungan

1. Setiap hari (WITA), sistem membuat kode 4 angka acak pada saat pertama kali dibutuhkan.
2. Petugas menampilkan kode di meja layanan, sebaiknya lewat **Admin › Kode kunjungan › Buka layar meja layanan** di monitor atau tablet. Layar itu juga menampilkan QR menuju halaman buku tamu.
3. Tamu mengisi buku tamu dari ponselnya dan memasukkan kode sebelum mengirim. Lima kali salah membuat tamu menunggu 5 menit; batas ini bisa diatur di `config/dispusip.php`.
4. Kode bisa diacak ulang atau ditetapkan manual kapan saja; kode lama langsung tidak berlaku.

**Tablet kios:** dari tablet meja layanan, masuk admin › *Kode kunjungan* › **Jadikan perangkat ini kios**. Sesi admin di tablet itu otomatis berakhir, lalu tablet membuka `/kios` dan tidak meminta kode lagi.

## Kamera dan HTTPS

Peramban hanya mengizinkan kamera di halaman HTTPS, atau di `localhost`. Jadi di server produksi situs **harus memakai HTTPS**, misalnya lewat reverse proxy dengan sertifikat Let's Encrypt. Tanpa HTTPS, tombol *Unggah foto* tetap membuka kamera ponsel melalui pemilih berkas.

## Data contoh

Semua isi bawaan (alamat, nomor, akun media sosial, berita, statistik, foto ilustrasi di `public/images/contoh`) adalah **contoh** dari design system. Ganti dengan data resmi lewat panel admin sebelum diluncurkan.

Untuk server produksi yang dimulai dari data kosong:
1. Set `SEED_DEMO=false` sebelum container pertama kali dijalankan. Seeder hanya akan membuat akun admin dan pengaturan bawaan.
2. Lengkapi isi situs lewat panel admin.

## Sebelum produksi

- `.env`:
  - `APP_ENV=production` dan `APP_DEBUG=false`;
  - `APP_URL` dengan domain HTTPS;
  - ganti sandi database dan admin.
- `docker compose run --rm node npm run build`, lalu `docker compose exec app php artisan optimize`.
- Pasang HTTPS di depan container `web` (port 8080).
- Unggah logo resmi dan foto asli di admin › *Pengaturan* dan *Slider beranda*.

## Struktur singkat

```
app/Services/            StatistikService, VisitorTracker, GuestbookService
app/Support/             SiteSettings (pengaturan situs), Jadwal (jam layanan WITA), Format, Icons
resources/views/pages/   halaman Livewire (publik dan admin/)
resources/views/livewire/ komponen Livewire: guestbook, news-section, question-form
resources/views/components/dk/  komponen UI design system (navbar, hero-slider, visitor-stats, …)
resources/css/dispusip/  tokens.css (dari tokens.json), components.css (dari design system), site.css, admin.css
resources/js/dispusip/   Alpine: hero, camera, visit-code, chart, stats, gallery, a11y, qr
docker/                  Dockerfile PHP, entrypoint, konfigurasi nginx
```
