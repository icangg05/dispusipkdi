<?php

namespace Tests\Feature;

use App\Http\Middleware\LimitGuestUploads;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class UploadLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_upload_limits_are_set_from_the_settings_page(): void
    {
        $page = Livewire::actingAs(User::factory()->create())
            ->test('pages::admin.pengaturan')
            ->set('tab', 'unggah')
            ->assertSet('unggah', ['gambar_mb' => 5, 'berkas_mb' => 100])
            ->set('unggah.gambar_mb', 151)
            ->call('simpanUnggah')
            ->assertHasErrors(['unggah.gambar_mb' => 'max']);

        $page->set('unggah.gambar_mb', '8')
            ->set('unggah.berkas_mb', '150')
            ->call('simpanUnggah')
            ->assertHasNoErrors();

        $this->assertSame(8, site()->batasGambarMb());
        $this->assertSame(150, site()->batasBerkasMb());
    }

    public function test_admin_images_follow_the_image_limit(): void
    {
        Storage::fake('public');
        app(SiteSettings::class)->put('unggah', ['gambar_mb' => 1, 'berkas_mb' => 100]);

        Livewire::actingAs(User::factory()->create())
            ->test('pages::admin.galeri')
            ->call('tambah', 'foto')
            ->set('title', 'Ruang baca umum')
            ->set('gambar', UploadedFile::fake()->image('besar.jpg')->size(2048))
            ->call('simpan')
            ->assertHasErrors(['gambar' => 'max'])
            ->assertSee('Ukuran gambar paling besar 1 MB.');
    }

    public function test_visitors_cannot_send_large_temporary_uploads(): void
    {
        $url = URL::temporarySignedRoute('livewire.upload-file', now()->addMinutes(5), absolute: false);
        $large = ['CONTENT_LENGTH' => (LimitGuestUploads::GUEST_MAX_KB + 1) * 1024];

        $this->call('POST', $url, server: $large)->assertStatus(413);

        $admin = $this->actingAs(User::factory()->create())->call('POST', $url, server: $large);
        $this->assertNotSame(413, $admin->getStatusCode());
    }
}
