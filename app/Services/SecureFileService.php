<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class SecureFileService
{
    /**
     * Genera un nombre de archivo seguro y aleatorio
     */
    public static function generateSecureFileName(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $randomName = Str::random(40); // 40 caracteres aleatorios
        
        return $randomName . '.' . $extension;
    }

    /**
     * Genera un directorio aleatorio para mayor seguridad
     */
    public static function generateSecureDirectory(int $agreementId): string
    {
        $randomDir = Str::random(20);
        return "convenios/{$agreementId}/{$randomDir}";
    }

    /**
     * Almacena un archivo con nombre y directorio seguros
     */
    public static function storeSecurely($file, int $agreementId, string $category): array
    {
        $secureFileName = self::generateSecureFileName($file->getClientOriginalName());
        $secureDirectory = self::generateSecureDirectory($agreementId) . "/{$category}";
        
        $filePath = $file->storeAs($secureDirectory, $secureFileName, 'private');
        
        return [
            'file_path' => $filePath,
            'original_name' => $file->getClientOriginalName(),
            'secure_name' => $secureFileName,
            'directory' => $secureDirectory
        ];
    }
}
