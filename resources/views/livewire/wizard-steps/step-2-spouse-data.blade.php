{{-- Paso 3: Datos del Cónyuge/Coacreditado --}}
<div class="space-y-6">
    <div>
        <h2 class="text-lg font-medium text-gray-900 mb-4">Datos del Cónyuge/Coacreditado</h2>
        <p class="text-sm text-gray-600 mb-6">
            Complete la información del cónyuge o coacreditado. Puede omitir este paso si no aplica.
        </p>
    </div>

    {{-- Opciones de configuración --}}
    <div class="bg-gray-50 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-gray-900">Configuración del Cónyuge/Coacreditado</h3>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Incluir cónyuge --}}
            <div class="flex items-center">
                <input type="checkbox" 
                       wire:model="stepData.include_spouse"
                       id="include_spouse"
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="include_spouse" class="ml-2 block text-sm text-gray-900">
                    Incluir datos del cónyuge/coacreditado
                </label>
            </div>

            {{-- Mismo domicilio --}}
            @if($stepData['include_spouse'] ?? false)
                <div class="flex items-center">
                    <input type="checkbox" 
                           wire:model="stepData.spouse_same_address"
                           id="spouse_same_address"
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="spouse_same_address" class="ml-2 block text-sm text-gray-900">
                        Mismo domicilio que el titular
                    </label>
                </div>

                {{-- Botón copiar datos --}}
                <div>
                    <button wire:click="copyHolderData" 
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        Copiar datos del titular
                    </button>
                </div>
            @endif
        </div>

        {{-- Justificación si no incluye cónyuge --}}
        @if(!($stepData['include_spouse'] ?? false))
            <div class="mt-4">
                <label for="spouse_omit_reason" class="block text-sm font-medium text-gray-700 mb-2">
                    Razón para omitir datos del cónyuge (opcional)
                </label>
                <textarea wire:model="stepData.spouse_omit_reason"
                          id="spouse_omit_reason"
                          rows="2"
                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                          placeholder="Ej: Divorcio en proceso, cónyuge no disponible, etc."></textarea>
            </div>
        @endif
    </div>

    {{-- Formulario del cónyuge (solo si está habilitado) --}}
    @if($stepData['include_spouse'] ?? false)
        {{-- Información básica --}}
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="text-md font-medium text-gray-900 mb-4">Información Básica del Cónyuge</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Nombre completo --}}
                <div class="md:col-span-2">
                    <label for="spouse_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre Completo *
                    </label>
                    <input type="text" 
                           wire:model="stepData.spouse_name"
                           id="spouse_name"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="Nombre completo del cónyuge"
                           required>
                    @error('stepData.spouse_name') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                </div>

                {{-- Fecha de nacimiento --}}
                <div>
                    <label for="spouse_birthdate" class="block text-sm font-medium text-gray-700 mb-2">
                        Fecha de Nacimiento *
                    </label>
                    <input type="date" 
                           wire:model="stepData.spouse_birthdate"
                           id="spouse_birthdate"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           required>
                    @error('stepData.spouse_birthdate') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                </div>

                {{-- Estado civil --}}
                <div>
                    <label for="spouse_civil_status" class="block text-sm font-medium text-gray-700 mb-2">
                        Estado Civil *
                    </label>
                    <select wire:model="stepData.spouse_civil_status" 
                            id="spouse_civil_status"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            required>
                        <option value="">Seleccione...</option>
                        <option value="soltero">Soltero(a)</option>
                        <option value="casado">Casado(a)</option>
                        <option value="divorciado">Divorciado(a)</option>
                        <option value="viudo">Viudo(a)</option>
                        <option value="union_libre">Unión Libre</option>
                    </select>
                    @error('stepData.spouse_civil_status') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                </div>

                {{-- CURP --}}
                <div>
                    <label for="spouse_curp" class="block text-sm font-medium text-gray-700 mb-2">
                        CURP *
                    </label>
                    <input type="text" 
                           wire:model="stepData.spouse_curp"
                           id="spouse_curp"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="18 caracteres"
                           maxlength="18"
                           style="text-transform: uppercase"
                           required>
                    @error('stepData.spouse_curp') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                </div>

                {{-- RFC --}}
                <div>
                    <label for="spouse_rfc" class="block text-sm font-medium text-gray-700 mb-2">
                        RFC
                    </label>
                    <input type="text" 
                           wire:model="stepData.spouse_rfc"
                           id="spouse_rfc"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="13 caracteres"
                           maxlength="13"
                           style="text-transform: uppercase">
                    @error('stepData.spouse_rfc') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                </div>

                {{-- Ocupación --}}
                <div>
                    <label for="spouse_occupation" class="block text-sm font-medium text-gray-700 mb-2">
                        Ocupación *
                    </label>
                    <input type="text" 
                           wire:model="stepData.spouse_occupation"
                           id="spouse_occupation"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="Ocupación o profesión"
                           required>
                    @error('stepData.spouse_occupation') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                </div>

                {{-- Régimen fiscal --}}
                <div>
                    <label for="spouse_regime_type" class="block text-sm font-medium text-gray-700 mb-2">
                        Régimen Fiscal
                    </label>
                    <select wire:model="stepData.spouse_regime_type" 
                            id="spouse_regime_type"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">Seleccione...</option>
                        <option value="asalariado">Asalariado</option>
                        <option value="honorarios">Honorarios</option>
                        <option value="actividad_empresarial">Actividad Empresarial</option>
                        <option value="arrendamiento">Arrendamiento</option>
                        <option value="otro">Otro</option>
                    </select>
                    @error('stepData.spouse_regime_type') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                </div>

                {{-- Entrega expediente --}}
                <div>
                    <label for="spouse_delivery_file" class="block text-sm font-medium text-gray-700 mb-2">
                        Entrega Expediente
                    </label>
                    <input type="text" 
                           wire:model="stepData.spouse_delivery_file"
                           id="spouse_delivery_file"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="Fecha o estado de entrega">
                    @error('stepData.spouse_delivery_file') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                </div>
            </div>
        </div>

        {{-- Información de contacto --}}
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="text-md font-medium text-gray-900 mb-4">Información de Contacto del Cónyuge</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Email --}}
                <div>
                    <label for="spouse_email" class="block text-sm font-medium text-gray-700 mb-2">
                        Correo Electrónico
                    </label>
                    <input type="email" 
                           wire:model="stepData.spouse_email"
                           id="spouse_email"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="correo@ejemplo.com">
                    @error('stepData.spouse_email') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                </div>

                {{-- Teléfono celular --}}
                <div>
                    <label for="spouse_phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Teléfono Celular
                    </label>
                    <input type="tel" 
                           wire:model="stepData.spouse_phone"
                           id="spouse_phone"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="55 1234 5678">
                    @error('stepData.spouse_phone') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                </div>

                {{-- Teléfono oficina --}}
                <div>
                    <label for="spouse_office_phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Teléfono Oficina
                    </label>
                    <input type="tel" 
                           wire:model="stepData.spouse_office_phone"
                           id="spouse_office_phone"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="55 1234 5678">
                    @error('stepData.spouse_office_phone') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                </div>

                {{-- Teléfono contacto adicional --}}
                <div>
                    <label for="spouse_additional_contact_phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Teléfono Contacto Adicional
                    </label>
                    <input type="tel" 
                           wire:model="stepData.spouse_additional_contact_phone"
                           id="spouse_additional_contact_phone"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           placeholder="55 1234 5678">
                    @error('stepData.spouse_additional_contact_phone') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                    @enderror
                </div>
            </div>
        </div>

        {{-- Domicilio (solo si no es el mismo) --}}
        @if(!($stepData['spouse_same_address'] ?? false))
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-md font-medium text-gray-900 mb-4">Domicilio del Cónyuge</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {{-- Dirección completa --}}
                    <div class="md:col-span-2 lg:col-span-3">
                        <label for="spouse_current_address" class="block text-sm font-medium text-gray-700 mb-2">
                            Dirección Completa *
                        </label>
                        <textarea wire:model="stepData.spouse_current_address"
                                  id="spouse_current_address"
                                  rows="3"
                                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                  placeholder="Calle, número exterior, número interior..."
                                  required></textarea>
                        @error('stepData.spouse_current_address') 
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                        @enderror
                    </div>

                    {{-- Colonia --}}
                    <div>
                        <label for="spouse_neighborhood" class="block text-sm font-medium text-gray-700 mb-2">
                            Colonia *
                        </label>
                        <input type="text" 
                               wire:model="stepData.spouse_neighborhood"
                               id="spouse_neighborhood"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                               placeholder="Nombre de la colonia"
                               required>
                        @error('stepData.spouse_neighborhood') 
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                        @enderror
                    </div>

                    {{-- Código postal --}}
                    <div>
                        <label for="spouse_postal_code" class="block text-sm font-medium text-gray-700 mb-2">
                            Código Postal *
                        </label>
                        <input type="text" 
                               wire:model="stepData.spouse_postal_code"
                               id="spouse_postal_code"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                               placeholder="12345"
                               maxlength="5"
                               pattern="[0-9]{5}"
                               required>
                        @error('stepData.spouse_postal_code') 
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                        @enderror
                    </div>

                    {{-- Municipio --}}
                    <div>
                        <label for="spouse_municipality" class="block text-sm font-medium text-gray-700 mb-2">
                            Municipio/Alcaldía *
                        </label>
                        <input type="text" 
                               wire:model="stepData.spouse_municipality"
                               id="spouse_municipality"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                               placeholder="Municipio o Alcaldía"
                               required>
                        @error('stepData.spouse_municipality') 
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                        @enderror
                    </div>

                    {{-- Estado --}}
                    <div class="md:col-span-2 lg:col-span-1">
                        <label for="spouse_state" class="block text-sm font-medium text-gray-700 mb-2">
                            Estado *
                        </label>
                        <select wire:model="stepData.spouse_state" 
                                id="spouse_state"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                required>
                            <option value="">Seleccione...</option>
                            <option value="Ciudad de México">Ciudad de México</option>
                            <option value="Estado de México">Estado de México</option>
                            <option value="Jalisco">Jalisco</option>
                            <option value="Nuevo León">Nuevo León</option>
                            <option value="Puebla">Puebla</option>
                            <option value="Guanajuato">Guanajuato</option>
                            <option value="Veracruz">Veracruz</option>
                            <option value="Chihuahua">Chihuahua</option>
                            <option value="Hidalgo">Hidalgo</option>
                            <option value="Baja California">Baja California</option>
                            <option value="Sonora">Sonora</option>
                            <option value="Coahuila">Coahuila</option>
                            <option value="Tamaulipas">Tamaulipas</option>
                            <option value="Michoacán">Michoacán</option>
                            <option value="Guerrero">Guerrero</option>
                            <option value="San Luis Potosí">San Luis Potosí</option>
                            <option value="Sinaloa">Sinaloa</option>
                            <option value="Oaxaca">Oaxaca</option>
                            <option value="Chiapas">Chiapas</option>
                            <option value="Yucatán">Yucatán</option>
                            <option value="Querétaro">Querétaro</option>
                            <option value="Morelos">Morelos</option>
                            <option value="Durango">Durango</option>
                            <option value="Zacatecas">Zacatecas</option>
                            <option value="Tabasco">Tabasco</option>
                            <option value="Quintana Roo">Quintana Roo</option>
                            <option value="Aguascalientes">Aguascalientes</option>
                            <option value="Tlaxcala">Tlaxcala</option>
                            <option value="Nayarit">Nayarit</option>
                            <option value="Campeche">Campeche</option>
                            <option value="Baja California Sur">Baja California Sur</option>
                            <option value="Colima">Colima</option>
                        </select>
                        @error('stepData.spouse_state') 
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                        @enderror
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- Información adicional --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Información importante</h3>
                <div class="mt-1 text-sm text-blue-700">
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Los datos del cónyuge son opcionales pero recomendados para convenios matrimoniales</li>
                        <li>Si el cónyuge tiene el mismo domicilio, puede marcar la casilla correspondiente</li>
                        <li>Puede copiar los datos del titular como base y modificar lo necesario</li>
                        <li>Los documentos del cónyuge se solicitarán en el paso de documentación</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Scripts para funcionalidad adicional --}}
@push('scripts')
<script>
    // Auto-copiar domicilio si está marcado "mismo domicilio"
    document.addEventListener('livewire:load', function () {
        Livewire.on('copyHolderData', () => {
            // Aquí se podría implementar lógica adicional si es necesario
            console.log('Copiando datos del titular al cónyuge');
        });
    });

    // Validación de CURP del cónyuge
    document.getElementById('spouse_curp')?.addEventListener('input', function(e) {
        let curp = e.target.value.toUpperCase();
        e.target.value = curp;
        
        if (curp.length === 18) {
            console.log('Validando CURP del cónyuge:', curp);
        }
    });

    // Validación de RFC del cónyuge
    document.getElementById('spouse_rfc')?.addEventListener('input', function(e) {
        let rfc = e.target.value.toUpperCase();
        e.target.value = rfc;
        
        if (rfc.length >= 12) {
            console.log('Validando RFC del cónyuge:', rfc);
        }
    });
</script>
@endpush
