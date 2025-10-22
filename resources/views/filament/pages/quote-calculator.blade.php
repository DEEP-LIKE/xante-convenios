<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 24px;">
        {{-- Header con información del modo actual --}}
        <div style="background: linear-gradient(90deg, #6C2582 0%, #7C4794 100%); border-radius: 12px; padding: 24px; color: white; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                <div style="flex: 1; min-width: 300px;">
                    <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 8px; margin-top: 0;">
                        @if($selectedClientId)
                            🔗 Modo Enlazado
                        @else
                            ⚡ Modo Rápido
                        @endif
                    </h2>
                    <p style="color: #ddd6fe; font-size: 14px; line-height: 1.5; margin: 0;">
                        @if($selectedClientId)
                            Calculadora enlazada al cliente seleccionado. Los resultados se guardarán automáticamente.
                        @else
                            Calculadora independiente para cálculos rápidos sin guardar datos.
                        @endif
                    </p>
                </div>
                <div style="text-align: right; min-width: 200px;">
                    @if($selectedClientId)
                        <div style="background: rgba(255, 255, 255, 0.2); border-radius: 8px; padding: 12px; backdrop-filter: blur(10px);">
                            <div style="font-size: 12px; font-weight: 500; margin-bottom: 4px;">Cliente Seleccionado</div>
                            <div style="font-size: 16px; font-weight: 700;">{{ $selectedClientIdxante }}</div>
                        </div>
                    @else
                        <div style="background: rgba(255, 255, 255, 0.2); border-radius: 8px; padding: 12px; backdrop-filter: blur(10px);">
                            <div style="font-size: 12px; font-weight: 500; margin-bottom: 4px;">Sin Cliente</div>
                            <div style="font-size: 16px; font-weight: 700;">Cálculo Libre</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Formulario principal --}}
        <div style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); padding: 20px;">
            {{ $this->form }}
        </div>

        {{-- Resumen de resultados (solo si hay cálculos) --}}
        @if($showResults && !empty($calculationResults))
            <div style="background: linear-gradient(135deg, #f0fdf4 0%, #f3f4f6 100%); border-radius: 12px; padding: 20px; border: 1px solid #BDCE0F; margin-top: 20px;">
                <h3 style="font-size: 16px; font-weight: 700; color: #6C2582; margin-bottom: 16px; display: flex; align-items: center;">
                    <svg style="width: 18px; height: 18px; margin-right: 8px;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    Resumen Financiero
                </h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px; margin-bottom: 16px;">
                    <div style="background: white; border-radius: 8px; padding: 16px; border: 1px solid #7C4794;">
                        <div style="font-size: 12px; font-weight: 500; color: #6C2582; margin-bottom: 4px;">Valor del Convenio</div>
                        <div style="font-size: 20px; font-weight: 700; color: #342970;">
                            ${{ number_format($calculationResults['parametros_utilizados']['valor_convenio'] ?? 0, 2) }}
                        </div>
                    </div>
                    
                    <div style="background: white; border-radius: 8px; padding: 16px; border: 1px solid #FFD729;">
                        <div style="font-size: 12px; font-weight: 500; color: #D63B8E; margin-bottom: 4px;">Comisión Total</div>
                        <div style="font-size: 20px; font-weight: 700; color: #AD167F;">
                            ${{ number_format($calculationResults['comision_total_pagar'] ?? 0, 2) }}
                        </div>
                    </div>
                    
                    <div style="background: white; border-radius: 8px; padding: 16px; border: 1px solid #BDCE0F;">
                        <div style="font-size: 12px; font-weight: 500; color: #BDCE0F; margin-bottom: 4px;">Ganancia Final</div>
                        <div style="font-size: 20px; font-weight: 700; color: {{ ($calculationResults['ganancia_final'] ?? 0) > 0 ? '#6C2582' : '#D63B8E' }};">
                            ${{ number_format($calculationResults['ganancia_final'] ?? 0, 2) }}
                        </div>
                        @if(($calculationResults['ganancia_final'] ?? 0) > 0)
                            <div style="font-size: 11px; color: #BDCE0F; margin-top: 4px;">✅ Propuesta Rentable</div>
                        @else
                            <div style="font-size: 11px; color: #D63B8E; margin-top: 4px;">⚠️ Revisar Parámetros</div>
                        @endif
                    </div>
                </div>

                {{-- Indicadores adicionales --}}
                @if(!empty($calculationResults['parametros_utilizados']))
                    @php
                        $valorConvenio = $calculationResults['parametros_utilizados']['valor_convenio'] ?? 0;
                        $gananciaFinal = $calculationResults['ganancia_final'] ?? 0;
                        $porcentajeGanancia = $valorConvenio > 0 ? ($gananciaFinal / $valorConvenio) * 100 : 0;
                    @endphp
                    
                    <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #BDCE0F;">
                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 13px;">
                            <span style="color: #6C2582; font-weight: 500;">Porcentaje de Ganancia:</span>
                            <span style="font-weight: 700; color: {{ $porcentajeGanancia > 0 ? '#6C2582' : '#D63B8E' }};">
                                {{ number_format($porcentajeGanancia, 2) }}%
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Información de ayuda --}}
        <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 8px; padding: 16px; border: 1px solid #7C4794; margin-top: 24px;">
            <h4 style="font-weight: 600; color: #6C2582; margin-bottom: 12px; font-size: 14px;">💡 Cómo usar la calculadora:</h4>
            <ul style="font-size: 13px; color: #342970; line-height: 1.4; margin: 0; padding-left: 16px;">
                <li style="margin-bottom: 6px;"><strong>Modo Enlazado:</strong> Seleccione un cliente para guardar la propuesta enlazada a su ID Xante.</li>
                <li style="margin-bottom: 6px;"><strong>Modo Rápido:</strong> Use sin seleccionar cliente para cálculos rápidos sin guardar.</li>
                <li style="margin-bottom: 6px;"><strong>Valor Convenio:</strong> Campo principal que activa todos los cálculos automáticos.</li>
                <li style="margin-bottom: 6px;"><strong>Parámetros:</strong> Los porcentajes se cargan desde la configuración del sistema.</li>
                <li style="margin-bottom: 0;"><strong>Costos:</strong> ISR y Cancelación de Hipoteca son editables según el caso específico.</li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>
