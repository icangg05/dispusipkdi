<?php

namespace Tests\Feature;

use App\Models\SiteVisit;
use App\Models\User;
use App\Services\VisitorTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VisitorTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected const BROWSER = 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Mobile Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-15 09:00:00'));
    }

    public function test_a_visitor_is_counted_once_per_day(): void
    {
        $this->withHeader('User-Agent', self::BROWSER)->get('/')->assertOk()->assertCookie(VisitorTracker::COOKIE);
        $this->withHeader('User-Agent', self::BROWSER)->get('/profil')->assertOk();

        $visit = SiteVisit::query()->sole();
        $this->assertSame('2026-10-15', $visit->visit_date->toDateString());
        $this->assertSame(2, $visit->hits);
        $this->assertSame(64, strlen($visit->visitor_hash));

        // The next day the same browser counts again.
        $this->travelTo(Carbon::parse('2026-10-16 09:00:00'));
        $this->withHeader('User-Agent', self::BROWSER)->get('/');

        $this->assertDatabaseCount('site_visits', 2);
    }

    public function test_the_cookie_skips_repeat_writes(): void
    {
        $this->withHeader('User-Agent', self::BROWSER)
            ->withCookie(VisitorTracker::COOKIE, '2026-10-15')
            ->get('/');

        $this->assertDatabaseCount('site_visits', 0);
    }

    public function test_bots_and_admin_pages_are_not_counted(): void
    {
        $this->withHeader('User-Agent', 'Googlebot/2.1 (+http://www.google.com/bot.html)')->get('/');
        $this->withHeader('User-Agent', self::BROWSER)->get('/admin/masuk');
        $this->actingAs(User::factory()->create())->withHeader('User-Agent', self::BROWSER)->get('/admin');

        $this->assertDatabaseCount('site_visits', 0);
    }

    public function test_no_personal_data_is_stored(): void
    {
        $this->withHeader('User-Agent', self::BROWSER)->get('/', ['REMOTE_ADDR' => '203.0.113.7']);

        $row = SiteVisit::query()->sole()->getAttributes();

        $this->assertStringNotContainsString('203.0.113.7', implode('|', array_map('strval', $row)));
    }
}
