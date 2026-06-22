<?php

use App\Livewire\Configuration;
use App\Livewire\Inbox;
use App\Livewire\Listings;
use App\Livewire\Login;
use App\Livewire\PostComposer;
use App\Livewire\Settings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/inbox');

// Auth
Route::get('/login', Login::class)->middleware('guest')->name('login');
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
})->middleware('auth')->name('logout');

// Halaman publik kebijakan privasi (dibutuhkan untuk menerbitkan app Meta).
Route::view('/privacy', 'privacy')->name('privacy');

// Aplikasi (butuh login)
Route::middleware('auth')->group(function () {
    Route::get('/inbox', Inbox::class)->name('inbox');
    Route::get('/settings', Settings::class)->name('settings');
    Route::get('/configuration', Configuration::class)->name('configuration');
    Route::get('/compose', PostComposer::class)->name('compose');
    Route::get('/listings', Listings::class)->name('listings');

    // Placeholder modul lain (dibangun bertahap)
    $placeholders = [
        'dashboard' => 'Dashboard',
        'pipeline' => 'Pipeline',
        'contacts' => 'Profil Kontak',
    ];
    foreach ($placeholders as $name => $title) {
        Route::get('/'.$name, fn () => view('placeholder', ['title' => $title]))->name($name);
    }
});
