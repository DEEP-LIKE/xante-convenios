{{-- Paso 4: Información de la Propiedad --}}
<div class="space-y-6">
    <div>
        <h2 class="text-lg font-medium text-gray-900 mb-4">Información de la Propiedad</h2>
        <p class="text-sm text-gray-600 mb-6">
            Complete los datos de la propiedad objeto del convenio. Los campos marcados con * son obligatorios.
        </p>
    </div>

    {{-- Búsqueda en Data Lake --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-blue-800">Búsqueda en Data Lake</h3>
            <button wire:click="searchInDataLake" 
                    class="inline-flex items-center px-3 py-2 border border-blue-300 shadow-sm text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Buscar Propiedad
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="text" 
                   wire:model="stepData.property_search_term"
                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                   placeholder="Dirección, lote, manzana...">
            <select wire:model="stepData.property_search_type" 
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <option value="address">Dirección</option>
                <option value="lot">Lote/Manzana</option>
                <option value="development">Desarrollo</option>
            </select>
        </div>
    </div>

    {{-- Datos básicos de la propiedad --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-md font-medium text-gray-900 mb-4">Datos Básicos de la Propiedad</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Domicilio de la vivienda --}}
            <div class="md:col-span-2">
                <label for="domicilio_vivienda" class="block text-sm font-medium text-gray-700 mb-2">
                    Domicilio de la Vivienda *
                </label>
                <textarea wire:model="stepData.domicilio_vivienda"
                          id="domicilio_vivienda"
                          rows="3"
                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                          placeholder="Dirección completa de la propiedad..."
                          required></textarea>
                @error('stepData.domicilio_vivienda') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Comunidad/Desarrollo --}}
            <div>
                <label for="comunidad" class="block text-sm font-medium text-gray-700 mb-2">
                    Comunidad/Desarrollo *
                </label>
                <input type="text" 
                       wire:model="stepData.comunidad"
                       id="comunidad"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="Nombre del desarrollo o comunidad"
                       required>
                @error('stepData.comunidad') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Tipo de vivienda --}}
            <div>
                <label for="tipo_vivienda" class="block text-sm font-medium text-gray-700 mb-2">
                    Tipo de Vivienda *
                </label>
                <select wire:model="stepData.tipo_vivienda" 
                        id="tipo_vivienda"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        required>
                    <option value="">Seleccione...</option>
                    <option value="casa">Casa</option>
                    <option value="departamento">Departamento</option>
                    <option value="condominio">Condominio</option>
                    <option value="townhouse">Townhouse</option>
                    <option value="duplex">Dúplex</option>
                    <option value="otro">Otro</option>
                </select>
                @error('stepData.tipo_vivienda') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Prototipo --}}
            <div>
                <label for="prototipo" class="block text-sm font-medium text-gray-700 mb-2">
                    Prototipo/Modelo
                </label>
                <input type="text" 
                       wire:model="stepData.prototipo"
                       id="prototipo"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="Modelo o prototipo de la vivienda">
                @error('stepData.prototipo') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Superficie del terreno --}}
            <div>
                <label for="superficie_terreno" class="block text-sm font-medium text-gray-700 mb-2">
                    Superficie del Terreno (m²)
                </label>
                <input type="number" 
                       wire:model="stepData.superficie_terreno"
                       id="superficie_terreno"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="0.00"
                       step="0.01">
                @error('stepData.superficie_terreno') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Superficie de construcción --}}
            <div>
                <label for="superficie_construccion" class="block text-sm font-medium text-gray-700 mb-2">
                    Superficie de Construcción (m²)
                </label>
                <input type="number" 
                       wire:model="stepData.superficie_construccion"
                       id="superficie_construccion"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="0.00"
                       step="0.01">
                @error('stepData.superficie_construccion') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>
        </div>
    </div>

    {{-- Régimen de propiedad --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-md font-medium text-gray-900 mb-4">Régimen de Propiedad</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Tipo de régimen --}}
            <div>
                <label for="regimen_propiedad" class="block text-sm font-medium text-gray-700 mb-2">
                    Tipo de Régimen *
                </label>
                <select wire:model="stepData.regimen_propiedad" 
                        id="regimen_propiedad"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        required>
                    <option value="">Seleccione...</option>
                    <option value="condominio">Condominio</option>
                    <option value="privada">Privada</option>
                    <option value="fraccionamiento">Fraccionamiento</option>
                    <option value="propiedad_individual">Propiedad Individual</option>
                </select>
                @error('stepData.regimen_propiedad') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Estado físico --}}
            <div>
                <label for="estado_fisico" class="block text-sm font-medium text-gray-700 mb-2">
                    Estado Físico de la Propiedad
                </label>
                <select wire:model="stepData.estado_fisico" 
                        id="estado_fisico"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="">Seleccione...</option>
                    <option value="nueva">Nueva</option>
                    <option value="excelente">Excelente</option>
                    <option value="bueno">Bueno</option>
                    <option value="regular">Regular</option>
                    <option value="requiere_reparaciones">Requiere Reparaciones</option>
                </select>
                @error('stepData.estado_fisico') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>
        </div>
    </div>

    {{-- Información de crédito --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-md font-medium text-gray-900 mb-4">Información del Crédito</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Monto del crédito --}}
            <div>
                <label for="monto_credito" class="block text-sm font-medium text-gray-700 mb-2">
                    Monto del Crédito *
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input type="number" 
                           wire:model="stepData.monto_credito"
                           id="monto_credito"
                           class="block w-full pl-7 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="0.00"
                           step="0.01"
                           required>
                </div>
                @error('stepData.monto_credito') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Tipo de crédito --}}
            <div>
                <label for="tipo_credito" class="block text-sm font-medium text-gray-700 mb-2">
                    Tipo de Crédito *
                </label>
                <select wire:model="stepData.tipo_credito" 
                        id="tipo_credito"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        required>
                    <option value="">Seleccione...</option>
                    <option value="BANCARIO">Bancario</option>
                    <option value="INFONAVIT">INFONAVIT</option>
                    <option value="FOVISSSTE">FOVISSSTE</option>
                    <option value="COFINAVIT">COFINAVIT</option>
                    <option value="OTRO">Otro</option>
                </select>
                @error('stepData.tipo_credito') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Banco (si aplica) --}}
            @if(($stepData['tipo_credito'] ?? '') === 'BANCARIO' || ($stepData['tipo_credito'] ?? '') === 'OTRO')
                <div class="md:col-span-2">
                    <label for="otro_banco" class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre del Banco/Institución
                    </label>
                    <input type="text" 
                           wire:model="stepData.otro_banco"
                           id="otro_banco"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="Nombre del banco o institución financiera">
                    @error('stepData.otro_banco') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                </div>
            @endif
        </div>
    </div>

    {{-- Documentación jurídica --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-md font-medium text-gray-900 mb-4">Documentación Jurídica Disponible</h3>
        
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach([
                    'escritura_publica' => 'Escritura Pública',
                    'certificado_libertad_gravamen' => 'Certificado de Libertad de Gravamen',
                    'avaluo_comercial' => 'Avalúo Comercial',
                    'planos_arquitectonicos' => 'Planos Arquitectónicos',
                    'licencia_construccion' => 'Licencia de Construcción',
                    'manifestacion_construccion' => 'Manifestación de Construcción'
                ] as $key => $label)
                    <div class="flex items-center">
                        <input type="checkbox" 
                               wire:model="stepData.documentos_disponibles.{{ $key }}"
                               id="doc_{{ $key }}"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="doc_{{ $key }}" class="ml-2 block text-sm text-gray-900">
                            {{ $label }}
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Observaciones --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-md font-medium text-gray-900 mb-4">Observaciones Adicionales</h3>
        
        <div>
            <label for="observaciones_propiedad" class="block text-sm font-medium text-gray-700 mb-2">
                Observaciones sobre la Propiedad
            </label>
            <textarea wire:model="stepData.observaciones_propiedad"
                      id="observaciones_propiedad"
                      rows="4"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                      placeholder="Cualquier información adicional relevante sobre la propiedad..."></textarea>
            @error('stepData.observaciones_propiedad') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>
    </div>

    {{-- Integración con sistemas externos --}}
    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800">Validaciones automáticas</h3>
                <div class="mt-1 text-sm text-green-700">
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Los datos se validan contra el Data Lake de propiedades</li>
                        <li>Se verifican comparables de mercado automáticamente</li>
                        <li>La información catastral se consulta cuando está disponible</li>
                        <li>Los precios se validan contra rangos de la zona</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
