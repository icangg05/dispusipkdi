<?php

use App\Http\Controllers\Admin\GuestExportController;
use App\Http\Controllers\Admin\GuestPhotoController;
use App\Http\Controllers\Admin\KioskController;
use App\Http\Controllers\Admin\LogoutController;
use App\Http\Controllers\OwnGuestPhotoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Situs publik
|--------------------------------------------------------------------------
*/

Route::middleware('track')->group(function () {
    Route::livewire('/', 'pages::beranda')->name('beranda');
    Route::livewire('/profil', 'pages::profil')->name('profil');
    Route::livewire('/berita', 'pages::berita.index')->name('berita.index');
    Route::livewire('/berita/{post:slug}', 'pages::berita.show')->name('berita.show');
    Route::livewire('/galeri', 'pages::galeri')->name('galeri');
    Route::livewire('/statistik', 'pages::statistik')->name('statistik');
    Route::livewire('/kontak', 'pages::kontak')->name('kontak');
    Route::livewire('/buku-tamu', 'pages::buku-tamu')->name('buku-tamu');
});

Route::get('/buku-tamu/foto/{guest}', OwnGuestPhotoController::class)->name('buku-tamu.foto-saya');

// Front-desk tablet; only opens on a device an admin switched to kiosk mode.
Route::livewire('/kios', 'pages::kios')->middleware('kios')->name('kios');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/masuk', 'pages::admin.login')->middleware('guest')->name('login');

    Route::middleware('auth')->group(function () {
        Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');

        Route::livewire('/buku-tamu', 'pages::admin.buku-tamu')->name('buku-tamu');
        Route::get('/buku-tamu/ekspor', GuestExportController::class)->name('buku-tamu.ekspor');
        Route::get('/buku-tamu/{guest}/foto', GuestPhotoController::class)->name('buku-tamu.foto');

        Route::livewire('/kode', 'pages::admin.kode')->name('kode');
        Route::livewire('/kode/layar', 'pages::admin.kode-layar')->name('kode.layar');
        Route::post('/kios/aktifkan', [KioskController::class, 'activate'])->name('kios.aktifkan');
        Route::post('/kios/nonaktifkan', [KioskController::class, 'deactivate'])->name('kios.nonaktifkan');

        Route::livewire('/pertanyaan', 'pages::admin.pertanyaan')->name('pertanyaan');

        Route::livewire('/berita', 'pages::admin.berita.index')->name('berita.index');
        Route::livewire('/berita/tulis', 'pages::admin.berita.form')->name('berita.create');
        Route::livewire('/berita/{post}/ubah', 'pages::admin.berita.form')->name('berita.edit');

        Route::livewire('/kategori', 'pages::admin.kategori')->name('kategori');
        Route::livewire('/galeri', 'pages::admin.galeri')->name('galeri');
        Route::livewire('/slider', 'pages::admin.slider')->name('slider');
        Route::livewire('/layanan', 'pages::admin.layanan')->name('layanan');
        Route::livewire('/tanya-jawab', 'pages::admin.faq')->name('faq');
        Route::livewire('/pengaturan', 'pages::admin.pengaturan')->name('pengaturan');
        Route::livewire('/akun', 'pages::admin.akun')->name('akun');

        Route::post('/keluar', LogoutController::class)->name('logout');
    });
});
