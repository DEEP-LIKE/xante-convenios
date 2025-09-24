<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    {{-- Header del Wizard --}}
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        @if($agreementId)
                            Convenio #{{ $agreementId }}
                        @else
                            Nuevo Convenio
                        @endif
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Paso {{ $currentStep }} de 6: {{ $steps[$currentStep] ?? 'Paso Desconocido' }}
                    </p>
                </div>
                
                <div class="flex items-center space-x-4">
                    {{-- Progreso general --}}
                    <div class="flex items-center">
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                                 style="width: {{ $completionPercentage }}%"></div>
                        </div>
                        <span class="ml-2 text-sm font-medium text-gray-700">{{ $completionPercentage }}%</span>
                    </div>
                    
                    {{-- Botón guardar y salir --}}
                    <button wire:click="saveAndExit" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Guardar y Salir
                    </button>
                </div>
            </div>
        </div>
        
        {{-- Navegación de pasos --}}
        <div class="px-6 py-4">
            <nav class="flex space-x-8" aria-label="Progress">
                @foreach($steps as $stepNumber => $stepName)
                    <div class="flex items-center">
                        @if($stepNumber < $currentStep || ($wizardSummary['steps'][$stepNumber]['is_completed'] ?? false))
                            {{-- Paso completado --}}
                            <div class="flex items-center text-sm font-medium text-green-600">
                                <span class="flex items-center justify-center w-8 h-8 bg-green-600 rounded-full text-white text-xs">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </span>
                                <span class="ml-2">{{ $stepName }}</span>
                            </div>
                        @elseif($stepNumber == $currentStep)
                            {{-- Paso actual --}}
                            <div class="flex items-center text-sm font-medium text-blue-600">
                                <span class="flex items-center justify-center w-8 h-8 bg-blue-600 rounded-full text-white text-xs">
                                    {{ $stepNumber }}
                                </span>
                                <span class="ml-2">{{ $stepName }}</span>
                            </div>
                        @elseif($canAccessStep[$stepNumber] ?? false)
                            {{-- Paso accesible --}}
                            <button wire:click="goToStep({{ $stepNumber }})" 
                                    class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                                <span class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-full text-gray-600 text-xs hover:bg-gray-300">
                                    {{ $stepNumber }}
                                </span>
                                <span class="ml-2">{{ $stepName }}</span>
                            </button>
                        @else
                            {{-- Paso no accesible --}}
                            <div class="flex items-center text-sm font-medium text-gray-400">
                                <span class="flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full text-gray-400 text-xs">
                                    {{ $stepNumber }}
                                </span>
                                <span class="ml-2">{{ $stepName }}</span>
                            </div>
                        @endif
                        
                        @if(!$loop->last)
                            <div class="ml-8 w-8 border-t border-gray-300"></div>
                        @endif
                    </div>
                @endforeach
            </nav>
        </div>
    </div>

    {{-- Contenido del paso actual --}}
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-6">
            {{-- Mostrar errores de validación --}}
            @if(!empty($validationErrors))
                <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Errores de validación</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    @foreach($validationErrors as $field => $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Contenido específico de cada paso --}}
            @switch($currentStep)
                @case(1)
                    @include('livewire.wizard-steps.step-1-search')
                    @break
                @case(2)
                    @include('livewire.wizard-steps.step-2-client-data')
                    @break
                @case(3)
                    @include('livewire.wizard-steps.step-3-spouse-data')
                    @break
                @case(4)
                    @include('livewire.wizard-steps.step-4-property-data')
                    @break
                @case(5)
                    @include('livewire.wizard-steps.step-5-calculator')
                    @break
                @case(6)
                    @include('livewire.wizard-steps.step-6-documents')
                    @break
                @default
                    <div class="text-center py-12">
                        <p class="text-gray-500">Paso no encontrado</p>
                    </div>
            @endswitch
        </div>

        {{-- Navegación inferior --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
            <div>
                @if($currentStep > 1)
                    <button wire:click="previousStep" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Anterior
                    </button>
                @endif
            </div>

            <div class="flex space-x-3">
                @if($currentStep < 6)
                    <button wire:click="nextStep" 
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="nextStep">Siguiente</span>
                        <span wire:loading wire:target="nextStep">Guardando...</span>
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="nextStep">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                @else
                    <button wire:click="completeWizard" 
                            class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="completeWizard">Completar Convenio</span>
                        <span wire:loading wire:target="completeWizard">Completando...</span>
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="completeWizard">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Loading overlay --}}
    <div wire:loading.flex wire:target="nextStep,previousStep,goToStep,saveAndExit,completeWizard" 
         class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-4">
            <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-700">Procesando...</span>
        </div>
    </div>
</div>

{{-- Scripts para interactividad --}}
@push('scripts')
<script>
    // Escuchar eventos del wizard
    window.addEventListener('stepChanged', event => {
        console.log('Paso cambiado a:', event.detail);
        
        // Scroll al top cuando cambie de paso
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    window.addEventListener('searchResults', event => {
        console.log('Resultados de búsqueda:', event.detail);
    });

    window.addEventListener('clientSelected', event => {
        console.log('Cliente seleccionado:', event.detail);
    });

    window.addEventListener('calculationsUpdated', event => {
        console.log('Cálculos actualizados:', event.detail);
    });

    // Auto-save cada 30 segundos
    setInterval(() => {
        if (window.Livewire) {
            window.Livewire.emit('autoSave');
        }
    }, 30000);
</script>
@endpush
