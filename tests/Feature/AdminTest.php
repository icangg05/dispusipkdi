<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Guest;
use App\Models\Post;
use App\Models\Question;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get(route('admin.buku-tamu.ekspor'))->assertRedirect(route('admin.login'));
    }

    public function test_an_admin_can_sign_in(): void
    {
        $user = User::factory()->create(['email' => 'petugas@dispusip.test']);

        Livewire::test('pages::admin.login')
            ->set('email', 'petugas@dispusip.test')
            ->set('password', 'salah')
            ->call('masuk')
            ->assertHasErrors(['email']);

        Livewire::test('pages::admin.login')
            ->set('email', 'petugas@dispusip.test')
            ->set('password', 'password')
            ->call('masuk')
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_every_admin_page_renders(): void
    {
        $post = Post::factory()->create();
        Question::factory()->create();
        Guest::factory()->create();

        $this->actingAs(User::factory()->create());

        foreach (['admin.dashboard', 'admin.buku-tamu', 'admin.kode', 'admin.kode.layar', 'admin.pertanyaan', 'admin.berita.index', 'admin.berita.create', 'admin.kategori', 'admin.galeri', 'admin.slider', 'admin.layanan', 'admin.faq', 'admin.pengaturan', 'admin.akun'] as $route) {
            $this->get(route($route))->assertOk();
        }

        $this->get(route('admin.berita.edit', $post))->assertOk()->assertSee($post->title);
    }

    public function test_guest_photos_are_only_served_to_admins(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('tamu/2026/10/foto.jpg', 'jpeg');
        $guest = Guest::factory()->create(['foto' => 'tamu/2026/10/foto.jpg']);

        $this->get(route('admin.buku-tamu.foto', $guest))->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->create())->get(route('admin.buku-tamu.foto', $guest))->assertOk();
    }

    public function test_the_register_can_be_exported(): void
    {
        Guest::factory()->create(['nama' => 'Wa Ode Sitti', 'kategori' => 'Mahasiswa']);

        $csv = $this->actingAs(User::factory()->create())
            ->get(route('admin.buku-tamu.ekspor', ['dari' => today()->toDateString(), 'sampai' => today()->toDateString()]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Wa Ode Sitti', $csv);
        $this->assertStringContainsString('Datang sebagai', $csv);
    }

    public function test_an_admin_can_write_a_post(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();

        Livewire::actingAs(User::factory()->create())
            ->test('pages::admin.berita.form')
            ->set('title', 'Jam layanan baru mulai November')
            ->set('category_id', $category->id)
            ->set('body', 'Ruang baca kini buka hingga **16.00 WITA**.')
            ->set('gambar', UploadedFile::fake()->image('sampul.jpg', 1600, 900))
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.berita.index'));

        $post = Post::query()->sole();
        $this->assertSame('jam-layanan-baru-mulai-november', $post->slug);
        $this->assertTrue($post->isVisible());
        Storage::disk('public')->assertExists($post->image);
    }

    public function test_service_hours_are_saved_in_minutes(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test('pages::admin.pengaturan')
            ->set('tab', 'jadwal')
            ->set('jadwal.1.sesi.0.mulai', '07:30')
            ->set('jadwal.1.sesi.0.selesai', '16:00')
            ->set('jadwal.6.buka', false)
            ->call('simpanJadwal')
            ->assertHasNoErrors();

        $jadwal = app(SiteSettings::class)->jadwal();
        $this->assertSame([[450, 960]], $jadwal[1]['sesi']);
        $this->assertSame([], $jadwal[6]['sesi']);
    }

    public function test_a_map_embed_is_taken_from_the_pasted_iframe(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test('pages::admin.pengaturan')
            ->set('kontak.maps_embed', '<iframe src="https://www.google.com/maps/embed?pb=!1m18" width="600"></iframe>')
            ->call('simpanKontak')
            ->assertHasNoErrors();

        $this->assertSame('https://www.google.com/maps/embed?pb=!1m18', app(SiteSettings::class)->kontak()['maps_embed']);

        Livewire::actingAs(User::factory()->create())
            ->test('pages::admin.pengaturan')
            ->set('kontak.maps_embed', 'https://evil.example/embed')
            ->call('simpanKontak')
            ->assertHasErrors(['kontak.maps_embed']);
    }
}
