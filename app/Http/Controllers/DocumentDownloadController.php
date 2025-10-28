<?php

namespace App\Http\Controllers;

use App\Models\GeneratedDocument;
use App\Models\Agreement;
use App\Mail\DocumentsReadyMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    public function download(GeneratedDocument $document): StreamedResponse
    {
        // Verificar que el archivo existe
        if (!$document->fileExists()) {
            abort(404, 'Documento no encontrado');
        }

        // Obtener el contenido del archivo
        $filePath = $document->file_path;
        $fileName = basename($filePath);

        return Storage::disk('private')->download($filePath, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function sendToClient(Agreement $agreement)
    {
        try {
            // Validar que existen documentos generados
            if ($agreement->generatedDocuments->isEmpty()) {
                return redirect()->back()->with('error', 'No hay documentos generados para enviar.');
            }

            // Obtener email del cliente
            $wizardData = $agreement->wizard_data ?? [];
            $clientEmail = $wizardData['holder_email'] ?? null;

            if (!$clientEmail && $agreement->client) {
                $clientEmail = $agreement->client->email;
            }

            if (!$clientEmail) {
                return redirect()->back()->with('error', 'El cliente no tiene un email registrado.');
            }

            // Validar que los archivos PDF existen físicamente
            $documentsWithFiles = $agreement->generatedDocuments->filter(function ($document) {
                return $document->fileExists();
            });

            if ($documentsWithFiles->isEmpty()) {
                return redirect()->back()->with('error', 'Los archivos PDF no se encontraron en el servidor.');
            }

            // Actualizar estado del convenio
            $agreement->update([
                'status' => 'documents_sent',
                'documents_sent_at' => now(),
            ]);

            // Obtener email del asesor (usuario autenticado)
            $advisorEmail = auth()->user()->email;
            $advisorName = auth()->user()->name ?? 'Asesor';

            // Enviar el correo al cliente con copia al asesor
            Mail::to($clientEmail)
                ->cc($advisorEmail)
                ->send(new DocumentsReadyMail($agreement));

            // Redirigir de vuelta con mensaje de éxito
            return redirect()->back()->with('success', "Documentos enviados exitosamente a {$clientEmail} y al asesor {$advisorName} ({$advisorEmail}). Ambos recibirán {$documentsWithFiles->count()} archivos PDF adjuntos.");

        } catch (\Exception $e) {
            // Log del error
            \Log::error('Error sending documents to client', [
                'agreement_id' => $agreement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Ocurrió un error al enviar los documentos. Por favor, inténtelo nuevamente.');
        }
    }
}
