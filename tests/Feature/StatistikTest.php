<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\SiteVisit;
use App\Services\StatistikService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class StatistikTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Thursday 15 October 2026, 11.00 WITA.
        $this->travelTo(Carbon::parse('2026-10-15 11:00:00'));
    }

    /** @var array<string, int> last register number per day */
    protected array $nomor = [];

    protected function guests(string $datetime, int $count): void
    {
        $at = Carbon::parse($datetime);
        $day = $at->toDateString();

        for ($i = 1; $i <= $count; $i++) {
            $this->nomor[$day] = ($this->nomor[$day] ?? 0) + 1;
            Guest::factory()->on($at, $this->nomor[$day])->create();
        }
    }

    protected function visits(string $date, int $count, string $time = '10:00:00'): void
    {
        for ($i = 0; $i < $count; $i++) {
            SiteVisit::query()->create(['visit_date' => $date, 'visitor_hash' => Str::random(64), 'hits' => 1])
                ->forceFill(['created_at' => $date.' '.$time])->save();
        }
    }

    public function test_guest_series_cover_days_months_and_years(): void
    {
        $this->guests('2026-10-15 09:00', 3);
        $this->guests('2026-10-14 09:00', 2);
        $this->guests('2026-10-14 14:00', 1);
        $this->guests('2026-09-03 10:00', 4);
        $this->guests('2025-05-20 10:00', 5);

        $tamu = app(StatistikService::class)->all()['tamu'];

        $this->assertSame(3, $tamu['hariIni']);
        $this->assertSame(3, $tamu['kemarin']);
        $this->assertSame(2, $tamu['kemarinSamaJam'], 'Only yesterday’s guests before 11.00 compare with today so far.');
        $this->assertSame(6, $tamu['bulanIni']);
        $this->assertSame(4, $tamu['bulanLalu']);
        $this->assertSame(4, $tamu['bulanLaluSamaPeriode']);
        $this->assertSame(15, $tamu['total']);
        $this->assertSame(2025, $tamu['sejak']);

        $this->assertCount(7, $tamu['harian']);
        $this->assertSame(['label' => 'Kam', 'value' => 3, 'current' => true], $tamu['harian'][6]);
        $this->assertSame(['label' => 'Min', 'value' => 0, 'current' => false, 'note' => 'Tutup'], $tamu['harian'][2], 'Sunday has no service hours.');

        $this->assertCount(12, $tamu['bulanan']);
        $this->assertSame('Okt', $tamu['bulanan'][11]['label']);
        $this->assertSame('Nov', $tamu['bulanan'][0]['label']);

        $this->assertSame(['2025', '2026'], array_column($tamu['semua'], 'label'));
        $this->assertSame('s.d. Okt', $tamu['semua'][1]['note']);
    }

    public function test_website_visitors_are_counted_per_day(): void
    {
        $this->visits('2026-10-15', 4);
        $this->visits('2026-10-14', 2, '09:00:00');
        $this->visits('2026-10-14', 3, '20:00:00');

        $web = app(StatistikService::class)->all()['web'];

        $this->assertSame(4, $web['hariIni']);
        $this->assertSame(5, $web['kemarin']);
        $this->assertSame(2, $web['kemarinSamaJam']);
        $this->assertSame(9, $web['total']);
        $this->assertArrayNotHasKey('note', $web['harian'][2]);
    }

    public function test_guest_categories_for_this_month_keep_the_form_order(): void
    {
        Guest::factory()->on(today(), 1)->create(['kategori' => 'Peneliti']);
        Guest::factory()->on(today(), 2)->create(['kategori' => 'Pelajar']);
        Guest::factory()->on(today(), 3)->create(['kategori' => 'Pelajar']);

        $kategori = app(StatistikService::class)->all()['kategoriTamu'];

        $this->assertSame(['label' => 'Pelajar', 'value' => 2], $kategori[0]);
        $this->assertSame(['label' => 'Peneliti', 'value' => 1], $kategori[4]);
    }

    public function test_the_statistics_page_renders(): void
    {
        $this->guests('2026-10-15 09:00', 2);

        $this->get(route('statistik'))->assertOk()->assertSee('Statistik Pengunjung')->assertSee('12 bulan terakhir');
    }
}
