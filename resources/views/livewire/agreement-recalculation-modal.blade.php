<div class="p-6 bg-white rounded-lg shadow-xl">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-800">
            Actualizar Calculadora Financiera
            <span class="ml-2 text-sm font-normal text-gray-500">Recálculo #{{ $recalculationNumber }}</span>
        </h2>

    </div>

    <!-- Calculadora -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <!-- Columna Izquierda: Inputs Editables -->
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">Valores Editables</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Valor Convenio</label>
                <div class="relative mt-1 rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input type="number" step="0.01" wire:model.live.debounce.500ms="valor_convenio" 
                           class="block w-full pl-7 pr-12 border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="0.00">
                </div>
                @error('valor_convenio') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- Aquí podrían ir otros campos editables si se requieren en el futuro (ISR, Cancelación, etc) -->
             <div class="p-4 bg-gray-50 rounded-md text-xs text-gray-500">
                <p><strong>Info:</strong> Los porcentajes de comisión e impuestos se toman de la configuración original del convenio/estado.</p>
                <ul class="list-disc ml-4 mt-1">
                    <li>Comisión Estatal: {{ $state_commission_percentage }}%</li>
                    <li>ISR: ${{ number_format($isr, 2) }}</li>
                    <li>Cancelación Hipoteca: ${{ number_format($cancelacion_hipoteca, 2) }}</li>
                    <li>Monto Crédito: ${{ number_format($monto_credito, 2) }}</li>
                </ul>
            </div>
        </div>

        <!-- Columna Derecha: Resultados Calculados -->
        <div class="space-y-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">Resultados (Automático)</h3>
            
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-600">Precio Promoción:</span>
                <span class="text-lg font-bold text-gray-900">${{ number_format($precio_promocion, 2) }}</span>
            </div>

            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-600">Comisión Total:</span>
                <span class="text-lg font-bold text-gray-900">${{ number_format($commission_total, 2) }}</span>
            </div>

            <hr class="border-gray-300">

            <div class="flex justify-between items-center bg-green-50 p-2 rounded border border-green-200">
                <span class="text-sm font-bold text-green-700">Ganancia Final:</span>
                <span class="text-xl font-black text-green-700">${{ number_format($final_profit, 2) }}</span>
            </div>
        </div>
    </div>

    <!-- Motivo -->
    <div class="mt-6">
        <label class="block text-sm font-medium text-gray-700">Motivo del recálculo <span class="text-red-500">*</span></label>
        <div class="mt-1">
            <textarea wire:model="motivo" rows="3" 
                      class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" 
                      placeholder="Describe el motivo de esta actualización..."></textarea>
        </div>
        @error('motivo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
    </div>

    <!-- Acciones -->
    <div class="mt-6 flex justify-end space-x-3">
        <button type="button" wire:click="$dispatch('close-modal', {id: 'recalculation-modal'})"
                class="px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Cancelar
        </button>
        <button type="button" wire:click="save" wire:loading.attr="disabled"
                class="px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
            <span wire:loading.remove>Guardar Actualización</span>
            <span wire:loading>Guardando...</span>
        </button>
    </div>
</div>
