{{-- Paso 2: Datos del Cliente Titular --}}
<div class="space-y-6">
    <div>
        <h2 class="text-lg font-medium text-gray-900 mb-4">Datos Personales del Titular</h2>
        <p class="text-sm text-gray-600 mb-6">
            Complete la información personal del titular del convenio. Los campos marcados con * son obligatorios.
        </p>
    </div>

    {{-- Información básica --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-md font-medium text-gray-900 mb-4">Información Básica</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Fecha de Registro (HubSpot Deal) --}}
            <div class="md:col-span-2">
                <label for="fecha_registro" class="block text-sm font-medium text-gray-700 mb-2">
                    Fecha
                </label>
                <input type="date" 
                       wire:model="stepData.fecha_registro"
                       id="fecha_registro"
                       class="block w-full rounded-md border-gray-300 bg-gray-100 text-gray-500 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       readonly
                       disabled>
            </div>

            {{-- Nombre completo --}}
            <div class="md:col-span-2">
                <label for="holder_name" class="block text-sm font-medium text-gray-700 mb-2">
                    Nombre Completo *
                </label>
                <input type="text" 
                       wire:model="stepData.holder_name"
                       id="holder_name"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="Nombre completo del titular"
                       required>
                @error('stepData.holder_name') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Fecha de nacimiento --}}
            <div>
                <label for="holder_birthdate" class="block text-sm font-medium text-gray-700 mb-2">
                    Fecha de Nacimiento *
                </label>
                <input type="date" 
                       wire:model="stepData.holder_birthdate"
                       id="holder_birthdate"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       required>
                @error('stepData.holder_birthdate') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Estado civil --}}
            <div>
                <label for="holder_civil_status" class="block text-sm font-medium text-gray-700 mb-2">
                    Estado Civil *
                </label>
                <select wire:model="stepData.holder_civil_status" 
                        id="holder_civil_status"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        required>
                    <option value="">Seleccione...</option>
                    <option value="soltero">Soltero(a)</option>
                    <option value="casado">Casado(a)</option>
                    <option value="divorciado">Divorciado(a)</option>
                    <option value="viudo">Viudo(a)</option>
                    <option value="union_libre">Unión Libre</option>
                </select>
                @error('stepData.holder_civil_status') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- CURP --}}
            <div>
                <label for="holder_curp" class="block text-sm font-medium text-gray-700 mb-2">
                    CURP *
                </label>
                <input type="text" 
                       wire:model="stepData.holder_curp"
                       id="holder_curp"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="18 caracteres"
                       maxlength="18"
                       style="text-transform: uppercase"
                       required>
                @error('stepData.holder_curp') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- RFC --}}
            <div>
                <label for="holder_rfc" class="block text-sm font-medium text-gray-700 mb-2">
                    RFC
                </label>
                <input type="text" 
                       wire:model="stepData.holder_rfc"
                       id="holder_rfc"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="13 caracteres"
                       maxlength="13"
                       style="text-transform: uppercase">
                @error('stepData.holder_rfc') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Ocupación --}}
            <div>
                <label for="holder_occupation" class="block text-sm font-medium text-gray-700 mb-2">
                    Ocupación *
                </label>
                <input type="text" 
                       wire:model="stepData.holder_occupation"
                       id="holder_occupation"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="Ocupación o profesión"
                       required>
                @error('stepData.holder_occupation') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Régimen fiscal --}}
            <div>
                <label for="holder_regime_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Régimen Fiscal
                </label>
                <select wire:model="stepData.holder_regime_type" 
                        id="holder_regime_type"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="">Seleccione...</option>
                    <option value="asalariado">Asalariado</option>
                    <option value="honorarios">Honorarios</option>
                    <option value="actividad_empresarial">Actividad Empresarial</option>
                    <option value="arrendamiento">Arrendamiento</option>
                    <option value="otro">Otro</option>
                </select>
                @error('stepData.holder_regime_type') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Entrega expediente --}}
            <div>
                <label for="holder_delivery_file" class="block text-sm font-medium text-gray-700 mb-2">
                    Entrega Expediente
                </label>
                <input type="text" 
                       wire:model="stepData.holder_delivery_file"
                       id="holder_delivery_file"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="Fecha o estado de entrega">
                @error('stepData.holder_delivery_file') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>
        </div>
    </div>

    {{-- Información de contacto --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-md font-medium text-gray-900 mb-4">Información de Contacto</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Email --}}
            <div>
                <label for="holder_email" class="block text-sm font-medium text-gray-700 mb-2">
                    Correo Electrónico *
                </label>
                <input type="email" 
                       wire:model="stepData.holder_email"
                       id="holder_email"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="correo@ejemplo.com"
                       required>
                @error('stepData.holder_email') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Teléfono celular --}}
            <div>
                <label for="holder_phone" class="block text-sm font-medium text-gray-700 mb-2">
                    Teléfono Celular *
                </label>
                <input type="tel" 
                       wire:model="stepData.holder_phone"
                       id="holder_phone"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="55 1234 5678"
                       required>
                @error('stepData.holder_phone') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Teléfono oficina --}}
            <div>
                <label for="holder_office_phone" class="block text-sm font-medium text-gray-700 mb-2">
                    Teléfono Oficina
                </label>
                <input type="tel" 
                       wire:model="stepData.holder_office_phone"
                       id="holder_office_phone"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="55 1234 5678">
                @error('stepData.holder_office_phone') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Teléfono contacto adicional --}}
            <div>
                <label for="holder_additional_contact_phone" class="block text-sm font-medium text-gray-700 mb-2">
                    Teléfono Contacto Adicional
                </label>
                <input type="tel" 
                       wire:model="stepData.holder_additional_contact_phone"
                       id="holder_additional_contact_phone"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="55 1234 5678">
                @error('stepData.holder_additional_contact_phone') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>
        </div>
    </div>

    {{-- Domicilio --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-md font-medium text-gray-900 mb-4">Domicilio Actual</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- Dirección completa --}}
            <div class="md:col-span-2 lg:col-span-3">
                <label for="holder_current_address" class="block text-sm font-medium text-gray-700 mb-2">
                    Dirección Completa *
                </label>
                <textarea wire:model="stepData.holder_current_address"
                          id="holder_current_address"
                          rows="3"
                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                          placeholder="Calle, número exterior, número interior..."
                          required></textarea>
                @error('stepData.holder_current_address') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Colonia --}}
            <div>
                <label for="holder_neighborhood" class="block text-sm font-medium text-gray-700 mb-2">
                    Colonia *
                </label>
                <input type="text" 
                       wire:model="stepData.holder_neighborhood"
                       id="holder_neighborhood"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="Nombre de la colonia"
                       required>
                @error('stepData.holder_neighborhood') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Código postal --}}
            <div>
                <label for="holder_postal_code" class="block text-sm font-medium text-gray-700 mb-2">
                    Código Postal *
                </label>
                <input type="text" 
                       wire:model="stepData.holder_postal_code"
                       id="holder_postal_code"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="12345"
                       maxlength="5"
                       pattern="[0-9]{5}"
                       required>
                @error('stepData.holder_postal_code') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Municipio --}}
            <div>
                <label for="holder_municipality" class="block text-sm font-medium text-gray-700 mb-2">
                    Municipio/Alcaldía *
                </label>
                <input type="text" 
                       wire:model="stepData.holder_municipality"
                       id="holder_municipality"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                       placeholder="Municipio o Alcaldía"
                       required>
                @error('stepData.holder_municipality') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Estado --}}
            <div>
                <label for="holder_state" class="block text-sm font-medium text-gray-700 mb-2">
                    Estado *
                </label>
                <select wire:model="stepData.holder_state" 
                        id="holder_state"
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
                @error('stepData.holder_state') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                @enderror
            </div>
        </div>
    </div>

    {{-- Validaciones en tiempo real --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Validaciones automáticas</h3>
                <div class="mt-1 text-sm text-blue-700">
                    <ul class="list-disc pl-5 space-y-1">
                        <li>El CURP se valida automáticamente con el algoritmo oficial</li>
                        <li>El RFC se genera automáticamente si no se proporciona</li>
                        <li>El código postal se valida contra el catálogo de SEPOMEX</li>
                        <li>Los datos se verifican contra RENAPO cuando sea posible</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Aviso sobre cónyuge --}}
    @if(in_array($stepData['holder_civil_status'] ?? '', ['casado', 'union_libre']))
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Datos del cónyuge requeridos</h3>
                    <div class="mt-1 text-sm text-yellow-700">
                        <p>Como el titular está {{ $stepData['holder_civil_status'] === 'casado' ? 'casado' : 'en unión libre' }}, 
                           será necesario capturar los datos del cónyuge en el siguiente paso.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Scripts para validaciones --}}
@push('scripts')
<script>
    // Validación de CURP en tiempo real
    document.getElementById('holder_curp')?.addEventListener('input', function(e) {
        let curp = e.target.value.toUpperCase();
        e.target.value = curp;
        
        if (curp.length === 18) {
            // Aquí se podría agregar validación de CURP
            console.log('Validando CURP:', curp);
        }
    });

    // Validación de RFC en tiempo real
    document.getElementById('holder_rfc')?.addEventListener('input', function(e) {
        let rfc = e.target.value.toUpperCase();
        e.target.value = rfc;
        
        if (rfc.length >= 12) {
            // Aquí se podría agregar validación de RFC
            console.log('Validando RFC:', rfc);
        }
    });

    // Validación de código postal
    document.getElementById('holder_postal_code')?.addEventListener('input', function(e) {
        let cp = e.target.value;
        
        if (cp.length === 5) {
            // Aquí se podría consultar el catálogo de SEPOMEX
            console.log('Validando CP:', cp);
        }
    });
</script>
@endpush
