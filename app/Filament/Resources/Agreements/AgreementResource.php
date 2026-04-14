<?php

namespace App\Filament\Resources\Agreements;

use App\Filament\Resources\Agreements\Pages\CreateAgreement;
use App\Filament\Resources\Agreements\Pages\EditAgreement;
use App\Filament\Resources\Agreements\Pages\ListAgreements;
use App\Models\Agreement;
use App\Models\Client;
use App\Models\ConfigurationCalculator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AgreementResource extends Resource
{
    protected static ?string $model = Agreement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Convenios';

    protected static ?string $modelLabel = 'Convenio';

    protected static ?string $pluralModelLabel = 'Convenios';

    protected static ?int $navigationSort = 2;

    // Ocultar del menú principal - solo accesible vía wizard
    protected static bool $shouldRegisterNavigation = false;

    // Helper para limpiar valores monetarios
    protected static function cleanMoneyValue($value): float
    {
        if (is_null($value) || $value === '') {
            return 0.0;
        }

        // Convertir a string y remover todo excepto dígitos, punto y signo negativo
        $stringValue = (string) $value;

        // Remover símbolos de moneda, comas, espacios, etc.
        $cleaned = preg_replace('/[^\d.-]/', '', $stringValue);

        // Si está vacío después de limpiar, retornar 0
        if (empty($cleaned) || $cleaned === '.' || $cleaned === '-') {
            return 0.0;
        }

        return (float) $cleaned;
    }

    // Método helper corregido para recalcular todos los campos
    protected static function recalculateAll(callable $set, callable $get): void
    {
        $valorConvenio = (float) $get('valor_convenio') ?? 0;
        $porcentajeComisionSinIva = (float) $get('porcentaje_comision_sin_iva') ?? 0;
        $isr = (float) $get('isr') ?? 0;
        $cancelacionHipoteca = (float) $get('cancelacion_hipoteca') ?? 0;
        $montoCredito = (float) $get('monto_credito') ?? 0;

        // 1. Precio Promoción = Valor Convenio * Multiplicador
        // Intentar obtener multiplicador del estado (Priorizar estado de la propiedad)
        $multiplicador = 1.0;
        $stateName = $get('estado_propiedad') ?? $get('holder_state');
        if ($stateName) {
            $rate = \App\Models\StateCommissionRate::where('state_name', $stateName)->first();
            if ($rate) {
                $multiplicador = 1 + ($rate->commission_percentage / 100);
            }
        }

        $precioPromocion = $valorConvenio * $multiplicador;
        $set('precio_promocion', round($precioPromocion, 0));

        // 2. Monto Comisión (Sin IVA) = % Comisión (Sin IVA) * Valor Convenio / 100
        $montoComisionSinIva = ($porcentajeComisionSinIva * $valorConvenio) / 100;
        $set('monto_comision_sin_iva', round($montoComisionSinIva, 2));

        // 3. Comisión total por pagar = Monto Comisión (Sin IVA) * IVA Multiplier
        $ivaPercentage = (float) ConfigurationCalculator::get('iva_valor', 16.00);
        $ivaMultiplier = 1 + ($ivaPercentage / 100);
        $comisionTotalPagar = $montoComisionSinIva * $ivaMultiplier;
        $set('comision_total_pagar', round($comisionTotalPagar, 2));

        // 4. Comisión IVA Incluido = (Comisión Total / Valor Convenio) * 100
        if ($valorConvenio > 0) {
            $comisionIvaIncluido = ($comisionTotalPagar / $valorConvenio) * 100;
            $set('comision_iva_incluido', round($comisionIvaIncluido, 2));
        }

        // 5. Total Gastos FI (Venta) = ISR + Cancelación de hipoteca
        $totalGastosFi = $isr + $cancelacionHipoteca;
        $set('total_gastos_fi', round($totalGastosFi, 2));

        // 6. Ganancia Final = Precio Promoción - ISR - Cancelación - Comisión total - Monto de crédito
        $gananciaFinal = $precioPromocion - $isr - $cancelacionHipoteca - $comisionTotalPagar - $montoCredito;
        $set('ganancia_final', round($gananciaFinal, 2));

        // 7. Campos espejo
        $set('valor_compraventa', $valorConvenio);
        $set('comision_total', round($comisionTotalPagar, 2));
    }

    /**
     * Schema de la calculadora financiera completa
     */
    public static function getCalculatorSchema(): array
    {
        return [
            Section::make('📊 CALCULADORA FINANCIERA')
                ->description('Cálculos automáticos en tiempo real basados en configuración dinámica')
                ->afterStateHydrated(function (callable $set, callable $get) {
                    // Auto-calcular al cargar el formulario
                    self::recalculateAll($set, $get);
                })
                ->schema([

                    // DATOS Y VALOR VIVIENDA
                    Section::make('DATOS Y VALOR VIVIENDA')
                        ->schema([
                            Grid::make(4)
                                ->schema([
                                    Forms\Components\TextInput::make('domicilio_convenio')
                                        ->label('Domicilio Viv. Convenio')
                                        ->columnSpan(2),

                                    Forms\Components\TextInput::make('comunidad')
                                        ->label('Comunidad')
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('tipo_vivienda')
                                        ->label('Tipo de vivienda')
                                        ->options([
                                            'CASA' => 'CASA',
                                            'DEPARTAMENTO' => 'DEPARTAMENTO',
                                            'TOWNHOUSE' => 'TOWNHOUSE',
                                            'CONDOMINIO' => 'CONDOMINIO',
                                        ])
                                        ->columnSpan(2),

                                    Forms\Components\TextInput::make('prototipo')
                                        ->label('Prototipo')
                                        ->columnSpan(2),
                                ]),
                        ]),

                    // CONFIGURACIÓN DE COMISIONES
                    Section::make('CONFIGURACIÓN DE COMISIONES')
                        ->schema([
                            Grid::make(4)
                                ->schema([
                                    Forms\Components\TextInput::make('porcentaje_comision_sin_iva')
                                        ->label('% Comisión Sin IVA*')
                                        ->suffix('%')
                                        ->numeric()
                                        ->default(fn () => ConfigurationCalculator::get('comision_sin_iva_default', 6.50))
                                        ->live(debounce: 500)
                                        ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::recalculateAll($set, $get)
                                        )
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('monto_comision_sin_iva')
                                        ->label('Monto Comisión (Sin IVA)')
                                        ->prefix('$')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '0.00')
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('comision_iva_incluido')
                                        ->label('Comisión TOTAL (IVA incluido)')
                                        ->suffix('%')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '0.00')
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('comision_total_pagar')
                                        ->label('Comisión total por pagar')
                                        ->prefix('$')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '0.00')
                                        ->columnSpan(1),
                                ]),
                        ]),

                    // DATOS PRINCIPALES DE VALOR
                    Section::make('VALORES PRINCIPALES')
                        ->description('💡 **Valor Convenio** es el campo principal. Al modificarlo se recalculan automáticamente todos los demás valores.')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    Forms\Components\TextInput::make('precio_promocion')
                                        ->label('Precio promoción')
                                        ->prefix('$')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->formatStateUsing(fn ($state) => $state ? number_format($state, 0) : '0'),

                                    Forms\Components\TextInput::make('valor_convenio')
                                        ->label('Valor Convenio*')
                                        ->prefix('$')
                                        ->numeric()
                                        ->required()
                                        ->default(0)
                                        ->live(debounce: 500)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            if ($state && $state > 0) {
                                                self::recalculateAll($set, $get);
                                            }
                                        })
                                        ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                            if ($state && $state > 0) {
                                                self::recalculateAll($set, $get);
                                            }
                                        }),

                                    Forms\Components\TextInput::make('monto_credito')
                                        ->label('Monto de crédito')
                                        ->prefix('$')
                                        ->numeric()
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::recalculateAll($set, $get)
                                        ),
                                ]),

                            Grid::make(3)
                                ->schema([
                                    Forms\Components\Select::make('tipo_credito')
                                        ->label('Tipo de crédito')
                                        ->options([
                                            'BANCARIO' => 'BANCARIO',
                                            'INFONAVIT' => 'INFONAVIT',
                                            'FOVISSSTE' => 'FOVISSSTE',
                                            'CONTADO' => 'CONTADO',
                                            'OTRO' => 'OTRO',
                                        ])
                                        ->default('BANCARIO'),

                                    Forms\Components\TextInput::make('otro_banco')
                                        ->label('Otro - Banco')
                                        ->placeholder('NO APLICA')
                                        ->visible(fn (callable $get) => $get('tipo_credito') === 'OTRO')
                                        ->columnSpan(2),
                                ]),
                        ]),

                    // COSTOS DE OPERACIÓN
                    Section::make('COSTOS DE OPERACIÓN POR VENTA DE INMUEBLE')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    Forms\Components\TextInput::make('valor_compraventa')
                                        ->label('Valor CompraVenta')
                                        ->prefix('$')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->formatStateUsing(fn ($state) => $state ? number_format($state, 0) : '0'),

                                    Forms\Components\TextInput::make('isr')
                                        ->label('ISR')
                                        ->prefix('$')
                                        ->numeric()
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::recalculateAll($set, $get)
                                        ),

                                    Forms\Components\TextInput::make('cancelacion_hipoteca')
                                        ->label('Cancelación de hipoteca')
                                        ->prefix('$')
                                        ->numeric()
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::recalculateAll($set, $get)
                                        ),
                                ]),

                            Grid::make(3)
                                ->schema([
                                    Forms\Components\TextInput::make('comision_total')
                                        ->label('Comisión total')
                                        ->prefix('$')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->formatStateUsing(fn ($state) => $state ? number_format($state, 0) : '0'),

                                    Forms\Components\TextInput::make('ganancia_final')
                                        ->label('Ganancia Final (Est.)')
                                        ->prefix('$')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->formatStateUsing(fn ($state) => $state ? number_format($state, 0) : '0'),

                                    Forms\Components\TextInput::make('total_gastos_fi')
                                        ->label('Total Gastos FI (Venta)')
                                        ->prefix('$')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->formatStateUsing(fn ($state) => $state ? number_format($state, 0) : '0'),
                                ]),
                        ]),

                    // NOTA EXPLICATIVA
                    Forms\Components\Placeholder::make('nota_calculadora')
                        ->label('')
                        ->content('
                            📝 **Nota:** La ganancia es estimada, ya que se requiere confirmar costo de cancelación, cuando aplique, de acuerdo a Notaría asignada y entidad donde se ubica inmueble e ISR (impuesto sobre la renta) que corren por su cuenta.
                            
                            Así como, gastos adicionales como certificaciones, aplica en Estado de México y Cancún y servicios al corriente (sin adeudos) de predial, agua, CFE y mantenimientos (privada y/o AC).
                        ')
                        ->extraAttributes(['class' => 'text-sm text-gray-600 italic bg-yellow-50 p-3 rounded']),
                ])
                ->collapsible()
                ->persistCollapsed(),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cliente y Estado')
                    ->schema([
                        Forms\Components\Select::make('client_xante_id')
                            ->label('Cliente')
                            ->options(function () {
                                return Client::all()->pluck('name', 'xante_id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $client = Client::where('xante_id', $state)->first();
                                    if ($client) {
                                        // Datos personales titular
                                        $set('holder_name', $client->name);
                                        $set('holder_birthdate', $client->birthdate);
                                        $set('holder_curp', $client->curp);
                                        $set('holder_rfc', $client->rfc);
                                        $set('holder_email', $client->email);
                                        $set('holder_phone', $client->phone);
                                        $set('holder_delivery_file', $client->delivery_file);
                                        $set('holder_civil_status', $client->civil_status);
                                        $set('holder_regime_type', $client->regime_type);
                                        $set('holder_occupation', $client->occupation);
                                        $set('holder_office_phone', $client->office_phone);
                                        $set('holder_additional_contact_phone', $client->additional_contact_phone);
                                        $set('holder_current_address', $client->current_address);
                                        $set('holder_neighborhood', $client->neighborhood);
                                        $set('holder_postal_code', $client->postal_code);
                                        $set('holder_municipality', $client->municipality);
                                        $set('holder_state', $client->state);

                                        // Datos cónyuge
                                        $set('spouse_name', $client->spouse_name);
                                        $set('spouse_birthdate', $client->spouse_birthdate);
                                        $set('spouse_curp', $client->spouse_curp);
                                        $set('spouse_rfc', $client->spouse_rfc);
                                        $set('spouse_email', $client->spouse_email);
                                        $set('spouse_phone', $client->spouse_phone);
                                        $set('spouse_delivery_file', $client->spouse_delivery_file);
                                        $set('spouse_civil_status', $client->spouse_civil_status);
                                        $set('spouse_regime_type', $client->spouse_regime_type);
                                        $set('spouse_occupation', $client->spouse_occupation);
                                        $set('spouse_office_phone', $client->spouse_office_phone);
                                        $set('spouse_additional_contact_phone', $client->spouse_additional_contact_phone);
                                        $set('spouse_current_address', $client->spouse_current_address);
                                        $set('spouse_neighborhood', $client->spouse_neighborhood);
                                        $set('spouse_postal_code', $client->spouse_postal_code);
                                        $set('spouse_municipality', $client->spouse_municipality);
                                        $set('spouse_state', $client->spouse_state);

                                        // Contactos AC/Presidente
                                        $set('ac_name', $client->ac_name);
                                        $set('ac_phone', $client->ac_phone);
                                        $set('ac_quota', $client->ac_quota);
                                        $set('private_president_name', $client->private_president_name);
                                        $set('private_president_phone', $client->private_president_phone);
                                        $set('private_president_quota', $client->private_president_quota);
                                    }
                                }
                            })
                            ->createOptionForm([
                                Forms\Components\TextInput::make('xante_id')
                                    ->label('ID Xante')
                                    ->required()
                                    ->unique('clients', 'xante_id'),
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre Completo')
                                    ->required(),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required(),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Teléfono')
                                    ->required(),
                            ])
                            ->createOptionUsing(function (array $data): string {
                                $client = Client::create($data);

                                return $client->xante_id;
                            }),
                        Forms\Components\Select::make('status')
                            ->label('Estado del Convenio')
                            ->options([
                                'sin_convenio' => 'Sin Convenio',
                                'expediente_incompleto' => 'Expediente Incompleto',
                                'expediente_completo' => 'Expediente Completo',
                                'convenio_proceso' => 'Convenio en Proceso',
                                'convenio_firmado' => 'Convenio Firmado',
                            ])
                            ->default('expediente_incompleto')
                            ->required(),
                    ])->columns(2),

                Section::make('Datos Personales Titular')
                    ->schema([
                        Forms\Components\TextInput::make('holder_name')
                            ->label('Nombre Cliente')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DatePicker::make('holder_birthdate')
                            ->label('Fecha de Nacimiento')
                            ->disabled()
                            ->dehydrated(false)
                            ->native(false),
                        Forms\Components\TextInput::make('holder_curp')
                            ->label('CURP')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('holder_rfc')
                            ->label('RFC')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('holder_email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('holder_phone')
                            ->label('Núm. Celular')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('holder_delivery_file')
                            ->label('Entrega expediente')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('holder_civil_status')
                            ->label('Estado civil')
                            ->options([
                                'soltero' => 'Soltero(a)',
                                'casado' => 'Casado(a)',
                                'divorciado' => 'Divorciado(a)',
                                'viudo' => 'Viudo(a)',
                                'union_libre' => 'Unión Libre',
                            ])
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('holder_regime_type')
                            ->label('Régimen Fiscal')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('holder_occupation')
                            ->label('Ocupación')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('holder_office_phone')
                            ->label('Tel. oficina')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('holder_additional_contact_phone')
                            ->label('Tel. Contacto Adic.')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make('holder_current_address')
                            ->label('Domicilio Actual')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(3),
                        Forms\Components\TextInput::make('holder_neighborhood')
                            ->label('Colonia')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('holder_postal_code')
                            ->label('C.P.')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('holder_municipality')
                            ->label('Municipio - Alcaldía')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('holder_state')
                            ->label('Estado')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),

                Section::make('DATOS PERSONALES COACREDITADO / CÓNYUGE')
                    ->schema([
                        Forms\Components\TextInput::make('spouse_name')
                            ->label('Nombre Cliente')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DatePicker::make('spouse_birthdate')
                            ->label('Fecha de Nacimiento')
                            ->disabled()
                            ->dehydrated(false)
                            ->native(false),
                        Forms\Components\TextInput::make('spouse_curp')
                            ->label('CURP')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('spouse_rfc')
                            ->label('RFC')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('spouse_email')
                            ->label('Correo electrónico')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('spouse_phone')
                            ->label('Núm. Celular')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('spouse_delivery_file')
                            ->label('Entrega expediente')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('spouse_civil_status')
                            ->label('Estado civil')
                            ->options([
                                'soltero' => 'Soltero(a)',
                                'casado' => 'Casado(a)',
                                'divorciado' => 'Divorciado(a)',
                                'viudo' => 'Viudo(a)',
                                'union_libre' => 'Unión Libre',
                            ])
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('spouse_regime_type')
                            ->label('Bajo ¿qué régimen?')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('spouse_occupation')
                            ->label('Ocupación')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('spouse_office_phone')
                            ->label('Tel. oficina')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('spouse_additional_contact_phone')
                            ->label('Tel. Contacto Adic.')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make('spouse_current_address')
                            ->label('Domicilio Actual')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(3),
                        Forms\Components\TextInput::make('spouse_neighborhood')
                            ->label('Colonia')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('spouse_postal_code')
                            ->label('C.P.')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('spouse_municipality')
                            ->label('Municipio - Alcaldía')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('spouse_state')
                            ->label('Estado')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),

                Section::make('CONTACTO AC Y/O PRESIDENTE DE PRIVADA')
                    ->schema([
                        Forms\Components\TextInput::make('ac_name')
                            ->label('NOMBRE AC')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('private_president_name')
                            ->label('PRESIDENTE PRIVADA')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('ac_phone')
                            ->label('Núm. Celular (AC)')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('private_president_phone')
                            ->label('Núm. Celular (Presidente)')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('ac_quota')
                            ->label('Mantenimiento desarrollo ($)')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('private_president_quota')
                            ->label('Mantenimiento privada ($)')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),

                Section::make('CHECKLIST DE DOCUMENTOS')
                    ->schema([
                        Forms\Components\CheckboxList::make('documents_checklist')
                            ->label('Documentos Entregados')
                            ->options([
                                'identificacion_oficial' => 'Identificación Oficial',
                                'comprobante_ingresos' => 'Comprobante de Ingresos',
                                'comprobante_domicilio' => 'Comprobante de Domicilio',
                                'curp' => 'CURP',
                                'rfc' => 'RFC',
                                'acta_nacimiento' => 'Acta de Nacimiento',
                                'estado_cuenta' => 'Estado de Cuenta',
                                'referencias_comerciales' => 'Referencias Comerciales',
                                'referencias_personales' => 'Referencias Personales',
                                'autorizacion_buro' => 'Autorización Buró de Crédito',
                            ])
                            ->columns(2),
                    ]),

                // SECCIÓN CALCULADORA FINANCIERA COMPLETA
                ...self::getCalculatorSchema(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.xante_id')
                    ->label('ID Xante')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
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
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('validation_status')
                    ->label('Estado Validación')
                    ->badge()
                    ->icon(fn (string $state): ?string => match ($state) {
                        'pending' => 'heroicon-m-clock',
                        'approved' => 'heroicon-m-check-circle',
                        'rejected' => 'heroicon-m-x-circle',
                        'with_observations' => 'heroicon-m-exclamation-triangle',
                        default => null,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'with_observations' => 'orange',
                        'not_required' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'En Revisión',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        'with_observations' => 'Con Observaciones',
                        'not_required' => 'No Requerido',
                        default => 'Pendiente',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('edit')
                    ->url(fn (Agreement $record): string => static::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-o-pencil'),
            ])
            ->bulkActions(
                in_array(auth()->user()?->role, ['admin', 'gerencia'])
                    ? [
                        BulkActionGroup::make([
                            DeleteBulkAction::make(),
                        ]),
                    ]
                    : []
            )
            ->checkIfRecordIsSelectableUsing(
                fn ($record): bool => in_array(auth()->user()?->role, ['admin', 'gerencia']),
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
            'index' => ListAgreements::route('/'),
            'create' => CreateAgreement::route('/create'),
            'edit' => EditAgreement::route('/{record}/edit'),
        ];
    }
}
