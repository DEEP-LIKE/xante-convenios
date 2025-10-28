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
    
    // Ruta para enviar documentos al cliente
    Route::get('/documents/send-to-client/{agreement}', [App\Http\Controllers\DocumentDownloadController::class, 'sendToClient'])
        ->name('documents.send-to-client');
    
    // Ruta para descargar checklist actualizado
    Route::get('/admin/download-updated-checklist/{agreement}', function ($agreementId) {
        $agreement = \App\Models\Agreement::findOrFail($agreementId);
        
        // Obtener documentos cargados del cliente
        $uploadedDocuments = \App\Models\ClientDocument::where('agreement_id', $agreement->id)
            ->pluck('document_type')
            ->toArray();

        // Generar PDF con datos actualizados usando el servicio
        $pdfService = app(\App\Services\PdfGenerationService::class);
        
        // Generar checklist con flag de actualización
        $pdf = $pdfService->generateChecklist(
            $agreement,
            $uploadedDocuments, // Lista de tipos de documentos ya cargados
            true // Flag: isUpdatedVersion
        );

        // Nombre del archivo con timestamp
        $fileName = 'checklist_actualizado_' . $agreement->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';

        // Descargar PDF
        return response()->streamDownload(
            fn() => print($pdf->output()),
            $fileName,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
            ]
        );
    })->name('download.updated.checklist');
});

require __DIR__.'/auth.php';
