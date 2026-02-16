@php
    $recalculations = $recalculations->sortByDesc('created_at');
@endphp

<div style="display: flex; flex-direction: column; gap: 1rem;">
    @foreach($recalculations as $recalc)
        <div style="background-color: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 0.75rem; padding: 1.25rem; position: relative; overflow: hidden;">
            <!-- Decorator -->
            <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: #8b5cf6;"></div>

            <!-- Header: Metadatos -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <h4 style="font-size: 1rem; font-weight: 700; color: #5b21b6; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Actualización #{{ $recalc->recalculation_number }}
                    </h4>
                    <p style="font-size: 0.875rem; color: #6d28d9; margin: 0.25rem 0 0 0;">
                        {{ $recalc->created_at->timezone('America/Mexico_City')->format('d/m/Y H:i') }} • Por: {{ $recalc->user->name ?? 'Sistema' }}
                    </p>
                </div>
                
                @if($recalc->motivo)
                <div style="background-color: white; padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid #ede9fe; max-width: 300px;">
                    <p style="font-size: 0.75rem; color: #4c1d95; margin: 0; font-style: italic;">
                        "{{ $recalc->motivo }}"
                    </p>
                </div>
                @endif
            </div>

            <!-- Grid Financiero -->
            <div style="display: flex; flex-wrap: wrap; gap: 1rem; padding-top: 1rem; border-top: 1px solid #ddd6fe;">
                
                <div style="flex: 1; min-width: 140px;">
                    <span style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #7c3aed; margin-bottom: 0.25rem;">Valor Convenio</span>
                    <span style="display: block; font-size: 1.125rem; font-weight: 700; color: #111827;">$ {{ number_format($recalc->agreement_value, 2) }}</span>
                </div>

                <div style="flex: 1; min-width: 140px;">
                    <span style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #7c3aed; margin-bottom: 0.25rem;">Precio Promoción</span>
                    <span style="display: block; font-size: 1.125rem; font-weight: 700; color: #111827;">$ {{ number_format($recalc->proposal_value, 2) }}</span>
                </div>

                <div style="flex: 1; min-width: 140px;">
                    <span style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #7c3aed; margin-bottom: 0.25rem;">Ganancia Final</span>
                    <span style="display: block; font-size: 1.125rem; font-weight: 700; color: {{ $recalc->final_profit >= 0 ? '#16a34a' : '#ef4444' }};">
                        $ {{ number_format($recalc->final_profit, 2) }}
                    </span>
                </div>

            </div>
        </div>
    @endforeach

    <!-- Cálculo Original (Siempre al final) -->
    <div style="background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; opacity: 0.8;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
            <svg style="width: 1.25rem; height: 1.25rem; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <h4 style="font-size: 1rem; font-weight: 700; color: #374151; margin: 0;">Cálculo Original</h4>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
             <div style="flex: 1; min-width: 140px;">
                <span style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #6b7280; margin-bottom: 0.25rem;">Valor Convenio</span>
                <span style="display: block; font-size: 1.125rem; font-weight: 700; color: #4b5563;">
                    $ {{ number_format($original->wizard_data['valor_convenio'] ?? 0, 2) }}
                </span>
            </div>

            <div style="flex: 1; min-width: 140px;">
                <span style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #6b7280; margin-bottom: 0.25rem;">Precio Promoción</span>
                <span style="display: block; font-size: 1.125rem; font-weight: 700; color: #4b5563;">
                    $ {{ number_format($original->wizard_data['precio_promocion'] ?? 0, 2) }}
                </span>
            </div>

             <div style="flex: 1; min-width: 140px;">
                <span style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #6b7280; margin-bottom: 0.25rem;">Ganancia Final</span>
                <span style="display: block; font-size: 1.125rem; font-weight: 700; color: #4b5563;">
                    $ {{ number_format($original->wizard_data['ganancia_final'] ?? 0, 2) }}
                </span>
            </div>
        </div>
    </div>
</div>
