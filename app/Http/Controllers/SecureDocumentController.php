<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\ClientDocument;
use App\Models\GeneratedDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
        if (! $this->userCanAccessAgreement($agreement)) {
            abort(403, 'No tienes acceso a este documento');
        }

        // Verificar que el archivo existe
        if (! Storage::disk('s3')->exists($document->file_path)) {
            abort(404, 'Archivo no encontrado');
        }

        // Servir el archivo
        return Storage::disk('s3')->response($document->file_path, null, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($document->file_path).'"',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Servir documentos del cliente de forma segura
     */
    public function serveClientDocument(ClientDocument $document): StreamedResponse
    {
        // Verificar que el usuario tiene acceso al convenio
        $agreement = $document->agreement;
        if (! $this->userCanAccessAgreement($agreement)) {
            abort(403, 'No tienes acceso a este documento');
        }

        // Verificar que el archivo existe
        if (! Storage::disk('s3')->exists($document->file_path)) {
            abort(404, 'Archivo no encontrado');
        }

        // Obtener el tipo MIME
        $mimeType = Storage::disk('s3')->mimeType($document->file_path);

        // Determinar disposición (inline vs attachment)
        $disposition = request()->has('download') ? 'attachment' : 'inline';
        
        // Servir el archivo
        return Storage::disk('s3')->response($document->file_path, null, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => $disposition.'; filename="'.$document->file_name.'"',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Cache-Control' => 'private, max-age=3600',
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
