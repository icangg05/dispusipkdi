<?php

namespace Tests\Unit;

use App\Support\Jadwal;
use App\Support\SiteSettings;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class JadwalTest extends TestCase
{
    /**
     * @return array<int, array{hari: string, sesi: array<int, array{0: int, 1: int}>}>
     */
    protected function jadwal(): array
    {
        return SiteSettings::defaults()['jadwal'];
    }

    public function test_open_during_a_session(): void
    {
        $status = Jadwal::status($this->jadwal(), Carbon::parse('2026-10-15 10:00', 'Asia/Makassar'));

        $this->assertTrue($status['open']);
        $this->assertSame('tutup 15.30 WITA', $status['detail']);
        $this->assertSame(4, $status['day']);
    }

    public function test_friday_prayer_break_and_next_opening(): void
    {
        $status = Jadwal::status($this->jadwal(), Carbon::parse('2026-10-16 12:00', 'Asia/Makassar'));

        $this->assertFalse($status['open']);
        $this->assertSame('buka lagi 13.30 WITA', $status['detail']);
    }

    public function test_closed_on_sunday_until_monday(): void
    {
        $status = Jadwal::status($this->jadwal(), Carbon::parse('2026-10-18 10:00', 'Asia/Makassar'));

        $this->assertFalse($status['open']);
        $this->assertSame('buka Senin 08.00', $status['detail']);
    }

    public function test_the_footer_summary_groups_equal_days(): void
    {
        $this->assertSame(
            'Sen–Kam 08.00–15.30 · Jum 08.00–11.30, 13.30–16.00 · Sab 09.00–14.00 WITA',
            Jadwal::ringkas($this->jadwal()),
        );
    }
}
