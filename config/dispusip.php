<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Buku tamu
    |--------------------------------------------------------------------------
    |
    | Pilihan pada lembar buku tamu dan batas percobaan kode kunjungan harian.
    |
    */

    'kategori_tamu' => ['Pelajar', 'Mahasiswa', 'Umum', 'ASN / Instansi', 'Peneliti'],

    'keperluan' => [
        'Membaca di tempat',
        'Meminjam atau mengembalikan buku',
        'Penelusuran arsip',
        'Kunjungan rombongan',
        'Mendaftar anggota',
        'Lainnya',
    ],

    'jenis_kelamin' => ['Laki-laki', 'Perempuan'],

    'kode' => [
        // Percobaan kode yang salah sebelum pengisi harus menunggu.
        'max_attempts' => 5,
        'decay_seconds' => 300,
    ],

    // Lama perangkat kios tetap aktif setelah diaktifkan admin (hari).
    'kios_cookie_days' => 365,

    /*
    |--------------------------------------------------------------------------
    | Pertanyaan untuk pustakawan
    |--------------------------------------------------------------------------
    */

    // Topik => ikon pilihan di formulir.
    'topik_pertanyaan' => [
        'Layanan perpustakaan' => 'book-open',
        'Keanggotaan' => 'users',
        'Kearsipan' => 'file-text',
        'Kunjungan rombongan' => 'bus',
        'Lainnya' => 'ellipsis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Statistik pengunjung website
    |--------------------------------------------------------------------------
    |
    | Permintaan dari agen yang cocok dengan pola ini tidak dihitung.
    |
    */

    'bot_pattern' => '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|curl|wget|python|headless|lighthouse|monitor/i',

    /*
    |--------------------------------------------------------------------------
    | Data awal
    |--------------------------------------------------------------------------
    |
    | Akun admin pertama dan apakah seeder mengisi konten serta statistik contoh.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Admin Dispusip'),
        'email' => env('ADMIN_EMAIL', 'admin@dispusip.test'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

    'seed_demo' => (bool) env('SEED_DEMO', true),

    /*
    |--------------------------------------------------------------------------
    | Pilihan ikon di panel admin
    |--------------------------------------------------------------------------
    |
    | Nama ikon Lucide yang tersedia di resources/icons/icons.php.
    |
    */

    'ikon_pilihan' => [
        'book-open', 'book-copy', 'book-marked', 'library', 'notebook-pen', 'armchair', 'bus', 'archive', 'baby',
        'id-card', 'users', 'user-check', 'megaphone', 'calendar-days', 'newspaper', 'landmark', 'heart-handshake',
        'graduation-cap', 'school', 'monitor-smartphone', 'wifi', 'clipboard-list', 'search', 'map-pin', 'globe',
        'image', 'film', 'layers', 'sparkles', 'target', 'briefcase', 'building-2', 'scroll-text', 'shield-check',
        'star', 'tag', 'message-circle', 'clock', 'history', 'folder-open', 'list-checks', 'chart-column', 'house',
    ],

];
