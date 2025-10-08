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
    
    // Rutas para el sistema de dos wizards
    // NOTA: La gestión de documentos ahora se maneja a través de ManageDocuments.php
    // Route::get('/admin/manage-agreement-documents/{agreement}', \App\Filament\Pages\ManageAgreementDocuments::class)
    //     ->name('manage-agreement-documents');
    
    // Rutas seguras para documentos
    Route::get('/secure/generated/{document}', [App\Http\Controllers\SecureDocumentController::class, 'serveGeneratedDocument'])
        ->name('secure.generated.document');
    
    Route::get('/secure/client/{document}', [App\Http\Controllers\SecureDocumentController::class, 'serveClientDocument'])
        ->name('secure.client.document');
    
    // Ruta para descargar documentos generados (mantener compatibilidad)
    Route::get('/documents/{document}/download', [App\Http\Controllers\DocumentDownloadController::class, 'download'])
        ->name('documents.download');
});

require __DIR__.'/auth.php';
