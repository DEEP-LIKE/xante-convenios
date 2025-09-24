<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::redirect('/', '/admin');


Route::get('/', function () {
    return redirect('/admin');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');
    
    // ELIMINADO: Rutas de wizard público - Ahora todo es nativo de Filament 4
});

require __DIR__.'/auth.php';
