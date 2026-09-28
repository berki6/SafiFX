<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::livewire('send', 'pages::⚡send-money')->name('transfer.send');
Route::livewire('track', 'pages::⚡track')->name('transfer.track');

// Hidden: no customer accounts in the SafiFX MVP, staff use /admin. Uncomment to bring back /dashboard.
// Route::middleware(['auth', 'verified'])->group(function () {
//     Route::view('dashboard', 'dashboard')->name('dashboard');
// });

// Hidden: staff log in at /admin (Filament's own login), not here. These override
// Fortify's /login routes (verified: app routes win — see RouteCollection::addToCollections()).
// Uncomment to bring back the app-level /login page.
Route::get('/login', fn () => abort(404))->name('login');
Route::post('/login', fn () => abort(404));

require __DIR__.'/settings.php';
