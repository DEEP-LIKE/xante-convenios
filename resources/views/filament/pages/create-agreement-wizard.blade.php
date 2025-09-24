<x-filament-panels::page>
    <div class="wizard-container max-w-4xl mx-auto">
        <!-- Barra de progreso -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                @for($i = 1; $i <= 4; $i++)
                    <div class="flex items-center {{ $i < 4 ? 'flex-1' : '' }}">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $currentStep >= $i ? 'bg-primary-600 text-white' : 'bg-gray-300 text-gray-600' }}">
                            {{ $i }}
                        </div>
                        @if($i < 4)
                            <div class="flex-1 h-1 mx-2 {{ $currentStep > $i ? 'bg-primary-600' : 'bg-gray-300' }}"></div>
                        @endif
                    </div>
                @endfor
            </div>
            
            @php $stepInfo = $this->getCurrentStepInfo(); @endphp
            <div class="text-center">
                <h2 class="text-xl font-semibold text-gray-900">{{ $stepInfo['title'] }}</h2>
                <p class="text-sm text-gray-600">{{ $stepInfo['description'] }}</p>
            </div>
        </div>

        <!-- Contenido del paso actual -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            @if($currentStep == 1)
                <div class="space-y-4">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            wire:model="data.search_term"
                            placeholder="Buscar cliente (ID Xante, nombre, email)"
                        />
                    </x-filament::input.wrapper>
                    
                    <x-filament::button wire:click="searchClient" color="primary">
                        Buscar Cliente
                    </x-filament::button>
                </div>
            @elseif($currentStep == 2)
                <div class="grid grid-cols-2 gap-4">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            wire:model="data.client_name"
                            placeholder="Nombre completo"
                        />
                    </x-filament::input.wrapper>
                    
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="email"
                            wire:model="data.client_email"
                            placeholder="Email"
                        />
                    </x-filament::input.wrapper>
                </div>
            @elseif($currentStep == 3)
                <div class="grid grid-cols-2 gap-4">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="number"
                            wire:model.live="data.valor_convenio"
                            placeholder="Valor Convenio"
                        />
                    </x-filament::input.wrapper>
                    
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="number"
                            wire:model="data.porcentaje_comision_sin_iva"
                            placeholder="% Comisión"
                        />
                    </x-filament::input.wrapper>
                </div>
            @elseif($currentStep == 4)
                <div class="space-y-4">
                    <h3 class="text-lg font-medium">Documentos Requeridos</h3>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="data.doc_identificacion" class="mr-2">
                            Identificación oficial
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="data.doc_comprobante_ingresos" class="mr-2">
                            Comprobante de ingresos
                        </label>
                    </div>
                </div>
            @endif
        </div>

        <!-- Botones de navegación -->
        <div class="flex justify-between">
            <x-filament::button 
                wire:click="previousStep" 
                color="gray"
                :disabled="$currentStep <= 1"
            >
                Anterior
            </x-filament::button>
            
            @if($currentStep < 4)
                <x-filament::button wire:click="nextStep" color="primary">
                    Siguiente
                </x-filament::button>
            @else
                <x-filament::button wire:click="submit" color="success">
                    Finalizar Convenio
                </x-filament::button>
            @endif
        </div>
    </div>
</x-filament-panels::page>
