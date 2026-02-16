<?php

namespace App\Filament\Schemas\CreateAgreement;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

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
                                    ->native(false)
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
                                Select::make('holder_delivery_file')
                                    ->label('Entrega expediente')
                                    ->options([
                                        'ENTREGADO' => 'ENTREGADO',
                                        'PENDIENTE' => 'PENDIENTE',
                                    ])
                                    ->required(),
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
                                    ->live()
                                    ->options([
                                        'soltero' => 'Soltero(a)',
                                        'casado' => 'Casado(a)',
                                        'divorciado' => 'Divorciado(a)',
                                        'viudo' => 'Viudo(a)',
                                        'union_libre' => 'Unión Libre',
                                    ]),
                                Select::make('holder_marital_regime')
                                    ->label('Régimen Matrimonial')
                                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('holder_civil_status') === 'casado')
                                    ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('holder_civil_status') === 'casado')
                                    ->options([
                                        'bienes_mancomunados' => 'Bienes Mancomunados',
                                        'bienes_separados' => 'Bienes Separados',
                                    ])
                                    ->live(),
                                TextInput::make('holder_curp')
                                    ->label('CURP')
                                    ->maxLength(18)
                                    ->minLength(18),
                                Select::make('holder_regime_type')
                                    ->label('Régimen Fiscal')
                                    ->options([
                                        '601' => '601 - General de Ley Personas Morales',
                                        '603' => '603 - Personas Morales con Fines no Lucrativos',
                                        '605' => '605 - Sueldos y Salarios e Ingresos Asimilados a Salarios',
                                        '606' => '606 - Arrendamiento',
                                        '607' => '607 - Régimen de Enajenación o Adquisición de Bienes',
                                        '608' => '608 - Demás ingresos',
                                        '610' => '610 - Residentes en el Extranjero sin Establecimiento Permanente en México',
                                        '611' => '611 - Ingresos por Dividendos (socios y accionistas)',
                                        '612' => '612 - Personas Físicas con Actividades Empresariales y Profesionales',
                                        '614' => '614 - Ingresos por intereses',
                                        '615' => '615 - Régimen de los ingresos por obtención de premios',
                                        '616' => '616 - Sin obligaciones fiscales',
                                        '620' => '620 - Sociedades Cooperativas de Producción que optan por diferir sus ingresos',
                                        '621' => '621 - Incorporación Fiscal',
                                        '622' => '622 - Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
                                        '623' => '623 - Opcional para Grupos de Sociedades',
                                        '624' => '624 - Coordinados',
                                        '625' => '625 - Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas',
                                        '626' => '626 - Régimen Simplificado de Confianza (RESICO)',
                                    ])
                                    ->searchable(),
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
                        Toggle::make('has_co_borrower')
                            ->label('¿Existe una segunda persona que participará en el crédito como Coacreditado?')
                            ->inline(false)
                            ->live(),
                    ])
                    ->collapsible(),

                // DATOS DEL CÓNYUGE (Bloque Legal)
                Section::make('DATOS DEL CÓNYUGE')
                    ->description('Información requerida por el estado civil Casado')
                    ->icon('heroicon-o-heart')
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('holder_civil_status') === 'casado' &&
                        ($get('holder_marital_regime') === 'bienes_mancomunados' ||
                        ($get('has_co_borrower') && $get('co_borrower_relationship') === 'cónyuge'))
                    )
                    ->headerActions([
                        Action::make('copy_from_holder_spouse')
                            ->label('Copiar del titular')
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
                                    ->title('Datos copiados')
                                    ->success()
                                    ->send();
                            })
                            ->tooltip('Copiar domicilio del titular'),
                    ])
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('spouse_name')
                                    ->label('Nombre Completo')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('spouse_delivery_file')
                                    ->label('Entrega expediente')
                                    ->options([
                                        'ENTREGADO' => 'ENTREGADO',
                                        'PENDIENTE' => 'PENDIENTE',
                                    ]),
                                DatePicker::make('spouse_birthdate')
                                    ->native(false)
                                    ->label('Fecha de Nacimiento')
                                    ->displayFormat('d/m/Y')
                                    ->suffixIcon(Heroicon::Calendar),
                                TextInput::make('spouse_curp')
                                    ->label('CURP')
                                    ->maxLength(18)
                                    ->minLength(18),
                                Select::make('spouse_regime_type')
                                    ->label('Régimen Fiscal')
                                    ->options([
                                        '601' => '601 - General de Ley Personas Morales',
                                        '603' => '603 - Personas Morales con Fines no Lucrativos',
                                        '605' => '605 - Sueldos y Salarios e Ingresos Asimilados a Salarios',
                                        '606' => '606 - Arrendamiento',
                                        '607' => '607 - Régimen de Enajenación o Adquisición de Bienes',
                                        '608' => '608 - Demás ingresos',
                                        '610' => '610 - Residentes en el Extranjero sin Establecimiento Permanente en México',
                                        '611' => '611 - Ingresos por Dividendos (socios y accionistas)',
                                        '612' => '612 - Personas Físicas con Actividades Empresariales y Profesionales',
                                        '614' => '614 - Ingresos por intereses',
                                        '615' => '615 - Régimen de los ingresos por obtención de premios',
                                        '616' => '616 - Sin obligaciones fiscales',
                                        '620' => '620 - Sociedades Cooperativas de Producción que optan por diferir sus ingresos',
                                        '621' => '621 - Incorporación Fiscal',
                                        '622' => '622 - Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
                                        '623' => '623 - Opcional para Grupos de Sociedades',
                                        '624' => '624 - Coordinados',
                                        '625' => '625 - Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas',
                                        '626' => '626 - Régimen Simplificado de Confianza (RESICO)',
                                    ])
                                    ->searchable(),
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
                                    ->tel(),
                                TextInput::make('spouse_phone')
                                    ->label('Núm. Celular')
                                    ->tel(),
                            ]),
                        Grid::make(1)
                            ->schema([
                                TextInput::make('spouse_current_address')
                                    ->label('Calle y Domicilio'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('spouse_neighborhood')
                                    ->label('Colonia'),
                                TextInput::make('spouse_postal_code')
                                    ->label('C.P.'),
                                TextInput::make('spouse_municipality')
                                    ->label('Municipio'),
                                TextInput::make('spouse_state')
                                    ->label('Estado'),
                            ]),
                    ])
                    ->collapsible(),

                // DATOS DEL COACREDITADO (Bloque Financiero)
                Section::make('DATOS DEL COACREDITADO')
                    ->description('Persona que participará financieramente en el crédito')
                    ->icon('heroicon-o-users')
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('has_co_borrower'))
                    ->schema([
                        ToggleButtons::make('co_borrower_relationship')
                            ->label('¿Quién es el Coacreditado?')
                            ->options([
                                'cónyuge' => 'El Cónyuge',
                                'coacreditado' => 'Un Tercero',
                            ])
                            ->default('cónyuge')
                            ->colors([
                                'cónyuge' => 'success',
                                'coacreditado' => 'info',
                            ])
                            ->icons([
                                'cónyuge' => 'heroicon-o-heart',
                                'coacreditado' => 'heroicon-o-user-plus',
                            ])
                            ->disableOptionWhen(fn (string $value, \Filament\Schemas\Components\Utilities\Get $get) => $value === 'cónyuge' && $get('holder_civil_status') !== 'casado'
                            )
                            ->inline()
                            ->required()
                            ->live(),

                        // Mensaje si es Cónyuge
                        Placeholder::make('co_borrower_spouse_info')
                            ->hiddenLabel()
                            ->content(new HtmlString('
                                <div class="text-sm text-blue-600 bg-blue-50 p-4 rounded-lg border border-blue-200">
                                    <span class="font-bold">Info:</span> 
                                    Se utilizarán los datos capturados en la sección "DATOS DEL CÓNYUGE".
                                </div>
                            '))
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('co_borrower_relationship') === 'cónyuge'),

                        // Campos para Tercero (Nuevos campos co_borrower_*)
                        Grid::make(1)
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('co_borrower_relationship') === 'coacreditado')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('co_borrower_name')
                                            ->label('Nombre Completo (Tercero)')
                                            ->required()
                                            ->maxLength(255),
                                        DatePicker::make('co_borrower_birthdate')
                                            ->native(false)
                                            ->label('Fecha de Nacimiento')
                                            ->displayFormat('d/m/Y')
                                            ->suffixIcon(Heroicon::Calendar),
                                        Select::make('co_borrower_civil_status')
                                            ->label('Estado Civil')
                                            ->options([
                                                'soltero' => 'Soltero(a)',
                                                'casado' => 'Casado(a)',
                                                'divorciado' => 'Divorciado(a)',
                                                'viudo' => 'Viudo(a)',
                                                'union_libre' => 'Unión Libre',
                                            ]),
                                        TextInput::make('co_borrower_curp')
                                            ->label('CURP')
                                            ->maxLength(18),
                                        TextInput::make('co_borrower_rfc')
                                            ->label('RFC')
                                            ->maxLength(13),
                                        TextInput::make('co_borrower_occupation')
                                            ->label('Ocupación'),
                                        TextInput::make('co_borrower_email')
                                            ->label('Email')
                                            ->email(),
                                        TextInput::make('co_borrower_phone')
                                            ->label('Teléfono')
                                            ->tel(),
                                    ]),
                                Grid::make(1)
                                    ->schema([
                                        TextInput::make('co_borrower_current_address')
                                            ->label('Direction Actual'),
                                    ]),
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
                                    ->label('Mantenimiento desarrollo ($)')
                                    ->numeric()
                                    ->prefix('$'),
                                TextInput::make('private_president_quota')
                                    ->label('Mantenimiento privada ($)')
                                    ->numeric()
                                    ->prefix('$'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
