<?php

namespace App\Services;

use Illuminate\Support\Facades\Blade;

/**
 * Servicio para renderizar resúmenes HTML del wizard
 */
class WizardSummaryRenderer
{
    /**
     * Renderiza el resumen de datos del TITULAR
     */
    public function renderHolderSummary(array $data): string
    {
        if (empty($data['holder_name'])) {
            return $this->renderEmptyState('No se capturó información del titular');
        }

        return Blade::render('
            <x-wizard.summary-card title="Datos del Titular" icon="heroicon-o-user" color="primary">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                    <x-wizard.infolist-entry label="Nombre Completo" :value="$data[\'holder_name\'] ?? null" />
                    <x-wizard.infolist-entry label="Email" :value="$data[\'holder_email\'] ?? null" />
                    <x-wizard.infolist-entry label="Teléfono Móvil" :value="$data[\'holder_phone\'] ?? null" />
                    <x-wizard.infolist-entry label="Tel. Oficina" :value="$data[\'holder_office_phone\'] ?? null" />
                    <x-wizard.infolist-entry label="CURP" :value="$data[\'holder_curp\'] ?? null" />
                    <x-wizard.infolist-entry label="RFC" :value="$data[\'holder_rfc\'] ?? null" />
                    <x-wizard.infolist-entry label="Estado Civil" :value="$data[\'holder_civil_status\'] ?? null" />
                    <x-wizard.infolist-entry label="Ocupación" :value="$data[\'holder_occupation\'] ?? null" />
                </div>

                @if(!empty($data[\'current_address\']))
                    <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                        <h4 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">Domicilio Actual</h4>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                            <x-wizard.infolist-entry 
                                label="Calle y Número" 
                                :value="($data[\'current_address\'] ?? \'\') . \' \' . (!empty($data[\'holder_house_number\']) ? \'#\' . $data[\'holder_house_number\'] : \'\')" 
                            />
                            <x-wizard.infolist-entry label="Colonia" :value="$data[\'neighborhood\'] ?? null" />
                            <x-wizard.infolist-entry label="Código Postal" :value="$data[\'postal_code\'] ?? null" />
                            <x-wizard.infolist-entry 
                                label="Municipio / Estado" 
                                :value="collect([$data[\'municipality\'] ?? null, $data[\'state\'] ?? null])->filter()->join(\', \')" 
                            />
                        </div>
                    </div>
                @endif
            </x-wizard.summary-card>
        ', ['data' => $data]);
    }

    /**
     * Renderiza el resumen de datos del CÓNYUGE/COACREDITADO
     */
    public function renderSpouseSummary(array $data): string
    {
        if (empty($data['spouse_name'])) {
            return $this->renderEmptyState('No se capturó información del cónyuge / coacreditado');
        }

        return Blade::render('
            <x-wizard.summary-card title="Cónyuge / Coacreditado" icon="heroicon-o-users" color="success">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                    <x-wizard.infolist-entry label="Nombre Completo" :value="$data[\'spouse_name\'] ?? null" />
                    <x-wizard.infolist-entry label="Email" :value="$data[\'spouse_email\'] ?? null" />
                    <x-wizard.infolist-entry label="Teléfono Móvil" :value="$data[\'spouse_phone\'] ?? null" />
                    <x-wizard.infolist-entry label="CURP" :value="$data[\'spouse_curp\'] ?? null" />
                </div>

                @if(!empty($data[\'spouse_current_address\']))
                    <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                        <h4 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">Domicilio</h4>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                            <x-wizard.infolist-entry 
                                label="Calle y Número" 
                                :value="($data[\'spouse_current_address\'] ?? \'\') . \' \' . (!empty($data[\'spouse_house_number\']) ? \'#\' . $data[\'spouse_house_number\'] : \'\')" 
                            />
                            <x-wizard.infolist-entry label="Colonia" :value="$data[\'spouse_neighborhood\'] ?? null" />
                            <x-wizard.infolist-entry label="Código Postal" :value="$data[\'spouse_postal_code\'] ?? null" />
                            <x-wizard.infolist-entry 
                                label="Municipio / Estado" 
                                :value="collect([$data[\'spouse_municipality\'] ?? null, $data[\'spouse_state\'] ?? null])->filter()->join(\', \')" 
                            />
                        </div>
                    </div>
                @endif
            </x-wizard.summary-card>
        ', ['data' => $data]);
    }

    /**
     * Renderiza el resumen de datos de la propiedad
     */
    public function renderPropertySummary(array $data): string
    {
        return Blade::render('
            <x-wizard.summary-card title="Propiedad del Convenio" icon="heroicon-o-home-modern" color="info">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                    <x-wizard.infolist-entry label="Domicilio Vivienda" :value="$data[\'domicilio_convenio\'] ?? null" />
                    <x-wizard.infolist-entry label="Comunidad" :value="$data[\'comunidad\'] ?? null" />
                    <x-wizard.infolist-entry label="Tipo Vivienda" :value="$data[\'tipo_vivienda\'] ?? null" />
                    <x-wizard.infolist-entry label="Prototipo" :value="$data[\'prototipo\'] ?? null" />
                    <x-wizard.infolist-entry label="Lote" :value="$data[\'lote\'] ?? null" />
                    <x-wizard.infolist-entry label="Manzana" :value="$data[\'manzana\'] ?? null" />
                    <x-wizard.infolist-entry label="Etapa" :value="$data[\'etapa\'] ?? null" />
                    <x-wizard.infolist-entry label="Municipio" :value="$data[\'municipio_propiedad\'] ?? null" />
                    <x-wizard.infolist-entry label="Estado" :value="$data[\'estado_propiedad\'] ?? null" />
                </div>
            </x-wizard.summary-card>
        ', ['data' => $data]);
    }

    /**
     * Renderiza el resumen financiero
     */
    public function renderFinancialSummary(array $data): string
    {
        $valorConvenio = floatval(str_replace(',', '', $data['valor_convenio'] ?? 0));
        $precioPromocion = floatval(str_replace(',', '', $data['precio_promocion'] ?? 0));
        $comisionTotal = floatval(str_replace(',', '', $data['comision_total_pagar'] ?? 0));
        $gananciaFinal = floatval(str_replace(',', '', $data['ganancia_final'] ?? 0));

        $viewData = [
            'valorConvenio' => $valorConvenio,
            'precioPromocion' => $precioPromocion,
            'comisionTotal' => $comisionTotal,
            'gananciaFinal' => $gananciaFinal,
        ];

        return Blade::render('
            <x-wizard.summary-card title="Resumen Financiero" icon="heroicon-o-currency-dollar" color="warning">
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
                    <x-wizard.infolist-entry 
                        label="Valor Convenio" 
                        :value="\' $\' . number_format($valorConvenio, 2)" 
                    />
                    <x-wizard.infolist-entry 
                        label="Precio Promoción" 
                        :value="\'$\' . number_format($precioPromocion, 2)" 
                    </div>
                </div>
            </x-wizard.summary-card>
        ', $viewData);
    }

    private function renderEmptyState(string $message): string
    {
        return Blade::render('
            <div class="rounded-xl border-2 border-dashed border-gray-300 p-6 text-center">
                <div class="text-gray-400 mb-2">
                    <x-filament::icon icon="heroicon-o-document" class="h-8 w-8 mx-auto" />
                </div>
                <span class="text-gray-500 font-medium">{{ $message }}</span>
            </div>
        ', ['message' => $message]);
    }
}
