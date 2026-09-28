<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('loads safifx configuration correctly', function () {
    expect(config('safifx.admin.name'))->toBe('SafiFX Admin');
    expect(config('safifx.admin.email'))->toBe('admin@safifx.com');
    expect(config('safifx.contact.email'))->toBe('support@safifx.com');
});

test('seeds the admin user with admin privileges in local environment', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'admin@safifx.com')->first();

    expect($admin)->not->toBeNull();
    expect($admin->is_admin)->toBeTrue();
});

test('allows admin user to access the admin panel', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful();
});

test('denies non admin user from accessing the admin panel', function () {
    $user = User::factory()->create([
        'is_admin' => false,
    ]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});
