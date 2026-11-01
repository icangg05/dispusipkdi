<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Drawer\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NavbarTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function pagesWithLiveFilters(): array
    {
        return [
            'searching the news' => ['berita.index', 'cari', 'perpustakaan', 'Berita'],
            'filtering the gallery' => ['galeri', 'jenis', 'foto', 'Galeri'],
        ];
    }

    #[DataProvider('pagesWithLiveFilters')]
    public function test_the_menu_item_stays_active_after_a_live_update(string $route, string $property, string $value, string $label): void
    {
        $page = $this->get(route($route))->assertOk();
        $activeLink = '/class="dk-navlink is-active"\s+aria-current="page"\s+wire:navigate\.hover>'.$label.'</';
        $this->assertMatchesRegularExpression($activeLink, $page->getContent());

        $update = $this->withHeaders(['X-Livewire' => 'true'])->postJson(app('livewire')->getUpdateUri(), [
            'components' => [[
                'snapshot' => json_encode(Utils::extractAttributeDataFromHtml($page->getContent(), 'wire:snapshot')),
                'updates' => [$property => $value],
                'calls' => [],
            ]],
        ]);

        $update->assertOk();
        $this->assertMatchesRegularExpression($activeLink, $update->json('components.0.effects.html'));
    }

    public function test_the_survey_button_opens_the_survey_from_the_code(): void
    {
        config(['dispusip.survei_url' => 'https://surveidigital.spbe.go.id/embed/survey/contoh/embed/view/']);

        $this->get(route('beranda'))
            ->assertOk()
            ->assertSee('Survei Kepuasan')
            ->assertSee('src="https://surveidigital.spbe.go.id/embed/survey/contoh/embed/view/"', false);
    }

    public function test_the_survey_button_is_hidden_without_a_survey_link(): void
    {
        config(['dispusip.survei_url' => '']);

        $this->get(route('beranda'))->assertOk()->assertDontSee('Survei Kepuasan');
    }
}
