<?php

use App\Models\User;

// SafiFX has no customer accounts (docs/SAFIFX.md §24/§25) — only staff, who manage
// everything via /admin (Filament), never a customer settings area. Hidden via
// routes/settings.php, not deleted. This locks in that /settings/* stays 404 for
// anyone, the same way HiddenAuthRoutesTest does for /register, /login, /dashboard.

test('settings pages do not exist, for guests or authenticated users', function () {
    $this->get('/settings')->assertNotFound();
    $this->get('/settings/profile')->assertNotFound();
    $this->get('/settings/security')->assertNotFound();
    $this->get('/settings/appearance')->assertNotFound();

    $this->actingAs(User::factory()->create());
    $this->get('/settings')->assertNotFound();
    $this->get('/settings/profile')->assertNotFound();
    $this->get('/settings/security')->assertNotFound();
    $this->get('/settings/appearance')->assertNotFound();
});

test('the homepage has no working link to settings', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertDontSee('href="/settings"', false);
});
