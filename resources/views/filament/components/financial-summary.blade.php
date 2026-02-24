<div style="background-color: #fefce8; border: 1px solid #fef08a; border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); margin-bottom: 1.5rem; font-family: sans-serif;">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
        <div style="background-color: #fef9c3; color: #a16207; padding: 0.375rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div>
            <h3 style="font-size: 1.125rem; font-weight: 700; color: #713f12; line-height: 1; margin: 0;">Resumen Financiero</h3>
            <p style="font-size: 0.875rem; color: #a16207; margin: 0.25rem 0 0 0;">Valores financieros actuales del convenio</p>
        </div>
    </div>

    @if($agreement->hasPendingValidation() || $agreement->hasPendingAuthorization())
        <div style="margin-bottom: 1.5rem; padding: 1rem; background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 0.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <div style="background-color: #fef3c7; color: #d97706; padding: 0.375rem; border-radius: 9999px;">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <span style="display: block; font-size: 0.875rem; font-weight: 700; color: #92400e;">Actualización Pendiente</span>
                <span style="display: block; font-size: 0.75rem; color: #b45309;">La próxima actualización del resumen financiero está en proceso de validación por un superior.</span>
            </div>
        </div>
    @endif

    <div style="display: flex; flex-wrap: wrap; gap: 1.5rem; justify-content: space-between;">
        <div style="flex: 1; min-width: 140px;">
            <span style="display: block; font-size: 0.875rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">Valor Convenio</span>
            <span style="display: block; font-size: 1.25rem; font-weight: 700; color: #111827;">$ {{ number_format($agreement->currentFinancials['agreement_value'], 2) }}</span>
        </div>
        
        <div style="flex: 1; min-width: 140px;">
            <span style="display: block; font-size: 0.875rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">Precio Promoción</span>
            <span style="display: block; font-size: 1.25rem; font-weight: 700; color: #111827;">$ {{ number_format($agreement->currentFinancials['proposal_value'], 2) }}</span>
        </div>

        <div style="flex: 1; min-width: 140px;">
            <span style="display: block; font-size: 0.875rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">Comisión Total</span>
            <span style="display: block; font-size: 1.25rem; font-weight: 700; color: #111827;">$ {{ number_format($agreement->currentFinancials['commission_total'], 2) }}</span>
        </div>

        <div style="flex: 1; min-width: 140px;">
            <span style="display: block; font-size: 0.875rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">Ganancia Final</span>
            <span style="display: block; font-size: 1.25rem; font-weight: 700; color: #16a34a;">$ {{ number_format($agreement->currentFinancials['final_profit'], 2) }}</span>
        </div>
    </div>

    @if($agreement->currentFinancials['is_recalculated'])
        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #fef08a;">
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <span style="display: inline-flex; align-items: center; padding: 0.125rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background-color: #dbeafe; color: #1e40af;">
                    Recálculo #{{ $agreement->currentFinancials['recalculation_number'] }}
                </span>
                <span style="font-size: 0.75rem; color: #6b7280;">
                    Actualizado: {{ $agreement->currentFinancials['recalculation_date']->timezone('America/Mexico_City')->format('d/m/Y H:i') }}
                </span>
                <span style="font-size: 0.75rem; color: #6b7280; border-left: 1px solid #d1d5db; padding-left: 0.5rem; margin-left: 0.25rem;">
                     Por: {{ $agreement->currentFinancials['user']->name ?? 'Usuario' }}
                </span>
                @if(!empty($agreement->currentFinancials['authorized_user']) && $agreement->currentFinancials['authorized_by'] !== ($agreement->currentFinancials['user']->id ?? null))
                    <span style="font-size: 0.75rem; color: #6b7280; border-left: 1px solid #d1d5db; padding-left: 0.5rem; margin-left: 0.25rem;">
                        Autorizado por: {{ $agreement->currentFinancials['authorized_user']->name }}
                    </span>
                @endif
            </div>
            
            @if(!empty($agreement->currentFinancials['motivo']))
                <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #854d0e; background-color: rgba(254, 240, 138, 0.5); padding: 0.375rem 0.5rem; border-radius: 0.375rem; border: 1px dashed #eab308;">
                    <strong>Motivo:</strong> {{ $agreement->currentFinancials['motivo'] }}
                </div>
            @endif
        </div>
    @endif
</div>
