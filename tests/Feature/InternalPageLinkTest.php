<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InternalPageLinkTest extends TestCase
{
    public function test_pages_of_this_site_open_through_wire_navigate(): void
    {
        $this->assertTrue(is_internal_page('/kontak#tanya'));
        $this->assertTrue(is_internal_page('/berita?kategori=kegiatan'));
        $this->assertTrue(is_internal_page(route('profil')));
        $this->assertTrue(is_internal_page(route('beranda').'#layanan'));
    }

    #[DataProvider('regularLinks')]
    public function test_other_links_keep_normal_browser_behaviour(?string $href): void
    {
        $this->assertFalse(is_internal_page($href));
    }

    /**
     * @return array<string, array{0: ?string}>
     */
    public static function regularLinks(): array
    {
        return [
            'empty' => [null],
            'in-page anchor' => ['#layanan'],
            'other site' => ['https://instagram.com/dispusipkendari'],
            'protocol-relative' => ['//contoh.test/berita'],
            'email' => ['mailto:dispusip@kendarikota.go.id'],
            'phone' => ['tel:+6281234567890'],
            'script' => ['javascript:alert(1)'],
            'file download' => ['/storage/berkas/jadwal-keliling.pdf'],
        ];
    }
}
