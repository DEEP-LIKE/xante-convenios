<div class="space-y-6">
    {{-- Estadísticas generales --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Total Convenios</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">En Progreso</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['in_progress'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Completados</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['completed'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Tasa Completitud</p>
                    <p class="text-2xl font-semibold text-gray-900">
                        {{ $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100, 1) : 0 }}%
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Distribución por pasos --}}
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Distribución por Pasos</h3>
            <div class="space-y-3">
                @foreach($stats['by_step'] as $step => $count)
                    @php
                        $stepNames = [
                            1 => 'Búsqueda e Identificación',
                            2 => 'Datos del Cliente Titular',
                            3 => 'Datos del Cónyuge',
                            4 => 'Información de la Propiedad',
                            5 => 'Calculadora de Convenio',
                            6 => 'Documentación y Cierre'
                        ];
                        $percentage = $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0;
                    @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Paso {{ $step }}: {{ $stepNames[$step] }}</span>
                            <span class="font-medium">{{ $count }} ({{ round($percentage, 1) }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all duration-300 
                                {{ $step <= 2 ? 'bg-blue-600' : ($step <= 4 ? 'bg-yellow-500' : 'bg-green-600') }}" 
                                 style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Distribución por estado --}}
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Distribución por Estado</h3>
            <div class="space-y-3">
                @foreach($stats['by_status'] as $status => $count)
                    @php
                        $statusLabels = [
                            'sin_convenio' => 'Sin Convenio',
                            'expediente_incompleto' => 'Expediente Incompleto',
                            'expediente_completo' => 'Expediente Completo',
                            'convenio_proceso' => 'Convenio en Proceso',
                            'convenio_firmado' => 'Convenio Firmado'
                        ];
                        $statusColors = [
                            'sin_convenio' => 'bg-gray-400',
                            'expediente_incompleto' => 'bg-yellow-500',
                            'expediente_completo' => 'bg-green-500',
                            'convenio_proceso' => 'bg-blue-500',
                            'convenio_firmado' => 'bg-green-600'
                        ];
                        $percentage = $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0;
                    @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">{{ $statusLabels[$status] }}</span>
                            <span class="font-medium">{{ $count }} ({{ round($percentage, 1) }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all duration-300 {{ $statusColors[$status] }}" 
                                 style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Actividad reciente --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Actividad Reciente</h3>
        <div class="space-y-3">
            @forelse($stats['recent_activity'] as $agreement)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-xs font-medium text-blue-600">#{{ $agreement->id }}</span>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                {{ $agreement->client->name ?? 'Sin cliente asignado' }}
                            </p>
                            <p class="text-xs text-gray-500">
                                Paso {{ $agreement->current_step }} • {{ $agreement->getCurrentStepName() }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
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
                        <span class="text-xs text-gray-500">
                            {{ $agreement->updated_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center py-6">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">No hay actividad reciente</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Métricas de rendimiento --}}
    <div class="bg-gradient-to-r from-blue-50 to-green-50 border border-blue-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Métricas de Rendimiento</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">
                    {{ $stats['total'] > 0 ? round(($stats['in_progress'] / $stats['total']) * 100, 1) : 0 }}%
                </div>
                <div class="text-sm text-gray-600">Convenios en Progreso</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">
                    {{ $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100, 1) : 0 }}%
                </div>
                <div class="text-sm text-gray-600">Tasa de Completitud</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-600">
                    @php
                        $avgStep = $stats['total'] > 0 ? 
                            collect($stats['by_step'])->map(fn($count, $step) => $count * $step)->sum() / $stats['total'] : 0;
                    @endphp
                    {{ round($avgStep, 1) }}
                </div>
                <div class="text-sm text-gray-600">Paso Promedio</div>
            </div>
        </div>
    </div>
</div>
