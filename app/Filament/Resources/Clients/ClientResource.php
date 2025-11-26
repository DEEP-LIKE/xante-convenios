<?php

namespace App\Filament\Resources\Clients;

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Models\Client;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use BackedEnum;
use App\Filament\Resources\Clients\Pages\ListClients;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Clientes';
    
    protected static ?string $modelLabel = 'Cliente';
    
    protected static ?string $pluralModelLabel = 'Clientes';
    
    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        // Sincronización automática cada vez que se carga la tabla
        static::syncClientAgreementRelations();
        
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
                    ->sortable(),
                TextColumn::make('fecha_registro')
                    ->label('Fecha de Registro')
                    ->dateTime('d/m/Y H:i')
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
                    ->getStateUsing(function (Client $record) {
                        if (!$record->hubspot_deal_id) return null;
                        
                        try {
                            $service = new \App\Services\HubspotSyncService();
                            $deal = $service->getDealDetails($record->hubspot_deal_id);
                            return $deal['amount'] ?? null;
                        } catch (\Exception $e) {
                            return null;
                        }
                    })
                    ->money('MXN')
                    ->placeholder('N/A')
                    ->tooltip('Monto del negocio en HubSpot (tiempo real)')
                    ->sortable(false)
                    ->visible(fn () => auth()->user()?->role === 'admin'), // Solo admin
                
                // Estatus del Convenio (HubSpot)
                TextColumn::make('hubspot_status')
                    ->label('Estatus HubSpot')
                    ->getStateUsing(function (Client $record) {
                        if (!$record->hubspot_deal_id) return null;
                        
                        try {
                            $service = new \App\Services\HubspotSyncService();
                            $deal = $service->getDealDetails($record->hubspot_deal_id);
                            return $deal['estatus_de_convenio'] ?? null;
                        } catch (\Exception $e) {
                            return null;
                        }
                    })
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Aceptado' => 'success',
                        'En Proceso' => 'info',
                        'Rechazado' => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('N/A')
                    ->tooltip('Estatus del convenio en HubSpot (tiempo real)')
                    ->sortable(false),
                
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
                        if (!empty($data['value'])) {
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
            ->actions([])
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
                
                if (!$wizardData || !isset($wizardData['xante_id'])) {
                    continue;
                }

                $xanteId = $wizardData['xante_id'];
                
                // Buscar cliente existente
                $client = Client::where('xante_id', $xanteId)->first();
                
                if (!$client) {
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
            Log::error('Error en sincronización automática: ' . $e->getMessage());
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
            $clientData = array_filter($clientData, function($value) {
                return $value !== null && $value !== '';
            });

            // Asegurar campos requeridos
            if (!isset($clientData['xante_id'])) {
                return null;
            }

            return Client::create($clientData);

        } catch (\Exception $e) {
            Log::error('Error creando cliente: ' . $e->getMessage());
            return null;
        }
    }
}
