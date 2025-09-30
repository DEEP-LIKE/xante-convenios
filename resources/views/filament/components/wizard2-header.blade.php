{{-- Header del Wizard 2 - Gestión de Documentos --}}
<div class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 rounded-2xl shadow-2xl mb-6">
    {{-- Patrón decorativo de fondo --}}
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-xl"></div>
        <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-white/5 rounded-full blur-xl"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-60 h-60 bg-white/5 rounded-full blur-2xl"></div>
    </div>
    
    {{-- Contenido principal --}}
    <div class="relative z-10 p-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-6 lg:space-y-0">
            {{-- Información del convenio --}}
            <div class="flex-1">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl ring-8 ring-white/10">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white">Gestión de Documentos</h1>
                        <p class="text-blue-100 text-lg">Convenio #{{ $agreement->id }} - {{ $agreement->client->name ?? 'Cliente' }}</p>
                    </div>
                </div>
                
                {{-- Información del cliente --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-green-500/20 rounded-lg">
                                <svg class="w-5 h-5 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-blue-200">Cliente</p>
                                <p class="font-semibold text-white">{{ $agreement->client->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-blue-500/20 rounded-lg">
                                <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-blue-200">Email</p>
                                <p class="font-semibold text-white text-sm">{{ $agreement->client->email ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-purple-500/20 rounded-lg">
                                <svg class="w-5 h-5 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-blue-200">Estado</p>
                                <p class="font-semibold text-white">{{ ucfirst(str_replace('_', ' ', $agreement->status)) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Indicador de progreso --}}
            <div class="lg:ml-8">
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                    <div class="text-center">
                        <div class="mb-4">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-white/20 rounded-full mb-3">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <p class="text-sm text-blue-200 mb-2">Progreso del Wizard 2</p>
                            <div class="w-full bg-white/20 rounded-full h-3 mb-2">
                                @php
                                    $currentStep = match($agreement->status) {
                                        'documents_generating', 'documents_generated' => 1,
                                        'documents_sent' => 2,
                                        'documents_received', 'documents_validated', 'completed' => 3,
                                        default => 1
                                    };
                                    $progress = ($currentStep / 3) * 100;
                                @endphp
                                <div class="bg-white h-3 rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                            </div>
                            <p class="text-white font-bold">{{ number_format($progress, 0) }}% Completado</p>
                        </div>
                        <p class="text-xs text-blue-200">Paso {{ $currentStep }} de 3</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Advertencia de no retorno --}}
@if(!($agreement->can_return_to_wizard1 ?? false))
<div class="bg-amber-50 border-l-4 border-amber-400 p-4 mb-6 rounded-r-lg">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm text-amber-700">
                <strong>⚠️ Proceso Irreversible:</strong> Una vez que los documentos han sido generados, no es posible regresar al Wizard de captura de información. Asegúrese de que toda la información sea correcta antes de proceder.
            </p>
        </div>
    </div>
</div>
@endif
