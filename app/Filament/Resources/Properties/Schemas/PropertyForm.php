<?php

namespace App\Filament\Resources\Properties\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PropertyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de la Propiedad')
                    ->schema([
                        Textarea::make('address')
                            ->label('Dirección Completa')
                            ->required()
                            ->rows(3),
                        TextInput::make('community')
                            ->label('Comunidad/Fraccionamiento')
                            ->required()
                            ->maxLength(255),
                        Select::make('property_type')
                            ->label('Tipo de Propiedad')
                            ->required()
                            ->options([
                                'casa' => 'Casa',
                                'departamento' => 'Departamento',
                                'condominio' => 'Condominio',
                                'terreno' => 'Terreno',
                                'local_comercial' => 'Local Comercial',
                                'oficina' => 'Oficina',
                                'bodega' => 'Bodega',
                                'otro' => 'Otro',
                            ]),
                    ])->columns(2),

                Section::make('Información Financiera')
                    ->schema([
                        TextInput::make('value')
                            ->label('Valor de la Propiedad')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0),
                        TextInput::make('mortgage_amount')
                            ->label('Monto de Hipoteca')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0)
                            ->helperText('Dejar vacío si no aplica'),
                    ])->columns(2),
            ]);
    }
}
