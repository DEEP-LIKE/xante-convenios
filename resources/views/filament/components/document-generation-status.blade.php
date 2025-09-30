{{-- Estado de Generación de Documentos --}}
<div class="text-center py-12">
    @if($isGenerating)
        {{-- Estado: Generando documentos --}}
        <div class="mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-100 rounded-full mb-4 animate-pulse">
                <svg class="w-10 h-10 text-blue-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-800 mb-2">⏳ Generando Documentos...</h3>
            <p class="text-gray-600 mb-6">Los documentos PDF se están creando en segundo plano. Este proceso puede tomar unos minutos.</p>
            
            {{-- Barra de progreso animada --}}
            <div class="max-w-md mx-auto mb-6">
                <div class="bg-gray-200 rounded-full h-3 mb-2">
                    <div class="bg-blue-600 h-3 rounded-full animate-pulse" style="width: 65%"></div>
                </div>
                <p class="text-sm text-gray-500">Procesando plantillas PDF...</p>
            </div>
            
            {{-- Lista de documentos esperados --}}
            <div class="bg-blue-50 rounded-xl p-6 max-w-lg mx-auto">
                <h4 class="font-semibold text-blue-800 mb-4">📄 Documentos que se están generando:</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between p-2 bg-white rounded-lg">
                        <span class="text-gray-700">📋 Acuerdo de Promoción Inmobiliaria</span>
                        <div class="w-4 h-4 bg-blue-200 rounded-full animate-pulse"></div>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-white rounded-lg">
                        <span class="text-gray-700">📊 Datos Generales - Fase I</span>
                        <div class="w-4 h-4 bg-blue-200 rounded-full animate-pulse"></div>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-white rounded-lg">
                        <span class="text-gray-700">✅ Checklist de Expediente Básico</span>
                        <div class="w-4 h-4 bg-blue-200 rounded-full animate-pulse"></div>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-white rounded-lg">
                        <span class="text-gray-700">🏠 Condiciones para Comercialización</span>
                        <div class="w-4 h-4 bg-blue-200 rounded-full animate-pulse"></div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Mensaje informativo --}}
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 max-w-lg mx-auto">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-amber-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-left">
                    <p class="text-sm text-amber-700">
                        <strong>💡 Tip:</strong> Puede cerrar esta página y regresar más tarde. 
                        Recibirá una notificación cuando los documentos estén listos.
                    </p>
                </div>
            </div>
        </div>
        
    @else
        {{-- Estado: Sin documentos (error o no generados) --}}
        <div class="mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-4">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-800 mb-2">📄 Sin Documentos Disponibles</h3>
            <p class="text-gray-600 mb-6">No se encontraron documentos generados para este convenio.</p>
            
            {{-- Información del estado actual --}}
            <div class="bg-gray-50 rounded-xl p-6 max-w-lg mx-auto mb-6">
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Estado del Convenio:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            @if($agreement->status === 'error_generating_documents') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ ucfirst(str_replace('_', ' ', $agreement->status)) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Última Actualización:</span>
                        <span class="text-gray-800">{{ $agreement->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
            
            {{-- Acciones sugeridas --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 max-w-lg mx-auto">
                <h4 class="font-semibold text-blue-800 mb-2">🔧 Acciones Disponibles:</h4>
                <div class="text-sm text-blue-700 space-y-1">
                    <p>• Use el botón "Recargar" para verificar si hay documentos nuevos</p>
                    <p>• Use "Regenerar Documentos" si hubo algún error en la generación</p>
                    <p>• Verifique que el convenio tenga todos los datos necesarios</p>
                </div>
            </div>
        </div>
    @endif
    
    {{-- Auto-refresh para estado de generación --}}
    @if($isGenerating)
    <script>
        // Auto-refresh cada 10 segundos cuando se están generando documentos
        setTimeout(function() {
            window.location.reload();
        }, 10000);
    </script>
    @endif
</div>
