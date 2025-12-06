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
    /**
     * Renderiza el estado de validación con el mismo diseño que los otros cards
     */
    public function renderValidationStatus(string $status, ?object $currentValidation = null): string
    {
        // Estado: Aprobado
        if ($status === 'approved') {
            $aprobadoPor = $currentValidation?->validatedBy?->name ?? 'Coordinador FI';
            $fecha = $currentValidation?->validated_at->format('d/m/Y H:i') ?? '';
            
            return Blade::render('
                <x-wizard.summary-card title="Validación Aprobada" icon="heroicon-s-check-circle" color="success">
                    <p style="font-size: 15px; color: #047857; margin-bottom: 20px; line-height: 1.6;">
                        ¡Excelente! Tu calculadora ha sido aprobada por el Coordinador FI. 
                        Ya puedes continuar con la generación de documentos.
                    </p>
                    
                    <div style="background: white; padding: 20px; border-radius: 10px; border: 1px solid #A7F3D0;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <svg style="width: 20px; height: 20px; color: #10B981;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <div>
                                    <span style="display: block; font-size: 13px; color: #064e3b; font-weight: 600;">Aprobado por</span>
                                    <span style="font-size: 14px; color: #059669;">{{ $aprobadoPor }}</span>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <svg style="width: 20px; height: 20px; color: #10B981;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <div>
                                    <span style="display: block; font-size: 13px; color: #064e3b; font-weight: 600;">Fecha</span>
                                    <span style="font-size: 14px; color: #059669; font-weight: 600;">{{ $fecha }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-wizard.summary-card>
            ', ['aprobadoPor' => $aprobadoPor, 'fecha' => $fecha]);
        }
        
        // Estado: Pendiente
        if ($status === 'pending') {
            $fecha = $currentValidation?->created_at->format('d/m/Y H:i') ?? '';
            $revision = $currentValidation?->revision_number ?? 1;
            
            return Blade::render('
                <x-wizard.summary-card title="En Proceso de Validación" icon="heroicon-o-clock" color="warning">
                    <p style="font-size: 15px; color: #B45309; margin-bottom: 20px; line-height: 1.6;">
                        Tu calculadora está siendo revisada por el Coordinador FI. 
                        Recibirás una notificación cuando sea aprobada o si requiere cambios.
                    </p>
                    
                    <div style="background: white; padding: 20px; border-radius: 10px; border: 1px solid #FED7AA; margin-bottom: 20px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <svg style="width: 20px; height: 20px; color: #F97316;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <div>
                                    <span style="display: block; font-size: 13px; color: #7c2d12; font-weight: 600;">Solicitud enviada</span>
                                    <span style="font-size: 14px; color: #ea580c;">{{ $fecha }}</span>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <svg style="width: 20px; height: 20px; color: #F97316;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                </svg>
                                <div>
                                    <span style="display: block; font-size: 13px; color: #7c2d12; font-weight: 600;">Revisión</span>
                                    <span style="font-size: 14px; color: #ea580c; font-weight: 600;">#{{ $revision }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: rgba(255, 237, 213, 0.5); border-radius: 8px; border-left: 3px solid #F97316;">
                        <svg style="width: 20px; height: 20px; color: #EA580C; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p style="font-size: 13px; color: #9A3412; margin: 0;">
                            No puedes continuar hasta que el coordinador apruebe la calculadora. El botón "Continuar y Generar Documentos" estará deshabilitado.
                        </p>
                    </div>
                </x-wizard.summary-card>
            ', ['fecha' => $fecha, 'revision' => $revision]);
        }
        
        return $this->renderEmptyState('Esperando inicio de validación...');
    }
}
