# Website Dispusip Kota Kendari

Website Dinas Perpustakaan dan Kearsipan Kota Kendari: situs publik, buku tamu digital, dan panel admin.

**Stack:** Laravel 13 · Livewire 4 · Alpine.js · Tailwind CSS 4 · Vite 8 · MySQL 8.4 · Docker (PHP 8.5.12-FPM, nginx, Node 22)

## Fitur

**Situs publik:** beranda, profil, berita (kategori, pencarian, gambar bisa diperbesar), galeri foto dan video, statistik kunjungan, kontak, menu aksesibilitas, dan tombol survei kepuasan (tautan diisi di `survei_url`, `config/dispusip.php`).

**Buku tamu** (`/buku-tamu`): tamu mengisi dari ponsel, berfoto lewat kamera atau unggah, lalu memasukkan **kode kunjungan 4 angka** yang berganti setiap hari (WITA). Lima kali salah membuat tamu menunggu 5 menit. Tablet di meja layanan bisa dijadikan **kios** (`/kios`) yang tidak meminta kode.

**Panel admin** (`/admin`): dasbor, buku tamu (filter dan unduh CSV), kode kunjungan (acak ulang, layar meja layanan dengan QR, aktivasi kios), berita, kategori, galeri, slider, layanan, tanya jawab, kotak masuk pertanyaan, pengaturan situs, akun, dan **backup database** (unduh `.sql.gz`).

## Menjalankan lokal

```bash
cp .env.example .env
docker compose up -d --build
```

Saat pertama jalan, container `app` memasang dependensi, membuat `APP_KEY`, migrasi, mengisi data contoh (sekali, ditandai `storage/app/.seeded`), dan `storage:link`. Container `node` membangun aset ke `public/build`.

| Alamat | Isi |
| --- | --- |
| http://localhost:8080 | Situs publik |
| http://localhost:8080/admin | Admin: `admin@dispusip.test` / `password` (dari `ADMIN_*` di `.env`) |
| localhost:3307 | MySQL: `dispusip` / `dispusip-secret` |

```bash
docker compose exec app php artisan test     # tes (SQLite di memori, MySQL tidak tersentuh)
docker compose exec app vendor/bin/pint      # format kode
docker compose run --rm node npm run build   # build ulang aset
docker compose --profile dev up -d vite      # Vite hot reload di :5173 (hapus public/hot setelah selesai)
docker compose exec app php artisan migrate:fresh --seed   # reset ke data contoh, MENGHAPUS SEMUA DATA
```

## Produksi

- Jalankan dengan `COMPOSE_FILE=compose.prod.yml docker compose up -d --build`. Situs terbuka di `127.0.0.1:8010` dan butuh reverse proxy **HTTPS** di depannya (kamera buku tamu hanya jalan di HTTPS).
- Push ke `main` otomatis men-deploy ke VPS lewat GitHub Actions (`.github/workflows/deploy.yml`).
- Isi `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` HTTPS, serta sandi database dan admin yang kuat.
- Semua isi bawaan adalah **data contoh**. Untuk mulai dari kosong, set `SEED_DEMO=false` sebelum container pertama kali jalan, lalu lengkapi isi lewat admin.

## Struktur

```
app/Services/                   statistik, pencatat pengunjung, buku tamu, backup database
app/Support/                    pengaturan situs, jam layanan, format, ikon
resources/views/pages/          halaman Livewire (publik dan admin/)
resources/views/components/dk/  komponen UI design system
resources/css/dispusip/         tokens, komponen, site.css, admin.css
resources/js/dispusip/          komponen Alpine
docker/                         Dockerfile, entrypoint, nginx
```
