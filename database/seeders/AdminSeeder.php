<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * The first admin account, from ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD (config/dispusip.php).
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => config('dispusip.admin.email')],
            [
                'name' => config('dispusip.admin.name'),
                'password' => config('dispusip.admin.password'),
                'email_verified_at' => now(),
            ],
        );
    }
}
