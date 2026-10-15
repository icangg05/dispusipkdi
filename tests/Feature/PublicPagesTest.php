<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Faq;
use App\Models\MediaItem;
use App\Models\Post;
use App\Models\Service;
use App\Models\Slide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_public_page_renders(): void
    {
        Slide::factory()->count(2)->create();
        Service::factory()->count(3)->create();
        Faq::factory()->count(2)->create();
        MediaItem::factory()->count(2)->create();
        MediaItem::factory()->video()->create();
        Post::factory()->count(4)->create();

        foreach (['beranda', 'profil', 'berita.index', 'galeri', 'statistik', 'kontak', 'buku-tamu'] as $route) {
            $this->get(route($route))->assertOk();
        }

        $this->get('/')->assertSee('Isi Buku Tamu')->assertSee('Statistik')->assertSee('Hubungi');
    }

    public function test_page_titles_put_a_pipe_before_the_site_name(): void
    {
        $identitas = site()->identitas();

        $this->get(route('kontak'))
            ->assertOk()
            ->assertSee('<title>Kontak | '.$identitas['singkatan'].' '.$identitas['kota'].'</title>', false);
    }

    public function test_home_falls_back_to_a_sample_slide(): void
    {
        $this->get('/')->assertOk()->assertSee('/images/contoh/ruang-baca.jpg', false);
    }

    public function test_news_can_be_filtered_by_category(): void
    {
        $pengumuman = Category::factory()->create(['name' => 'Pengumuman', 'slug' => 'pengumuman']);
        $kegiatan = Category::factory()->create(['name' => 'Kegiatan', 'slug' => 'kegiatan']);
        Post::factory()->for($pengumuman)->create(['title' => 'Jadwal libur akhir tahun']);
        Post::factory()->for($kegiatan)->create(['title' => 'Bedah buku pesisir']);

        $this->get(route('berita.index', ['kategori' => 'pengumuman']))
            ->assertOk()
            ->assertSee('Jadwal libur akhir tahun')
            ->assertDontSee('Bedah buku pesisir');
    }

    public function test_a_published_post_opens_and_counts_one_read_per_session(): void
    {
        $post = Post::factory()->create(['views' => 10, 'body' => "## Subjudul\n\nIsi **tebal**.\n\n<script>alert(1)</script>"]);

        $this->get(route('berita.show', $post))
            ->assertOk()
            ->assertSee($post->title)
            ->assertSee('<h2>Subjudul</h2>', false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->get(route('berita.show', $post));

        $this->assertSame(11, $post->fresh()->views);
    }

    public function test_drafts_and_scheduled_posts_are_hidden(): void
    {
        $this->get(route('berita.show', Post::factory()->draft()->create()))->assertNotFound();
        $this->get(route('berita.show', Post::factory()->scheduled()->create()))->assertNotFound();
    }

    public function test_youtube_links_become_privacy_friendly_embeds(): void
    {
        $item = MediaItem::factory()->video('https://www.youtube.com/watch?v=AbCdEfGhIjK&t=5')->create();

        $this->assertSame('AbCdEfGhIjK', $item->youtube_id);
        $this->assertStringStartsWith('https://www.youtube-nocookie.com/embed/AbCdEfGhIjK', $item->embed_url);
    }
}
