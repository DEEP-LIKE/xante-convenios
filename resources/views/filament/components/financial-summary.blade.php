<div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 shadow-sm mb-6">
    <div class="flex items-center gap-2 mb-6">
        <div class="p-1.5 bg-yellow-100 rounded-full text-yellow-700">
            <x-heroicon-o-currency-dollar class="w-5 h-5" />
        </div>
        <div>
            <h3 class="text-lg font-bold text-yellow-900 leading-none">Resumen Financiero</h3>
            <p class="text-sm text-yellow-700 mt-1">Valores financieros actuales del convenio</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div>
            <span class="block text-sm font-medium text-gray-500 mb-1">Valor Convenio</span>
            <span class="block text-xl font-bold text-gray-900">$ {{ number_format($agreement->currentFinancials['agreement_value'], 2) }}</span>
        </div>
        
        <div>
            <span class="block text-sm font-medium text-gray-500 mb-1">Precio Promoción</span>
            <span class="block text-xl font-bold text-gray-900">$ {{ number_format($agreement->currentFinancials['proposal_value'], 2) }}</span>
        </div>

        <div>
            <span class="block text-sm font-medium text-gray-500 mb-1">Comisión Total</span>
            <span class="block text-xl font-bold text-gray-900">$ {{ number_format($agreement->currentFinancials['commission_total'], 2) }}</span>
        </div>

        <div>
            <span class="block text-sm font-medium text-gray-500 mb-1">Ganancia Final</span>
            <span class="block text-xl font-bold text-green-600">$ {{ number_format($agreement->currentFinancials['final_profit'], 2) }}</span>
        </div>
    </div>

    @if($agreement->currentFinancials['is_recalculated'])
        <div class="mt-4 pt-4 border-t border-yellow-200 flex items-center gap-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                Recálculo #{{ $agreement->currentFinancials['recalculation_number'] }}
            </span>
            <span class="text-xs text-gray-500">
                Actualizado: {{ $agreement->currentFinancials['recalculation_date']->timezone('America/Mexico_City')->format('d/m/Y H:i') }}
            </span>
            <span class="text-xs text-gray-500 border-l border-gray-300 pl-2 ml-1">
                 Por: {{ $agreement->currentFinancials['user']->name ?? 'Usuario' }}
            </span>
        </div>
    @endif
</div>
