{{-- Paso 5: Validación del Convenio --}}
<div class="space-y-6">
    <div>
        <h2 class="text-lg font-medium text-gray-900 mb-4">Validación del Convenio</h2>
        <p class="text-sm text-gray-600 mb-6">
            Tu calculadora ha sido enviada a validación. El Coordinador FI revisará los cálculos antes de que puedas continuar.
        </p>
    </div>

    {{-- Estado: Pendiente de Validación --}}
    @if($agreement->validation_status === 'pending')
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-yellow-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-medium text-yellow-800">
                        ⏳ En Proceso de Validación
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p class="mb-2">
                            Tu calculadora está siendo revisada por el Coordinador FI. 
                            Recibirás una notificación cuando sea aprobada o si requiere cambios.
                        </p>
                        <div class="mt-4 flex items-center space-x-2">
                            <div class="flex-1 bg-yellow-200 rounded-full h-2">
                                <div class="bg-yellow-600 h-2 rounded-full animate-pulse" style="width: 50%"></div>
                            </div>
                            <span class="text-xs font-medium text-yellow-800">En revisión...</span>
                        </div>
                    </div>
                    
                    @if($agreement->currentValidation)
                        <div class="mt-4 p-3 bg-white rounded border border-yellow-200">
                            <p class="text-xs text-gray-600">
                                <strong>Solicitud enviada:</strong> {{ $agreement->currentValidation->created_at->format('d/m/Y H:i') }}<br>
                                <strong>Revisión:</strong> #{{ $agreement->currentValidation->revision_number }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Resumen de la calculadora enviada --}}
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="text-md font-medium text-gray-900 mb-4">📊 Calculadora Enviada a Validación</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 p-4 rounded">
                    <p class="text-xs text-gray-500 mb-1">Precio Promoción</p>
                    <p class="text-lg font-semibold text-gray-900">${{ number_format($agreement->precio_promocion ?? 0, 2) }}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded">
                    <p class="text-xs text-gray-500 mb-1">Valor Convenio</p>
                    <p class="text-lg font-semibold text-gray-900">${{ number_format($agreement->valor_convenio ?? 0, 2) }}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded">
                    <p class="text-xs text-gray-500 mb-1">% Comisión sin IVA</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($agreement->porcentaje_comision_sin_iva ?? 0, 2) }}%</p>
                </div>
                <div class="bg-green-50 p-4 rounded border border-green-200">
                    <p class="text-xs text-green-600 mb-1">Ganancia Final</p>
                    <p class="text-lg font-semibold text-green-700">${{ number_format($agreement->ganancia_final ?? 0, 2) }}</p>
                </div>
            </div>
        </div>

        {{-- Botón deshabilitado --}}
        <div class="bg-gray-50 border border-gray-300 rounded-lg p-6 text-center">
            <button disabled 
                    class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-400 bg-gray-100 cursor-not-allowed">
                <svg class="animate-spin h-5 w-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                En Espera de Aprobación del Coordinador FI
            </button>
            <p class="text-xs text-gray-500 mt-2">
                No puedes continuar hasta que el coordinador apruebe la calculadora
            </p>
        </div>
    @endif

    {{-- Estado: Con Observaciones --}}
    @if($agreement->validation_status === 'with_observations')
        <div class="bg-orange-50 border-l-4 border-orange-400 p-6 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-medium text-orange-800">
                        📝 Observaciones del Coordinador FI
                    </h3>
                    <div class="mt-2 text-sm text-orange-700">
                        <p class="mb-3">
                            El Coordinador FI ha revisado tu calculadora y solicita algunos cambios antes de aprobarla.
                        </p>
                        
                        @if($agreement->currentValidation)
                            <div class="mt-4 p-4 bg-white rounded border border-orange-200">
                                <p class="text-xs text-gray-600 mb-2">
                                    <strong>Revisado por:</strong> {{ $agreement->currentValidation->validatedBy->name ?? 'Coordinador FI' }}<br>
                                    <strong>Fecha:</strong> {{ $agreement->currentValidation->validated_at->format('d/m/Y H:i') }}
                                </p>
                                <div class="mt-3 p-3 bg-orange-50 rounded">
                                    <p class="text-sm font-medium text-orange-900 mb-2">Observaciones:</p>
                                    <p class="text-sm text-orange-800 whitespace-pre-wrap">{{ $agreement->currentValidation->observations }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    <div class="mt-4 flex space-x-3">
                        <button wire:click="goToStep(4)" 
                                class="inline-flex items-center px-4 py-2 border border-orange-300 rounded-md shadow-sm text-sm font-medium text-orange-700 bg-white hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Regresar a Calculadora
                        </button>
                        <button wire:click="resendToValidation" 
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Enviar a Revisión Nuevamente
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Resumen de la calculadora --}}
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="text-md font-medium text-gray-900 mb-4">📊 Calculadora Actual</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 p-4 rounded">
                    <p class="text-xs text-gray-500 mb-1">Precio Promoción</p>
                    <p class="text-lg font-semibold text-gray-900">${{ number_format($agreement->precio_promocion ?? 0, 2) }}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded">
                    <p class="text-xs text-gray-500 mb-1">Valor Convenio</p>
                    <p class="text-lg font-semibold text-gray-900">${{ number_format($agreement->valor_convenio ?? 0, 2) }}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded">
                    <p class="text-xs text-gray-500 mb-1">% Comisión sin IVA</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($agreement->porcentaje_comision_sin_iva ?? 0, 2) }}%</p>
                </div>
                <div class="bg-green-50 p-4 rounded border border-green-200">
                    <p class="text-xs text-green-600 mb-1">Ganancia Final</p>
                    <p class="text-lg font-semibold text-green-700">${{ number_format($agreement->ganancia_final ?? 0, 2) }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Estado: Aprobado --}}
    @if($agreement->validation_status === 'approved')
        <div class="bg-green-50 border-l-4 border-green-400 p-6 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-medium text-green-800">
                        ✓ Validación Aprobada
                    </h3>
                    <div class="mt-2 text-sm text-green-700">
                        <p class="mb-2">
                            ¡Excelente! Tu calculadora ha sido aprobada por el Coordinador FI. 
                            Ya puedes continuar con la generación de documentos.
                        </p>
                        
                        @if($agreement->currentValidation)
                            <div class="mt-4 p-3 bg-white rounded border border-green-200">
                                <p class="text-xs text-gray-600">
                                    <strong>Aprobado por:</strong> {{ $agreement->currentValidation->validatedBy->name ?? 'Coordinador FI' }}<br>
                                    <strong>Fecha de aprobación:</strong> {{ $agreement->currentValidation->validated_at->format('d/m/Y H:i') }}<br>
                                    <strong>Revisión:</strong> #{{ $agreement->currentValidation->revision_number }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Resumen de la calculadora aprobada --}}
        <div class="bg-white border border-green-200 rounded-lg p-6">
            <h3 class="text-md font-medium text-gray-900 mb-4">📊 Calculadora Aprobada</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-gray-50 p-4 rounded">
                    <p class="text-xs text-gray-500 mb-1">Precio Promoción</p>
                    <p class="text-lg font-semibold text-gray-900">${{ number_format($agreement->precio_promocion ?? 0, 2) }}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded">
                    <p class="text-xs text-gray-500 mb-1">Valor Convenio</p>
                    <p class="text-lg font-semibold text-gray-900">${{ number_format($agreement->valor_convenio ?? 0, 2) }}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded">
                    <p class="text-xs text-gray-500 mb-1">% Comisión sin IVA</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($agreement->porcentaje_comision_sin_iva ?? 0, 2) }}%</p>
                </div>
                <div class="bg-green-50 p-4 rounded border-2 border-green-300">
                    <p class="text-xs text-green-600 mb-1 font-medium">✓ Ganancia Final</p>
                    <p class="text-xl font-bold text-green-700">${{ number_format($agreement->ganancia_final ?? 0, 2) }}</p>
                </div>
            </div>
        </div>

        {{-- Botón habilitado para continuar --}}
        <div class="bg-gradient-to-r from-green-50 to-blue-50 border-2 border-green-300 rounded-lg p-6 text-center">
            <div class="flex items-center justify-center mb-4">
                <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h4 class="text-lg font-medium text-gray-900 mb-2">¡Listo para Continuar!</h4>
            <p class="text-sm text-gray-600 mb-4">
                Tu convenio ha sido validado y aprobado. Puedes proceder con la generación de documentos.
            </p>
            <button wire:click="nextStep" 
                    class="inline-flex items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Continuar y Generar Documentación
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    @endif

    {{-- Estado: Rechazado --}}
    @if($agreement->validation_status === 'rejected')
        <div class="bg-red-50 border-l-4 border-red-400 p-6 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-medium text-red-800">
                        ❌ Validación Rechazada
                    </h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p class="mb-3">
                            El Coordinador FI ha rechazado la calculadora. Por favor revisa el motivo y realiza los ajustes necesarios.
                        </p>
                        
                        @if($agreement->currentValidation)
                            <div class="mt-4 p-4 bg-white rounded border border-red-200">
                                <p class="text-xs text-gray-600 mb-2">
                                    <strong>Rechazado por:</strong> {{ $agreement->currentValidation->validatedBy->name ?? 'Coordinador FI' }}<br>
                                    <strong>Fecha:</strong> {{ $agreement->currentValidation->validated_at->format('d/m/Y H:i') }}
                                </p>
                                <div class="mt-3 p-3 bg-red-50 rounded">
                                    <p class="text-sm font-medium text-red-900 mb-2">Motivo del Rechazo:</p>
                                    <p class="text-sm text-red-800 whitespace-pre-wrap">{{ $agreement->currentValidation->observations }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    <div class="mt-4">
                        <button wire:click="goToStep(4)" 
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Regresar a Calculadora y Corregir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Estado por defecto (not_required) --}}
    @if($agreement->validation_status === 'not_required' || !$agreement->validation_status)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
            <svg class="w-16 h-16 text-blue-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Completa la Calculadora</h3>
            <p class="text-sm text-gray-600 mb-4">
                Primero debes completar la calculadora financiera en el paso anterior.
            </p>
            <button wire:click="goToStep(4)" 
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Ir a Calculadora
            </button>
        </div>
    @endif
</div>
