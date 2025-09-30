{{-- Resumen de Finalización del Convenio --}}
<div class="bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-2xl p-8 shadow-lg">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h2 class="text-3xl font-bold text-green-800 mb-2">¡Convenio Completado Exitosamente! 🎉</h2>
        <p class="text-green-600 text-lg">El proceso de gestión documental ha sido finalizado</p>
    </div>
    
    {{-- Resumen del proceso --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        {{-- Información del convenio --}}
        <div class="bg-white rounded-xl p-6 shadow-sm border border-green-100">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Información del Convenio
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">ID del Convenio:</span>
                    <span class="font-semibold text-gray-800">#{{ $agreement->id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Cliente:</span>
                    <span class="font-semibold text-gray-800">{{ $agreement->client->name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Fecha de Inicio:</span>
                    <span class="font-semibold text-gray-800">{{ $agreement->created_at->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Estado Final:</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        ✅ Completado
                    </span>
                </div>
            </div>
        </div>
        
        {{-- Estadísticas del proceso --}}
        <div class="bg-white rounded-xl p-6 shadow-sm border border-green-100">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <svg class="w-5 h-5 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2-2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Estadísticas del Proceso
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Documentos Generados:</span>
                    <span class="font-semibold text-gray-800">{{ $agreement->generatedDocuments->count() }} PDFs</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Tamaño Total:</span>
                    <span class="font-semibold text-gray-800">
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
                <div class="flex justify-between">
                    <span class="text-gray-600">Documentos Enviados:</span>
                    <span class="font-semibold text-gray-800">{{ $agreement->documents_sent_at ? $agreement->documents_sent_at->format('d/m/Y H:i') : 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Documentos Recibidos:</span>
                    <span class="font-semibold text-gray-800">{{ $agreement->documents_received_at ? $agreement->documents_received_at->format('d/m/Y H:i') : 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Timeline del proceso --}}
    <div class="bg-white rounded-xl p-6 shadow-sm border border-green-100">
        <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
            <svg class="w-5 h-5 text-indigo-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Timeline del Proceso
        </h3>
        
        <div class="flow-root">
            <ul class="-mb-8">
                {{-- Paso 1: Documentos generados --}}
                <li>
                    <div class="relative pb-8">
                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-green-200" aria-hidden="true"></span>
                        <div class="relative flex space-x-3">
                            <div>
                                <span class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </span>
                            </div>
                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                <div>
                                    <p class="text-sm text-gray-500">📄 <strong>Documentos Generados</strong></p>
                                    <p class="text-xs text-gray-400">Se generaron {{ $agreement->generatedDocuments->count() }} documentos PDF</p>
                                </div>
                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                    {{ $agreement->documents_generated_at ? $agreement->documents_generated_at->format('d/m/Y H:i') : 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                
                {{-- Paso 2: Documentos enviados --}}
                @if($agreement->documents_sent_at)
                <li>
                    <div class="relative pb-8">
                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-green-200" aria-hidden="true"></span>
                        <div class="relative flex space-x-3">
                            <div>
                                <span class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </span>
                            </div>
                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                <div>
                                    <p class="text-sm text-gray-500">📤 <strong>Documentos Enviados</strong></p>
                                    <p class="text-xs text-gray-400">Documentos enviados al cliente por correo</p>
                                </div>
                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                    {{ $agreement->documents_sent_at->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                @endif
                
                {{-- Paso 3: Documentos recibidos --}}
                @if($agreement->documents_received_at)
                <li>
                    <div class="relative">
                        <div class="relative flex space-x-3">
                            <div>
                                <span class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </span>
                            </div>
                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                <div>
                                    <p class="text-sm text-gray-500">🎉 <strong>Proceso Completado</strong></p>
                                    <p class="text-xs text-gray-400">Todos los documentos han sido procesados</p>
                                </div>
                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                    {{ $agreement->documents_received_at->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                @endif
            </ul>
        </div>
    </div>
    
    {{-- Mensaje de agradecimiento --}}
    <div class="text-center mt-8 p-6 bg-gradient-to-r from-green-100 to-emerald-100 rounded-xl border border-green-200">
        <p class="text-green-800 font-medium text-lg">
            🙏 ¡Gracias por utilizar nuestro sistema de gestión de convenios!
        </p>
        <p class="text-green-600 text-sm mt-2">
            El convenio ha sido procesado exitosamente y todos los documentos están disponibles para su consulta.
        </p>
    </div>
</div>
