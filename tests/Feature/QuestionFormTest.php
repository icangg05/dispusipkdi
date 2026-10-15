<?php

namespace Tests\Feature;

use App\Enums\QuestionStatus;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuestionFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_question_reaches_the_inbox(): void
    {
        Livewire::test('question-form')
            ->set('nama', 'Rina Kartika')
            ->set('kontak', '0812-3456-7890')
            ->set('topik', 'Keanggotaan')
            ->set('isi', 'Apakah mahasiswa luar kota bisa mendaftar anggota?')
            ->call('kirim')
            ->assertHasNoErrors()
            ->assertSee('Jawaban kami kirim lewat WhatsApp ke');

        $question = Question::query()->sole();
        $this->assertSame(QuestionStatus::Baru, $question->status);
        $this->assertSame('https://wa.me/6281234567890', $question->whatsappLink());
    }

    public function test_the_question_is_saved_starting_with_a_capital_letter(): void
    {
        Livewire::test('question-form')
            ->set('nama', 'Rina Kartika')
            ->set('kontak', '081234567890')
            ->set('topik', 'Keanggotaan')
            ->set('isi', '  apakah kartu anggota bisa dipakai di perpustakaan keliling?')
            ->call('kirim')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('questions', ['isi' => 'Apakah kartu anggota bisa dipakai di perpustakaan keliling?']);
    }

    public function test_an_email_address_is_rejected_because_answers_go_by_whatsapp(): void
    {
        Livewire::test('question-form')
            ->set('nama', 'Rina')
            ->set('kontak', 'rina@example.com')
            ->set('topik', 'Lainnya')
            ->set('isi', 'Apakah perpustakaan buka pada hari Minggu?')
            ->call('kirim')
            ->assertHasErrors(['kontak']);

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_the_contact_must_be_a_whatsapp_number(): void
    {
        Livewire::test('question-form')
            ->set('nama', 'Rina')
            ->set('kontak', 'rina')
            ->set('topik', 'Lainnya')
            ->set('isi', 'Pendek')
            ->call('kirim')
            ->assertHasErrors(['kontak', 'isi']);

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_the_honeypot_drops_bot_submissions_quietly(): void
    {
        Livewire::test('question-form')
            ->set('nama', 'Bot')
            ->set('kontak', '081234567890')
            ->set('topik', 'Lainnya')
            ->set('isi', 'Kunjungi situs promosi kami sekarang juga.')
            ->set('situs', 'https://spam.example')
            ->call('kirim')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('questions', 0);
    }
}
