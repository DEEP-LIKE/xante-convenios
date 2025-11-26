<?php

namespace App\Filament\Schemas\ManageDocuments;

use App\Filament\Pages\ManageDocuments;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\HtmlString;

class StepThreeSchema
{
    public static function make(ManageDocuments $page): array
    {
        $agreement = $page->agreement;
        $documents = $agreement->generatedDocuments ?? collect();

        return [
            // MENSAJE DE ÉXITO
            Section::make('¡Convenio Finalizado con Éxito!')
                ->icon('heroicon-o-check-badge')
                ->iconColor('success')
                ->description('El proceso de gestión documental ha finalizado correctamente')
                ->schema([
                    Placeholder::make('success_message')
                        ->content(new HtmlString('
                            <div style="display: flex; align-items: flex-start; gap: 1rem; padding: 1.5rem; background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-left: 4px solid #10b981; border-radius: 0 0.75rem 0.75rem 0; margin-bottom: 1rem; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);">
                                <div style="flex-shrink: 0;">
                                    <svg style="height: 2rem; width: 2rem; color: #10b981;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 style="font-size: 1.125rem; font-weight: 700; color: #047857; margin-bottom: 0.5rem;">
                                        ✅ Proceso Completado
                                    </h3>
                                    <p style="font-size: 0.875rem; color: #065f46; line-height: 1.5;">
                                        El convenio se ha completado exitosamente. Todos los documentos han sido procesados y están listos para su descarga.
                                    </p>
                                </div>
                            </div>
                        '))
                        ->hiddenLabel()
                        ->columnSpanFull(),
                        
                    // Información del convenio
                    Grid::make(3)
                        ->schema([
                            Placeholder::make('completion_date')
                                ->label('📅 Fecha de Finalización')
                                ->content($agreement->completed_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i')),
                                
                            Placeholder::make('total_documents')
                                ->label('📄 Documentos Generados')
                                ->content($documents->count() . ' PDFs'),
                                
                            Placeholder::make('final_status')
                                ->label('✅ Estado Final')
                                ->content(new HtmlString('<span style="color: #10b981; font-weight: 600;">Completado</span>'))
                        ]),
                ]),
                
            // VALOR DE CIERRE
            Section::make('💰 Valor de Cierre')
                ->icon('heroicon-o-currency-dollar')
                ->iconColor('warning')
                ->description('Registrar el valor final con el que se cerró el convenio')
                ->schema([
                    // Advertencia sobre valor de cierre
                    Placeholder::make('value_warning')
                        ->content(new HtmlString('
                            <div style="display: flex; align-items: flex-start; gap: 1rem; padding: 1rem; background-color: #fff7ed; border-left: 4px solid #FFD729; border-radius: 0 0.5rem 0.5rem 0; margin-bottom: 1rem;">
                                <div style="flex-shrink: 0;">
                                    <svg style="height: 1.5rem; width: 1.5rem; color: #f59e0b;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 style="font-size: 0.875rem; font-weight: 700; color: #92400e; margin-bottom: 0.25rem;">
                                        Valor Único
                                    </h3>
                                    <p style="font-size: 0.875rem; color: #78350f;">
                                        Una vez guardado, el valor de propuesta no podrá ser modificado.
                                    </p>
                                </div>
                            </div>
                        '))
                        ->hiddenLabel()
                        ->columnSpanFull(),
                        
                    // Valores de Referencia Original
                    Grid::make(3)
                        ->schema([
                            Placeholder::make('original_valor_compraventa')
                                ->label('📋 Valor CompraVenta Original')
                                ->content(fn() => new HtmlString($page->getOriginalValorCompraventa())),
                                
                            Placeholder::make('original_comision_total')
                                ->label('💰 Comisión Total Original')
                                ->content(fn() => new HtmlString($page->getOriginalComisionTotal())),
                                
                            Placeholder::make('original_ganancia_final')
                                ->label('💵 Ganancia Final Original')
                                ->content(fn() => new HtmlString($page->getOriginalGananciaFinal())),
                        ])
                        ->columnSpanFull(),
                        
                    // Separador visual
                    Placeholder::make('separator')
                        ->label('')
                        ->content(new HtmlString('<hr style="border: 1px solid #e5e7eb; margin: 16px 0;">'))
                        ->columnSpanFull(),
                        
                    // Valor de Cierre Final
                    Grid::make(2)
                        ->schema([
                            TextInput::make('proposal_value')
                                ->label('🎯 Valor de Propuesta Final Ofrecido')
                                ->numeric()
                                ->prefix('$')
                                ->step(0.01)
                                ->placeholder('Ej: 14896545.50')
                                ->helperText(fn() => $page->agreement->proposal_value 
                                    ? 'Valor registrado el ' . $page->agreement->proposal_saved_at?->format('d/m/Y H:i')
                                    : 'Ingrese el valor final con el que se cerró el convenio (contraoferta)'
                                )
                                ->default(fn() => $page->agreement->proposal_value ?? null)
                                ->disabled(fn() => $page->agreement->proposal_value !== null)
                                ->statePath('proposal_value'),
                                
                            Placeholder::make('proposal_status')
                                ->label('Estado de Registro')
                                ->content(fn() => $page->agreement->proposal_value 
                                    ? '✅ Valor registrado: $' . number_format($page->agreement->proposal_value, 2)
                                    : '⏳ Pendiente de registro'
                                ),
                        ]),
                        
                    Placeholder::make('save_proposal_button')
                        ->label('💾 Guardar Valor de Propuesta')
                        ->content(fn() => view('components.action-button', [
                            'icon' => 'heroicon-o-check-circle',
                            'label' => 'Guardar Valor',
                            'sublabel' => 'de Propuesta Final',
                            'color' => 'success',
                            'action' => 'saveProposalValue',
                            'confirm' => '¿Desea guardar el valor de propuesta registrado?',
                            'prevent' => false
                        ]))
                        ->visible(fn() => $page->agreement->proposal_value === null),
                ]),
                
            // ACCIONES DISPONIBLES
            Section::make('🎯 Acciones Disponibles')
                ->icon('heroicon-o-wrench-screwdriver')
                ->iconColor('primary')
                ->description('Opciones para gestionar el convenio completado')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            // Card: Descargar Todos los Documentos
                            Placeholder::make('action_download')
                                ->label('📥 Descargar Documentos')
                                ->content(fn() => view('components.action-link-button', [
                                    'icon' => 'heroicon-o-arrow-down-tray',
                                    'label' => 'Descargar Todos',
                                    'sublabel' => 'los Documentos PDF',
                                    'url' => route('documents.download-all', ['agreement' => $agreement->id]),
                                    'color' => 'success'
                                ])),
                                
                            // Card: Regresar a Inicio
                            Placeholder::make('action_home')
                                ->label('🏠 Regresar al Dashboard')
                                ->content(fn() => view('components.action-link-button', [
                                    'icon' => 'heroicon-o-home',
                                    'label' => 'Volver al Inicio',
                                    'sublabel' => 'Dashboard Principal',
                                    'url' => '/admin',
                                    'color' => 'primary'
                                ])),
                        ]),
                ]),
        ];
    }
}
