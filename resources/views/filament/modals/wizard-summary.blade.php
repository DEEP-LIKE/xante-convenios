<div class="space-y-6">
    {{-- Información general --}}
    <div class="bg-gray-50 rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h3 class="text-sm font-medium text-gray-900 mb-2">Información General</h3>
                <dl class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">ID:</dt>
                        <dd class="font-medium">#{{ $agreement->id }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Cliente:</dt>
                        <dd class="font-medium">{{ $agreement->client->name ?? 'Sin cliente' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Estado:</dt>
                        <dd>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                @switch($agreement->status)
                                    @case('sin_convenio') bg-gray-100 text-gray-800 @break
                                    @case('expediente_incompleto') bg-yellow-100 text-yellow-800 @break
                                    @case('expediente_completo') bg-green-100 text-green-800 @break
                                    @case('convenio_proceso') bg-blue-100 text-blue-800 @break
                                    @case('convenio_firmado') bg-green-100 text-green-800 @break
                                    @default bg-gray-100 text-gray-800
                                @endswitch">
                                {{ $agreement->getStatusLabelAttribute() }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Creado:</dt>
                        <dd class="font-medium">{{ $agreement->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-900 mb-2">Progreso del Wizard</h3>
                <div class="space-y-3">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-500">Progreso General</span>
                            <span class="font-medium">{{ $agreement->completion_percentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                                 style="width: {{ $agreement->completion_percentage }}%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <span class="text-sm text-gray-500">Paso Actual:</span>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Paso {{ $agreement->current_step }}: {{ $agreement->getCurrentStepName() }}
                            </span>

                            @if($agreement->validation_status === 'approved')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-2" title="Validación Aprobada">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Aprobado
                                </span>
                            @elseif($agreement->validation_status === 'pending')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 ml-2" title="En Revisión">
                                    <svg class="w-4 h-4 mr-1 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    En Revisión
                                </span>
                             @elseif($agreement->validation_status === 'with_observations')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 ml-2" title="Con Observaciones">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    Con Observaciones
                                </span>
                            @elseif($agreement->validation_status === 'rejected')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 ml-2" title="Rechazado">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    Rechazado
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Progreso por pasos --}}
    <div>
        <h3 class="text-lg font-medium text-gray-900 mb-4">Progreso por Pasos</h3>
        <div class="space-y-3">
            @foreach($summary['steps'] as $stepNumber => $step)
                <div class="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg
                    {{ $step['is_completed'] ? 'bg-green-50 border-green-200' : ($step['can_access'] ? 'bg-blue-50 border-blue-200' : 'bg-gray-50') }}">
                    
                    <div class="flex-shrink-0">
                        @if($step['is_completed'])
                            <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        @elseif($stepNumber == $agreement->current_step)
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                {{ $stepNumber }}
                            </div>
                        @elseif($step['can_access'])
                            <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-gray-600 text-sm font-medium">
                                {{ $stepNumber }}
                            </div>
                        @else
                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center text-gray-400 text-sm font-medium">
                                {{ $stepNumber }}
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-medium text-gray-900">{{ $step['name'] }}</h4>
                            <div class="flex items-center space-x-2">
                                @if($step['is_completed'])
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Completado
                                    </span>
                                @elseif($stepNumber == $agreement->current_step)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        En Progreso
                                    </span>
                                @elseif($step['can_access'])
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Disponible
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-400">
                                        Bloqueado
                                    </span>
                                @endif
                                
                                <span class="text-xs text-gray-500">{{ $step['completion_percentage'] }}%</span>
                            </div>
                        </div>
                        
                        @if($step['last_saved_at'])
                            <p class="text-xs text-gray-500 mt-1">
                                Última actualización: {{ $step['last_saved_at']->format('d/m/Y H:i') }}
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Datos financieros (si están disponibles) --}}
    @if($agreement->valor_convenio)
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Resumen Financiero</h3>
            <div class="bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Precio Promoción:</dt>
                                <dd class="font-medium">${{ number_format($agreement->precio_promocion ?? 0, 2) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Valor Convenio:</dt>
                                <dd class="font-medium">${{ number_format($agreement->valor_convenio, 2) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Comisión Total:</dt>
                                <dd class="font-medium">${{ number_format($agreement->comision_total_pagar ?? 0, 2) }}</dd>
                            </div>
                        </dl>
                    </div>
                    
                    <div>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-600">ISR:</dt>
                                <dd class="font-medium">${{ number_format($agreement->isr ?? 0, 2) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Gastos FI:</dt>
                                <dd class="font-medium">${{ number_format($agreement->total_gastos_fi ?? 0, 2) }}</dd>
                            </div>
                            <div class="flex justify-between border-t pt-2">
                                <dt class="text-gray-900 font-medium">Ganancia Final:</dt>
                                <dd class="font-bold {{ ($agreement->ganancia_final ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ${{ number_format($agreement->ganancia_final ?? 0, 2) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Acciones rápidas --}}
    <div class="flex justify-end space-x-3 pt-4 border-t">
        <a href="{{ route('agreement-wizard', ['agreementId' => $agreement->id]) }}" 
           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5l7 7-7 7M5 5l7 7-7 7"></path>
            </svg>
            Continuar Wizard
        </a>
    </div>
</div>
