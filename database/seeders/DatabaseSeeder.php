<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Reference data (countries, mobile-money networks, exchange rates) is
     * upsert-safe reference data, not secrets, so it is seeded unconditionally
     * in every environment, including production.
     *
     * Production: the admin user is only created/updated when ADMIN_PASSWORD is set.
     * Local/staging: a known admin is seeded with is_admin = true.
     */
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            MobileMoneyNetworkSeeder::class,
            ExchangeRateSeeder::class,
        ]);

        if (app()->environment('production')) {
            $this->seedProductionAdmin();

            return;
        }

        $this->seedLocalAdmin();
    }

    private function seedProductionAdmin(): void
    {
        $password = config('safifx.admin.password');

        if (! is_string($password) || $password === '') {
            return;
        }

        User::query()->updateOrCreate(
            ['email' => (string) config('safifx.admin.email')],
            [
                'name' => (string) config('safifx.admin.name'),
                'password' => $password,
                'is_admin' => true,
                'role' => UserRole::SuperAdmin,
                'email_verified_at' => now(),
            ],
        );
    }

    private function seedLocalAdmin(): void
    {
        User::query()->updateOrCreate(
            ['email' => (string) config('safifx.admin.email')],
            [
                'name' => (string) config('safifx.admin.name'),
                'password' => (string) (config('safifx.admin.password') ?: 'password'),
                'is_admin' => true,
                'role' => UserRole::SuperAdmin,
                'email_verified_at' => now(),
            ],
        );
    }
}
