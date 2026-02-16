<?php

namespace App\Livewire;

use App\Models\Agreement;
use App\Models\AgreementRecalculation;
use App\Services\AgreementCalculatorService;
use Filament\Notifications\Notification;
use Livewire\Component;

class AgreementRecalculationModal extends Component
{
    public $agreementId;
    public $agreement;
    
    // Campos Financieros
    public $valor_convenio;
    public $precio_promocion;
    public $commission_total;
    public $final_profit;
    public $motivo;

    // Campos de cálculo interno (invisibles o readonly)
    public $state_commission_percentage;
    public $porcentaje_comision_sin_iva;
    public $isr;
    public $cancelacion_hipoteca;
    public $monto_credito;
    public $monto_comision_sin_iva;
    public $comision_iva_incluido;
    public $total_gastos_fi_venta;
    public $estado_propiedad;

    // Control
    public $recalculationNumber;

    protected $rules = [
        'valor_convenio' => 'required|numeric|min:0',
        'motivo' => 'required|string|min:10',
    ];

    public function mount($agreementId)
    {
        $this->agreementId = $agreementId;
        $this->agreement = Agreement::findOrFail($agreementId);
        
        // Cargar valores iniciales
        $this->loadInitialValues();
        // realizar un primer cálculo para asegurar consistencia
        $this->recalculate(); 
    }

    public function loadInitialValues()
    {
        $latest = $this->agreement->latestRecalculation;
        // Usar el accessor robusto que ya incluye lógica de fallback
        $financials = $this->agreement->currentFinancials; 
        
        // Si hay recálculo previo, usar sus datos (incluyendo calculation_data para restaurar estado)
        if ($latest && $latest->calculation_data) {
            $data = $latest->calculation_data;
            $this->recalculationNumber = $latest->recalculation_number + 1;
        } else {
            // Si es el primero, usar datos del wizard (inputs) + financials (resultados)
            $data = $this->agreement->wizard_data ?? [];
            $this->recalculationNumber = 1;

            // Asegurar que los valores financieros base vengan del accessor si no están en wizard_data
            if (empty($data['valor_convenio'])) $data['valor_convenio'] = $financials['agreement_value'];
            if (empty($data['precio_promocion'])) $data['precio_promocion'] = $financials['proposal_value'];
            if (empty($data['comision_total_pagar'])) $data['comision_total_pagar'] = $financials['commission_total'];
            if (empty($data['ganancia_final'])) $data['ganancia_final'] = $financials['final_profit'];
        }

        // Asignar propiedades
        $this->valor_convenio = $data['valor_convenio'] ?? 0;
        $this->precio_promocion = $data['precio_promocion'] ?? 0;
        $this->commission_total = $data['comision_total_pagar'] ?? $data['comision_total'] ?? 0;
        $this->final_profit = $data['ganancia_final'] ?? 0;

        // Variables de cálculo (Inputs)
        $this->state_commission_percentage = $data['state_commission_percentage'] ?? 0;
        $this->porcentaje_comision_sin_iva = $data['porcentaje_comision_sin_iva'] ?? 6.50;
        $this->isr = $data['isr'] ?? 0;
        $this->cancelacion_hipoteca = $data['cancelacion_hipoteca'] ?? 0;
        $this->monto_credito = $data['monto_credito'] ?? 0;
        $this->estado_propiedad = $data['estado_propiedad'] ?? $data['holder_state'] ?? null;
        
        // Si porcentaje estatal es 0, intentar buscarlo de nuevo por estado
        if (($this->state_commission_percentage == 0) && $this->estado_propiedad) {
             $rate = \App\Models\StateCommissionRate::where('state_name', $this->estado_propiedad)->first();
             $this->state_commission_percentage = $rate ? (float) $rate->commission_percentage : 0;
        }

        // Si valor convenio sigue siendo 0 (caso extremo), intentar tomarlo de la columna directamente por si acaso
        if ($this->valor_convenio == 0 && $this->agreement->agreement_value > 0) {
            $this->valor_convenio = $this->agreement->agreement_value;
        }
    }

    public function updatedValorConvenio()
    {
        $this->recalculate();
    }

    public function recalculate()
    {
        // Limpiar formato moneda si viene como string
        $valorConvenio = (float) str_replace(['$', ','], '', $this->valor_convenio);
        $this->valor_convenio = $valorConvenio;

        if ($valorConvenio <= 0) {
            $this->precio_promocion = 0;
            $this->commission_total = 0;
            $this->final_profit = 0;
            return;
        }

        $calculatorService = app(AgreementCalculatorService::class);

        // Preparar parámetros
        $ivaPercentage = (float) (\App\Models\ConfigurationCalculator::where('key', 'iva_valor')->value('value') ?? 16.00);
        $ivaMultiplier = 1 + ($ivaPercentage / 100);
        $multiplicadorPrecioPromocion = 1 + ($this->state_commission_percentage / 100);

        $parameters = [
            'porcentaje_comision_sin_iva' => (float) $this->porcentaje_comision_sin_iva,
            'base_iva_percentage' => $ivaPercentage,
            'iva_multiplier' => $ivaMultiplier,
            'precio_promocion_multiplicador' => $multiplicadorPrecioPromocion,
            'isr' => (float) $this->isr,
            'cancelacion_hipoteca' => (float) $this->cancelacion_hipoteca,
            'monto_credito' => (float) $this->monto_credito,
        ];

        $calculations = $calculatorService->calculateAllFinancials($valorConvenio, $parameters);
        
        // Actualizar propiedades con resultados
        $this->precio_promocion = $calculations['precio_promocion'];
        $this->commission_total = $calculations['comision_total_pagar'];
        $this->final_profit = $calculations['ganancia_final'];
        $this->monto_comision_sin_iva = $calculations['monto_comision_sin_iva'];
        $this->comision_iva_incluido = $calculations['comision_iva_incluido'];
        $this->total_gastos_fi_venta = $calculations['total_gastos_fi_venta'];
    }

    public function save()
    {
        $this->validate();

        // Preparar calculation_data (snapshot completo)
        $calculationData = [
            'valor_convenio' => $this->valor_convenio,
            'precio_promocion' => $this->precio_promocion,
            'comision_total_pagar' => $this->commission_total,
            'ganancia_final' => $this->final_profit,
            'state_commission_percentage' => $this->state_commission_percentage,
            'porcentaje_comision_sin_iva' => $this->porcentaje_comision_sin_iva,
            'isr' => $this->isr,
            'cancelacion_hipoteca' => $this->cancelacion_hipoteca,
            'monto_credito' => $this->monto_credito,
            'monto_comision_sin_iva' => $this->monto_comision_sin_iva,
            'comision_iva_incluido' => $this->comision_iva_incluido,
            'total_gastos_fi_venta' => $this->total_gastos_fi_venta,
            'estado_propiedad' => $this->estado_propiedad,
        ];

        AgreementRecalculation::create([
            'agreement_id' => $this->agreementId,
            'user_id' => auth()->id(),
            'recalculation_number' => $this->agreement->recalculations()->count() + 1,
            'agreement_value' => $this->valor_convenio,
            'proposal_value' => $this->precio_promocion,
            'commission_total' => $this->commission_total,
            'final_profit' => $this->final_profit,
            'calculation_data' => $calculationData,
            'motivo' => $this->motivo,
        ]);

        Notification::make()
            ->title('✓ Recálculo guardado exitosamente')
            ->success()
            ->send();

        $this->dispatch('close-modal', id: 'recalculation-modal');
        $this->dispatch('recalculation-saved'); // Para actualizar la vista padre
        
        // Reset (opcional, si se mantuviera abierto)
        $this->motivo = '';
        $this->loadInitialValues();
        
        $this->redirect(request()->header('Referer')); // Recargar para ver cambios
    }

    public function render()
    {
        return view('livewire.agreement-recalculation-modal');
    }
}
