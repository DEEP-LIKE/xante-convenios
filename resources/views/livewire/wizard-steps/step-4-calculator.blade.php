{{-- Paso 5: Calculadora de Convenio --}}
<div class="space-y-6">
    <div>
        <h2 class="text-lg font-medium text-gray-900 mb-4">Calculadora Financiera del Convenio</h2>
        <p class="text-sm text-gray-600 mb-6">
            Configure los valores financieros del convenio. Los cálculos se actualizan automáticamente en tiempo real.
        </p>
    </div>

    {{-- Valores de entrada principales --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-md font-medium text-gray-900 mb-4">Valores de Entrada</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Precio promoción (CAMPO DE ENTRADA PRINCIPAL) --}}
            <div>
                <label for="precio_promocion" class="block text-sm font-medium text-gray-700 mb-2">
                    Precio Promoción *
                    <span class="text-xs text-blue-600">(Campo de entrada principal)</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input type="number" 
                           wire:model.live.debounce.500ms="stepData.precio_promocion"
                           wire:change="updateCalculations"
                           id="precio_promocion"
                           class="block w-full pl-7 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="0.00"
                           step="0.01"
                           required>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    El valor convenio se calculará automáticamente (× 1.09)
                </p>
                @error('stepData.precio_promocion') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Valor convenio (CALCULADO/EDITABLE) --}}
            <div>
                <label for="valor_convenio" class="block text-sm font-medium text-gray-700 mb-2">
                    Valor del Convenio *
                    <span class="text-xs text-green-600">(Calculado automáticamente)</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input type="number" 
                           wire:model.live.debounce.500ms="stepData.valor_convenio"
                           wire:change="updateCalculations"
                           id="valor_convenio"
                           class="block w-full pl-7 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-green-50"
                           placeholder="0.00"
                           step="0.01"
                           required>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    También puede editarse directamente
                </p>
                @error('stepData.valor_convenio') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Porcentaje de comisión --}}
            <div>
                <label for="porcentaje_comision_sin_iva" class="block text-sm font-medium text-gray-700 mb-2">
                    % Comisión (Sin IVA) *
                </label>
                <div class="relative">
                    <input type="number" 
                           wire:model.live.debounce.500ms="stepData.porcentaje_comision_sin_iva"
                           wire:change="updateCalculations"
                           id="porcentaje_comision_sin_iva"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="6.50"
                           step="0.01"
                           min="0"
                           max="100"
                           required>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">%</span>
                    </div>
                </div>
                @error('stepData.porcentaje_comision_sin_iva') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Monto de crédito --}}
            <div>
                <label for="monto_credito" class="block text-sm font-medium text-gray-700 mb-2">
                    Monto de Crédito
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input type="number" 
                           wire:model.live.debounce.500ms="stepData.monto_credito"
                           wire:change="updateCalculations"
                           id="monto_credito"
                           class="block w-full pl-7 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="800000.00"
                           step="0.01">
                </div>
                @error('stepData.monto_credito') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>
        </div>
    </div>

    {{-- Cálculos automáticos (solo lectura) --}}
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
        <h3 class="text-md font-medium text-gray-900 mb-4">
            Cálculos Automáticos
            <span class="text-xs text-gray-500 font-normal">(Campos calculados automáticamente)</span>
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- Monto comisión sin IVA --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Monto Comisión (Sin IVA)
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input type="text" 
                           value="{{ number_format($stepData['monto_comision_sin_iva'] ?? 0, 2) }}"
                           class="block w-full pl-7 rounded-md border-gray-300 bg-gray-100 shadow-sm sm:text-sm"
                           readonly>
                </div>
            </div>

            {{-- Comisión total por pagar --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Comisión Total (Con IVA)
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input type="text" 
                           value="{{ number_format($stepData['comision_total_pagar'] ?? 0, 2) }}"
                           class="block w-full pl-7 rounded-md border-gray-300 bg-gray-100 shadow-sm sm:text-sm"
                           readonly>
                </div>
            </div>

            {{-- Valor compraventa --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Valor CompraVenta
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input type="text" 
                           value="{{ number_format($stepData['valor_compraventa'] ?? 0, 2) }}"
                           class="block w-full pl-7 rounded-md border-gray-300 bg-gray-100 shadow-sm sm:text-sm"
                           readonly>
                </div>
            </div>
        </div>
    </div>

    {{-- Gastos y costos operativos --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-md font-medium text-gray-900 mb-4">Gastos y Costos Operativos</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- ISR --}}
            <div>
                <label for="isr" class="block text-sm font-medium text-gray-700 mb-2">
                    ISR
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input type="number" 
                           wire:model.live.debounce.500ms="stepData.isr"
                           wire:change="updateCalculations"
                           id="isr"
                           class="block w-full pl-7 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="0.00"
                           step="0.01">
                </div>
                @error('stepData.isr') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Cancelación de hipoteca --}}
            <div>
                <label for="cancelacion_hipoteca" class="block text-sm font-medium text-gray-700 mb-2">
                    Cancelación de Hipoteca
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input type="number" 
                           wire:model.live.debounce.500ms="stepData.cancelacion_hipoteca"
                           wire:change="updateCalculations"
                           id="cancelacion_hipoteca"
                           class="block w-full pl-7 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="20000.00"
                           step="0.01">
                </div>
                @error('stepData.cancelacion_hipoteca') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Total gastos FI (calculado) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Total Gastos FI
                    <span class="text-xs text-green-600">(Calculado)</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input type="text" 
                           value="{{ number_format($stepData['total_gastos_fi'] ?? 0, 2) }}"
                           class="block w-full pl-7 rounded-md border-gray-300 bg-green-50 shadow-sm sm:text-sm"
                           readonly>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    ISR + Cancelación de Hipoteca
                </p>
            </div>
        </div>
    </div>

    {{-- Ganancia final --}}
    <div class="bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Ganancia Final Estimada</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Ganancia Final (Estimada)
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 text-lg">$</span>
                    </div>
                    <input type="text" 
                           value="{{ number_format($stepData['ganancia_final'] ?? 0, 2) }}"
                           class="block w-full pl-8 text-lg font-semibold rounded-md border-gray-300 bg-white shadow-sm sm:text-lg {{ ($stepData['ganancia_final'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}"
                           readonly>
                </div>
                <p class="mt-1 text-xs text-gray-600">
                    Valor Convenio - Comisión Total - ISR - Cancelación - Monto Crédito
                </p>
            </div>

            {{-- Indicadores visuales --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Margen de ganancia:</span>
                    <span class="font-medium {{ ($stepData['ganancia_final'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        @if(($stepData['valor_convenio'] ?? 0) > 0)
                            {{ number_format((($stepData['ganancia_final'] ?? 0) / ($stepData['valor_convenio'] ?? 1)) * 100, 2) }}%
                        @else
                            0.00%
                        @endif
                    </span>
                </div>
                
                <div class="w-full bg-gray-200 rounded-full h-2">
                    @php
                        $percentage = ($stepData['valor_convenio'] ?? 0) > 0 ? (($stepData['ganancia_final'] ?? 0) / ($stepData['valor_convenio'] ?? 1)) * 100 : 0;
                        $percentage = max(0, min(100, $percentage));
                    @endphp
                    <div class="h-2 rounded-full {{ $percentage >= 20 ? 'bg-green-600' : ($percentage >= 10 ? 'bg-yellow-600' : 'bg-red-600') }}" 
                         style="width: {{ $percentage }}%"></div>
                </div>
                
                <div class="text-xs text-gray-500">
                    @if($percentage >= 20)
                        ✅ Margen saludable
                    @elseif($percentage >= 10)
                        ⚠️ Margen bajo
                    @else
                        ❌ Margen crítico
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Calculadora de escenarios --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-md font-medium text-gray-900">Calculadora de Escenarios</h3>
            <button wire:click="$toggle('stepData.show_scenarios')" 
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                ¿Qué pasaría si...?
            </button>
        </div>

        @if($stepData['show_scenarios'] ?? false)
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Escenario: Reducir comisión --}}
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-blue-900 mb-2">Reducir Comisión</h4>
                        <div class="space-y-2">
                            <input type="number" 
                                   wire:model.live="stepData.scenario_commission"
                                   class="block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="5.50"
                                   step="0.01">
                            <div class="text-xs text-blue-700">
                                Ganancia: ${{ number_format($this->calculateScenario('commission', $stepData['scenario_commission'] ?? 0), 2) }}
                            </div>
                        </div>
                    </div>

                    {{-- Escenario: Cambiar precio --}}
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-green-900 mb-2">Cambiar Precio</h4>
                        <div class="space-y-2">
                            <input type="number" 
                                   wire:model.live="stepData.scenario_price"
                                   class="block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                   placeholder="1500000"
                                   step="1000">
                            <div class="text-xs text-green-700">
                                Ganancia: ${{ number_format($this->calculateScenario('price', $stepData['scenario_price'] ?? 0), 2) }}
                            </div>
                        </div>
                    </div>

                    {{-- Escenario: Reducir gastos --}}
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-yellow-900 mb-2">Reducir Gastos</h4>
                        <div class="space-y-2">
                            <input type="number" 
                                   wire:model.live="stepData.scenario_expenses"
                                   class="block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500"
                                   placeholder="15000"
                                   step="1000">
                            <div class="text-xs text-yellow-700">
                                Ganancia: ${{ number_format($this->calculateScenario('expenses', $stepData['scenario_expenses'] ?? 0), 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Resumen de configuración --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Configuración de la Calculadora</h3>
                <div class="mt-1 text-sm text-blue-700">
                    <ul class="list-disc pl-5 space-y-1">
                        <li><strong>Flujo correcto:</strong> Precio Promoción → Valor Convenio (×1.09) → Cálculos</li>
                        <li><strong>IVA:</strong> Se aplica automáticamente (16%) a las comisiones</li>
                        <li><strong>Valores por defecto:</strong> Cargados desde configuración centralizada</li>
                        <li><strong>Actualización:</strong> Todos los cálculos se actualizan en tiempo real</li>
                    </ul>
                </div>
                <div class="mt-3">
                    <button wire:click="resetToDefaults" 
                            class="text-xs bg-blue-200 text-blue-800 px-2 py-1 rounded hover:bg-blue-300">
                        🔄 Restaurar Valores por Defecto
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Scripts para funcionalidad adicional --}}
@push('scripts')
<script>
    // Formatear números mientras se escriben
    document.addEventListener('input', function(e) {
        if (e.target.type === 'number' && e.target.step === '0.01') {
            // Formatear números monetarios
            let value = parseFloat(e.target.value);
            if (!isNaN(value)) {
                // Aquí se podría agregar formateo adicional si es necesario
            }
        }
    });

    // Validaciones en tiempo real
    document.addEventListener('livewire:load', function () {
        Livewire.on('calculationsUpdated', (calculations) => {
            console.log('Cálculos actualizados:', calculations);
            
            // Mostrar notificación si la ganancia es negativa
            if (calculations.ganancia_final < 0) {
                // Aquí se podría mostrar una alerta
                console.warn('Ganancia negativa detectada');
            }
        });
    });
</script>
@endpush
