<div class="space-y-4">
    @foreach($recalculations as $recalculation)
        <div class="bg-white border rounded-lg shadow-sm p-4 {{ $loop->first ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200' }}">
            <div class="flex justify-between items-start">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $loop->first ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800' }}">
                            Actualización #{{ $recalculation->recalculation_number }}
                        </span>
                        <span class="text-xs text-gray-500">
                            {{ $recalculation->created_at->timezone('America/Mexico_City')->format('d/m/Y H:i') }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-900 font-medium">
                        Por: {{ $recalculation->user->name ?? 'Sistema' }}
                    </p>
                    <p class="mt-1 text-sm text-gray-600">
                        <span class="font-medium">Motivo:</span> {{ $recalculation->motivo }}
                    </p>
                </div>
                
                <div class="text-right text-sm">
                    <div class="flex flex-col gap-1">
                        <div>
                            <span class="text-gray-500 text-xs">Valor Convenio:</span>
                            <span class="font-semibold text-gray-900">${{ number_format($recalculation->agreement_value, 2) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 text-xs">Precio Promoción:</span>
                            <span class="font-semibold text-gray-900">${{ number_format($recalculation->proposal_value, 2) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 text-xs">Ganancia Final:</span>
                            <span class="font-bold text-green-600">${{ number_format($recalculation->final_profit, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    {{-- Mostrar original si es necesario --}}
    @if($original)
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 opacity-75">
            <div class="flex justify-between items-start">
                <div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        Cálculo Original
                    </span>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ $original->created_at->timezone('America/Mexico_City')->format('d/m/Y') }}
                    </p>
                </div>
                <div class="text-right text-sm">
                    <div class="flex flex-col gap-1">
                         <div>
                            <span class="text-gray-500 text-xs">Valor Convenio:</span>
                            <span class="font-semibold text-gray-900">${{ number_format($original->agreement_value, 2) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 text-xs">Ganancia Final:</span>
                            <span class="font-bold text-green-600">${{ number_format($original->final_profit, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
