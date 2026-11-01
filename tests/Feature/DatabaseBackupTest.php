<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_or_download_the_backup(): void
    {
        $this->get(route('admin.backup'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.backup.unduh'))->assertRedirect(route('admin.login'));
    }

    public function test_the_backup_page_lists_every_table(): void
    {
        Post::factory()->count(3)->create();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.backup'))
            ->assertOk()
            ->assertSee('Unduh backup')
            ->assertSee(route('admin.backup.unduh'))
            ->assertSeeInOrder(['posts', '3'])
            ->assertSee('Hanya struktur.');
    }

    public function test_the_download_restores_into_an_empty_database(): void
    {
        $post = Post::factory()->create(['title' => "Pustakawan 'teladan' Kendari", 'body' => "Baris satu\nBaris dua \\ garis miring"]);
        Guest::factory()->count(150)->create();
        DB::table('sessions')->insert(['id' => 'sesi-rahasia', 'payload' => 'isi', 'last_activity' => time()]);

        $response = $this->actingAs(User::factory()->create())->get(route('admin.backup.unduh'))->assertOk();

        $this->assertMatchesRegularExpression('/^attachment; filename=backup-dispusip-\d{8}-\d{6}\.sql\.gz$/', $response->headers->get('Content-Disposition'));
        $sql = gzdecode($response->streamedContent());
        $this->assertStringNotContainsString('sesi-rahasia', $sql);

        config(['database.connections.pemulihan' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        $restored = DB::connection('pemulihan');
        $restored->unprepared($sql);

        $this->assertSame($post->title, $restored->table('posts')->value('title'));
        $this->assertSame($post->body, $restored->table('posts')->value('body'));
        $this->assertSame(150, $restored->table('guests')->count());
        $this->assertSame(1, $restored->table('users')->count());
        $this->assertSame(0, $restored->table('sessions')->count());
    }

    public function test_the_plain_sql_script_can_be_downloaded(): void
    {
        $post = Post::factory()->create();

        $response = $this->actingAs(User::factory()->create())->get(route('admin.backup.unduh', ['format' => 'sql']))->assertOk();

        $this->assertStringEndsWith('.sql', $response->headers->get('Content-Disposition'));
        $sql = $response->streamedContent();
        $this->assertStringContainsString('CREATE TABLE "posts"', $sql);
        $this->assertStringContainsString($post->slug, $sql);
    }
}
