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
                            ->label('Bajo ¿qué régimen?')
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
                            ->label('Bajo ¿qué régimen?')
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
        return $table
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
                TextColumn::make('latestAgreement.status')
                    ->label('Estado Convenio')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sin_convenio' => 'gray',
                        'expediente_incompleto' => 'warning',
                        'expediente_completo' => 'success',
                        'convenio_proceso' => 'info',
                        'convenio_firmado' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sin_convenio' => 'Sin Convenio',
                        'expediente_incompleto' => 'Expediente Incompleto',
                        'expediente_completo' => 'Expediente Completo',
                        'convenio_proceso' => 'Convenio en Proceso',
                        'convenio_firmado' => 'Convenio Firmado',
                        default => 'Sin Convenio',
                    })
                    ->placeholder('Sin Convenio'),
            ])
            ->filters([
                //
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
}
