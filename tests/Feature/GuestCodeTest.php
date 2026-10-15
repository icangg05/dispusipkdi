<?php

namespace Tests\Feature;

use App\Models\GuestCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class GuestCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_day_gets_one_random_four_digit_code(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 08:00:00'));
        $today = GuestCode::today();

        $this->assertMatchesRegularExpression('/^\d{4}$/', $today->code);
        $this->assertSame($today->code, GuestCode::today()->code);
        $this->assertDatabaseCount('guest_codes', 1);

        $this->travelTo(Carbon::parse('2026-10-16 08:00:00'));
        GuestCode::today();

        $this->assertDatabaseCount('guest_codes', 2);
    }

    public function test_the_day_follows_wita(): void
    {
        // 23.30 UTC on the 15th is already the 16th in Kendari.
        $this->travelTo(Carbon::parse('2026-10-15 23:30:00', 'UTC'));

        $this->assertSame('2026-10-16', GuestCode::today()->date->toDateString());
    }

    public function test_admin_can_regenerate_or_set_todays_code(): void
    {
        $admin = User::factory()->create();
        $old = GuestCode::today()->code;

        Livewire::actingAs($admin)->test('pages::admin.kode')->call('acak')->assertDispatched('toast');

        $code = GuestCode::today();
        $this->assertNotSame($old, $code->code);
        $this->assertTrue($code->user->is($admin));

        Livewire::actingAs($admin)->test('pages::admin.kode')
            ->set('kodeManual', '12a4')->call('simpanManual')->assertHasErrors(['kodeManual'])
            ->set('kodeManual', '2468')->call('simpanManual')->assertHasNoErrors();

        $this->assertSame('2468', GuestCode::today()->code);
        $this->assertTrue(GuestCode::matchesToday('2468'));
    }

    public function test_the_front_desk_screen_shows_the_code(): void
    {
        $admin = User::factory()->create();
        GuestCode::setToday('5731');

        $this->actingAs($admin)->get(route('admin.kode.layar'))->assertOk()->assertSee('Kode 5 7 3 1');
    }
}
