<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\GuestCode;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class GuestbookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-15 10:00:00'));
        Storage::fake('local');
    }

    protected function filledSheet(?string $code = null): Testable
    {
        return Livewire::test('guestbook')
            ->set('nama', 'La Ode Fajar Hidayat')
            ->set('asal', 'Universitas Halu Oleo')
            ->set('kategori', 'Mahasiswa')
            ->set('keperluan', 'Penelusuran arsip')
            ->set('foto', UploadedFile::fake()->image('foto.jpg', 300, 400))
            ->set('kode', $code ?? GuestCode::today()->code);
    }

    public function test_the_guestbook_page_shows_the_register(): void
    {
        $this->get(route('buku-tamu'))
            ->assertOk()
            ->assertSee('Kode kunjungan hari ini')
            ->assertSee('Tercatat hari ini')
            ->assertSeeLivewire('guestbook');
    }

    public function test_a_guest_is_recorded_with_the_visit_code_and_a_private_photo(): void
    {
        $this->filledSheet()
            ->call('catat')
            ->assertHasNoErrors()
            ->assertSet('tercatat.nomor', 1)
            ->assertSet('tercatat.nama', 'La Ode Fajar')
            ->assertDispatched('tamu-tercatat')
            ->assertSee('Tercatat');

        $guest = Guest::query()->sole();

        $this->assertSame('La Ode Fajar Hidayat', $guest->nama);
        $this->assertSame('web', $guest->sumber);
        $this->assertSame('2026-10-15', $guest->visit_date->toDateString());
        Storage::disk('local')->assertExists($guest->foto);
        $this->assertSame('La Ode Fajar H.', $guest->maskedName());
    }

    public function test_numbers_continue_from_todays_register(): void
    {
        Guest::factory()->on(today(), 41)->create();
        Guest::factory()->on(today()->subDay(), 90)->create();

        $this->filledSheet()->call('catat')->assertHasNoErrors()->assertSet('tercatat.nomor', 42);
    }

    public function test_required_rows_show_hints(): void
    {
        Livewire::test('guestbook')
            ->set('setuju', false)
            ->call('catat')
            ->assertHasErrors(['nama', 'kategori', 'keperluan', 'foto', 'setuju', 'kode'])
            ->assertSee('Tulis nama Anda.');

        $this->assertDatabaseCount('guests', 0);
    }

    public function test_a_wrong_code_is_rejected(): void
    {
        $wrong = GuestCode::today()->code === '0000' ? '1111' : '0000';

        $this->filledSheet($wrong)
            ->call('catat')
            ->assertHasErrors(['kode'])
            ->assertSee('Kode tidak cocok')
            ->assertSet('kode', '')
            ->assertDispatched('kode-salah');

        $this->assertDatabaseCount('guests', 0);
    }

    public function test_wrong_codes_are_rate_limited(): void
    {
        $wrong = GuestCode::today()->code === '0000' ? '1111' : '0000';
        $sheet = $this->filledSheet($wrong);

        for ($i = 0; $i < config('dispusip.kode.max_attempts'); $i++) {
            $sheet->set('kode', $wrong)->call('catat')->assertSee('Kode tidak cocok');
        }

        // Even the right code waits once the limit is reached.
        $sheet->set('kode', GuestCode::today()->code)->call('catat')->assertSee('Terlalu banyak percobaan kode');
        $this->assertDatabaseCount('guests', 0);
    }

    public function test_a_regenerated_code_replaces_the_old_one(): void
    {
        $old = GuestCode::today()->code;
        GuestCode::regenerate();

        $this->filledSheet($old)->call('catat')->assertHasErrors(['kode']);
        $this->filledSheet()->call('catat')->assertHasNoErrors();
    }

    public function test_the_photo_can_be_made_optional(): void
    {
        app(SiteSettings::class)->put('bukutamu', ['foto_wajib' => false]);

        $this->filledSheet()->set('foto', null)->call('catat')->assertHasNoErrors();

        $this->assertNull(Guest::query()->sole()->foto);
    }

    public function test_the_guest_can_see_only_their_own_photo(): void
    {
        $this->filledSheet()->call('catat');
        $guest = Guest::query()->sole();

        // Another visitor (no session) cannot open it.
        $this->flushSession();
        $this->get(route('buku-tamu.foto-saya', $guest))->assertNotFound();
        $this->withSession(['tamu_foto' => $guest->id])->get(route('buku-tamu.foto-saya', $guest))->assertOk();
    }
}
