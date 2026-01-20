<?php

namespace App\Filament\Schemas\ManageDocuments;

use App\Filament\Pages\ManageDocuments;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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
                                ->content($documents->count().' PDFs'),

                            Placeholder::make('final_status')
                                ->label('✅ Estado Final')
                                ->content(new HtmlString('<span style="color: #10b981; font-weight: 600;">Completado</span>')),
                        ]),
                ]),

            // AUTORIZACIÓN DE PRECIO FINAL
            Section::make('🔐 Autorización de Precio Final')
                ->icon('heroicon-o-shield-check')
                ->iconColor('info')
                ->description('Solicitar autorización administrativa para precio final acordado')
                ->schema(function () use ($page, $agreement) {
                    // Obtener la última autorización (pendiente o aprobada/rechazada)
                    $latestAuth = $agreement->finalPriceAuthorizations()
                        ->latest()
                        ->first();

                    $baseSchema = [
                        // Valores de Referencia Original
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('original_valor_compraventa')
                                    ->label('📋 Valor CompraVenta Original')
                                    ->content(fn () => new HtmlString($page->getOriginalValorCompraventa())),

                                Placeholder::make('original_comision_total')
                                    ->label('💰 Comisión Total Original')
                                    ->content(fn () => new HtmlString($page->getOriginalComisionTotal())),

                                Placeholder::make('original_ganancia_final')
                                    ->label('💵 Ganancia Final Original')
                                    ->content(fn () => new HtmlString($page->getOriginalGananciaFinal())),
                            ])
                            ->columnSpanFull(),

                        // Separador visual
                        Placeholder::make('separator')
                            ->label(' ')
                            ->content(new HtmlString('<hr style="border: 1px solid #e5e7eb; margin: 16px 0;">'))
                            ->columnSpanFull(),
                    ];

                    // Si no hay autorización o fue rechazada, mostrar formulario
                    if (! $latestAuth || $latestAuth->status === 'rejected') {
                        return array_merge($baseSchema, [
                            Placeholder::make('auth_info')
                                ->content(new HtmlString('
                                    <div style="display: flex; align-items: flex-start; gap: 1rem; padding: 1rem; background-color: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 0 0.5rem 0.5rem 0; margin-bottom: 1rem;">
                                        <div style="flex-shrink: 0;">
                                            <svg style="height: 1.5rem; width: 1.5rem; color: #3b82f6;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 style="font-size: 0.875rem; font-weight: 700; color: #1e40af; margin-bottom: 0.25rem;">
                                                Autorización Requerida
                                            </h3>
                                            <p style="font-size: 0.875rem; color: #1e3a8a;">
                                                El precio final acordado requiere aprobación del administrador antes de ser registrado.
                                            </p>
                                        </div>
                                    </div>
                                '))
                                ->hiddenLabel()
                                ->columnSpanFull(),

                            Grid::make(2)
                                ->schema([
                                    TextInput::make('final_price_value')
                                        ->label('💰 Precio Final Acordado')
                                        ->numeric()
                                        ->prefix('$')
                                        ->step(0.01)
                                        ->placeholder('Ej: 1500000.00')
                                        ->helperText('Ingrese el precio final acordado con el cliente')
                                        ->required()
                                        ->statePath('final_price_value'),

                                    \Filament\Forms\Components\Textarea::make('final_price_justification')
                                        ->label('📝 Justificación')
                                        ->placeholder('Explique el motivo del precio final acordado...')
                                        ->helperText('Proporcione una justificación detallada para la autorización')
                                        ->required()
                                        ->rows(4)
                                        ->statePath('final_price_justification'),
                                ]),

                            Placeholder::make('request_button')
                                ->label('📤 Solicitar Autorización')
                                ->content(fn () => view('components.action-button', [
                                    'icon' => 'heroicon-o-paper-airplane',
                                    'label' => 'Enviar Solicitud',
                                    'sublabel' => 'de Autorización',
                                    'color' => 'primary',
                                    'action' => 'requestFinalPriceAuthorization',
                                    'confirm' => '¿Desea enviar la solicitud de autorización al administrador?',
                                    'prevent' => false,
                                ])),
                        ]);
                    }

                    // Si está pendiente
                    if ($latestAuth->status === 'pending') {
                        return array_merge($baseSchema, [
                            Placeholder::make('pending_status')
                                ->content(new HtmlString('
                                    <div style="display: flex; align-items: flex-start; gap: 1rem; padding: 1.5rem; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; border-radius: 0 0.75rem 0.75rem 0; margin-bottom: 1rem; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15);">
                                        <div style="flex-shrink: 0;">
                                            <svg style="height: 2rem; width: 2rem; color: #f59e0b;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 style="font-size: 1.125rem; font-weight: 700; color: #92400e; margin-bottom: 0.5rem;">
                                                ⏳ Solicitud Pendiente de Aprobación
                                            </h3>
                                            <p style="font-size: 0.875rem; color: #78350f; margin-bottom: 0.75rem;">
                                                La solicitud de autorización ha sido enviada y está pendiente de revisión por el administrador.
                                            </p>
                                            <div style="background: white; padding: 0.75rem; border-radius: 0.5rem; margin-top: 0.75rem;">
                                                <p style="font-size: 0.875rem; color: #374151; margin-bottom: 0.5rem;">
                                                    <strong>Precio Solicitado:</strong> $'.number_format($latestAuth->final_price, 2).'
                                                </p>
                                                <p style="font-size: 0.875rem; color: #374151;">
                                                    <strong>Justificación:</strong> '.e($latestAuth->justification).'
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                '))
                                ->hiddenLabel()
                                ->columnSpanFull(),
                        ]);
                    }

                    // Si fue aprobado
                    if ($latestAuth->status === 'approved') {
                        return array_merge($baseSchema, [
                            Placeholder::make('approved_status')
                                ->content(new HtmlString('
                                    <div style="display: flex; align-items: flex-start; gap: 1rem; padding: 1.5rem; background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-left: 4px solid #10b981; border-radius: 0 0.75rem 0.75rem 0; margin-bottom: 1rem; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);">
                                        <div style="flex-shrink: 0;">
                                            <svg style="height: 2rem; width: 2rem; color: #10b981;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 style="font-size: 1.125rem; font-weight: 700; color: #047857; margin-bottom: 0.5rem;">
                                                ✅ Precio Final Autorizado
                                            </h3>
                                            <p style="font-size: 0.875rem; color: #065f46; margin-bottom: 0.75rem;">
                                                El precio final ha sido aprobado por el administrador.
                                            </p>
                                            <div style="background: white; padding: 0.75rem; border-radius: 0.5rem; margin-top: 0.75rem;">
                                                <p style="font-size: 0.875rem; color: #374151; margin-bottom: 0.5rem;">
                                                    <strong>Precio Autorizado:</strong> $'.number_format($latestAuth->final_price, 2).'
                                                </p>
                                                <p style="font-size: 0.875rem; color: #374151; margin-bottom: 0.5rem;">
                                                    <strong>Autorizado por:</strong> '.($latestAuth->reviewer->name ?? 'N/A').'
                                                </p>
                                                <p style="font-size: 0.875rem; color: #374151;">
                                                    <strong>Fecha:</strong> '.($latestAuth->reviewed_at ? $latestAuth->reviewed_at->format('d/m/Y H:i') : 'N/A').'
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                '))
                                ->hiddenLabel()
                                ->columnSpanFull(),
                        ]);
                    }

                    return $baseSchema;
                }),

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
                                ->content(fn () => view('components.action-link-button', [
                                    'icon' => 'heroicon-o-arrow-down-tray',
                                    'label' => 'Descargar Todos',
                                    'sublabel' => 'los Documentos PDF',
                                    'url' => route('documents.download-all', ['agreement' => $agreement->id]),
                                    'color' => 'success',
                                ])),

                            // Card: Regresar a Inicio
                            Placeholder::make('action_home')
                                ->label('🏠 Regresar al Dashboard')
                                ->content(fn () => view('components.action-link-button', [
                                    'icon' => 'heroicon-o-home',
                                    'label' => 'Volver al Inicio',
                                    'sublabel' => 'Dashboard Principal',
                                    'url' => '/admin',
                                    'color' => 'primary',
                                ])),
                        ]),
                ]),
        ];
    }
}
