<?php

namespace App\Filament\Schemas\CreateAgreement;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

class StepTwoSchema
{
    public static function make($page): Step
    {
        return Step::make('Cliente')
            ->description('Información personal del cliente')
            ->icon('heroicon-o-user')
            ->afterValidation(function () use ($page) {
                $page->saveStepData(3);
            })
            ->schema([
                // DATOS GENERALES - FASE I
                Section::make('DATOS GENERALES "FASE I"')
                    ->description('Información básica del convenio')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('xante_id')
                                    ->label('ID Xante')
                                    ->disabled()
                                    ->dehydrated(false),
                                DatePicker::make('fecha_registro')
                                    ->label('Fecha')
                                    ->displayFormat('d/m/Y')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffixIcon(Heroicon::Calendar),
                            ]),
                    ])
                    ->collapsible(),

                // DATOS PERSONALES TITULAR
                Section::make('DATOS PERSONALES TITULAR:')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('holder_name')
                                    ->label('Nombre Cliente')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('holder_delivery_file')
                                    ->label('Entrega expediente')
                                    ->maxLength(100),
                                DatePicker::make('holder_birthdate')
                                    ->native(false)
                                    ->label('Fecha de Nacimiento (min 18 años)')
                                    ->displayFormat('d/m/Y')
                                    ->maxDate(Carbon::today()->subYears(18))
                                    ->validationMessages([
                                        'max' => 'El titular debe ser mayor de 18 años.',
                                    ])
                                    ->suffixIcon(Heroicon::Calendar),
                                Select::make('holder_civil_status')
                                    ->label('Estado civil')
                                    ->options([
                                        'soltero' => 'Soltero(a)',
                                        'casado' => 'Casado(a)',
                                        'divorciado' => 'Divorciado(a)',
                                        'viudo' => 'Viudo(a)',
                                        'union_libre' => 'Unión Libre',
                                    ]),
                                TextInput::make('holder_curp')
                                    ->label('CURP')
                                    ->maxLength(18)
                                    ->minLength(18),
                                TextInput::make('holder_regime_type')
                                    ->label('Régimen Fiscal')
                                    ->maxLength(100),
                                TextInput::make('holder_rfc')
                                    ->label('RFC')
                                    ->maxLength(13),
                                TextInput::make('holder_occupation')
                                    ->label('Ocupación')
                                    ->maxLength(100),
                                TextInput::make('holder_email')
                                    ->label('Correo electrónico')
                                    ->disabled()
                                    ->email()
                                    ->required(),
                                TextInput::make('holder_office_phone')
                                    ->label('Tel. oficina')
                                    ->tel()
                                    ->maxLength(20),
                                TextInput::make('holder_phone')
                                    ->label('Núm. Celular')
                                    ->tel()
                                    ->required()
                                    ->maxLength(20),
                                TextInput::make('holder_additional_contact_phone')
                                    ->label('Tel. Contacto Adic.')
                                    ->tel()
                                    ->maxLength(20),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('current_address')
                                    ->label('Calle y Domicilio')
                                    ->maxLength(400)
                                    ->columnSpan(1),
                                TextInput::make('holder_house_number')
                                    ->label('Número')
                                    ->maxLength(20)
                                    ->columnSpan(1),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('neighborhood')
                                    ->label('Colonia')
                                    ->maxLength(100),
                                TextInput::make('postal_code')
                                    ->label('C.P.')
                                    ->maxLength(10),
                                TextInput::make('municipality')
                                    ->label('Municipio - Alcaldía')
                                    ->maxLength(100),
                                TextInput::make('state')
                                    ->label('Estado')
                                    ->maxLength(100),
                            ]),
                    ])
                    ->collapsible(),

                // DATOS PERSONALES COACREDITADO / CÓNYUGE
                Section::make('DATOS PERSONALES COACREDITADO / CÓNYUGE:')
                    ->description('Información del cónyuge o coacreditado')
                    ->headerActions([
                        Action::make('copy_from_holder')
                            ->label('Copiar datos del titular')
                            ->icon('heroicon-o-document-duplicate')
                            ->color('gray')
                            ->size('sm')
                            ->action(function (callable $set, callable $get) {
                                // Copiar datos de domicilio
                                $set('spouse_current_address', $get('current_address'));
                                $set('spouse_house_number', $get('holder_house_number'));
                                $set('spouse_neighborhood', $get('neighborhood'));
                                $set('spouse_postal_code', $get('postal_code'));
                                $set('spouse_municipality', $get('municipality'));
                                $set('spouse_state', $get('state'));

                                // Copiar datos de teléfono
                                $set('spouse_phone', $get('holder_phone'));
                                $set('spouse_office_phone', $get('holder_office_phone'));
                                $set('spouse_additional_contact_phone', $get('holder_additional_contact_phone'));

                                Notification::make()
                                    ->title('Datos copiados exitosamente')
                                    ->body('Los datos de domicilio y teléfono del titular han sido copiados al cónyuge.')
                                    ->success()
                                    ->duration(5000)
                                    ->send();
                            })
                            ->tooltip('Copiar domicilios y teléfonos del titular'),
                    ])
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('spouse_name')
                                    ->label('Nombre Cliente')
                                    ->maxLength(255),
                                TextInput::make('spouse_delivery_file')
                                    ->label('Entrega expediente')
                                    ->maxLength(100),
                                DatePicker::make('spouse_birthdate')
                                    ->native(false)
                                    ->label('Fecha de Nacimiento (min 18 años)')
                                    ->displayFormat('d/m/Y')
                                    ->maxDate(Carbon::today()->subYears(18))
                                    ->validationMessages([
                                        'max' => 'El titular debe ser mayor de 18 años.',
                                    ])
                                    ->suffixIcon(Heroicon::Calendar),
                                Select::make('spouse_civil_status')
                                    ->label('Estado civil')
                                    ->options([
                                        'soltero' => 'Soltero(a)',
                                        'casado' => 'Casado(a)',
                                        'divorciado' => 'Divorciado(a)',
                                        'viudo' => 'Viudo(a)',
                                        'union_libre' => 'Unión Libre',
                                    ]),
                                TextInput::make('spouse_curp')
                                    ->label('CURP')
                                    ->maxLength(18)
                                    ->minLength(18),
                                TextInput::make('spouse_regime_type')
                                    ->label('Régimen Fiscal')
                                    ->maxLength(100),
                                TextInput::make('spouse_rfc')
                                    ->label('RFC')
                                    ->maxLength(13),
                                TextInput::make('spouse_occupation')
                                    ->label('Ocupación')
                                    ->maxLength(100),
                                TextInput::make('spouse_email')
                                    ->label('Correo electrónico')
                                    ->email(),
                                TextInput::make('spouse_office_phone')
                                    ->label('Tel. oficina')
                                    ->tel()
                                    ->maxLength(20),
                                TextInput::make('spouse_phone')
                                    ->label('Núm. Celular')
                                    ->tel()
                                    ->maxLength(20),
                                TextInput::make('spouse_additional_contact_phone')
                                    ->label('Tel. Contacto Adic.')
                                    ->tel()
                                    ->maxLength(20),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('spouse_current_address')
                                    ->label('Calle y Domicilio')
                                    ->maxLength(400)
                                    ->columnSpan(1),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('spouse_neighborhood')
                                    ->label('Colonia')
                                    ->maxLength(100),
                                TextInput::make('spouse_postal_code')
                                    ->label('C.P.')
                                    ->maxLength(10),
                                TextInput::make('spouse_municipality')
                                    ->label('Municipio - Alcaldía')
                                    ->maxLength(100),
                                TextInput::make('spouse_state')
                                    ->label('Estado')
                                    ->maxLength(100),
                            ]),
                    ])
                    ->collapsible(),

                // CONTACTO AC Y/O PRESIDENTE DE PRIVADA
                Section::make('CONTACTO AC Y/O PRESIDENTE DE PRIVADA')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('ac_name')
                                    ->label('NOMBRE AC')
                                    ->maxLength(255),
                                TextInput::make('private_president_name')
                                    ->label('PRESIDENTE PRIVADA')
                                    ->maxLength(255),
                                TextInput::make('ac_phone')
                                    ->label('Núm. Celular')
                                    ->tel()
                                    ->maxLength(20),
                                TextInput::make('private_president_phone')
                                    ->label('Núm. Celular')
                                    ->tel()
                                    ->maxLength(20),
                                TextInput::make('ac_quota')
                                    ->label('CUOTA')
                                    ->numeric()
                                    ->prefix('$'),
                                TextInput::make('private_president_quota')
                                    ->label('CUOTA')
                                    ->numeric()
                                    ->prefix('$'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
