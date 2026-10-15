<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Content and statistics are sample data from the design system — replace them with official data.
     * Set SEED_DEMO=false to create only the admin account and default settings.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            SettingSeeder::class,
        ]);

        if (config('dispusip.seed_demo')) {
            $this->call([
                ContentSeeder::class,
                DemoStatsSeeder::class,
            ]);
        }
    }
}
