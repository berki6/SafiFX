<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Production: only creates/updates the admin when ADMIN_PASSWORD is set.
     * Local/staging: seeds a known admin with is_admin = true.
     */
    public function run(): void
    {
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
                'email_verified_at' => now(),
            ],
        );
    }
}
