{{-- Paso 1: Búsqueda e Identificación --}}
<div class="space-y-6">
    <div>
        <h2 class="text-lg font-medium text-gray-900 mb-4">Búsqueda e Identificación de Cliente</h2>
        <p class="text-sm text-gray-600 mb-6">
            Busque un cliente existente por ID Xante, nombre, CURP o email. Si no existe, podrá crear uno nuevo.
        </p>
    </div>

    {{-- Barra de búsqueda --}}
    <div class="bg-gray-50 rounded-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            {{-- Tipo de búsqueda --}}
            <div>
                <label for="search_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Buscar por
                </label>
                <select wire:model="stepData.search_type" 
                        id="search_type"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="xante_id">ID Xante</option>
                    <option value="name">Nombre</option>
                    <option value="email">Email</option>
                    <option value="curp">CURP</option>
                </select>
            </div>

            {{-- Campo de búsqueda --}}
            <div class="md:col-span-2">
                <label for="search_term" class="block text-sm font-medium text-gray-700 mb-2">
                    Término de búsqueda
                </label>
                <input type="text" 
                       wire:model.debounce.500ms="stepData.search_term"
                       id="search_term"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="Ingrese el término de búsqueda..."
                       wire:keydown.enter="searchClient($event.target.value, stepData.search_type)">
            </div>

            {{-- Botón de búsqueda --}}
            <div class="flex items-end">
                <button wire:click="searchClient(stepData.search_term, stepData.search_type)" 
                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        wire:loading.attr="disabled"
                        wire:target="searchClient">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="searchClient">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" wire:loading wire:target="searchClient">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="searchClient">Buscar</span>
                    <span wire:loading wire:target="searchClient">Buscando...</span>
                </button>
            </div>
        </div>

        {{-- Búsquedas rápidas --}}
        <div class="flex flex-wrap gap-2">
            <span class="text-xs text-gray-500">Búsquedas rápidas:</span>
            <button wire:click="searchClient('', 'recent')" 
                    class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-200 text-gray-700 hover:bg-gray-300">
                Recientes
            </button>
            <button wire:click="searchClient('', 'incomplete')" 
                    class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-200 text-yellow-800 hover:bg-yellow-300">
                Expedientes Incompletos
            </button>
        </div>
    </div>

    {{-- Resultados de búsqueda --}}
    @if(isset($stepData['search_results']) && !empty($stepData['search_results']))
        <div class="bg-white border border-gray-200 rounded-lg">
            <div class="px-4 py-3 border-b border-gray-200">
                <h3 class="text-sm font-medium text-gray-900">
                    Resultados de búsqueda ({{ count($stepData['search_results']) }})
                </h3>
            </div>
            <div class="divide-y divide-gray-200">
                @foreach($stepData['search_results'] as $client)
                    <div class="px-4 py-4 hover:bg-gray-50 cursor-pointer" 
                         wire:click="selectClient('{{ $client['xante_id'] }}')">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $client['name'] }}</div>
                                        <div class="text-sm text-gray-500">ID Xante: {{ $client['xante_id'] }}</div>
                                        @if($client['email'])
                                            <div class="text-sm text-gray-500">{{ $client['email'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if(isset($client['latest_agreement_status']))
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @switch($client['latest_agreement_status'])
                                            @case('sin_convenio') bg-gray-100 text-gray-800 @break
                                            @case('expediente_incompleto') bg-yellow-100 text-yellow-800 @break
                                            @case('expediente_completo') bg-green-100 text-green-800 @break
                                            @case('convenio_proceso') bg-blue-100 text-blue-800 @break
                                            @case('convenio_firmado') bg-green-100 text-green-800 @break
                                            @default bg-gray-100 text-gray-800
                                        @endswitch">
                                        {{ ucfirst(str_replace('_', ' ', $client['latest_agreement_status'])) }}
                                    </span>
                                @endif
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Cliente seleccionado --}}
    @if(isset($stepData['client_found']) && $stepData['client_found'])
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800">Cliente seleccionado</h3>
                    <div class="mt-1 text-sm text-green-700">
                        <p>ID Xante: {{ $stepData['client_xante_id'] ?? 'N/A' }}</p>
                        @if(isset($stepData['client_name']))
                            <p>Nombre: {{ $stepData['client_name'] }}</p>
                        @endif
                    </div>
                </div>
                <div class="ml-auto">
                    <button wire:click="$set('stepData.client_found', false)" 
                            class="text-green-600 hover:text-green-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Crear nuevo cliente --}}
    @if(!isset($stepData['client_found']) || !$stepData['client_found'])
        <div class="border-t border-gray-200 pt-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-900">¿No encontró el cliente?</h3>
                <button wire:click="$toggle('stepData.show_create_form')" 
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Crear Nuevo Cliente
                </button>
            </div>

            @if(isset($stepData['show_create_form']) && $stepData['show_create_form'])
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="new_client_xante_id" class="block text-sm font-medium text-gray-700 mb-1">
                                ID Xante *
                            </label>
                            <input type="text" 
                                   wire:model="stepData.new_client.xante_id"
                                   id="new_client_xante_id"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                   placeholder="Ej: XAN001">
                        </div>

                        <div>
                            <label for="new_client_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre Completo *
                            </label>
                            <input type="text" 
                                   wire:model="stepData.new_client.name"
                                   id="new_client_name"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                   placeholder="Nombre completo del cliente">
                        </div>

                        <div>
                            <label for="new_client_email" class="block text-sm font-medium text-gray-700 mb-1">
                                Email *
                            </label>
                            <input type="email" 
                                   wire:model="stepData.new_client.email"
                                   id="new_client_email"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                   placeholder="cliente@email.com">
                        </div>

                        <div>
                            <label for="new_client_phone" class="block text-sm font-medium text-gray-700 mb-1">
                                Teléfono *
                            </label>
                            <input type="tel" 
                                   wire:model="stepData.new_client.phone"
                                   id="new_client_phone"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                   placeholder="55 1234 5678">
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end space-x-3">
                        <button wire:click="$set('stepData.show_create_form', false)" 
                                class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancelar
                        </button>
                        <button wire:click="createNewClient" 
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                wire:loading.attr="disabled"
                                wire:target="createNewClient">
                            <span wire:loading.remove wire:target="createNewClient">Crear Cliente</span>
                            <span wire:loading wire:target="createNewClient">Creando...</span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Integración con sistemas externos --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Integración con sistemas externos</h3>
                <div class="mt-1 text-sm text-blue-700">
                    <p>La búsqueda también incluye datos de HubSpot y Data Lake cuando estén disponibles.</p>
                </div>
                <div class="mt-3 flex space-x-3">
                    <button class="text-xs bg-blue-200 text-blue-800 px-2 py-1 rounded">
                        🔄 Sincronizar HubSpot
                    </button>
                    <button class="text-xs bg-blue-200 text-blue-800 px-2 py-1 rounded">
                        📊 Consultar Data Lake
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
