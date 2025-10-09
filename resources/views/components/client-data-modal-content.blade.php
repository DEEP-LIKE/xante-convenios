@props(['agreement', 'data'])

@php
    // Helper function para formatear números de manera segura
    function formatCurrency($value) {
        if (is_null($value) || $value === '') {
            return '0.00';
        }
        
        // Remover comas y convertir a float
        $cleanValue = (float) str_replace(',', '', $value);
        return number_format($cleanValue, 2);
    }
@endphp

<div class="space-y-6">
    @if($agreement && $agreement->wizard_data)
        <!-- Información General -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Información General
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">ID Xante</label>
                    <p class="text-gray-900 dark:text-white font-semibold">{{ $data['xante_id'] ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Fecha de Registro</label>
                    <p class="text-gray-900 dark:text-white">{{ $agreement->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Estado del Convenio</label>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                        {{ $agreement->status_label }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Datos del Titular -->
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-800 px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Datos del Titular
                </h4>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Nombre Completo</label>
                        <p class="text-gray-900 dark:text-white font-semibold">{{ $data['holder_name'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">CURP</label>
                        <p class="text-gray-900 dark:text-white">{{ $data['holder_curp'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">RFC</label>
                        <p class="text-gray-900 dark:text-white">{{ $data['holder_rfc'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Email</label>
                        <p class="text-gray-900 dark:text-white">{{ $data['holder_email'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Teléfono</label>
                        <p class="text-gray-900 dark:text-white">{{ $data['holder_phone'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Estado Civil</label>
                        <p class="text-gray-900 dark:text-white">{{ $data['holder_civil_status'] ?? 'N/A' }}</p>
                    </div>
                    <div class="md:col-span-2 lg:col-span-3">
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Domicilio</label>
                        <p class="text-gray-900 dark:text-white">{{ $data['current_address'] ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Datos de la Propiedad -->
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-800 px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Datos de la Propiedad
                </h4>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Domicilio</label>
                        <p class="text-gray-900 dark:text-white">{{ $data['domicilio_convenio'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Comunidad</label>
                        <p class="text-gray-900 dark:text-white">{{ $data['comunidad'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Tipo de Vivienda</label>
                        <p class="text-gray-900 dark:text-white">{{ $data['tipo_vivienda'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Prototipo</label>
                        <p class="text-gray-900 dark:text-white">{{ $data['prototipo'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Lote</label>
                        <p class="text-gray-900 dark:text-white">{{ $data['lote'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Manzana</label>
                        <p class="text-gray-900 dark:text-white">{{ $data['manzana'] ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información Financiera -->
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-800 px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                    Información Financiera
                </h4>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Valor del Convenio</label>
                        <p class="text-gray-900 dark:text-white font-bold text-lg text-green-600">
                            ${{ formatCurrency($data['valor_convenio'] ?? 0) }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Monto de Crédito</label>
                        <p class="text-gray-900 dark:text-white font-semibold">
                            ${{ formatCurrency($data['monto_credito'] ?? 0) }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Ganancia Final</label>
                        <p class="text-gray-900 dark:text-white font-bold text-lg text-blue-600">
                            ${{ formatCurrency($data['ganancia_final'] ?? 0) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

    @else
        <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No hay datos disponibles</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No se encontraron datos del cliente para este convenio.</p>
        </div>
    @endif
</div>
