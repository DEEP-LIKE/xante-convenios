<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use App\Models\Agreement;
use Illuminate\Support\Facades\Auth;
use BackedEnum;

class CreateAgreementWizard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-plus';
    protected static ?string $title = 'Nuevo Convenio';
    protected static ?string $navigationLabel = 'Crear Convenio';
    protected static bool $shouldRegisterNavigation = false;
    
    // Propiedades del wizard
    public $currentStep = 1;
    public $data = [];
    public $agreementId;
    
    public function getView(): string
    {
        return 'filament.pages.create-agreement-wizard';
    }

    public function mount(?int $agreement = null): void
    {
        if ($agreement) {
            // CONVENIO EXISTENTE: Cargar datos del Agreement
            $this->agreementId = $agreement;
            $agreementModel = Agreement::findOrFail($agreement);
            $this->data = $agreementModel->wizard_data ?? [];
            
            // Si no hay datos, cargar valores por defecto
            if (empty($this->data)) {
                $this->loadDefaults();
            }
        } else {
            // CONVENIO NUEVO: Crear Agreement básico
            $newAgreement = Agreement::create([
                'status' => 'expediente_incompleto',
                'current_step' => 1,
                'created_by' => Auth::id(),
            ]);
            $this->agreementId = $newAgreement->id;
            $this->loadDefaults();
        }
    }
    
    private function loadDefaults()
    {
        $this->data = [
            'valor_convenio' => 1495000,
            'porcentaje_comision_sin_iva' => 6.50,
            'monto_credito' => 800000,
            'isr' => 0,
            'cancelacion_hipoteca' => 20000,
            'client_name' => '',
            'client_email' => '',
            'search_term' => '',
            'doc_identificacion' => false,
            'doc_comprobante_ingresos' => false,
        ];
    }

    // Métodos de navegación del wizard
    public function nextStep()
    {
        if ($this->currentStep < 4) {
            $this->currentStep++;
        }
    }
    
    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }
    
    public function goToStep($step)
    {
        if ($step >= 1 && $step <= 4) {
            $this->currentStep = $step;
        }
    }
    
    // Obtener información del paso actual
    public function getCurrentStepInfo()
    {
        return match($this->currentStep) {
            1 => ['title' => 'Búsqueda e Identificación', 'description' => 'Buscar cliente existente o crear nuevo'],
            2 => ['title' => 'Datos del Cliente', 'description' => 'Información del titular'],
            3 => ['title' => 'Calculadora', 'description' => 'Cálculos financieros'],
            4 => ['title' => 'Documentación', 'description' => 'Checklist de documentos'],
            default => ['title' => '', 'description' => '']
        };
    }
    
    // Métodos de acción
    public function searchClient()
    {
        // Lógica de búsqueda de cliente
        // Por ahora solo avanzamos al siguiente paso
        $this->nextStep();
    }
    
    public function submit(): void
    {
        // Finalizar wizard
        if ($this->agreementId) {
            // Guardar datos finales
            Agreement::find($this->agreementId)->update([
                'status' => 'expediente_completo',
                'completion_percentage' => 100,
                'completed_at' => now(),
                'wizard_data' => $this->data,
            ]);
        }
        
        // Mostrar notificación de éxito
        Notification::make()
            ->title('Convenio creado exitosamente')
            ->success()
            ->send();
        
        // Redireccionar a la lista de convenios
        $this->redirect('/admin/wizard');
    }
}
