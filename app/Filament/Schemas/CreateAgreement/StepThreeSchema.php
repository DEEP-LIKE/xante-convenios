<?php

namespace App\Filament\Schemas\CreateAgreement;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
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
                $page->saveStepData(3);
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
                                TextInput::make('estado_propiedad')
                                    ->label('Estado')
                                    ->maxLength(100)
                                    ->required(),
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
                                        'max' => 'La propiedad debe tener una antigüedad mínima de 3 años.'
                                    ]),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
