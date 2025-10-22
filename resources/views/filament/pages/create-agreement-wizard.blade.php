<x-filament-panels::page>
    <div class="max-w-4xl mx-auto">
        {{ $this->form }}
        
        {{-- Resumen Financiero (solo si hay cálculos) --}}
        @if(!empty($this->data['valor_convenio']) && $this->data['valor_convenio'] > 0)
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
                            ${{ number_format($this->data['valor_convenio'] ?? 0, 2) }}
                        </div>
                    </div>
                    
                    <div style="background: white; border-radius: 8px; padding: 16px; border: 1px solid #FFD729;">
                        <div style="font-size: 12px; font-weight: 500; color: #D63B8E; margin-bottom: 4px;">Comisión Total</div>
                        <div style="font-size: 20px; font-weight: 700; color: #AD167F;">
                            ${{ number_format($this->data['comision_total_pagar'] ?? 0, 2) }}
                        </div>
                    </div>
                    
                    <div style="background: white; border-radius: 8px; padding: 16px; border: 1px solid #BDCE0F;">
                        <div style="font-size: 12px; font-weight: 500; color: #BDCE0F; margin-bottom: 4px;">Ganancia Final</div>
                        <div style="font-size: 20px; font-weight: 700; color: {{ ($this->data['ganancia_final'] ?? 0) > 0 ? '#6C2582' : '#D63B8E' }};">
                            ${{ number_format($this->data['ganancia_final'] ?? 0, 2) }}
                        </div>
                        @if(($this->data['ganancia_final'] ?? 0) > 0)
                            <div style="font-size: 11px; color: #BDCE0F; margin-top: 4px;">✅ Propuesta Rentable</div>
                        @else
                            <div style="font-size: 11px; color: #D63B8E; margin-top: 4px;">⚠️ Revisar Parámetros</div>
                        @endif
                    </div>
                </div>

                {{-- Porcentaje de Ganancia --}}
                @if(!empty($this->data['valor_convenio']) && $this->data['valor_convenio'] > 0)
                    @php
                        $valorConvenio = $this->data['valor_convenio'] ?? 0;
                        $gananciaFinal = $this->data['ganancia_final'] ?? 0;
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
    </div>
    
    <!-- Componente de Loading Xante -->
    @livewire('xante-loading')

    @push('scripts')
    <script>
        // Escuchar el evento de actualización de query string
        document.addEventListener('livewire:init', () => {
            Livewire.on('update-query-string', (data) => {
                if (data && data.agreement) {
                    // Actualizar la URL sin recargar la página
                    const url = new URL(window.location);
                    
                    // Remover client_id si existe y agregar agreement
                    url.searchParams.delete('client_id');
                    url.searchParams.set('agreement', data.agreement);
                    
                    // Actualizar la URL
                    window.history.replaceState({}, '', url);
                    
                    console.log('URL actualizada con agreement ID:', data.agreement);
                }
            });
        });
    </script>
    @endpush
</x-filament-panels::page>
