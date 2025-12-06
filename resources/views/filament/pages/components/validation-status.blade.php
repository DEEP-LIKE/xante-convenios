{{-- Vista de Estado de Validación para Filament Wizard --}}
@php
    $status = $validation_status ?? 'not_required';
@endphp

<div class="space-y-4">
    {{-- Estado: Pendiente --}}
    @if($status === 'pending')
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-yellow-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-yellow-800">
                        ⏳ En Proceso de Validación
                    </h3>
                    <p class="mt-2 text-sm text-yellow-700">
                        Tu calculadora está siendo revisada por el Coordinador FI. 
                        Recibirás una notificación cuando sea aprobada o si requiere cambios.
                    </p>
                    @if($current_validation)
                        <div class="mt-4 p-3 bg-white rounded border border-yellow-200">
                            <p class="text-xs text-gray-600">
                                <strong>Solicitud enviada:</strong> {{ $current_validation->created_at->format('d/m/Y H:i') }}<br>
                                <strong>Revisión:</strong> #{{ $current_validation->revision_number }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Estado: Aprobado --}}
    @if($status === 'approved')
        <div class="bg-green-50 border-l-4 border-green-400 p-6 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-green-800">
                        ✓ Validación Aprobada
                    </h3>
                    <p class="mt-2 text-sm text-green-700">
                        ¡Excelente! Tu calculadora ha sido aprobada por el Coordinador FI. 
                        Ya puedes continuar con la generación de documentos.
                    </p>
                    @if($current_validation)
                        <div class="mt-4 p-3 bg-white rounded border border-green-200">
                            <p class="text-xs text-gray-600">
                                <strong>Aprobado por:</strong> {{ $current_validation->validatedBy->name ?? 'Coordinador FI' }}<br>
                                <strong>Fecha:</strong> {{ $current_validation->validated_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Estado: Con Observaciones --}}
    @if($status === 'with_observations')
        <div class="bg-orange-50 border-l-4 border-orange-400 p-6 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-orange-800">
                        📝 Observaciones del Coordinador FI
                    </h3>
                    <p class="mt-2 text-sm text-orange-700">
                        El Coordinador FI ha revisado tu calculadora y solicita algunos cambios antes de aprobarla.
                    </p>
                    @if($current_validation)
                        <div class="mt-4 p-4 bg-white rounded border border-orange-200">
                            <p class="text-xs text-gray-600 mb-2">
                                <strong>Revisado por:</strong> {{ $current_validation->validatedBy->name ?? 'Coordinador FI' }}<br>
                                <strong>Fecha:</strong> {{ $current_validation->validated_at->format('d/m/Y H:i') }}
                            </p>
                            <div class="mt-3 p-3 bg-orange-50 rounded">
                                <p class="text-sm font-medium text-orange-900 mb-2">Observaciones:</p>
                                <p class="text-sm text-orange-800 whitespace-pre-wrap">{{ $current_validation->observations }}</p>
                            </div>
                        </div>
                    @endif
                    <div class="mt-4">
                        <p class="text-sm text-orange-700">
                            Por favor, regresa al paso anterior (Calculadora) para realizar los ajustes necesarios.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Estado: Rechazado --}}
    @if($status === 'rejected')
        <div class="bg-red-50 border-l-4 border-red-400 p-6 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-red-800">
                        ❌ Validación Rechazada
                    </h3>
                    <p class="mt-2 text-sm text-red-700">
                        El Coordinador FI ha rechazado la calculadora. Por favor revisa el motivo y realiza los ajustes necesarios.
                    </p>
                    @if($current_validation)
                        <div class="mt-4 p-4 bg-white rounded border border-red-200">
                            <p class="text-xs text-gray-600 mb-2">
                                <strong>Rechazado por:</strong> {{ $current_validation->validatedBy->name ?? 'Coordinador FI' }}<br>
                                <strong>Fecha:</strong> {{ $current_validation->validated_at->format('d/m/Y H:i') }}
                            </p>
                            <div class="mt-3 p-3 bg-red-50 rounded">
                                <p class="text-sm font-medium text-red-900 mb-2">Motivo del Rechazo:</p>
                                <p class="text-sm text-red-800 whitespace-pre-wrap">{{ $current_validation->observations }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Estado: No Requerido (default) --}}
    @if($status === 'not_required' || !$status)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
            <svg class="w-16 h-16 text-blue-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Completa la Calculadora</h3>
            <p class="text-sm text-gray-600">
                Primero debes completar la calculadora financiera en el paso anterior.
            </p>
        </div>
    @endif
</div>
