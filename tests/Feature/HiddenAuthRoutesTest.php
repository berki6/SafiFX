<?php

use App\Models\User;

// SafiFX has no customer accounts (docs/SAFIFX.md §24/§25) — only staff, who use
// /admin (Filament's own login), never these. Hidden via config/fortify.php and
// routes/web.php, not deleted. This test locks in that they stay 404 for anyone.

test('register does not exist, for guests or authenticated users', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();

    $this->actingAs(User::factory()->create());
    $this->get('/register')->assertNotFound();
});

test('dashboard does not exist, for guests or authenticated users', function () {
    $this->get('/dashboard')->assertNotFound();

    $this->actingAs(User::factory()->create());
    $this->get('/dashboard')->assertNotFound();
});

test('login does not exist, for guests or authenticated users', function () {
    $this->get('/login')->assertNotFound();
    $this->post('/login')->assertNotFound();

    $this->actingAs(User::factory()->create());
    $this->get('/login')->assertNotFound();
});

test('the homepage has no working link to register, dashboard, or login', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertDontSee('href="/register"', false);
    $response->assertDontSee('href="/dashboard"', false);
    $response->assertDontSee('href="/login"', false);
});

test('the homepage does not advertise the admin panel to guests', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertDontSee('href="/admin"', false);
});

test('the admin panel is unaffected', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->get('/admin/login')->assertOk();
    $this->actingAs($admin)->get('/admin')->assertOk();
});
