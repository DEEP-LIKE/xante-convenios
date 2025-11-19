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
            Section::make('¡Convenio Finalizado con Éxito!')
                ->icon('heroicon-o-check-badge')
                ->iconColor('success')
                ->description('El proceso de gestión documental ha finalizado correctamente')
                ->schema([
                    // Mensaje de celebración
                    Placeholder::make('celebration')
                        ->content('✅ El convenio se ha completado exitosamente. Todos los documentos han sido procesados.')
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
                                ->content('Completado')
                        ]),
                ]),
                
                
            Section::make('💰 Valor de Cierre')
                ->icon('heroicon-o-currency-dollar')
                ->iconColor('success')
                ->description('Registrar el valor final con el que se cerró el convenio')
                ->schema([
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
                        
                    // Comparación de valores
                    // Grid::make(1)
                    //     ->schema([
                    //         Placeholder::make('value_comparison')
                    //             ->label('📊 Comparación de Valores')
                    //             ->content(fn() => new HtmlString($page->getValueComparison()))
                    //             ->visible(fn() => $page->agreement->proposal_value !== null),
                    //     ])
                    //     ->columnSpanFull(),
                        
                    Placeholder::make('save_proposal_button')
                        ->label('💾 Guardar Valor de Propuesta')
                        ->content(fn() => view('components.action-button', [
                            'icon' => 'heroicon-o-check-circle',
                            'label' => 'Guardar Valor',
                            'sublabel' => 'de Propuesta Final',
                            'color' => 'success',
                            'action' => 'saveProposalValue',
                            'confirm' => '¿Desea guardar el valor de propuesta registrado?',
                            'prevent' => false // Evitar el prevent. que causa conflictos con wire:confirm
                        ]))
                        ->visible(fn() => $page->agreement->proposal_value === null),
                ]),
                
            Section::make('Acciones Disponibles')
            ->icon('heroicon-o-wrench-screwdriver')
            ->iconColor('warning')
            ->description('Opciones para gestionar el convenio completado')
            ->schema([
                Grid::make(3)
                    ->schema([
                        // Card: Descargar Todos los Documentos
                        Placeholder::make('action_download')
                            ->label('📥 Descargar Documentos')
                            ->content(fn() => view('components.action-button', [
                                'icon' => 'heroicon-o-arrow-down-tray',
                                'label' => 'Descargar Todos',
                                'sublabel' => 'los Documentos PDF',
                                'action' => 'downloadAllDocuments',
                                'color' => 'success'
                            ])),
                            
                        // // Card: Enviar Correos
                        // Placeholder::make('action_email')
                        //     ->label('📧 Enviar por Email')
                        //     ->content(fn() => view('components.action-button', [
                        //         'icon' => 'heroicon-o-envelope',
                        //         'label' => 'Enviar Correos',
                        //         'sublabel' => 'Reenviar Documentos',
                        //         'action' => 'sendDocumentsToClient',
                        //         'color' => 'info',
                        //         'confirm' => '¿Está seguro de reenviar los documentos al cliente?'
                        //     ])),
                            
                        // Card: Regresar a Inicio
                        Placeholder::make('action_home')
                            ->label('🏠 Regresar al Dashboard')
                            ->content(fn() => view('components.action-button', [
                                'icon' => 'heroicon-o-home',
                                'label' => 'Volver al Inicio',
                                'sublabel' => 'Dashboard Principal',
                                'action' => 'returnToHome',
                                'color' => 'primary'
                            ])),
                    ]),
            ]),
        ];
    }
}
