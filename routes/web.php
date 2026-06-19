<?php

use App\Livewire\Inbox;
use App\Livewire\Settings;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/inbox');

Route::get('/inbox', Inbox::class)->name('inbox');
Route::get('/settings', Settings::class)->name('settings');

// Placeholder modul lain (dibangun bertahap)
$placeholders = [
    'dashboard' => 'Dashboard',
    'pipeline' => 'Pipeline',
    'listings' => 'Listing Properti',
    'contacts' => 'Profil Kontak',
];
foreach ($placeholders as $name => $title) {
    Route::get('/'.$name, fn () => view('placeholder', ['title' => $title]))->name($name);
}
