<?php

namespace Database\Seeders;

use App\Support\SiteSettings;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sample visit history since January 2024 so the statistics show daily, monthly and yearly series.
 * Guests follow the service hours (none on Sunday); website visitors are counted every day.
 */
class DemoStatsSeeder extends Seeder
{
    protected const FIRST_NAMES = ['Nur', 'Muh.', 'Andi', 'Siti', 'Rizky', 'Putri', 'Dewi', 'Fajar', 'Ahmad', 'Rahmat', 'Ayu', 'Indah', 'Yusuf', 'Fitri', 'Hendra', 'Nadia', 'Ilham', 'Sri', 'Arif', 'Wulan', 'La Ode', 'Wa Ode', 'Hasrul', 'Asrul', 'Nurul', 'Rahma', 'Alfian', 'Sartika'];

    protected const LAST_NAMES = ['Aisyah', 'Rizal', 'Pratama', 'Sitti', 'Fajar', 'Saputra', 'Lestari', 'Hidayat', 'Ramadhan', 'Wahyuni', 'Kurniawan', 'Halim', 'Syahrir', 'Amalia', 'Rahman', 'Safitri', 'Maulana', 'Hasan', 'Anwar', 'Mardiana', 'Tamburaka', 'Lakidende', 'Silondae'];

    protected const ASAL = ['SMA Negeri 1 Kendari', 'SMP Negeri 2 Kendari', 'Universitas Halu Oleo', 'IAIN Kendari', 'Universitas Sulawesi Tenggara', 'SMK Negeri 1 Kendari', 'Dinas Pendidikan', 'SD Negeri 5 Kendari', null, null, null, null];

    public function run(SiteSettings $settings): void
    {
        $jadwal = $settings->jadwal();
        $start = CarbonImmutable::create(2024, 1, 1);
        $today = CarbonImmutable::today();
        $now = CarbonImmutable::now();

        $kategori = ['Pelajar' => 38, 'Mahasiswa' => 27, 'Umum' => 22, 'ASN / Instansi' => 9, 'Peneliti' => 4];
        $keperluan = ['Membaca di tempat' => 46, 'Meminjam atau mengembalikan buku' => 28, 'Penelusuran arsip' => 8, 'Kunjungan rombongan' => 3, 'Mendaftar anggota' => 10, 'Lainnya' => 5];

        $guests = [];
        $visits = [];

        for ($day = $start; $day->lte($today); $day = $day->addDay()) {
            $season = $this->season($day);
            $sesi = $jadwal[$day->dayOfWeek]['sesi'];

            // Guests: only on open days.
            if ($sesi !== []) {
                $base = $day->dayOfWeek === 6 ? 52 : 82;
                $count = (int) round(($base + mt_rand(-14, 14)) * $season * (1 + ($day->year - 2024) * 0.12));
                $times = [];

                for ($i = 0; $i < $count; $i++) {
                    [$open, $close] = $sesi[array_rand($sesi)];
                    $times[] = mt_rand($open, $close - 5);
                }

                sort($times);
                $nomor = 0;

                foreach ($times as $minute) {
                    $at = $day->setTime(intdiv($minute, 60), $minute % 60, mt_rand(0, 59));

                    if ($at->gt($now)) {
                        break;
                    }

                    $kep = $this->pick($keperluan);
                    $rombongan = $kep === 'Kunjungan rombongan';
                    $asal = $rombongan ? 'SD Negeri '.mt_rand(1, 90).' Kendari' : self::ASAL[array_rand(self::ASAL)];

                    $guests[] = [
                        'visit_date' => $day->toDateString(),
                        'nomor' => ++$nomor,
                        'nama' => self::FIRST_NAMES[array_rand(self::FIRST_NAMES)].' '.self::LAST_NAMES[array_rand(self::LAST_NAMES)],
                        'asal' => $asal,
                        'kategori' => $rombongan ? 'Pelajar' : $this->pick($kategori),
                        'keperluan' => $kep,
                        'jenis_kelamin' => mt_rand(0, 9) < 1 ? null : (mt_rand(0, 1) ? 'Laki-laki' : 'Perempuan'),
                        'jumlah' => $rombongan ? mt_rand(12, 40) : (mt_rand(0, 9) < 1 ? mt_rand(2, 4) : 1),
                        'whatsapp' => null,
                        'foto' => null,
                        'sumber' => mt_rand(0, 2) ? 'web' : 'kios',
                        'ip_address' => null,
                        'user_agent' => null,
                        'created_at' => $at->toDateTimeString(),
                        'updated_at' => $at->toDateTimeString(),
                    ];

                    if (count($guests) >= 1000) {
                        DB::table('guests')->insert($guests);
                        $guests = [];
                    }
                }
            }

            // Website visitors: every day, fewer at weekends; today only up to now.
            $webBase = in_array($day->dayOfWeek, [0, 6], true) ? 230 : 420;
            $web = (int) round(($webBase + mt_rand(-60, 60)) * $season * (1 + ($day->year - 2024) * 0.18));

            if ($day->equalTo($today)) {
                $web = (int) round($web * min(1, ($now->hour * 60 + $now->minute) / (20 * 60)));
            }

            $lastSecond = $day->equalTo($today) ? (int) $now->secondsSinceMidnight() : 86399;

            for ($i = 0; $i < $web; $i++) {
                // Most visits fall in the day, a few late at night.
                $at = $day->addSeconds(min($lastSecond, mt_rand(0, 9) < 8 ? mt_rand(25200, 79200) : mt_rand(0, 86399)));
                $visits[] = [
                    'visit_date' => $day->toDateString(),
                    'visitor_hash' => bin2hex(random_bytes(32)),
                    'hits' => mt_rand(1, 6),
                    'landing_path' => '/',
                    'created_at' => $at->toDateTimeString(),
                    'updated_at' => $at->toDateTimeString(),
                ];

                if (count($visits) >= 2000) {
                    DB::table('site_visits')->insert($visits);
                    $visits = [];
                }
            }
        }

        if ($guests) {
            DB::table('guests')->insert($guests);
        }

        if ($visits) {
            DB::table('site_visits')->insert($visits);
        }
    }

    /**
     * Quieter during school holidays (late June–July, late December).
     */
    protected function season(CarbonImmutable $day): float
    {
        return match (true) {
            $day->month === 12 && $day->day > 18 => 0.55,
            $day->month === 7 && $day->day < 15 => 0.7,
            $day->month === 6 && $day->day > 20 => 0.75,
            in_array($day->month, [8, 9, 10], true) => 1.08,
            default => 1.0,
        };
    }

    /**
     * @param  array<string, int>  $weights
     */
    protected function pick(array $weights): string
    {
        $roll = mt_rand(1, array_sum($weights));

        foreach ($weights as $value => $weight) {
            $roll -= $weight;

            if ($roll <= 0) {
                return $value;
            }
        }

        return array_key_first($weights);
    }
}
