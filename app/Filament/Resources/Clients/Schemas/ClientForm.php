<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('DATOS PERSONALES TITULAR')
                    ->schema([
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
                    ->relationship('spouse')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre Cliente')
                            ->maxLength(255),
                        DatePicker::make('birthdate')
                            ->label('Fecha de Nacimiento')
                            ->native(false),
                        TextInput::make('curp')
                            ->label('CURP')
                            ->maxLength(18)
                            ->rules(['regex:/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[0-9]{2}$/']),
                        TextInput::make('rfc')
                            ->label('RFC')
                            ->maxLength(13)
                            ->rules(['regex:/^[A-Z&Ñ]{3,4}[0-9]{6}[A-V1-9][A-Z1-9][0-9A]$/']),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Núm. Celular')
                            ->tel()
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
                            ->rows(3),
                        TextInput::make('neighborhood')
                            ->label('Colonia')
                            ->maxLength(255),
                        TextInput::make('postal_code')
                            ->label('C.P.')
                            ->maxLength(10),
                        TextInput::make('municipality')
                            ->label('Municipio - Alcaldía')
                            ->maxLength(255),
                        TextInput::make('state')
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
}
