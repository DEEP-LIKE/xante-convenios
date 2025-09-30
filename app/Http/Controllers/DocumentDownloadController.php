<?php

namespace App\Http\Controllers;

use App\Models\GeneratedDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
}
