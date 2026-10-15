<?php

namespace Tests\Feature;

use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuestionInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_sending_a_reply_marks_the_question_answered_and_opens_whatsapp_with_the_message(): void
    {
        $this->travelTo('2026-10-15 10:30:00');
        $question = Question::factory()->create(['kontak' => '0812-3456-7890']);

        Livewire::actingAs(User::factory()->create())
            ->test('pages::admin.pertanyaan')
            ->call('lihat', $question->id)
            ->set('pesan', 'ruang baca buka pukul 08.00 WITA.')
            ->call('kirim', $question->id)
            ->assertHasNoErrors()
            ->assertReturned('https://wa.me/6281234567890?text=Ruang%20baca%20buka%20pukul%2008.00%20WITA.')
            ->assertSet('buka', null);

        $question->refresh();
        $this->assertSame(QuestionStatus::Dijawab, $question->status);
        $this->assertSame('Ruang baca buka pukul 08.00 WITA.', $question->catatan);
        $this->assertSame('2026-10-15 10:30:00', $question->answered_at->toDateTimeString());
    }

    public function test_an_empty_reply_is_not_sent(): void
    {
        $question = Question::factory()->create();

        Livewire::actingAs(User::factory()->create())
            ->test('pages::admin.pertanyaan')
            ->call('lihat', $question->id)
            ->set('pesan', '')
            ->call('kirim', $question->id)
            ->assertHasErrors(['pesan' => 'required'])
            ->assertReturned(null);

        $question->refresh();
        $this->assertSame(QuestionStatus::Baru, $question->status);
        $this->assertNull($question->catatan);
    }

    public function test_an_unanswered_question_opens_with_the_first_template_filled_in(): void
    {
        app(SiteSettings::class)->put('balasan', ['template' => [
            ['judul' => 'Uji', 'isi' => 'Halo {nama}, soal {topik}: "{pertanyaan}" Salam, {petugas} ({dinas})'],
        ]]);
        $question = Question::factory()->create([
            'nama' => 'Wa Ode Sitti Rahma',
            'topik' => 'Keanggotaan',
            'isi' => 'apakah kartu anggota berlaku di perpustakaan keliling?',
        ]);

        Livewire::actingAs(User::factory()->create(['name' => 'Rahmat']))
            ->test('pages::admin.pertanyaan')
            ->call('lihat', $question->id)
            ->assertSet('template', 0)
            ->assertSet('pesan', 'Halo Wa Ode Sitti, soal keanggotaan: "Apakah kartu anggota berlaku di perpustakaan keliling?" Salam, Rahmat (DISPUSIP Kota Kendari)');
    }

    public function test_an_answered_question_opens_with_the_reply_that_was_sent(): void
    {
        $question = Question::factory()->create([
            'status' => QuestionStatus::Dijawab,
            'answered_at' => now(),
            'catatan' => 'Sudah kami jawab lewat WhatsApp.',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test('pages::admin.pertanyaan')
            ->call('lihat', $question->id)
            ->assertSet('template', null)
            ->assertSet('pesan', 'Sudah kami jawab lewat WhatsApp.');
    }

    public function test_a_question_that_left_an_email_is_not_sent_to_whatsapp(): void
    {
        $question = Question::factory()->create(['kontak' => 'rina@example.com']);

        Livewire::actingAs(User::factory()->create())
            ->test('pages::admin.pertanyaan')
            ->call('lihat', $question->id)
            ->set('pesan', 'Jawaban lewat email.')
            ->call('kirim', $question->id)
            ->assertReturned(null);

        $this->assertSame(QuestionStatus::Baru, $question->refresh()->status);
    }

    public function test_reply_templates_are_saved_starting_with_a_capital_letter(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test('pages::admin.pertanyaan')
            ->call('bukaTemplate')
            ->set('templates', [['judul' => 'balasan cepat', 'isi' => 'terima kasih, {nama}.']])
            ->call('simpanTemplate')
            ->assertHasNoErrors()
            ->assertSet('kelolaTemplate', false);

        $this->assertSame([['judul' => 'Balasan cepat', 'isi' => 'Terima kasih, {nama}.']], app(SiteSettings::class)->templateBalasan());
    }

    public function test_a_template_needs_a_title_and_a_message(): void
    {
        $before = app(SiteSettings::class)->templateBalasan();

        Livewire::actingAs(User::factory()->create())
            ->test('pages::admin.pertanyaan')
            ->call('bukaTemplate')
            ->set('templates', [['judul' => '', 'isi' => '']])
            ->call('simpanTemplate')
            ->assertHasErrors(['templates.0.judul' => 'required', 'templates.0.isi' => 'required']);

        $this->assertSame($before, app(SiteSettings::class)->templateBalasan());
    }
}
