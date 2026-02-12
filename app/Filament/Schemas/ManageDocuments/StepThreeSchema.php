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
                                        Proceso Completado
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
                                ->label('Fecha de Finalización')
                                ->content($agreement->completed_at?->timezone('America/Mexico_City')->format('d/m/Y H:i') ?? now()->timezone('America/Mexico_City')->format('d/m/Y H:i')),

                            Placeholder::make('total_documents')
                                ->label('Documentos Generados')
                                ->content($documents->count().' PDFs'),

                            Placeholder::make('final_status')
                                ->label('Estado Final')
                                ->content(new HtmlString('<span style="color: #10b981; font-weight: 600;">Completado</span>')),
                        ]),
                ]),

            // ACCIONES DISPONIBLES
            Section::make('Acciones Disponibles')
                ->icon('heroicon-o-wrench-screwdriver')
                ->iconColor('primary')
                ->description('Opciones para gestionar el convenio completado')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            // Card: Descargar Todos los Documentos
                            Placeholder::make('action_download')
                                ->label('Descargar Documentos')
                                ->content(fn () => view('components.action-link-button', [
                                    'icon' => 'heroicon-o-arrow-down-tray',
                                    'label' => 'Descargar Todos',
                                    'sublabel' => 'los Documentos PDF',
                                    'url' => route('documents.download-all', ['agreement' => $agreement->id]),
                                    'color' => 'success',
                                ])),

                            // Card: Regresar a Inicio
                            Placeholder::make('action_home')
                                ->label('Regresar al Dashboard')
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
