<div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 50; display: flex; align-items: center; justify-content: center; background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px);">
    <div style="background-color: white; border-radius: 1rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; font-family: sans-serif; width: 100%; max-width: 64rem; margin: 1.5rem; max-height: 90vh; overflow-y: auto;">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); padding: 1.5rem; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 10;">
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 700; color: white; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <svg style="width: 1.5rem; height: 1.5rem; opacity: 0.9;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    Actualizar Calculadora
                </h2>
                <p style="color: #ddd6fe; font-size: 0.875rem; margin-top: 0.25rem;">Recálculo #{{ $recalculationNumber }}</p>
            </div>
            <button type="button" @click="$dispatch('close-modal', {id: 'recalculation-modal'})" style="color: #ede9fe; background: rgba(255,255,255,0.1); border: none; border-radius: 9999px; padding: 0.5rem; cursor: pointer; transition: background 0.2s;">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div style="padding: 1.5rem;">
            <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem; @media (min-width: 768px) { grid-template-columns: 1fr 1fr; }">
                
                <!-- Columna Izquierda: Inputs -->
                <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                    <div style="padding-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb;">
                        <h3 style="font-size: 1rem; font-weight: 600; color: #374151; margin: 0;">Valores Editables</h3>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Valor Convenio</label>
                        <div style="position: relative; border-radius: 0.375rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                            <div style="position: absolute; top: 0; bottom: 0; left: 0; padding-left: 0.75rem; display: flex; align-items: center; pointer-events: none;">
                                <span style="color: #6b7280; font-size: 0.875rem;">$</span>
                            </div>
                            <input type="number" step="0.01" wire:model.live.debounce.500ms="valor_convenio" 
                                   style="display: block; width: 100%; padding: 0.625rem 0.75rem 0.625rem 2rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; color: #111827; outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
                                   onfocus="this.style.borderColor='#8b5cf6'; this.style.boxShadow='0 0 0 3px rgba(139, 92, 246, 0.2)';"
                                   onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
                                   placeholder="0.00">
                        </div>
                        @error('valor_convenio') <span style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem; display: block;">{{ $message }}</span> @enderror
                    </div>

                    <div style="background-color: #f9fafb; border-radius: 0.5rem; padding: 1.25rem; border: 1px solid #f3f4f6; display: flex; flex-direction: column; gap: 1rem;">
                        <p style="margin: 0; font-weight: 600; font-size: 0.875rem; color: #374151;">Gastos y Monto Crédito (Requieren aprobación)</p>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <!-- % Comisión -->
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">Comisión Convenio (%)</label>
                                <div style="position: relative;">
                                    <input type="number" step="0.01" wire:model.live.debounce.500ms="porcentaje_comision_sin_iva" 
                                           style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;">
                                    <span style="position: absolute; right: 0.5rem; top: 0.5rem; color: #9ca3af; font-size: 0.875rem;">%</span>
                                </div>
                            </div>

                            <!-- Monto Crédito -->
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">Monto Crédito</label>
                                <div style="position: relative;">
                                    <span style="position: absolute; left: 0.5rem; top: 0.5rem; color: #9ca3af; font-size: 0.875rem;">$</span>
                                    <input type="number" step="0.01" wire:model.live.debounce.500ms="monto_credito" 
                                           style="width: 100%; padding: 0.5rem 0.5rem 0.5rem 1.25rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;">
                                </div>
                            </div>

                            <!-- ISR -->
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">ISR</label>
                                <div style="position: relative;">
                                    <span style="position: absolute; left: 0.5rem; top: 0.5rem; color: #9ca3af; font-size: 0.875rem;">$</span>
                                    <input type="number" step="0.01" wire:model.live.debounce.500ms="isr" 
                                           style="width: 100%; padding: 0.5rem 0.5rem 0.5rem 1.25rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;">
                                </div>
                            </div>

                            <!-- Cancelación Hipoteca -->
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">Canc. Hipoteca</label>
                                <div style="position: relative;">
                                    <span style="position: absolute; left: 0.5rem; top: 0.5rem; color: #9ca3af; font-size: 0.875rem;">$</span>
                                    <input type="number" step="0.01" wire:model.live.debounce.500ms="cancelacion_hipoteca" 
                                           style="width: 100%; padding: 0.5rem 0.5rem 0.5rem 1.25rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;">
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #e5e7eb;">
                            <p style="margin: 0; font-size: 0.75rem; color: #6b7280;">Comisión Estatal: {{ $state_commission_percentage }}% (Fijo)</p>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Resultados -->
                <div style="background-color: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div style="padding-bottom: 0.75rem; border-bottom: 1px solid #ddd6fe;">
                        <h3 style="font-size: 1rem; font-weight: 600; color: #5b21b6; margin: 0;">Resultados Calculados</h3>
                    </div>
                    
                    <div style="display: flex; justify-between; align-items: center;">
                        <span style="font-size: 0.875rem; font-weight: 500; color: #4b5563;">Comisión Total:</span>
                        <span style="font-size: 1.125rem; font-weight: 700; color: #111827;">${{ number_format($commission_total, 2) }}</span>
                    </div>

                    <div style="display: flex; justify-between; align-items: center;">
                        <span style="font-size: 0.875rem; font-weight: 500; color: #4b5563;">Ganancia Final</span>
                        <span style="font-size: 1.125rem; font-weight: 700; color: #111827;">${{ number_format($final_profit, 2) }}</span>
                    </div>

                    <div style="height: 1px; background-color: #ddd6fe; margin: 0.25rem 0;"></div>

                    <div style="background-color: white; padding: 1rem; border-radius: 0.5rem; border: 1px solid #c4b5fd; display: flex; flex-direction: column; gap: 0.25rem;">
                        <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6d28d9;">Precio Promoción:</span>
                        <span style="font-size: 1.5rem; font-weight: 800; color: #6d28d9;">${{ number_format($precio_promocion, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Motivo -->
            <div style="margin-top: 1.5rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">
                    Motivo del recálculo <span style="color: #ef4444;">*</span>
                </label>
                <textarea wire:model="motivo" rows="2" 
                          style="display: block; width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; color: #111827; outline: none; transition: border-color 0.2s, box-shadow 0.2s; resize: vertical; min-height: 80px;"
                          onfocus="this.style.borderColor='#8b5cf6'; this.style.boxShadow='0 0 0 3px rgba(139, 92, 246, 0.2)';"
                          onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
                          placeholder="Describe el motivo de esta actualización..."></textarea>
                @error('motivo') <span style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem; display: block;">{{ $message }}</span> @enderror
            </div>

            <!-- Actions -->
            <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 0.75rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                <button type="button" @click="$dispatch('close-modal', {id: 'recalculation-modal'})"
                        style="padding: 0.5rem 1rem; background-color: white; border: 1px solid #d1d5db; color: #374151; font-weight: 500; font-size: 0.875rem; border-radius: 0.375rem; cursor: pointer; transition: background 0.2s;"
                        onmouseover="this.style.backgroundColor='#f9fafb'"
                        onmouseout="this.style.backgroundColor='white'">
                    Cancelar
                </button>
                <button type="button" wire:click="save" wire:loading.attr="disabled"
                        style="padding: 0.5rem 1.25rem; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; font-weight: 500; font-size: 0.875rem; border-radius: 0.375rem; border: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(124, 58, 237, 0.3); transition: transform 0.1s; display: flex; align-items: center; gap: 0.5rem;"
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 8px -1px rgba(124, 58, 237, 0.4)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(124, 58, 237, 0.3)';"
                        onmousedown="this.style.transform='translateY(1px)';">
                    <span wire:loading.remove>Guardar Actualización</span>
                    <span wire:loading>Guardando...</span>
                </button>
            </div>
        </div>
    </div>
</div>
