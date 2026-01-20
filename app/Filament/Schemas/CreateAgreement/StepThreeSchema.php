<?php

namespace App\Filament\Schemas\CreateAgreement;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

class StepThreeSchema
{
    public static function make($page): Step
    {
        return Step::make('Propiedad')
            ->description('Datos de la vivienda y ubicación')
            ->icon('heroicon-o-home-modern')
            ->afterValidation(function () use ($page) {
                $page->saveStepData(4);
            })
            ->schema([
                Section::make('INFORMACIÓN DE LA PROPIEDAD')
                    ->description('Datos de ubicación y características de la vivienda')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('domicilio_convenio')
                                    ->label('Domicilio Viv. Convenio')
                                    ->maxLength(255)
                                    ->required(),
                                TextInput::make('comunidad')
                                    ->label('Comunidad')
                                    ->maxLength(255)
                                    ->required(),
                                TextInput::make('tipo_vivienda')
                                    ->label('Tipo de Vivienda')
                                    ->maxLength(100)
                                    ->required(),
                                TextInput::make('prototipo')
                                    ->label('Prototipo')
                                    ->maxLength(100)
                                    ->required(),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('DATOS ADICIONALES')
                    ->description('Información complementaria de la propiedad')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('lote')
                                    ->label('Lote')
                                    ->maxLength(50)
                                    ->required(),
                                TextInput::make('manzana')
                                    ->label('Manzana')
                                    ->maxLength(50)
                                    ->required(),
                                TextInput::make('etapa')
                                    ->label('Etapa')
                                    ->maxLength(50)
                                    ->required(),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('municipio_propiedad')
                                    ->label('Municipio')
                                    ->maxLength(100)
                                    ->required(),
                                \Filament\Forms\Components\Select::make('estado_propiedad')
                                    ->label('Estado')
                                    ->options(\App\Models\StateCommissionRate::where('is_active', true)->pluck('state_name', 'state_name'))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $rate = \App\Models\StateCommissionRate::where('state_name', $state)->first();
                                            $set('state_commission_percentage', $rate ? $rate->commission_percentage : 0);
                                            // Limpiar la cuenta bancaria seleccionada cuando cambia el estado
                                            $set('bank_account_id', null);
                                        } else {
                                            $set('state_commission_percentage', 0);
                                            $set('bank_account_id', null);
                                        }
                                    }),
                                \Filament\Forms\Components\Hidden::make('state_commission_percentage')
                                    ->default(function (callable $get) {
                                        $stateName = $get('estado_propiedad');
                                        if ($stateName) {
                                            $rate = \App\Models\StateCommissionRate::where('state_name', $stateName)->first();

                                            return $rate ? $rate->commission_percentage : 0;
                                        }

                                        return 0;
                                    })
                                    ->afterStateHydrated(function (\Filament\Forms\Components\Hidden $component, $state, callable $get) {
                                        // Si el campo está vacío (convenio existente sin este dato), calcularlo
                                        if (! $state || $state == 0) {
                                            $stateName = $get('estado_propiedad');
                                            if ($stateName) {
                                                $rate = \App\Models\StateCommissionRate::where('state_name', $stateName)->first();
                                                $component->state($rate ? $rate->commission_percentage : 0);
                                            }
                                        }
                                    }),
                            ]),
                        Grid::make(1)
                            ->schema([
                                DatePicker::make('fecha_propiedad')
                                    ->label('Fecha escrituración')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->suffixIcon(Heroicon::Calendar)
                                    ->required()
                                    ->maxDate(Carbon::today()->subYears(3))
                                    ->validationMessages([
                                        'max' => 'La propiedad debe tener una antigüedad mínima de 3 años.',
                                    ]),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('CUENTA BANCARIA')
                    ->description('Seleccione la cuenta donde se realizará el depósito')
                    ->schema([
                        \Filament\Forms\Components\Select::make('bank_account_id')
                            ->label('Cuenta Bancaria')
                            ->options(function (callable $get) {
                                $stateName = $get('estado_propiedad');
                                if (! $stateName) {
                                    return [];
                                }

                                return \App\Models\StateBankAccount::where('state_name', $stateName)
                                    ->whereHas('commissionRate', function ($query) {
                                        $query->where('is_active', true);
                                    })
                                    ->get()
                                    ->mapWithKeys(function ($account) {
                                        $label = "{$account->bank_name} - {$account->account_number}";
                                        if ($account->municipality) {
                                            $label .= " ({$account->municipality})";
                                        }

                                        return [$account->id => $label];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->helperText('Seleccione la cuenta bancaria correspondiente al estado y municipio de la propiedad.'),
                    ])
                    ->visible(fn (callable $get) => ! empty($get('estado_propiedad')))
                    ->collapsible(),
            ]);
    }
}
