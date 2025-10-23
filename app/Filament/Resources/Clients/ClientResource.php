<?php

namespace App\Filament\Resources\Clients;

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Models\Client;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Clientes';
    
    protected static ?string $modelLabel = 'Cliente';
    
    protected static ?string $pluralModelLabel = 'Clientes';
    
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('DATOS PERSONALES TITULAR')
                    ->schema([
                        TextInput::make('xante_id')
                            ->label('ID Xante')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('name')
                            ->label('Nombre Cliente')
                            ->required()
                            ->maxLength(255),
                        DatePicker::make('birthdate')
                            ->label('Fecha de Nacimiento')
                            ->required()
                            ->native(false),
                        TextInput::make('curp')
                            ->label('CURP')
                            ->required()
                            ->maxLength(18)
                            ->unique(ignoreRecord: true)
                            ->rules(['regex:/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[0-9]{2}$/']),
                        TextInput::make('rfc')
                            ->label('RFC')
                            ->required()
                            ->maxLength(13)
                            ->unique(ignoreRecord: true)
                            ->rules(['regex:/^[A-Z&Ñ]{3,4}[0-9]{6}[A-V1-9][A-Z1-9][0-9A]$/']),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Núm. Celular')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('delivery_file')
                            ->label('Entrega expediente')
                            ->maxLength(255),
                        Select::make('civil_status')
                            ->label('Estado civil')
                            ->options([
                                'soltero' => 'Soltero(a)',
                                'casado' => 'Casado(a)',
                                'divorciado' => 'Divorciado(a)',
                                'viudo' => 'Viudo(a)',
                                'union_libre' => 'Unión Libre',
                            ]),
                        TextInput::make('regime_type')
                            ->label('Régimen Fiscal')
                            ->maxLength(255),
                        TextInput::make('occupation')
                            ->label('Ocupación')
                            ->maxLength(255),
                        TextInput::make('office_phone')
                            ->label('Tel. oficina')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('additional_contact_phone')
                            ->label('Tel. Contacto Adic.')
                            ->tel()
                            ->maxLength(255),
                        Textarea::make('current_address')
                            ->label('Domicilio Actual')
                            ->required()
                            ->rows(3),
                        TextInput::make('neighborhood')
                            ->label('Colonia')
                            ->maxLength(255),
                        TextInput::make('postal_code')
                            ->label('C.P.')
                            ->maxLength(10),
                        TextInput::make('municipality')
                            ->label('Municipio - Alcaldía')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('state')
                            ->label('Estado')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),
                
                Section::make('DATOS PERSONALES COACREDITADO / CÓNYUGE')
                    ->schema([
                        TextInput::make('spouse_name')
                            ->label('Nombre Cliente')
                            ->maxLength(255),
                        DatePicker::make('spouse_birthdate')
                            ->label('Fecha de Nacimiento')
                            ->native(false),
                        TextInput::make('spouse_curp')
                            ->label('CURP')
                            ->maxLength(18)
                            ->rules(['regex:/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[0-9]{2}$/']),
                        TextInput::make('spouse_rfc')
                            ->label('RFC')
                            ->maxLength(13)
                            ->rules(['regex:/^[A-Z&Ñ]{3,4}[0-9]{6}[A-V1-9][A-Z1-9][0-9A]$/']),
                        TextInput::make('spouse_email')
                            ->label('Correo electrónico')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('spouse_phone')
                            ->label('Núm. Celular')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('spouse_delivery_file')
                            ->label('Entrega expediente')
                            ->maxLength(255),
                        Select::make('spouse_civil_status')
                            ->label('Estado civil')
                            ->options([
                                'soltero' => 'Soltero(a)',
                                'casado' => 'Casado(a)',
                                'divorciado' => 'Divorciado(a)',
                                'viudo' => 'Viudo(a)',
                                'union_libre' => 'Unión Libre',
                            ]),
                        TextInput::make('spouse_regime_type')
                            ->label('Régimen Fiscal')
                            ->maxLength(255),
                        TextInput::make('spouse_occupation')
                            ->label('Ocupación')
                            ->maxLength(255),
                        TextInput::make('spouse_office_phone')
                            ->label('Tel. oficina')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('spouse_additional_contact_phone')
                            ->label('Tel. Contacto Adic.')
                            ->tel()
                            ->maxLength(255),
                        Textarea::make('spouse_current_address')
                            ->label('Domicilio Actual')
                            ->rows(3),
                        TextInput::make('spouse_neighborhood')
                            ->label('Colonia')
                            ->maxLength(255),
                        TextInput::make('spouse_postal_code')
                            ->label('C.P.')
                            ->maxLength(10),
                        TextInput::make('spouse_municipality')
                            ->label('Municipio - Alcaldía')
                            ->maxLength(255),
                        TextInput::make('spouse_state')
                            ->label('Estado')
                            ->maxLength(255),
                    ])->columns(2),
                
                Section::make('CONTACTO AC Y/O PRESIDENTE DE PRIVADA')
                    ->schema([
                        TextInput::make('ac_name')
                            ->label('NOMBRE AC')
                            ->maxLength(255),
                        TextInput::make('private_president_name')
                            ->label('PRESIDENTE PRIVADA')
                            ->maxLength(255),
                        TextInput::make('ac_phone')
                            ->label('Núm. Celular (AC)')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('private_president_phone')
                            ->label('Núm. Celular (Presidente)')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('ac_quota')
                            ->label('CUOTA (AC)')
                            ->numeric()
                            ->prefix('$'),
                        TextInput::make('private_president_quota')
                            ->label('CUOTA (Presidente)')
                            ->numeric()
                            ->prefix('$'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        // Sincronización automática cada vez que se carga la tabla
        static::syncClientAgreementRelations();
        
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('latestAgreement'))
            ->columns([
                TextColumn::make('xante_id')
                    ->label('ID Xante')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Fecha Registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('agreement_status')
                    ->label('Convenio')
                    ->badge()
                    ->tooltip('Haz clic para gestionar el convenio')
                    ->getStateUsing(fn (Client $record): string => $record->agreement_status)
                    ->color(fn (?string $state): string => match ($state) {
                        // Estados originales
                        'sin_convenio' => 'gray',
                        'expediente_incompleto' => 'warning',
                        'expediente_completo' => 'success',
                        'convenio_proceso' => 'info',
                        'convenio_firmado' => 'success',
                        // Nuevos estados del sistema de dos wizards
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
                        // Estados originales
                        'sin_convenio' => '➕ Sin Convenio',
                        'expediente_incompleto' => 'Expediente Incompleto',
                        'expediente_completo' => 'Expediente Completo',
                        'convenio_proceso' => 'Convenio en Proceso',
                        'convenio_firmado' => 'Convenio Firmado',
                        // Nuevos estados del sistema de dos wizards
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
                            // Si tiene convenio, ir al wizard apropiado
                            if (in_array($latestAgreement->status, ['documents_generated', 'documents_sent', 'awaiting_client_docs', 'documents_complete', 'completed'])) {
                                return "/admin/manage-documents/{$latestAgreement->id}";
                            } else {
                                return "/admin/convenios/crear?agreement={$latestAgreement->id}";
                            }
                        } else {
                            // Si no tiene convenio, crear uno nuevo
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
            ->recordActions([
                Action::make('edit')
                    ->url(fn (Client $record): string => static::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-o-pencil'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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
            'create' => CreateClient::route('/create'),
            'edit' => EditClient::route('/{record}/edit'),
        ];
    }

    /**
     * Sincroniza automáticamente las relaciones entre clientes y convenios
     */
    private static function syncClientAgreementRelations(): void
    {
        try {
            // Obtener convenios sin relación de cliente
            $agreementsWithoutClient = \App\Models\Agreement::whereNull('client_xante_id')
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
                    // Actualizar la relación
                    $agreement->update(['client_xante_id' => $client->xante_id]);
                }
            }

            // Verificar convenios con clientes inexistentes
            $orphanedAgreements = \App\Models\Agreement::whereNotNull('client_xante_id')
                ->whereDoesntHave('client')
                ->get();

            foreach ($orphanedAgreements as $agreement) {
                $wizardData = $agreement->wizard_data;
                
                if ($wizardData) {
                    $client = static::createClientFromWizardData($wizardData, $agreement);
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
