{{-- Información del Cliente para Envío de Documentos --}}
<div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6">
    <div class="flex items-start space-x-4">
        {{-- Icono del cliente --}}
        <div class="flex-shrink-0">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
        </div>
        
        {{-- Información del cliente --}}
        <div class="flex-1">
            <h4 class="text-lg font-semibold text-gray-800 mb-3">📧 Información de Envío</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Datos del cliente --}}
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium text-gray-600">Cliente:</span>
                        <span class="text-sm text-gray-800 font-semibold">{{ $agreement->client->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium text-gray-600">Email:</span>
                        <span class="text-sm text-gray-800">
                            @if($agreement->client && $agreement->client->email)
                                <a href="mailto:{{ $agreement->client->email }}" 
                                   class="text-blue-600 hover:text-blue-800 underline">
                                    {{ $agreement->client->email }}
                                </a>
                            @else
                                <span class="text-red-500 font-medium">⚠️ Email no disponible</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium text-gray-600">Teléfono:</span>
                        <span class="text-sm text-gray-800">{{ $agreement->client->phone ?? 'N/A' }}</span>
                    </div>
                </div>
                
                {{-- Estadísticas de documentos --}}
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium text-gray-600">Documentos:</span>
                        <span class="text-sm text-gray-800 font-semibold">{{ $agreement->generatedDocuments->count() }} PDFs</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium text-gray-600">Tamaño Total:</span>
                        <span class="text-sm text-gray-800">
                            @php
                                $totalSize = $agreement->generatedDocuments->sum('file_size');
                                $units = ['B', 'KB', 'MB', 'GB'];
                                $bytes = $totalSize;
                                for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
                                    $bytes /= 1024;
                                }
                            @endphp
                            {{ round($bytes, 2) }} {{ $units[$i] }}
                        </span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium text-gray-600">Estado:</span>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            @if($agreement->status === 'documents_sent') bg-green-100 text-green-800
                            @else bg-blue-100 text-blue-800 @endif">
                            @if($agreement->status === 'documents_sent')
                                ✅ Enviados {{ $agreement->documents_sent_at ? $agreement->documents_sent_at->format('d/m/Y H:i') : '' }}
                            @else
                                📤 Listos para enviar
                            @endif
                        </span>
                    </div>
                </div>
            </div>
            
            {{-- Lista de documentos que se enviarán --}}
            @if($agreement->generatedDocuments->isNotEmpty())
            <div class="mt-4 p-3 bg-white rounded-lg border border-blue-100">
                <h5 class="text-sm font-medium text-gray-700 mb-2">📋 Documentos que se enviarán:</h5>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                    @foreach($agreement->generatedDocuments as $document)
                    <div class="flex items-center space-x-2 p-2 bg-gray-50 rounded">
                        <span class="text-green-500">✓</span>
                        <span class="text-gray-700">{{ $document->formatted_type }}</span>
                        <span class="text-gray-500">({{ $document->formatted_size }})</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            
            {{-- Mensaje informativo --}}
            <div class="mt-4 p-3 bg-blue-100 rounded-lg">
                <div class="flex items-start space-x-2">
                    <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-xs text-blue-700">
                        @if($agreement->status === 'documents_sent')
                            <strong>Documentos enviados exitosamente.</strong> El cliente ha recibido un correo con todos los PDFs adjuntos.
                        @else
                            <strong>¿Listo para enviar?</strong> Se enviará un correo electrónico al cliente con todos los documentos PDF adjuntos. 
                            Asegúrese de que la dirección de correo sea correcta.
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
