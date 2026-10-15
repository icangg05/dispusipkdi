<?php

namespace Database\Seeders;

use App\Support\SiteSettings;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Store the default (sample) settings so they can be edited from the admin panel.
     */
    public function run(SiteSettings $settings): void
    {
        $defaults = SiteSettings::defaults();
        $defaults['profil']['gambar_utama'] = '/images/contoh/ruang-baca.jpg';
        $defaults['profil']['gambar_kedua'] = '/images/contoh/buku-terbuka.jpg';

        foreach ($defaults as $group => $value) {
            $settings->put($group, $value);
        }
    }
}
