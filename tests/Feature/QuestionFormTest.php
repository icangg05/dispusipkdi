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
            ->assertSee('Jawaban dikirim ke');

        $question = Question::query()->sole();
        $this->assertSame(QuestionStatus::Baru, $question->status);
        $this->assertSame('https://wa.me/6281234567890', $question->whatsappLink());
    }

    public function test_the_contact_must_be_an_email_or_phone_number(): void
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
            ->set('kontak', 'bot@example.com')
            ->set('topik', 'Lainnya')
            ->set('isi', 'Kunjungi situs promosi kami sekarang juga.')
            ->set('situs', 'https://spam.example')
            ->call('kirim')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('questions', 0);
    }
}
