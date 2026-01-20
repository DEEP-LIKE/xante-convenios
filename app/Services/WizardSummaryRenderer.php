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

        return Blade::render("
            <div style='border-radius: 0.75rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); border-left: 4px solid #4f46e5; background-color: #fff; overflow: hidden; margin-bottom: 1.5rem;'>
                <div style='padding: 1rem;'>
                    <!-- Header -->
                    <div style='display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; border-bottom: 1px solid rgba(0, 0, 0, 0.05); padding-bottom: 0.5rem;'>
                        <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor' style='height: 1.25rem; width: 1.25rem; color: #4f46e5;'>
                            <path stroke-linecap='round' stroke-linejoin='round' d='M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z' />
                        </svg>
                        <h3 style='font-weight: 700; font-size: 1.125rem; color: #4f46e5; margin: 0;'>Datos del Titular</h3>
                    </div>

                    <!-- Main Data Grid -->
                    <div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;'>
                        <!-- Nombre Completo -->
                        <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                            <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>Nombre Completo</div>
                            <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>{{ \$data['holder_name'] ?? 'N/A' }}</div>
                        </div>
                        <!-- Email -->
                        <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                            <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>Email</div>
                            <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>{{ \$data['holder_email'] ?? 'N/A' }}</div>
                        </div>
                        <!-- Teléfono Móvil -->
                        <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                            <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>Teléfono Móvil</div>
                            <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>{{ \$data['holder_phone'] ?? 'N/A' }}</div>
                        </div>
                        <!-- Tel. Oficina -->
                        <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                            <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>Tel. Oficina</div>
                            <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>{{ \$data['holder_office_phone'] ?? 'N/A' }}</div>
                        </div>
                        <!-- CURP -->
                        <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                            <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>CURP</div>
                            <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>{{ \$data['holder_curp'] ?? 'N/A' }}</div>
                        </div>
                        <!-- RFC -->
                        <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                            <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>RFC</div>
                            <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>{{ \$data['holder_rfc'] ?? 'N/A' }}</div>
                        </div>
                        <!-- Estado Civil -->
                        <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                            <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>Estado Civil</div>
                            <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>{{ \$data['holder_civil_status'] ?? 'N/A' }}</div>
                        </div>
                        <!-- Ocupación -->
                        <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                            <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>Ocupación</div>
                            <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>{{ \$data['holder_occupation'] ?? 'N/A' }}</div>
                        </div>
                    </div>

                    @if(!empty(\$data['current_address']))
                    <!-- Domicilio Actual Section -->
                    <div style='margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;'>
                        <h4 style='font-size: 0.875rem; font-weight: 600; color: #111827; margin-bottom: 1rem;'>Domicilio Actual</h4>
                        <div style='display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;'>
                            <!-- Calle y Número -->
                            <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                                <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>Calle y Número</div>
                                <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>
                                    {{ (\$data['current_address'] ?? '') . ' ' . (!empty(\$data['holder_house_number']) ? '#' . \$data['holder_house_number'] : '') }}
                                </div>
                            </div>
                            <!-- Colonia -->
                            <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                                <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>Colonia</div>
                                <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>{{ \$data['neighborhood'] ?? 'N/A' }}</div>
                            </div>
                            <!-- Código Postal -->
                            <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                                <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>Código Postal</div>
                                <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>{{ \$data['postal_code'] ?? 'N/A' }}</div>
                            </div>
                            <!-- Municipio / Estado -->
                            <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                                <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>Municipio / Estado</div>
                                <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>
                                    {{ collect([\$data['municipality'] ?? null, \$data['state'] ?? null])->filter()->join(', ') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        ", ['data' => $data]);
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
        return Blade::render("
            <div style='border-radius: 0.75rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); border-left: 4px solid #7C4794; background-color: #fff; overflow: hidden; margin-bottom: 1.5rem;'>
                <div style='padding: 1rem;'>
                    <div style='display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; border-bottom: 1px solid rgba(0, 0, 0, 0.05); padding-bottom: 0.5rem;'>
                        <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor' style='height: 1.25rem; width: 1.25rem; color: #7C4794;'>
                          <path stroke-linecap='round' stroke-linejoin='round' d='M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205 3 1m1.5.5-1.5-.5M6.75 7.364V3h-3v18m3-13.636 10.5-3.819' />
                        </svg>
                        <h3 style='font-weight: 700; font-size: 1.125rem; color: #7C4794; margin: 0;'>Propiedad del Convenio</h3>
                    </div>

                    <div style='display: flex; flex-direction: column; gap: 0.75rem;'>
                        <div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;'>
                            <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                                <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>Domicilio Vivienda</div>
                                <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>{{ \$data['domicilio_convenio'] ?? 'N/A' }}</div>
                            </div>
                            <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                                <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>Comunidad</div>
                                <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>{{ \$data['comunidad'] ?? 'N/A' }}</div>
                            </div>
                            <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                                <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>Tipo Vivienda</div>
                                <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>{{ \$data['tipo_vivienda'] ?? 'N/A' }}</div>
                            </div>
                            <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                                <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>Prototipo</div>
                                <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>{{ \$data['prototipo'] ?? 'N/A' }}</div>
                            </div>
                            <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                                <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>Lote</div>
                                <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>{{ \$data['lote'] ?? 'N/A' }}</div>
                            </div>
                            <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                                <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>Manzana</div>
                                <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>{{ \$data['manzana'] ?? 'N/A' }}</div>
                            </div>
                            <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                                <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>Etapa</div>
                                <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>{{ \$data['etapa'] ?? 'N/A' }}</div>
                            </div>
                            <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                                <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>Municipio</div>
                                <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>{{ \$data['municipio_propiedad'] ?? 'N/A' }}</div>
                            </div>
                            <div style='display: flex; flex-direction: column; gap: 0.25rem;'>
                                <div style='font-size: 0.875rem; font-weight: 500; color: #6b7280; margin: 0;'>Estado</div>
                                <div style='font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;'>{{ \$data['estado_propiedad'] ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        ", ['data' => $data]);
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
                        :value="\'$\' . number_format($valorConvenio, 2)" 
                    />
                    <x-wizard.infolist-entry 
                        label="Precio Promoción" 
                        :value="\'$\' . number_format($precioPromocion, 2)" 
                    />
                    <x-wizard.infolist-entry 
                        label="Comisión Total" 
                        :value="\'$\' . number_format($comisionTotal, 2)" 
                    />
                    <x-wizard.infolist-entry 
                        label="Ganancia Final" 
                        :value="\'$\' . number_format($gananciaFinal, 2)" 
                    />
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

        // Estado: Con Observaciones
        if ($status === 'with_observations') {
            $validadoPor = $currentValidation?->validatedBy?->name ?? 'Coordinador FI';
            $fecha = $currentValidation?->validated_at->format('d/m/Y H:i') ?? '';
            $observaciones = $currentValidation?->observations ?? 'Sin observaciones registradas.';

            return Blade::render('
                <x-wizard.summary-card title="Requiere Cambios" icon="heroicon-o-exclamation-triangle" color="warning">
                    <p style="font-size: 15px; color: #9a3412; margin-bottom: 20px; line-height: 1.6;">
                        El Coordinador FI ha revisado esta calculadora y ha dejado observaciones que requieren tu atención antes de poder continuar.
                    </p>
                    
                    <div style="background: white; padding: 20px; border-radius: 10px; border: 1px solid #fed7aa; margin-bottom: 20px;">
                        <h4 style="font-size: 14px; font-weight: 700; color: #9a3412; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                            <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            Observaciones:
                        </h4>
                        <div style="font-size: 14px; color: #4b5563; background-color: #fff7ed; padding: 12px; border-radius: 6px; border: 1px dashed #fdba74;">
                            {{ $observaciones }}
                        </div>
                        
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ffedd5; display: flex; align-items: center; gap: 10px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <svg style="width: 16px; height: 16px; color: #f97316;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                <span style="font-size: 13px; color: #9a3412;">Revisado por: <strong>{{ $validadoPor }}</strong></span>
                            </div>
                            <span style="color: #cbd5e1;">|</span>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <svg style="width: 16px; height: 16px; color: #f97316;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span style="font-size: 13px; color: #9a3412;">{{ $fecha }}</span>
                            </div>
                        </div>
                    </div>
                </x-wizard.summary-card>
            ', ['validadoPor' => $validadoPor, 'fecha' => $fecha, 'observaciones' => $observaciones]);
        }

        // Estado: Rechazado
        if ($status === 'rejected') {
            $validadoPor = $currentValidation?->validatedBy?->name ?? 'Coordinador FI';
            $fecha = $currentValidation?->validated_at->format('d/m/Y H:i') ?? '';
            $observaciones = $currentValidation?->observations ?? 'Sin motivo especificado.';

            return Blade::render('
                <x-wizard.summary-card title="Solicitud Rechazada" icon="heroicon-o-x-circle" color="danger">
                    <p style="font-size: 15px; color: #991b1b; margin-bottom: 20px; line-height: 1.6;">
                        Tu solicitud ha sido rechazada. Revisa el motivo a continuación contacta al Coordinador FI para más detalles.
                    </p>
                    
                    <div style="background: white; padding: 20px; border-radius: 10px; border: 1px solid #fecaca; margin-bottom: 20px;">
                        <h4 style="font-size: 14px; font-weight: 700; color: #991b1b; margin-bottom: 10px;">Motivo del rechazo:</h4>
                        <div style="font-size: 14px; color: #4b5563; background-color: #fef2f2; padding: 12px; border-radius: 6px; border: 1px dashed #f87171;">
                            {{ $observaciones }}
                        </div>
                        
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #fee2e2; display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 13px; color: #991b1b;">Rechazado por: <strong>{{ $validadoPor }}</strong></span>
                            <span style="color: #cbd5e1;">|</span>
                            <span style="font-size: 13px; color: #991b1b;">{{ $fecha }}</span>
                        </div>
                    </div>
                </x-wizard.summary-card>
            ', ['validadoPor' => $validadoPor, 'fecha' => $fecha, 'observaciones' => $observaciones]);
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
