{{-- Paso 6: Documentación y Cierre --}}
<div class="space-y-6">
    <div>
        <h2 class="text-lg font-medium text-gray-900 mb-4">Documentación y Cierre del Convenio</h2>
        <p class="text-sm text-gray-600 mb-6">
            Gestione los documentos requeridos y complete el proceso del convenio. Marque los documentos conforme los vaya recibiendo.
        </p>
    </div>

    {{-- Progreso de documentación --}}
    <div class="bg-gradient-to-r from-blue-50 to-green-50 border border-blue-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-md font-medium text-gray-900">Progreso de Documentación</h3>
            <div class="flex items-center space-x-4">
                <div class="text-sm text-gray-600">
                    {{ $this->getDocumentProgress()['completed'] }} de {{ $this->getDocumentProgress()['total'] }} documentos
                </div>
                <div class="w-32 bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                         style="width: {{ $this->getDocumentProgress()['percentage'] }}%"></div>
                </div>
                <span class="text-sm font-medium text-gray-700">{{ $this->getDocumentProgress()['percentage'] }}%</span>
            </div>
        </div>
    </div>

    {{-- Documentos del Titular --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-md font-medium text-gray-900 mb-4">📋 Documentos del Titular</h3>
        
        <div class="space-y-4">
            @foreach([
                'titular_ine' => ['label' => 'INE (A color, tamaño original, no fotos)', 'required' => true, 'notes' => 'Vigente, legible, sin alteraciones'],
                'titular_curp' => ['label' => 'CURP (Mes corriente)', 'required' => true, 'notes' => 'Impresión del mes actual'],
                'titular_rfc' => ['label' => 'Constancia de Situación Fiscal (Mes corriente, completa)', 'required' => true, 'notes' => 'Con código QR visible'],
                'titular_comprobante_domicilio_vivienda' => ['label' => 'Comprobante de Domicilio Vivienda (Mes corriente)', 'required' => true, 'notes' => 'Recibo de servicios'],
                'titular_comprobante_domicilio' => ['label' => 'Comprobante de Domicilio Titular (Mes corriente)', 'required' => true, 'notes' => 'Si es diferente a la vivienda'],
                'titular_acta_nacimiento' => ['label' => 'Acta de Nacimiento', 'required' => true, 'notes' => 'Copia certificada'],
                'titular_acta_matrimonio' => ['label' => 'Acta de Matrimonio', 'required' => false, 'notes' => 'Solo si aplica'],
                'titular_estado_cuenta' => ['label' => 'Carátula Estado de Cuenta Bancario con Datos Fiscales (Mes corriente)', 'required' => true, 'notes' => 'Primera hoja con datos completos']
            ] as $key => $doc)
                <div class="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <div class="flex items-center h-5">
                        <input type="checkbox" 
                               wire:model="stepData.documents_checklist.{{ $key }}"
                               id="doc_{{ $key }}"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    </div>
                    <div class="flex-1 min-w-0">
                        <label for="doc_{{ $key }}" class="block text-sm font-medium text-gray-900 cursor-pointer">
                            {{ $doc['label'] }}
                            @if($doc['required'])
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <p class="text-xs text-gray-500 mt-1">{{ $doc['notes'] }}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($stepData['documents_checklist'][$key] ?? false)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                ✓ Recibido
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Pendiente
                            </span>
                        @endif
                        
                        {{-- Botón para subir archivo --}}
                        <button wire:click="uploadDocument('{{ $key }}')" 
                                class="inline-flex items-center p-1 border border-transparent rounded text-xs text-blue-600 hover:text-blue-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Documentos del Cónyuge (si aplica) --}}
    @if($this->requiresSpouseDocuments())
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="text-md font-medium text-gray-900 mb-4">👥 Documentos del Cónyuge</h3>
            
            <div class="space-y-4">
                @foreach([
                    'conyuge_ine' => ['label' => 'INE del Cónyuge (A color, tamaño original)', 'required' => true],
                    'conyuge_curp' => ['label' => 'CURP del Cónyuge (Mes corriente)', 'required' => true],
                    'conyuge_rfc' => ['label' => 'RFC del Cónyuge', 'required' => false],
                    'conyuge_comprobante_domicilio' => ['label' => 'Comprobante de Domicilio del Cónyuge', 'required' => false],
                    'conyuge_acta_nacimiento' => ['label' => 'Acta de Nacimiento del Cónyuge', 'required' => true],
                    'conyuge_estado_cuenta' => ['label' => 'Estado de Cuenta del Cónyuge', 'required' => false]
                ] as $key => $doc)
                    <div class="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                        <div class="flex items-center h-5">
                            <input type="checkbox" 
                                   wire:model="stepData.documents_checklist.{{ $key }}"
                                   id="doc_{{ $key }}"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        </div>
                        <div class="flex-1 min-w-0">
                            <label for="doc_{{ $key }}" class="block text-sm font-medium text-gray-900 cursor-pointer">
                                {{ $doc['label'] }}
                                @if($doc['required'])
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>
                        </div>
                        <div class="flex items-center space-x-2">
                            @if($stepData['documents_checklist'][$key] ?? false)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ✓ Recibido
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    Pendiente
                                </span>
                            @endif
                            
                            <button wire:click="uploadDocument('{{ $key }}')" 
                                    class="inline-flex items-center p-1 border border-transparent rounded text-xs text-blue-600 hover:text-blue-800">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Documentos de la Propiedad --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-md font-medium text-gray-900 mb-4">🏠 Documentos de la Propiedad</h3>
        
        <div class="space-y-4">
            @foreach([
                'propiedad_instrumento_notarial' => ['label' => 'Instrumento Notarial con Antecedentes Registrales', 'required' => true, 'notes' => 'Escritura completa con antecedentes'],
                'propiedad_traslado_dominio' => ['label' => 'Traslado de Dominio (Escaneado, visible)', 'required' => true, 'notes' => 'Documento del Registro Público'],
                'propiedad_recibo_predial' => ['label' => 'Recibo Predial (Mes corriente)', 'required' => true, 'notes' => 'Al corriente en pagos'],
                'propiedad_recibo_agua' => ['label' => 'Recibo de Agua (Mes corriente)', 'required' => true, 'notes' => 'Servicio activo'],
                'propiedad_recibo_cfe' => ['label' => 'Recibo CFE con Datos Fiscales (Mes corriente)', 'required' => true, 'notes' => 'Con RFC del titular'],
                'propiedad_avaluo' => ['label' => 'Avalúo Comercial', 'required' => false, 'notes' => 'Si está disponible'],
                'propiedad_planos' => ['label' => 'Planos Arquitectónicos', 'required' => false, 'notes' => 'Si están disponibles']
            ] as $key => $doc)
                <div class="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <div class="flex items-center h-5">
                        <input type="checkbox" 
                               wire:model="stepData.documents_checklist.{{ $key }}"
                               id="doc_{{ $key }}"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    </div>
                    <div class="flex-1 min-w-0">
                        <label for="doc_{{ $key }}" class="block text-sm font-medium text-gray-900 cursor-pointer">
                            {{ $doc['label'] }}
                            @if($doc['required'])
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <p class="text-xs text-gray-500 mt-1">{{ $doc['notes'] }}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($stepData['documents_checklist'][$key] ?? false)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                ✓ Recibido
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Pendiente
                            </span>
                        @endif
                        
                        <button wire:click="uploadDocument('{{ $key }}')" 
                                class="inline-flex items-center p-1 border border-transparent rounded text-xs text-blue-600 hover:text-blue-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Otros documentos --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-md font-medium text-gray-900 mb-4">📄 Otros Documentos</h3>
        
        <div class="space-y-4">
            @foreach([
                'otros_referencias_comerciales' => ['label' => 'Referencias Comerciales', 'required' => false],
                'otros_referencias_personales' => ['label' => 'Referencias Personales', 'required' => false],
                'otros_autorizacion_buro' => ['label' => 'Autorización Buró de Crédito', 'required' => true],
                'otros_comprobante_ingresos' => ['label' => 'Comprobante de Ingresos', 'required' => false],
                'otros_carta_no_adeudo' => ['label' => 'Carta de No Adeudo', 'required' => false]
            ] as $key => $doc)
                <div class="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <div class="flex items-center h-5">
                        <input type="checkbox" 
                               wire:model="stepData.documents_checklist.{{ $key }}"
                               id="doc_{{ $key }}"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    </div>
                    <div class="flex-1 min-w-0">
                        <label for="doc_{{ $key }}" class="block text-sm font-medium text-gray-900 cursor-pointer">
                            {{ $doc['label'] }}
                            @if($doc['required'])
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($stepData['documents_checklist'][$key] ?? false)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                ✓ Recibido
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Pendiente
                            </span>
                        @endif
                        
                        <button wire:click="uploadDocument('{{ $key }}')" 
                                class="inline-flex items-center p-1 border border-transparent rounded text-xs text-blue-600 hover:text-blue-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Observaciones finales --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-md font-medium text-gray-900 mb-4">📝 Observaciones y Notas Finales</h3>
        
        <div class="space-y-4">
            <div>
                <label for="observaciones_documentos" class="block text-sm font-medium text-gray-700 mb-2">
                    Observaciones sobre Documentos
                </label>
                <textarea wire:model="stepData.observaciones_documentos"
                          id="observaciones_documentos"
                          rows="3"
                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                          placeholder="Notas sobre documentos faltantes, observaciones especiales, etc."></textarea>
            </div>

            <div>
                <label for="notas_seguimiento" class="block text-sm font-medium text-gray-700 mb-2">
                    Notas de Seguimiento
                </label>
                <textarea wire:model="stepData.notas_seguimiento"
                          id="notas_seguimiento"
                          rows="3"
                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                          placeholder="Próximos pasos, fechas importantes, recordatorios..."></textarea>
            </div>

            <div>
                <label for="fecha_compromiso" class="block text-sm font-medium text-gray-700 mb-2">
                    Fecha Compromiso de Entrega
                </label>
                <input type="date" 
                       wire:model="stepData.fecha_compromiso"
                       id="fecha_compromiso"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>
        </div>
    </div>

    {{-- Acciones finales --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-md font-medium text-gray-900 mb-4">🎯 Acciones Finales</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Generar documentos --}}
            <button wire:click="generateDocumentPackage" 
                    class="inline-flex items-center justify-center px-4 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Generar Paquete de Documentos
            </button>

            {{-- Enviar notificaciones --}}
            <button wire:click="sendNotifications" 
                    class="inline-flex items-center justify-center px-4 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                Enviar Notificaciones
            </button>

            {{-- Programar seguimiento --}}
            <button wire:click="scheduleFollowUp" 
                    class="inline-flex items-center justify-center px-4 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Programar Seguimiento
            </button>
        </div>
    </div>

    {{-- Estado final del convenio --}}
    <div class="bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900">Estado Final del Convenio</h3>
                <p class="text-sm text-gray-600 mt-1">
                    @if($this->isReadyToComplete())
                        ✅ El convenio está listo para ser completado
                    @else
                        ⚠️ Faltan documentos obligatorios por recibir
                    @endif
                </p>
            </div>
            
            @if($this->isReadyToComplete())
                <div class="flex space-x-3">
                    <button wire:click="markAsComplete" 
                            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Marcar como Completo
                    </button>
                </div>
            @endif
        </div>
        
        {{-- Resumen de documentos pendientes --}}
        @if(!$this->isReadyToComplete())
            <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <h4 class="text-sm font-medium text-yellow-800 mb-2">Documentos Obligatorios Pendientes:</h4>
                <ul class="text-sm text-yellow-700 list-disc pl-5 space-y-1">
                    @foreach($this->getMissingRequiredDocuments() as $doc)
                        <li>{{ $doc }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>

{{-- Modal para subir archivos --}}
@if($stepData['show_upload_modal'] ?? false)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closeUploadModal">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" wire:click.stop>
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Subir Documento</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Tipo de Documento
                        </label>
                        <p class="text-sm text-gray-600">{{ $stepData['upload_document_type'] ?? '' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Seleccionar Archivo
                        </label>
                        <input type="file" 
                               wire:model="stepData.upload_file"
                               accept=".pdf,.jpg,.jpeg,.png,.gif"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-500 mt-1">PDF, JPG, PNG (máx. 10MB)</p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button wire:click="closeUploadModal" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Cancelar
                    </button>
                    <button wire:click="processUpload" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove>Subir</span>
                        <span wire:loading>Subiendo...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
