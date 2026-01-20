<?php

namespace App\Filament\Schemas\ManageDocuments;

use App\Filament\Pages\ManageDocuments;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\HtmlString;

class StepOneSchema
{
    public static function make(ManageDocuments $page): array
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
                ->visible($page->agreement->generatedDocuments->isNotEmpty()),

            Section::make('Sin Documentos')
                ->description('No hay documentos generados')
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor('warning')
                ->schema([
                    Placeholder::make('no_documents')
                        ->label('Estado')
                        ->content('No se encontraron documentos generados para este convenio. Use el botón "Regenerar Documentos" en la parte superior.'),
                ])
                ->visible($page->agreement->generatedDocuments->isEmpty()),

            Section::make('Enviar al Cliente')
                ->description('Enviar documentos por correo electrónico')
                ->icon('heroicon-o-paper-airplane')
                ->iconColor('success')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Placeholder::make('send_summary')
                                ->label('📋 Resumen del Envío')
                                ->content(function () use ($page) {
                                    $clientName = $page->getClientName();
                                    $clientEmail = $page->getClientEmail();
                                    $docsCount = $page->agreement->generatedDocuments->count();
                                    $propertyAddress = $page->getPropertyAddress();

                                    return new HtmlString("Cliente: {$clientName}<br>Email: {$clientEmail}<br>Documentos: {$docsCount} PDFs<br>Propiedad: {$propertyAddress}");
                                }),

                            Placeholder::make('agreement_summary')
                                ->label('💰 Datos del Convenio')
                                ->content(function () use ($page) {
                                    $agreementValue = $page->getAgreementValue();
                                    $community = $page->getPropertyCommunity();
                                    $createdDate = $page->agreement->created_at->format('d/m/Y');

                                    $content = "Valor: {$agreementValue}<br>Comunidad: {$community}<br>Creado: {$createdDate}";

                                    if ($page->agreement->documents_sent_at) {
                                        $sentDate = $page->agreement->documents_sent_at->format('Y-m-d H:i:s');
                                        $content .= "<br><span style='color: #10b981; font-weight: 600;'>📧 Enviado: {$sentDate}</span>";
                                    }

                                    return new HtmlString($content);
                                }),
                        ]),

                    Placeholder::make('sent_info')
                        ->label('✅ Documentos Enviados')
                        ->content(function () use ($page) {
                            $sentDate = $page->agreement->documents_sent_at ?
                                $page->agreement->documents_sent_at->format('d/m/Y H:i') :
                                'Fecha no disponible';

                            return "Los documentos fueron enviados exitosamente el {$sentDate}";
                        })
                        ->visible(fn () => $page->agreement->status === 'documents_sent'),
                ])
                ->visible($page->agreement->generatedDocuments->isNotEmpty() && $page->agreement->status !== 'documents_sent'),

            Section::make('Documentos Enviados')
                ->description('Los documentos han sido enviados al cliente exitosamente')
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Placeholder::make('sent_confirmation')
                                ->label('📤 Estado del Envío')
                                ->content(function () use ($page) {
                                    $sentDate = $page->agreement->documents_sent_at?->format('d/m/Y H:i') ?? 'fecha no disponible';
                                    $clientName = $page->getClientName();
                                    $clientEmail = $page->getClientEmail();
                                    $docsCount = $page->agreement->generatedDocuments->count();

                                    return new HtmlString("✅ Enviado exitosamente el {$sentDate}<br>Cliente: {$clientName}<br>Email: {$clientEmail}<br>Documentos: {$docsCount} PDFs");
                                }),

                            Placeholder::make('next_steps')
                                ->label('📋 Próximos Pasos')
                                ->content('El cliente debe revisar los documentos y enviar la documentación requerida. Proceda al siguiente paso para gestionar la recepción de documentos del cliente.'),
                        ]),

                    Placeholder::make('resend_button')
                        ->label('')
                        ->content(function () {
                            return new HtmlString('<div style="display: flex; justify-content: center; width: 100%; margin-top: 16px;">
                                <button wire:click="sendDocumentsToClient" 
                                        wire:confirm="¿Desea reenviar los documentos al cliente?"
                                        style="
                                            display: inline-flex; 
                                            align-items: center; 
                                            padding: 12px 24px; 
                                            background: linear-gradient(135deg, #6B7280 0%, #4B5563 100%); 
                                            color: white; 
                                            font-weight: 500; 
                                            border-radius: 12px; 
                                            border: none; 
                                            cursor: pointer; 
                                            transition: all 0.3s ease; 
                                            box-shadow: 0 4px 16px rgba(107, 114, 128, 0.3); 
                                            text-decoration: none; 
                                            font-size: 14px;
                                        "
                                        onmouseover="this.style.background=\'linear-gradient(135deg, #4B5563 0%, #374151 100%)\'; this.style.boxShadow=\'0 8px 24px rgba(107, 114, 128, 0.5)\'; this.style.transform=\'translateY(-2px) scale(1.02)\';"
                                        onmouseout="this.style.background=\'linear-gradient(135deg, #6B7280 0%, #4B5563 100%)\'; this.style.boxShadow=\'0 4px 16px rgba(107, 114, 128, 0.3)\'; this.style.transform=\'translateY(0) scale(1)\';">
                                    <svg style="width: 16px; height: 16px; margin-right: 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                                    🔄 Reenviar Documentos
                                </button>
                            </div>');
                        }),
                ])
                ->visible($page->agreement->status === 'documents_sent'),
        ];
    }
}
