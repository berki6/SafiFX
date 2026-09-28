<?php

// Hidden: no customer accounts in the SafiFX MVP, staff manage everything via
// /admin (Filament) instead of a customer profile/security/appearance area.
// Uncomment to bring back /settings/*.
//
// use Illuminate\Support\Facades\Route;
//
// Route::middleware(['auth'])->group(function () {
//     Route::redirect('settings', 'settings/profile');
//
//     Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
// });
//
// Route::middleware(['auth', 'verified'])->group(function () {
//     Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');
//
//     Route::livewire('settings/security', 'pages::settings.security')
//         ->middleware([
//             'password.confirm',
//         ])
//         ->name('security.edit');
// });
