@props(['agreement'])

@php
    // Helper function para formatear números de manera segura
    function formatCurrency($value) {
        if (is_null($value) || $value === '') {
            return '0.00';
        }
        
        // Remover comas y convertir a float
        $cleanValue = (float) str_replace(',', '', $value);
        return number_format($cleanValue, 2);
    }
@endphp

<div 
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail.id === 'client-data-modal') open = true"
    x-show="open"
    style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 50; background: rgba(0,0,0,0.5);"
>
    <!-- Modal -->
    <div style="display: flex; min-height: 100vh; align-items: center; justify-content: center; padding: 1rem;">
        <div 
            x-show="open"
            style="background: white; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-width: 64rem; width: 100%; max-height: 90vh; overflow: hidden;"
        >
            <!-- Header del Modal -->
            <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 2.5rem; height: 2.5rem; background: #6366f1; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0;">
                                Datos Completos del Cliente
                            </h3>
                            <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">Información detallada del convenio</p>
                        </div>
                    </div>
                    <button 
                        x-on:click="open = false"
                        style="width: 2rem; height: 2rem; border: none; background: none; color: #6b7280; cursor: pointer; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;"
                    >
                        <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Contenido del Modal -->
            <div style="padding: 1.5rem; overflow-y: auto; max-height: calc(90vh - 120px);">
                    
                    @if($agreement && $agreement->wizard_data)
                        @php
                            $data = $agreement->wizard_data;
                        @endphp
                        
                        <!-- Información General -->
                        <div style="background: #f9fafb; border-radius: 8px; padding: 1.5rem; border: 1px solid #e5e7eb; margin-bottom: 1.5rem;">
                            <h4 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem; display: flex; align-items: center;">
                                <svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem; color: #2563eb;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Información General
                            </h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                <div>
                                    <label style="font-size: 0.875rem; font-weight: 500; color: #6b7280; display: block; margin-bottom: 0.25rem;">ID Xante</label>
                                    <p style="color: #111827; font-weight: 600; margin: 0;">{{ $data['xante_id'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label style="font-size: 0.875rem; font-weight: 500; color: #6b7280; display: block; margin-bottom: 0.25rem;">Fecha de Registro</label>
                                    <p style="color: #111827; margin: 0;">{{ $agreement->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                                <div>
                                    <label style="font-size: 0.875rem; font-weight: 500; color: #6b7280; display: block; margin-bottom: 0.25rem;">Estado del Convenio</label>
                                    <span style="display: inline-flex; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 9999px; background: #dcfce7; color: #166534;">
                                        {{ $agreement->status_label }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Datos del Titular -->
                        <div style="background: white; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden; margin-bottom: 1.5rem;">
                            <div style="background: #f9fafb; padding: 1rem 1.5rem; border-bottom: 1px solid #e5e7eb;">
                                <h4 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0; display: flex; align-items: center;">
                                    <svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem; color: #7c3aed;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Datos del Titular
                                </h4>
                            </div>
                            <div style="padding: 1.5rem;">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                    <div>
                                        <label style="font-size: 0.875rem; font-weight: 500; color: #6b7280; display: block; margin-bottom: 0.25rem;">Nombre Completo</label>
                                        <p style="color: #111827; font-weight: 600; margin: 0;">{{ $data['holder_name'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label style="font-size: 0.875rem; font-weight: 500; color: #6b7280; display: block; margin-bottom: 0.25rem;">CURP</label>
                                        <p style="color: #111827; margin: 0;">{{ $data['holder_curp'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label style="font-size: 0.875rem; font-weight: 500; color: #6b7280; display: block; margin-bottom: 0.25rem;">RFC</label>
                                        <p style="color: #111827; margin: 0;">{{ $data['holder_rfc'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label style="font-size: 0.875rem; font-weight: 500; color: #6b7280; display: block; margin-bottom: 0.25rem;">Email</label>
                                        <p style="color: #111827; margin: 0;">{{ $data['holder_email'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label style="font-size: 0.875rem; font-weight: 500; color: #6b7280; display: block; margin-bottom: 0.25rem;">Teléfono</label>
                                        <p style="color: #111827; margin: 0;">{{ $data['holder_phone'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label style="font-size: 0.875rem; font-weight: 500; color: #6b7280; display: block; margin-bottom: 0.25rem;">Estado Civil</label>
                                        <p style="color: #111827; margin: 0;">{{ $data['holder_civil_status'] ?? 'N/A' }}</p>
                                    </div>
                                    <div style="grid-column: 1 / -1;">
                                        <label style="font-size: 0.875rem; font-weight: 500; color: #6b7280; display: block; margin-bottom: 0.25rem;">Domicilio</label>
                                        <p style="color: #111827; margin: 0;">{{ $data['current_address'] ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Datos de la Propiedad -->
                        <div style="background: white; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden; margin-bottom: 1.5rem;">
                            <div style="background: #f9fafb; padding: 1rem 1.5rem; border-bottom: 1px solid #e5e7eb;">
                                <h4 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0; display: flex; align-items: center;">
                                    <svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem; color: #059669;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                    </svg>
                                    Datos de la Propiedad
                                </h4>
                            </div>
                            <div style="padding: 1.5rem;">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                    <div>
                                        <label style="font-size: 0.875rem; font-weight: 500; color: #6b7280; display: block; margin-bottom: 0.25rem;">Domicilio</label>
                                        <p style="color: #111827; margin: 0;">{{ $data['domicilio_convenio'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label style="font-size: 0.875rem; font-weight: 500; color: #6b7280; display: block; margin-bottom: 0.25rem;">Comunidad</label>
                                        <p style="color: #111827; margin: 0;">{{ $data['comunidad'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label style="font-size: 0.875rem; font-weight: 500; color: #6b7280; display: block; margin-bottom: 0.25rem;">Tipo de Vivienda</label>
                                        <p style="color: #111827; margin: 0;">{{ $data['tipo_vivienda'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label style="font-size: 0.875rem; font-weight: 500; color: #6b7280; display: block; margin-bottom: 0.25rem;">Prototipo</label>
                                        <p style="color: #111827; margin: 0;">{{ $data['prototipo'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label style="font-size: 0.875rem; font-weight: 500; color: #6b7280; display: block; margin-bottom: 0.25rem;">Lote</label>
                                        <p style="color: #111827; margin: 0;">{{ $data['lote'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label style="font-size: 0.875rem; font-weight: 500; color: #6b7280; display: block; margin-bottom: 0.25rem;">Manzana</label>
                                        <p style="color: #111827; margin: 0;">{{ $data['manzana'] ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información Financiera -->
                        <div style="background: white; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden; margin-bottom: 1.5rem;">
                            <div style="background: #f9fafb; padding: 1rem 1.5rem; border-bottom: 1px solid #e5e7eb;">
                                <h4 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0; display: flex; align-items: center;">
                                    <svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem; color: #d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                    Información Financiera
                                </h4>
                            </div>
                            <div style="padding: 1.5rem;">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                    <div>
                                        <label style="font-size: 0.875rem; font-weight: 500; color: #6b7280; display: block; margin-bottom: 0.25rem;">Valor del Convenio</label>
                                        <p style="color: #059669; font-weight: 700; font-size: 1.125rem; margin: 0;">
                                            ${{ formatCurrency($data['valor_convenio'] ?? 0) }}
                                        </p>
                                    </div>
                                    <div>
                                        <label style="font-size: 0.875rem; font-weight: 500; color: #6b7280; display: block; margin-bottom: 0.25rem;">Monto de Crédito</label>
                                        <p style="color: #111827; font-weight: 600; margin: 0;">
                                            ${{ formatCurrency($data['monto_credito'] ?? 0) }}
                                        </p>
                                    </div>
                                    <div>
                                        <label style="font-size: 0.875rem; font-weight: 500; color: #6b7280; display: block; margin-bottom: 0.25rem;">Ganancia Final</label>
                                        <p style="color: #2563eb; font-weight: 700; font-size: 1.125rem; margin: 0;">
                                            ${{ formatCurrency($data['ganancia_final'] ?? 0) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    @else
                        <div style="text-align: center; padding: 2rem 0;">
                            <svg style="margin: 0 auto 1rem; width: 3rem; height: 3rem; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 style="margin: 0.5rem 0; font-size: 0.875rem; font-weight: 500; color: #111827;">No hay datos disponibles</h3>
                            <p style="margin: 0.25rem 0 0; font-size: 0.875rem; color: #6b7280;">No se encontraron datos del cliente para este convenio.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Footer del Modal -->
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                <div class="flex justify-end">
                    <button 
                        x-on:click="open = false"
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
