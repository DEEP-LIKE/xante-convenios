<?php

namespace App\Filament\Schemas\ManageDocuments;

use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\HtmlString;

class StepOneSchema
{
    public static function make($page): array
    {
        return [
            Section::make('Información del Convenio')
                ->icon('heroicon-o-document-text')
                ->iconColor('success')
                ->description('Datos básicos del convenio')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Placeholder::make('agreement_id')
                                ->label('ID del Convenio')
                                ->content($page->agreement->id),

                            Placeholder::make('client_name')
                                ->label('Cliente Titular')
                                ->content($page->getClientName()),

                            Placeholder::make('client_email')
                                ->label('Email del Cliente')
                                ->content($page->getClientEmail()),

                            Placeholder::make('client_phone')
                                ->label('Teléfono del Cliente')
                                ->content($page->getClientPhone()),

                            Placeholder::make('property_address')
                                ->label('Domicilio de la Propiedad')
                                ->content($page->getPropertyAddress()),

                            Placeholder::make('documents_count')
                                ->label('Documentos Generados')
                                ->content($page->agreement->generatedDocuments->count().' PDFs'),
                        ]),
                ]),

            Section::make('Documentos Disponibles')
                ->description('Documentos PDF generados para este convenio')
                ->icon('heroicon-o-document-text')
                ->iconColor('success')
                ->schema($page->getDocumentFields())
                ->visible(fn () => $page->agreement->generatedDocuments->isNotEmpty()),

            Section::make('Sin Documentos')
                ->description('No hay documentos generados')
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor('warning')
                ->schema([
                    Placeholder::make('no_documents')
                        ->label('Estado')
                        ->content('No se encontraron documentos generados para este convenio. Use el botón "Regenerar Documentos" a continuación para intentar de nuevo.'),

                    Placeholder::make('regenerate_button')
                        ->hiddenLabel()
                        ->content(function () {
                            return new HtmlString('
                                <div style="display: flex; justify-content: center; width: 100%; margin-top: 16px;">
                                    <button wire:click="regenerateDocuments" 
                                            wire:loading.attr="disabled"
                                            style="
                                                display: inline-flex; 
                                                align-items: center; 
                                                padding: 12px 24px; 
                                                background: linear-gradient(135deg, #EF4444 0%, #B91C1C 100%); 
                                                color: white; 
                                                font-weight: 600; 
                                                border-radius: 12px; 
                                                border: none; 
                                                cursor: pointer; 
                                                transition: all 0.3s ease; 
                                                box-shadow: 0 4px 16px rgba(239, 68, 68, 0.4); 
                                                font-size: 14px;
                                                text-transform: uppercase;
                                                letter-spacing: 0.5px;
                                            "
                                            onmouseover="this.style.background=\'linear-gradient(135deg, #DC2626 0%, #991B1B 100%)\'; this.style.boxShadow=\'0 8px 24px rgba(239, 68, 68, 0.6)\'; this.style.transform=\'translateY(-2px)\';"
                                            onmouseout="this.style.background=\'linear-gradient(135deg, #EF4444 0%, #B91C1C 100%)\'; this.style.boxShadow=\'0 4px 16px rgba(239, 68, 68, 0.4)\'; this.style.transform=\'translateY(0)\';">
                                        <svg style="width: 18px; height: 18px; margin-right: 10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Regenerar Documentos
                                    </button>
                                </div>
                            ');
                        }),

                ])
                ->visible(fn () => $page->agreement->generatedDocuments->isEmpty()),

            Section::make('Estatus de Envío de Documentos')
                ->description('Información sobre el envío inicial de documentos al cliente')
                ->icon(fn () => $page->agreement->documents_sent_at ? 'heroicon-o-envelope-open' : 'heroicon-o-envelope')
                ->iconColor(fn () => $page->agreement->documents_sent_at ? 'success' : 'warning')
                ->schema([
                    Placeholder::make('email_confirmation_status')
                        ->hiddenLabel()
                        ->content(fn () => view('filament.components.email-confirmation-status', [
                            'agreement' => $page->agreement,
                            'clientEmail' => $page->getClientEmail(),
                            'docsCount' => $page->agreement->generatedDocuments->count(),
                        ])),
                ])
                ->visible(fn () => $page->agreement->generatedDocuments->isNotEmpty()),
        ];
    }
}
