<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::livewire('send', 'pages::⚡send-money')->name('transfer.send');
Route::livewire('track', 'pages::⚡track')->name('transfer.track');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
