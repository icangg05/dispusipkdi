<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureKioskDevice;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class KioskTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_kiosk_only_opens_on_an_activated_device(): void
    {
        $this->get(route('kios'))->assertForbidden()->assertSee('Perangkat ini belum menjadi kios');

        $this->withCookie(EnsureKioskDevice::COOKIE, 'aktif')->get(route('kios'))->assertOk()->assertSee('Buku Tamu Digital');
    }

    public function test_activating_a_kiosk_signs_the_admin_out_on_that_device(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.kios.aktifkan'))
            ->assertRedirect(route('kios'))
            ->assertCookie(EnsureKioskDevice::COOKIE, 'aktif');

        $this->assertGuest();
    }

    public function test_the_kiosk_sheet_does_not_ask_for_the_code(): void
    {
        Storage::fake('local');

        Livewire::withCookies([EnsureKioskDevice::COOKIE => 'aktif'])
            ->test('guestbook', ['variant' => 'kiosk'])
            ->assertDontSee('Kode kunjungan hari ini')
            ->set('nama', 'Andi Pratama')
            ->set('kategori', 'Peneliti')
            ->set('keperluan', 'Penelusuran arsip')
            ->set('foto', UploadedFile::fake()->image('foto.jpg'))
            ->call('catat')
            ->assertHasNoErrors();

        $this->assertSame('kios', Guest::query()->sole()->sumber);
    }

    public function test_the_kiosk_variant_still_needs_the_code_without_the_device_cookie(): void
    {
        Storage::fake('local');

        Livewire::test('guestbook', ['variant' => 'kiosk'])
            ->set('nama', 'Andi Pratama')
            ->set('kategori', 'Peneliti')
            ->set('keperluan', 'Penelusuran arsip')
            ->set('foto', UploadedFile::fake()->image('foto.jpg'))
            ->call('catat')
            ->assertHasErrors(['kode']);

        $this->assertDatabaseCount('guests', 0);
    }
}
