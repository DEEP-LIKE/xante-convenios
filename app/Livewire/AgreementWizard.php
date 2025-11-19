<?php

namespace App\Livewire;

use App\Models\Agreement;
use App\Models\Client;
use App\Services\WizardService;
use App\Services\CalculationService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

class AgreementWizard extends Component
{
    use WithFileUploads;

    // Propiedades principales
    public $agreementId;
    public $currentStep = 1;
    public $wizardData = [];
    public $agreement;
    public $wizardSummary = [];

    // Servicios
    protected WizardService $wizardService;
    protected CalculationService $calculationService;

    // Propiedades del paso actual
    public $stepData = [];
    public $validationErrors = [];
    public $isLoading = false;

    // Listeners para eventos
    protected $listeners = [
        'stepCompleted' => 'handleStepCompleted',
        'calculationUpdated' => 'handleCalculationUpdated',
        'documentUploaded' => 'handleDocumentUploaded',
    ];

    public function boot(WizardService $wizardService, CalculationService $calculationService)
    {
        $this->wizardService = $wizardService;
        $this->calculationService = $calculationService;
    }

    public function mount($agreementId = null)
    {
        if ($agreementId) {
            $this->agreementId = $agreementId;
            $this->agreement = Agreement::findOrFail($agreementId);
            $this->currentStep = $this->agreement->current_step;
            $this->wizardData = $this->agreement->wizard_data ?? [];
        } else {
            // Crear nuevo convenio
            $this->agreement = $this->wizardService->createAgreement();
            $this->agreementId = $this->agreement->id;
            $this->currentStep = 1;
        }

        $this->loadWizardSummary();
        $this->loadStepData();
    }

    public function render()
    {
        return view('livewire.agreement-wizard', [
            'steps' => $this->agreement->getWizardSteps(),
            'canAccessStep' => $this->getAccessibleSteps(),
            'completionPercentage' => $this->agreement->completion_percentage,
        ]);
    }

    /**
     * Navegar al siguiente paso
     */
    public function nextStep()
    {
        if ($this->validateCurrentStep()) {
            $this->saveCurrentStep();
            
            $nextStep = $this->wizardService->getNextStep($this->currentStep, $this->wizardData);
            
            if ($nextStep && $nextStep <= 6) {
                $this->currentStep = $nextStep;
                $this->loadStepData();
                $this->emit('stepChanged', $this->currentStep);
            }
        }
    }

    /**
     * Navegar al paso anterior
     */
    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->saveCurrentStep();
            $this->currentStep--;
            $this->loadStepData();
            $this->emit('stepChanged', $this->currentStep);
        }
    }

    /**
     * Ir directamente a un paso específico
     */
    public function goToStep($stepNumber)
    {
        if ($this->agreement->canAccessStep($stepNumber)) {
            $this->saveCurrentStep();
            $this->currentStep = $stepNumber;
            $this->loadStepData();
            $this->emit('stepChanged', $this->currentStep);
        } else {
            $this->addError('navigation', 'No puede acceder a este paso. Complete los pasos anteriores primero.');
        }
    }

    /**
     * Guardar y salir del wizard
     */
    public function saveAndExit()
    {
        $this->saveCurrentStep();
        
        session()->flash('message', 'Progreso guardado exitosamente. Puede continuar más tarde.');
        
        return redirect()->route('filament.admin.resources.agreements.index');
    }

    /**
     * Completar el wizard
     */
    public function completeWizard()
    {
        if ($this->validateAllSteps()) {
            $this->saveCurrentStep();
            
            // Marcar el convenio como completado
            $this->agreement->update([
                'status' => 'expediente_completo',
                'completed_at' => now(),
                'completion_percentage' => 100,
            ]);

            session()->flash('message', 'Convenio completado exitosamente.');
            
            return redirect()->route('filament.admin.resources.agreements.edit', $this->agreement);
        }
    }

    /**
     * Clonar convenio existente
     */
    public function cloneAgreement($sourceAgreementId)
    {
        try {
            $newAgreement = $this->wizardService->cloneAgreement($sourceAgreementId);
            
            session()->flash('message', 'Convenio clonado exitosamente.');
            
            return redirect()->route('agreement-wizard', ['agreementId' => $newAgreement->id]);
        } catch (\Exception $e) {
            $this->addError('clone', 'Error al clonar el convenio: ' . $e->getMessage());
        }
    }

    // Métodos para manejar eventos específicos de cada paso

    /**
     * Manejar búsqueda de cliente (Paso 1)
     */
    public function searchClient($searchTerm, $searchType = 'xante_id')
    {
        $this->isLoading = true;
        
        try {
            $results = [];
            
            switch ($searchType) {
                case 'xante_id':
                    $results = Client::where('xante_id', 'like', "%{$searchTerm}%")->limit(10)->get();
                    break;
                case 'name':
                    $results = Client::where('name', 'like', "%{$searchTerm}%")->limit(10)->get();
                    break;
                case 'email':
                    $results = Client::where('email', 'like', "%{$searchTerm}%")->limit(10)->get();
                    break;
            }
            
            $this->emit('searchResults', $results->toArray());
            
        } catch (\Exception $e) {
            $this->addError('search', 'Error en la búsqueda: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Seleccionar cliente encontrado
     */
    public function selectClient($clientXanteId)
    {
        $client = Client::where('xante_id', $clientXanteId)->first();
        
        if ($client) {
            $this->stepData['client_xante_id'] = $clientXanteId;
            $this->stepData['client_found'] = true;
            
            // Auto-poblar datos del cliente
            $this->populateClientData($client);
            
            $this->emit('clientSelected', $client->toArray());
        }
    }

    /**
     * Actualizar cálculos financieros (Paso 5)
     */
    public function updateCalculations()
    {
        try {
            $input = [
                'precio_promocion' => $this->stepData['precio_promocion'] ?? null,
                'valor_convenio' => $this->stepData['valor_convenio'] ?? null,
                'porcentaje_comision_sin_iva' => $this->stepData['porcentaje_comision_sin_iva'] ?? null,
                'isr' => $this->stepData['isr'] ?? null,
                'cancelacion_hipoteca' => $this->stepData['cancelacion_hipoteca'] ?? null,
                'monto_credito' => $this->stepData['monto_credito'] ?? null,
            ];

            $calculations = $this->calculationService->calculateFinancialValues($input);
            
            // Actualizar stepData con los cálculos
            $this->stepData = array_merge($this->stepData, $calculations);
            
            $this->emit('calculationsUpdated', $calculations);
            
        } catch (\Exception $e) {
            $this->addError('calculations', 'Error en los cálculos: ' . $e->getMessage());
        }
    }

    // Métodos privados

    private function loadWizardSummary()
    {
        $this->wizardSummary = $this->wizardService->getWizardSummary($this->agreementId);
    }

    private function loadStepData()
    {
        $progress = $this->agreement->wizardProgress()
            ->where('step_number', $this->currentStep)
            ->first();

        $this->stepData = $progress ? ($progress->step_data ?? []) : [];
        
        // Cargar datos del convenio si existen
        $this->mergeAgreementData();
    }

    private function mergeAgreementData()
    {
        switch ($this->currentStep) {
            case 1:
                if ($this->agreement->client_xante_id) {
                    $this->stepData['client_xante_id'] = $this->agreement->client_xante_id;
                    $this->stepData['client_found'] = true;
                }
                break;
                
            case 2:
                // Datos del titular
                $holderFields = [
                    'holder_name', 'holder_email', 'holder_phone', 'holder_birthdate',
                    'holder_curp', 'holder_rfc', 'holder_civil_status', 'holder_occupation'
                ];
                
                foreach ($holderFields as $field) {
                    if ($this->agreement->$field) {
                        $this->stepData[$field] = $this->agreement->$field;
                    }
                }
                break;
                
            case 5:
                // Datos de la calculadora
                $calculatorFields = [
                    'precio_promocion', 'valor_convenio', 'porcentaje_comision_sin_iva',
                    'monto_credito', 'tipo_credito', 'isr', 'cancelacion_hipoteca'
                ];
                
                foreach ($calculatorFields as $field) {
                    if ($this->agreement->$field) {
                        $this->stepData[$field] = $this->agreement->$field;
                    }
                }
                break;
        }
    }

    private function validateCurrentStep(): bool
    {
        $validationResult = $this->wizardService->validateStep($this->currentStep, $this->stepData);
        
        if (!$validationResult->isValid()) {
            $this->validationErrors = $validationResult->getErrors();
            return false;
        }
        
        $this->validationErrors = [];
        return true;
    }

    private function validateAllSteps(): bool
    {
        for ($step = 1; $step <= 6; $step++) {
            if (!$this->agreement->isStepCompleted($step)) {
                $this->addError('completion', "El paso {$step} no está completado.");
                return false;
            }
        }
        
        return true;
    }

    private function saveCurrentStep()
    {
        if (!empty($this->stepData)) {
            $this->wizardService->saveStep($this->agreementId, $this->currentStep, $this->stepData);
            $this->loadWizardSummary();
        }
    }

    private function populateClientData(Client $client)
    {
        // Poblar datos del titular con TODOS los campos disponibles
        $this->stepData = array_merge($this->stepData, [
            // Datos básicos (4 campos)
            'holder_name' => $client->name,
            'holder_email' => $client->email,
            'holder_phone' => $client->phone,
            'fecha_registro' => $client->fecha_registro?->format('Y-m-d'), // Nueva fecha del Deal
            
            // Datos personales
            'holder_birthdate' => $client->birthdate?->format('Y-m-d'),
            'holder_curp' => $client->curp,
            'holder_rfc' => $client->rfc,
            'holder_civil_status' => $client->civil_status,
            'holder_regime_type' => $client->regime_type,
            'holder_occupation' => $client->occupation,
            'holder_delivery_file' => $client->delivery_file,
            
            // Teléfonos adicionales
            'holder_office_phone' => $client->office_phone,
            'holder_additional_contact_phone' => $client->additional_contact_phone,
            
            // Dirección completa
            'current_address' => $client->current_address,
            'neighborhood' => $client->neighborhood,
            'postal_code' => $client->postal_code,
            'municipality' => $client->municipality,
            'state' => $client->state,
            
            // Datos del cónyuge (si existen)
            'spouse_name' => $client->spouse_name,
            'spouse_birthdate' => $client->spouse_birthdate?->format('Y-m-d'),
            'spouse_curp' => $client->spouse_curp,
            'spouse_rfc' => $client->spouse_rfc,
            'spouse_email' => $client->spouse_email,
            'spouse_phone' => $client->spouse_phone,
            'spouse_delivery_file' => $client->spouse_delivery_file,
            'spouse_civil_status' => $client->spouse_civil_status,
            'spouse_regime_type' => $client->spouse_regime_type,
            'spouse_occupation' => $client->spouse_occupation,
            'spouse_office_phone' => $client->spouse_office_phone,
            'spouse_additional_contact_phone' => $client->spouse_additional_contact_phone,
            'spouse_current_address' => $client->spouse_current_address,
            'spouse_neighborhood' => $client->spouse_neighborhood,
            'spouse_postal_code' => $client->spouse_postal_code,
            'spouse_municipality' => $client->spouse_municipality,
            'spouse_state' => $client->spouse_state,
            
            // Contacto AC y Presidente
            'ac_name' => $client->ac_name,
            'ac_phone' => $client->ac_phone,
            'ac_quota' => $client->ac_quota,
            'private_president_name' => $client->private_president_name,
            'private_president_phone' => $client->private_president_phone,
            'private_president_quota' => $client->private_president_quota,
        ]);
        
        // Filtrar valores nulos para no sobrescribir datos existentes
        $this->stepData = array_filter($this->stepData, function($value) {
            return $value !== null && $value !== '';
        });
        
        // Log para debugging
        \Log::info('Datos del cliente precargados en wizard', [
            'client_xante_id' => $client->xante_id,
            'fields_populated' => count($this->stepData),
            'has_spouse_data' => !empty($client->spouse_name),
        ]);
    }

    private function getAccessibleSteps(): array
    {
        $accessible = [];
        
        for ($step = 1; $step <= 6; $step++) {
            $accessible[$step] = $this->agreement->canAccessStep($step);
        }
        
        return $accessible;
    }

    // Eventos manejados

    public function handleStepCompleted($stepNumber)
    {
        $this->wizardService->markStepAsCompleted($this->agreementId, $stepNumber);
        $this->loadWizardSummary();
    }

    public function handleCalculationUpdated($calculations)
    {
        $this->stepData = array_merge($this->stepData, $calculations);
    }

    public function handleDocumentUploaded($documentData)
    {
        $this->emit('documentUploadSuccess', $documentData);
    }

    // Métodos específicos para los pasos del wizard

    /**
     * Crear nuevo cliente (Paso 1)
     */
    public function createNewClient()
    {
        $this->validate([
            'stepData.new_client.xante_id' => 'required|unique:clients,xante_id',
            'stepData.new_client.name' => 'required|string|max:255',
            'stepData.new_client.email' => 'required|email|unique:clients,email',
            'stepData.new_client.phone' => 'required|string|max:20',
        ]);

        try {
            $client = Client::create($this->stepData['new_client']);

            $this->stepData['client_xante_id'] = $client->xante_id;
            $this->stepData['client_found'] = true;
            $this->stepData['show_create_form'] = false;

            // Actualizar el agreement con el client_xante_id
            $this->agreement->update(['client_xante_id' => $client->xante_id]);

            $this->populateClientData($client);

            session()->flash('message', 'Cliente creado exitosamente.');

        } catch (\Exception $e) {
            $this->addError('create_client', 'Error al crear el cliente: ' . $e->getMessage());
        }
    }

    /**
     * Copiar datos del titular al cónyuge (Paso 3)
     */
    public function copyHolderData()
    {
        if (!empty($this->stepData['holder_name'])) {
            $this->stepData['spouse_name'] = $this->stepData['holder_name'] ?? '';
            $this->stepData['spouse_occupation'] = $this->stepData['holder_occupation'] ?? '';
            $this->stepData['spouse_regime_type'] = $this->stepData['holder_regime_type'] ?? '';
            
            // Si mismo domicilio está marcado, copiar también la dirección
            if ($this->stepData['spouse_same_address'] ?? false) {
                $this->stepData['spouse_current_address'] = $this->stepData['holder_current_address'] ?? '';
                $this->stepData['spouse_neighborhood'] = $this->stepData['holder_neighborhood'] ?? '';
                $this->stepData['spouse_postal_code'] = $this->stepData['holder_postal_code'] ?? '';
                $this->stepData['spouse_municipality'] = $this->stepData['holder_municipality'] ?? '';
                $this->stepData['spouse_state'] = $this->stepData['holder_state'] ?? '';
            }
            
            session()->flash('message', 'Datos del titular copiados al cónyuge.');
        }
    }

    /**
     * Buscar en Data Lake (Paso 4)
     */
    public function searchInDataLake()
    {
        $this->isLoading = true;
        
        try {
            // Aquí se integraría con el servicio real de Data Lake
            // Por ahora simulamos la búsqueda
            
            $searchTerm = $this->stepData['property_search_term'] ?? '';
            $searchType = $this->stepData['property_search_type'] ?? 'address';
            
            if (empty($searchTerm)) {
                $this->addError('property_search', 'Ingrese un término de búsqueda');
                return;
            }
            
            // Simulación de resultados
            $this->stepData['property_search_results'] = [
                [
                    'id' => 1,
                    'address' => $searchTerm,
                    'development' => 'Desarrollo Ejemplo',
                    'type' => 'Casa',
                    'status' => 'Disponible'
                ]
            ];
            
            session()->flash('message', 'Búsqueda completada en Data Lake.');
            
        } catch (\Exception $e) {
            $this->addError('property_search', 'Error en la búsqueda: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Resetear valores por defecto (Paso 5)
     */
    public function resetToDefaults()
    {
        $this->calculationService->clearConfigurationCache();
        
        $this->stepData['porcentaje_comision_sin_iva'] = ConfigurationCalculator::get('comision_sin_iva_default', 6.50);
        $this->stepData['monto_credito'] = ConfigurationCalculator::get('monto_credito_default', 800000);
        $this->stepData['isr'] = ConfigurationCalculator::get('isr_default', 0);
        $this->stepData['cancelacion_hipoteca'] = ConfigurationCalculator::get('cancelacion_hipoteca_default', 20000);
        $this->stepData['tipo_credito'] = ConfigurationCalculator::get('tipo_credito_default', 'BANCARIO');
        $this->stepData['otro_banco'] = ConfigurationCalculator::get('otro_banco_default', 'NO APLICA');
        
        $this->updateCalculations();
        
        session()->flash('message', 'Valores restaurados a los por defecto.');
    }

    /**
     * Calcular escenarios (Paso 5)
     */
    public function calculateScenario($type, $value)
    {
        if (!$value) return 0;
        
        $baseCalculation = [
            'valor_convenio' => $this->stepData['valor_convenio'] ?? 0,
            'porcentaje_comision_sin_iva' => $this->stepData['porcentaje_comision_sin_iva'] ?? 6.50,
            'isr' => $this->stepData['isr'] ?? 0,
            'cancelacion_hipoteca' => $this->stepData['cancelacion_hipoteca'] ?? 0,
            'monto_credito' => $this->stepData['monto_credito'] ?? 0,
        ];
        
        switch ($type) {
            case 'commission':
                $baseCalculation['porcentaje_comision_sin_iva'] = $value;
                break;
            case 'price':
                $baseCalculation['precio_promocion'] = $value;
                break;
            case 'expenses':
                $baseCalculation['cancelacion_hipoteca'] = $value;
                break;
        }
        
        $result = $this->calculationService->calculateFinancialValues($baseCalculation);
        return $result['ganancia_final'] ?? 0;
    }

    // Métodos para el Paso 6 - Documentos

    /**
     * Obtener progreso de documentos
     */
    public function getDocumentProgress()
    {
        $checklist = $this->stepData['documents_checklist'] ?? [];
        $total = count($this->getRequiredDocuments());
        $completed = count(array_filter($checklist));
        
        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0
        ];
    }

    /**
     * Verificar si requiere documentos del cónyuge
     */
    public function requiresSpouseDocuments()
    {
        return in_array($this->stepData['holder_civil_status'] ?? '', ['casado', 'union_libre']);
    }

    /**
     * Verificar si está listo para completar
     */
    public function isReadyToComplete()
    {
        $requiredDocs = $this->getRequiredDocuments();
        $checklist = $this->stepData['documents_checklist'] ?? [];
        
        foreach ($requiredDocs as $doc) {
            if (!($checklist[$doc] ?? false)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Obtener documentos obligatorios faltantes
     */
    public function getMissingRequiredDocuments()
    {
        $requiredDocs = $this->getRequiredDocuments();
        $checklist = $this->stepData['documents_checklist'] ?? [];
        $missing = [];
        
        $docLabels = [
            'titular_ine' => 'INE del Titular',
            'titular_curp' => 'CURP del Titular',
            'titular_rfc' => 'RFC del Titular',
            'propiedad_instrumento_notarial' => 'Instrumento Notarial',
            'propiedad_traslado_dominio' => 'Traslado de Dominio',
            // Agregar más según sea necesario
        ];
        
        foreach ($requiredDocs as $doc) {
            if (!($checklist[$doc] ?? false)) {
                $missing[] = $docLabels[$doc] ?? $doc;
            }
        }
        
        return $missing;
    }

    /**
     * Obtener lista de documentos requeridos
     */
    private function getRequiredDocuments()
    {
        $required = [
            'titular_ine',
            'titular_curp',
            'titular_rfc',
            'propiedad_instrumento_notarial',
            'propiedad_traslado_dominio',
            'otros_autorizacion_buro'
        ];
        
        // Agregar documentos del cónyuge si es necesario
        if ($this->requiresSpouseDocuments()) {
            $required = array_merge($required, [
                'conyuge_ine',
                'conyuge_curp',
                'conyuge_acta_nacimiento'
            ]);
        }
        
        return $required;
    }

    /**
     * Subir documento
     */
    public function uploadDocument($documentType)
    {
        $this->stepData['show_upload_modal'] = true;
        $this->stepData['upload_document_type'] = $documentType;
    }

    /**
     * Cerrar modal de subida
     */
    public function closeUploadModal()
    {
        $this->stepData['show_upload_modal'] = false;
        $this->stepData['upload_document_type'] = null;
        $this->stepData['upload_file'] = null;
    }

    /**
     * Procesar subida de archivo
     */
    public function processUpload()
    {
        $this->validate([
            'stepData.upload_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240'
        ]);

        try {
            // Aquí se integraría con DocumentService
            // Por ahora solo marcamos como recibido
            $documentType = $this->stepData['upload_document_type'];
            $this->stepData['documents_checklist'][$documentType] = true;
            
            $this->closeUploadModal();
            session()->flash('message', 'Documento subido exitosamente.');
            
        } catch (\Exception $e) {
            $this->addError('upload', 'Error al subir el documento: ' . $e->getMessage());
        }
    }

    /**
     * Marcar como completo
     */
    public function markAsComplete()
    {
        if ($this->isReadyToComplete()) {
            $this->agreement->update([
                'status' => 'expediente_completo',
                'completion_percentage' => 100,
                'completed_at' => now()
            ]);
            
            session()->flash('message', 'Convenio marcado como completo exitosamente.');
            
            return redirect()->route('filament.admin.resources.agreements.index');
        }
    }

    // Métodos de utilidad adicionales

    /**
     * Auto-save (llamado cada 30 segundos)
     */
    public function autoSave()
    {
        if (!empty($this->stepData)) {
            $this->saveCurrentStep();
        }
    }
}
