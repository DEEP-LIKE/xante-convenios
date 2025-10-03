<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\GeneratedDocument;
use App\Models\ClientDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecureDocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Servir documentos generados de forma segura
     */
    public function serveGeneratedDocument(GeneratedDocument $document): StreamedResponse
    {
        // Verificar que el usuario tiene acceso al convenio
        $agreement = $document->agreement;
        if (!$this->userCanAccessAgreement($agreement)) {
            abort(403, 'No tienes acceso a este documento');
        }

        // Verificar que el archivo existe
        if (!Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'Documento no encontrado');
        }

        // Servir el archivo con headers de seguridad
        return Storage::disk('private')->response($document->file_path, null, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($document->file_path) . '"',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Servir documentos del cliente de forma segura
     */
    public function serveClientDocument(ClientDocument $document): StreamedResponse
    {
        // Verificar que el usuario tiene acceso al convenio
        $agreement = $document->agreement;
        if (!$this->userCanAccessAgreement($agreement)) {
            abort(403, 'No tienes acceso a este documento');
        }

        // Verificar que el archivo existe
        if (!Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'Documento no encontrado');
        }

        // Determinar el tipo de contenido
        $mimeType = Storage::disk('private')->mimeType($document->file_path);
        
        // Servir el archivo con headers de seguridad
        return Storage::disk('private')->response($document->file_path, null, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $document->file_name . '"',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Verificar si el usuario puede acceder al convenio
     */
    private function userCanAccessAgreement(Agreement $agreement): bool
    {
        $user = Auth::user();
        
        // Aquí puedes implementar tu lógica de autorización
        // Por ejemplo:
        // - Solo el usuario que creó el convenio
        // - Solo usuarios con rol específico
        // - Solo usuarios del mismo equipo/empresa
        
        // Ejemplo básico: solo usuarios autenticados
        return $user !== null;
        
        // Ejemplo más restrictivo:
        // return $user && ($user->id === $agreement->created_by || $user->hasRole('admin'));
    }
}
