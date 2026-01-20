<?php

namespace App\Filament\Resources\Clients;

use App\Filament\Resources\Clients\Pages\ListClients;
use App\Models\Client;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $modelLabel = 'Cliente';

    protected static ?string $pluralModelLabel = 'Clientes';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        // Sincronización automática cada vez que se carga la tabla
        // Sincronización automática eliminada para mejorar rendimiento
        // static::syncClientAgreementRelations();

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('latestAgreement'))
            ->columns([
                // ID PRINCIPAL: xante_id es el identificador visible y principal
                // NOTA: hubspot_id se almacena en BD pero NO se muestra (uso interno)
                TextColumn::make('xante_id')
                    ->label('ID Xante')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 30 ? $state : null;
                    }),
                TextColumn::make('fecha_registro')
                    ->label('Fecha de Registro')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('No disponible')
                    ->tooltip('Fecha de creación del contacto en HubSpot'),
                TextColumn::make('hubspot_synced_at')
                    ->label('Fecha de Sincronización')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('No sincronizado')
                    ->tooltip('Última fecha de sincronización con HubSpot')
                    ->toggleable(isToggledHiddenByDefault: true), // Oculta por defecto

                // Monto del Convenio (HubSpot)
                TextColumn::make('hubspot_amount')
                    ->label('Monto HubSpot')
                    ->money('MXN')
                    ->placeholder('N/A')
                    ->tooltip('Monto del negocio en HubSpot (sincronizado)')
                    ->sortable()
                    ->visible(fn () => auth()->user()?->role === 'admin'), // Solo admin

                // Estatus del Convenio (HubSpot)
                TextColumn::make('hubspot_status')
                    ->label('Estatus HubSpot')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Aceptado' => 'success',
                        'En Proceso' => 'info',
                        'Rechazado' => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('N/A')
                    ->tooltip('Estatus del convenio en HubSpot (sincronizado)')
                    ->sortable(),

                // Convenio Local (movido al final)
                TextColumn::make('agreement_status')
                    ->label('Convenio')
                    ->badge()
                    ->tooltip('Haz clic para gestionar el convenio')
                    ->getStateUsing(fn (Client $record): string => $record->agreement_status)
                    ->color(fn (?string $state): string => match ($state) {
                        'sin_convenio' => 'gray',
                        'expediente_incompleto' => 'warning',
                        'expediente_completo' => 'success',
                        'convenio_proceso' => 'info',
                        'convenio_firmado' => 'success',
                        'draft' => 'gray',
                        'pending_validation' => 'warning',
                        'documents_generating' => 'info',
                        'documents_generated' => 'success',
                        'documents_sent' => 'info',
                        'awaiting_client_docs' => 'warning',
                        'documents_complete' => 'success',
                        'completed' => 'success',
                        'error_generating_documents' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'sin_convenio' => '➕ Sin Convenio',
                        'expediente_incompleto' => 'Expediente Incompleto',
                        'expediente_completo' => 'Expediente Completo',
                        'convenio_proceso' => 'Convenio en Proceso',
                        'convenio_firmado' => 'Convenio Firmado',
                        'draft' => '📝 Borrador',
                        'pending_validation' => '⏳ Pendiente de Validación',
                        'documents_generating' => '⚙️ Generando Documentos',
                        'documents_generated' => '📄 Documentos Generados',
                        'documents_sent' => '📧 Documentos Enviados',
                        'awaiting_client_docs' => '📤 Esperando Documentos del Cliente',
                        'documents_complete' => '✅ Documentos Completos',
                        'completed' => '🎉 Completado',
                        'error_generating_documents' => '❌ Error al Generar Documentos',
                        default => '➕ Sin Convenio',
                    })
                    ->url(function (Client $record): ?string {
                        $latestAgreement = $record->latestAgreement;
                        if ($latestAgreement) {
                            if (in_array($latestAgreement->status, ['documents_generated', 'documents_sent', 'awaiting_client_docs', 'documents_complete', 'completed'])) {
                                return "/admin/manage-documents/{$latestAgreement->id}";
                            } else {
                                return "/admin/convenios/crear?agreement={$latestAgreement->id}";
                            }
                        } else {
                            return "/admin/convenios/crear?client_id={$record->xante_id}";
                        }
                    })
                    ->openUrlInNewTab(false),
            ])
            ->filters([
                SelectFilter::make('agreement_status')
                    ->label('Estado de Convenio')
                    ->options([
                        'sin_convenio' => 'Sin Convenio',
                        'draft' => 'Borrador',
                        'pending_validation' => 'Pendiente de Validación',
                        'documents_generating' => 'Generando Documentos',
                        'documents_generated' => 'Documentos Generados',
                        'documents_sent' => 'Documentos Enviados',
                        'awaiting_client_docs' => 'Esperando Documentos del Cliente',
                        'documents_complete' => 'Documentos Completos',
                        'completed' => 'Completado',
                        'error_generating_documents' => 'Error al Generar Documentos',
                    ])
                    ->query(function ($query, array $data) {
                        if (! empty($data['value'])) {
                            if ($data['value'] === 'sin_convenio') {
                                return $query->whereDoesntHave('latestAgreement');
                            } else {
                                return $query->whereHas('latestAgreement', function ($q) use ($data) {
                                    $q->where('status', $data['value']);
                                });
                            }
                        }

                        return $query;
                    }),
            ])
            ->defaultSort('fecha_registro', 'desc') // Ordenar por más recientes primero
            ->actions([
                ViewAction::make()
                    ->label('Ver Detalle')
                    ->tooltip('Ver información completa del cliente'),
            ])
            ->bulkActions(
                auth()->user()?->role === 'admin'
                    ? [
                        BulkActionGroup::make([
                            DeleteBulkAction::make(),
                        ]),
                    ]
                    : []
            )
            ->checkIfRecordIsSelectableUsing(
                fn ($record): bool => auth()->user()?->role === 'admin',
            );
    }

    public static function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                Section::make('Información Principal')
                    ->columns(3)
                    ->schema([
                        Placeholder::make('xante_id')
                            ->label('ID Xante')
                            ->content(fn ($record) => $record->xante_id),
                        Placeholder::make('name')
                            ->label('Nombre Completo')
                            ->content(fn ($record) => $record->name),
                        Placeholder::make('email')
                            ->label('Correo Electrónico')
                            ->content(fn ($record) => $record->email ?? 'N/A'),
                        Placeholder::make('phone')
                            ->label('Teléfono')
                            ->content(fn ($record) => $record->phone ?? 'N/A'),
                        Placeholder::make('fecha_registro')
                            ->label('Fecha de Registro')
                            ->content(fn ($record) => $record->fecha_registro?->format('d/m/Y H:i') ?? 'N/A'),
                        Placeholder::make('hubspot_synced_at')
                            ->label('Última Sincronización')
                            ->content(fn ($record) => $record->hubspot_synced_at?->format('d/m/Y H:i') ?? 'Nunca'),
                    ]),

                Section::make('Datos de HubSpot')
                    ->columns(3)
                    ->schema([
                        Placeholder::make('hubspot_amount')
                            ->label('Monto del Negocio')
                            ->content(fn ($record) => $record->hubspot_amount ? '$'.number_format($record->hubspot_amount, 2).' MXN' : 'N/A'),
                        Placeholder::make('hubspot_status')
                            ->label('Estatus del Convenio')
                            ->content(fn ($record) => $record->hubspot_status ?? 'N/A'),
                        Placeholder::make('hubspot_deal_id')
                            ->label('ID Deal HubSpot')
                            ->content(fn ($record) => $record->hubspot_deal_id ?? 'N/A'),
                    ]),

                Section::make('Información Personal')
                    ->columns(3)
                    ->schema([
                        Placeholder::make('birthdate')
                            ->label('Fecha de Nacimiento')
                            ->content(fn ($record) => $record->birthdate?->format('d/m/Y') ?? 'N/A'),
                        Placeholder::make('curp')
                            ->label('CURP')
                            ->content(fn ($record) => $record->curp ?? 'N/A'),
                        Placeholder::make('rfc')
                            ->label('RFC')
                            ->content(fn ($record) => $record->rfc ?? 'N/A'),
                        Placeholder::make('civil_status')
                            ->label('Estado Civil')
                            ->content(fn ($record) => $record->civil_status ?? 'N/A'),
                        Placeholder::make('occupation')
                            ->label('Ocupación')
                            ->content(fn ($record) => $record->occupation ?? 'N/A'),
                    ]),

                Section::make('Domicilio')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('current_address')
                            ->label('Calle y Número')
                            ->content(fn ($record) => $record->current_address ?? 'N/A')
                            ->columnSpanFull(),
                        Placeholder::make('neighborhood')
                            ->label('Colonia')
                            ->content(fn ($record) => $record->neighborhood ?? 'N/A'),
                        Placeholder::make('postal_code')
                            ->label('Código Postal')
                            ->content(fn ($record) => $record->postal_code ?? 'N/A'),
                        Placeholder::make('municipality')
                            ->label('Municipio')
                            ->content(fn ($record) => $record->municipality ?? 'N/A'),
                        Placeholder::make('state')
                            ->label('Estado')
                            ->content(fn ($record) => $record->state ?? 'N/A'),
                    ]),

                Section::make('Información del Cónyuge')
                    ->columns(3)
                    ->schema([
                        Placeholder::make('spouse_name')
                            ->label('Nombre')
                            ->content(fn ($record) => $record->spouse?->name ?? 'N/A'),
                        Placeholder::make('spouse_email')
                            ->label('Email')
                            ->content(fn ($record) => $record->spouse?->email ?? 'N/A'),
                        Placeholder::make('spouse_phone')
                            ->label('Teléfono')
                            ->content(fn ($record) => $record->spouse?->phone ?? 'N/A'),
                    ])
                    ->visible(fn ($record) => $record->spouse()->exists()),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
        ];
    }

    /**
     * Sincroniza automáticamente las relaciones entre clientes y convenios
     */
    private static function syncClientAgreementRelations(): void
    {
        try {
            // Obtener convenios sin relación de cliente
            $agreementsWithoutClient = \App\Models\Agreement::whereNull('client_id')
                ->whereNotNull('wizard_data')
                ->get();

            foreach ($agreementsWithoutClient as $agreement) {
                $wizardData = $agreement->wizard_data;

                if (! $wizardData || ! isset($wizardData['xante_id'])) {
                    continue;
                }

                $xanteId = $wizardData['xante_id'];

                // Buscar cliente existente
                $client = Client::where('xante_id', $xanteId)->first();

                if (! $client) {
                    // Crear cliente si no existe
                    $client = static::createClientFromWizardData($wizardData, $agreement);
                }

                if ($client) {
                    // Actualizar la relación usando client_id
                    $agreement->update(['client_id' => $client->id]);
                }
            }

            // Verificar convenios con clientes inexistentes
            $orphanedAgreements = \App\Models\Agreement::whereNotNull('client_id')
                ->whereDoesntHave('client')
                ->get();

            foreach ($orphanedAgreements as $agreement) {
                $wizardData = $agreement->wizard_data;

                if ($wizardData) {
                    $client = static::createClientFromWizardData($wizardData, $agreement);
                    if ($client) {
                        $agreement->update(['client_id' => $client->id]);
                    }
                }
            }

        } catch (\Exception $e) {
            // Silencioso para no interrumpir la carga de la tabla
            Log::error('Error en sincronización automática: '.$e->getMessage());
        }
    }

    /**
     * Crea un cliente basándose en los datos del wizard
     */
    private static function createClientFromWizardData(array $wizardData, \App\Models\Agreement $agreement): ?Client
    {
        try {
            $clientData = [
                'xante_id' => $wizardData['xante_id'] ?? null,
                'name' => $wizardData['holder_name'] ?? $agreement->holder_name ?? 'Cliente Importado',
                'email' => $wizardData['holder_email'] ?? $agreement->holder_email ?? 'sin-email@xante.com',
                'phone' => $wizardData['holder_phone'] ?? $agreement->holder_phone ?? '0000000000',
                'curp' => $wizardData['holder_curp'] ?? $agreement->holder_curp,
                'rfc' => $wizardData['holder_rfc'] ?? $agreement->holder_rfc,
                'birthdate' => $wizardData['holder_birthdate'] ?? $agreement->holder_birthdate ?? now()->subYears(30),
                'current_address' => $wizardData['current_address'] ?? $agreement->current_address,
                'civil_status' => $wizardData['holder_civil_status'] ?? $agreement->holder_civil_status,
                'occupation' => $wizardData['holder_occupation'] ?? $agreement->holder_occupation,
                'municipality' => $wizardData['municipality'] ?? $agreement->municipality,
                'state' => $wizardData['state'] ?? $agreement->state,
                'postal_code' => $wizardData['postal_code'] ?? $agreement->postal_code,
                'neighborhood' => $wizardData['neighborhood'] ?? $agreement->neighborhood,
            ];

            // Filtrar valores nulos
            $clientData = array_filter($clientData, function ($value) {
                return $value !== null && $value !== '';
            });

            // Asegurar campos requeridos
            if (! isset($clientData['xante_id'])) {
                return null;
            }

            return Client::create($clientData);

        } catch (\Exception $e) {
            Log::error('Error creando cliente: '.$e->getMessage());

            return null;
        }
    }
}
