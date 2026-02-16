<?php

namespace App\Filament\Schemas\ManageDocuments;

use App\Filament\Pages\ManageDocuments;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\HtmlString;

use App\Models\ClientDocument;
use Illuminate\Support\Facades\Storage;

class StepTwoSchema
{
    public static function make(ManageDocuments $page): array
    {
        $getUploadedFileMetadata = function ($file) {
            if (! $file) return null;

            // Buscar el registro en BD para obtener el nombre original y tamaño guardado
            $document = ClientDocument::where('file_path', $file)->first();

            if (! $document) {
                $filename = basename($file);
                $document = ClientDocument::where('file_path', 'like', "%{$filename}")->first();
            }

            $url = null;
            if ($document) {
                // Priorizar ruta segura
                $url = route('secure.client.document', $document->id);
            } else {
                // Fallback a URL temporal de S3 para carga interna de FilePond
                try {
                    $url = Storage::disk('s3')->temporaryUrl($file, now()->addMinutes(60));
                } catch (\Exception $e) {
                    $url = Storage::disk('s3')->url($file);
                }
            }

            return [
                'name' => $document?->document_name ?? basename($file),
                'size' => $document?->file_size ?? 0,
                'type' => null,
                'url' => $url,
            ];
        };

        return [
            Section::make('Estatus de Notificaciones')
                ->description('Seguimiento de correos electrónicos enviados')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->iconColor('info')
                ->schema([
                    Placeholder::make('step_1_status')
                        ->label('1. Envío de Documentos (Paso 1)')
                        ->content(fn () => view('filament.components.email-confirmation-status', [
                            'agreement' => $page->agreement,
                            'clientEmail' => $page->getClientEmail(),
                            'docsCount' => $page->agreement->generatedDocuments->count(),
                        ])),

                    Placeholder::make('step_2_status')
                        ->label('2. Confirmación de Recepción (Paso 2)')
                        ->content(fn () => view('filament.components.confirmation-status', [
                            'agreement' => $page->agreement,
                            'clientEmail' => $page->getClientEmail(),
                            'advisorEmail' => auth()->user()->email ?? 'No disponible',
                        ])),
                ]),

            Section::make('Documentos Requeridos del Cliente')
                ->description(new HtmlString(
                    'Gestionar documentos que debe proporcionar el cliente <br> <span class="font-semibold text-gray-700">Documento cargado previamente se mostrará automáticamente</span>'
                ))
                ->icon('heroicon-o-clipboard-document-list')
                ->iconColor('info')
                ->headerActions([
                Action::make('downloadUpdatedChecklist')
                    ->label('Descargar Lista Actualizada')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () use ($page) {
                        return $page->downloadUpdatedChecklistAction();
                    }),
                ])
                ->schema([
                    Grid::make(1)
                    ->schema([

                        Section::make('DOCUMENTACIÓN TITULAR')
                            ->icon('heroicon-o-user')
                            ->description('Todos los documentos son obligatorios')
                            ->iconColor('primary')
                            ->columns(2)
                            ->schema([
                                FileUpload::make('holder_ine')
                                    ->label('1. INE (A color, tamaño original, no fotos)')
                                    ->required()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                    ->maxSize(10240)
                                    ->directory('client_documents/'.$page->agreement->id.'/titular')
                                    ->disk('s3')
                                    ->placeholder('Arrastra tu archivo aquí o haz clic para seleccionar')
                                    ->helperText('Límite: 10MB. Formatos: PDF, JPG, PNG.')
                                    ->getUploadedFileNameForStorageUsing(function ($file) {
                                        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

                                        return 'ine_'.now()->format('Y-m-d_H-i-s').'.'.$extension;
                                    })
                                    ->visibility('private')
                                    ->previewable(false)
                                    ->getUploadedFileUsing($getUploadedFileMetadata)
                                    ->afterStateUpdated(function ($state) use ($page) {
                                        $page->handleDocumentStateChange('holder_ine', 'titular', $state);
                                    }),

                                FileUpload::make('holder_curp')
                                    ->label('2. CURP (Mes corriente)')
                                    ->required()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                    ->maxSize(10240)
                                    ->directory('client_documents/'.$page->agreement->id.'/titular')
                                    ->disk('s3')
                                    ->helperText('Límite: 10MB. Formatos: PDF, JPG, PNG.')
                                    ->helperText('Límite: 10MB. Formatos: PDF, JPG, PNG.')
                                    ->getUploadedFileNameForStorageUsing(function ($file) {
                                        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

                                        return 'curp_'.now()->format('Y-m-d_H-i-s').'.'.$extension;
                                    })
                                    ->visibility('private')
                                    ->previewable(false)
                                    ->getUploadedFileUsing($getUploadedFileMetadata)
                                    ->afterStateUpdated(function ($state) use ($page) {
                                        $page->handleDocumentStateChange('holder_curp', 'titular', $state);
                                    }),

                                FileUpload::make('holder_fiscal_status')
                                    ->label('3. Constancia de Situación Fiscal (Mes corriente, completa)')
                                    ->required()
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                    ->maxSize(10240)
                                    ->directory('client_documents/'.$page->agreement->id.'/titular')
                                    ->disk('s3')
                                    ->helperText('Límite: 10MB. Formatos: PDF, JPG, PNG.')
                                    ->helperText('Límite: 10MB. Formatos: PDF, JPG, PNG.')
                                    ->getUploadedFileNameForStorageUsing(function ($file) {
                                        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

                                        return 'constancia_fiscal_'.now()->format('Y-m-d_H-i-s').'.'.$extension;
                                    })
                                    ->visibility('private')
                                    ->previewable(false)
                                    ->getUploadedFileUsing($getUploadedFileMetadata)
                                    ->afterStateUpdated(function ($state) use ($page) {
                                        $page->handleDocumentStateChange('holder_fiscal_status', 'titular', $state);
                                    }),

                                FileUpload::make('holder_proof_address_home')
                                    ->label('4. Comprobante de Domicilio Vivienda (Mes corriente)')
                                    ->required()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                    ->maxSize(10240)
                                    ->directory('client_documents/'.$page->agreement->id.'/titular')
                                    ->disk('s3')
                                    ->helperText('Límite: 10MB. Formatos: PDF, JPG, PNG.')
                                    ->helperText('Límite: 10MB. Formatos: PDF, JPG, PNG.')
                                    ->getUploadedFileNameForStorageUsing(function ($file) {
                                        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

                                        return 'comprobante_domicilio_vivienda_'.now()->format('Y-m-d_H-i-s').'.'.$extension;
                                    })
                                    ->visibility('private')
                                    ->previewable(false)
                                    ->getUploadedFileUsing($getUploadedFileMetadata)
                                    ->afterStateUpdated(function ($state) use ($page) {
                                        $page->handleDocumentStateChange('holder_proof_address_home', 'titular', $state);
                                    }),

                                FileUpload::make('holder_proof_address_titular')
                                    ->label('5. Comprobante de Domicilio Titular (Mes corriente)')
                                    ->required()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                    ->maxSize(10240)
                                    ->directory('client_documents/'.$page->agreement->id.'/titular')
                                    ->disk('s3')
                                    ->helperText('Límite: 10MB. Formatos: PDF, JPG, PNG.')
                                    ->helperText('Límite: 10MB. Formatos: PDF, JPG, PNG.')
                                    ->getUploadedFileNameForStorageUsing(function ($file) {
                                        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

                                        return 'comprobante_domicilio_titular_'.now()->format('Y-m-d_H-i-s').'.'.$extension;
                                    })
                                    ->visibility('private')
                                    ->previewable(false)
                                    ->getUploadedFileUsing($getUploadedFileMetadata)
                                    ->afterStateUpdated(function ($state) use ($page) {
                                        $page->handleDocumentStateChange('holder_proof_address_titular', 'titular', $state);
                                    }),

                                FileUpload::make('holder_birth_certificate')
                                    ->label('6. Acta Nacimiento')
                                    ->required()
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                    ->maxSize(10240)
                                    ->directory('client_documents/'.$page->agreement->id.'/titular')
                                    ->disk('s3')
                                    ->helperText('Límite: 10MB. Formatos: PDF, JPG, PNG.')
                                    ->helperText('Límite: 10MB. Formatos: PDF, JPG, PNG.')
                                    ->getUploadedFileNameForStorageUsing(function ($file) {
                                        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

                                        return 'acta_nacimiento_'.now()->format('Y-m-d_H-i-s').'.'.$extension;
                                    })
                                    ->visibility('private')
                                    ->previewable(false)
                                    ->getUploadedFileUsing($getUploadedFileMetadata)
                                    ->afterStateUpdated(function ($state) use ($page) {
                                        $page->handleDocumentStateChange('holder_birth_certificate', 'titular', $state);
                                    }),

                                FileUpload::make('holder_marriage_certificate')
                                    ->label('7. Acta Matrimonio (Si aplica)')
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                    ->maxSize(10240)
                                    ->directory('client_documents/'.$page->agreement->id.'/titular')
                                    ->disk('s3')
                                    ->helperText('Límite: 10MB. Formatos: PDF, JPG, PNG.')
                                    ->helperText('Límite: 10MB. Formatos: PDF, JPG, PNG.')
                                    ->getUploadedFileNameForStorageUsing(function ($file) {
                                        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

                                        return 'acta_matrimonio_'.now()->format('Y-m-d_H-i-s').'.'.$extension;
                                    })
                                    ->visibility('private')
                                    ->previewable(false)
                                    ->getUploadedFileUsing($getUploadedFileMetadata)
                                    ->afterStateUpdated(function ($state) use ($page) {
                                        $page->handleDocumentStateChange('holder_marriage_certificate', 'titular', $state);
                                    }),

                                FileUpload::make('holder_bank_statement')
                                    ->label('8. Carátula Estado de Cuenta Bancario con Datos Fiscales (Mes corriente)')
                                    ->required()
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                    ->maxSize(10240)
                                    ->directory('client_documents/'.$page->agreement->id.'/titular')
                                    ->disk('s3')
                                    ->helperText('Límite: 10MB. Formatos: PDF, JPG, PNG.')
                                    ->getUploadedFileNameForStorageUsing(function ($file) {
                                        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

                                        return 'estado_cuenta_'.now()->format('Y-m-d_H-i-s').'.'.$extension;
                                    })
                                    ->visibility('private')
                                    ->previewable(false)
                                    ->getUploadedFileUsing($getUploadedFileMetadata)
                                    ->afterStateUpdated(function ($state) use ($page) {
                                        $page->handleDocumentStateChange('holder_bank_statement', 'titular', $state);
                                    }),
                            ])
                            ->collapsible(),

                        Section::make('DOCUMENTACIÓN PROPIEDAD')
                            ->icon('heroicon-o-home')
                            ->description('Todos los documentos son obligatorios')
                            ->iconColor('primary')
                            ->columns(2)
                            ->schema([
                                    FileUpload::make('property_notarial_instrument')
                                    ->label('1. Instrumento Notarial con Antecedentes Registrales (Datos Registrales y Traslado de Dominio) Escaneada, visible')
                                    ->required()
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                    ->maxSize(10240)
                                    ->directory('client_documents/'.$page->agreement->id.'/propiedad')
                                    ->disk('s3')
                                    ->helperText('Límite: 10MB. Formatos: PDF, JPG, PNG.')
                                    ->getUploadedFileNameForStorageUsing(function ($file) {
                                        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

                                        return 'instrumento_notarial_'.now()->format('Y-m-d_H-i-s').'.'.$extension;
                                    })
                                    ->visibility('private')
                                    ->previewable(false)
                                    ->getUploadedFileUsing($getUploadedFileMetadata)
                                    ->afterStateUpdated(function ($state) use ($page) {
                                        $page->handleDocumentStateChange('property_notarial_instrument', 'propiedad', $state);
                                    }),

                                    FileUpload::make('property_tax_receipt')
                                    ->label('2. Recibo predial (Mes corriente)')
                                    ->required()
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                    ->maxSize(10240)
                                    ->directory('client_documents/'.$page->agreement->id.'/propiedad')
                                    ->disk('s3')
                                    ->helperText('Límite: 10MB. Formatos: PDF, JPG, PNG.')
                                    ->getUploadedFileNameForStorageUsing(function ($file) {
                                        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

                                        return 'recibo_predial_'.now()->format('Y-m-d_H-i-s').'.'.$extension;
                                    })
                                    ->visibility('private')
                                    ->previewable(false)
                                    ->getUploadedFileUsing($getUploadedFileMetadata)
                                    ->afterStateUpdated(function ($state) use ($page) {
                                        $page->handleDocumentStateChange('property_tax_receipt', 'propiedad', $state);
                                    }),

                                    FileUpload::make('property_water_receipt')
                                    ->label('3. Recibo de Agua (Mes corriente)')
                                    ->required()
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                    ->maxSize(10240)
                                    ->directory('client_documents/'.$page->agreement->id.'/propiedad')
                                    ->disk('s3')
                                    ->helperText('Límite: 10MB. Formatos: PDF, JPG, PNG.')
                                    ->getUploadedFileNameForStorageUsing(function ($file) {
                                        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

                                        return 'recibo_agua_'.now()->format('Y-m-d_H-i-s').'.'.$extension;
                                    })
                                    ->visibility('private')
                                    ->previewable(false)
                                    ->getUploadedFileUsing($getUploadedFileMetadata)
                                    ->afterStateUpdated(function ($state) use ($page) {
                                        $page->handleDocumentStateChange('property_water_receipt', 'propiedad', $state);
                                    }),

                                    FileUpload::make('property_cfe_receipt')
                                    ->label('4. Recibo CFE con datos fiscales (Mes corriente)')
                                    ->required()
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                    ->maxSize(10240)
                                    ->directory('client_documents/'.$page->agreement->id.'/propiedad')
                                    ->disk('s3')
                                    ->helperText('Límite: 10MB. Formatos: PDF, JPG, PNG.')
                                    ->getUploadedFileNameForStorageUsing(function ($file) {
                                        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

                                        return 'recibo_cfe_'.now()->format('Y-m-d_H-i-s').'.'.$extension;
                                    })
                                    ->visibility('private')
                                    ->previewable(false)
                                    ->getUploadedFileUsing($getUploadedFileMetadata)
                                    ->afterStateUpdated(function ($state) use ($page) {
                                        $page->handleDocumentStateChange('property_cfe_receipt', 'propiedad', $state);
                                    }),
                                ])
                            ->collapsible(),
                    ]),
                ]),

        ];
    }
}
