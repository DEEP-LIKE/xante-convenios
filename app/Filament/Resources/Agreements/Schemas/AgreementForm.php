<?php

namespace App\Filament\Resources\Agreements\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AgreementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Convenio')
                    ->schema([
                        Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                \Filament\Forms\Components\TextInput::make('name')
                                    ->label('Nombre Completo')
                                    ->required(),
                                \Filament\Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required(),
                                \Filament\Forms\Components\TextInput::make('phone')
                                    ->label('Teléfono')
                                    ->required(),
                            ]),
                        Select::make('property_id')
                            ->label('Propiedad')
                            ->relationship('property', 'address')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                \Filament\Forms\Components\Textarea::make('address')
                                    ->label('Dirección')
                                    ->required(),
                                \Filament\Forms\Components\TextInput::make('community')
                                    ->label('Comunidad')
                                    ->required(),
                                \Filament\Forms\Components\TextInput::make('value')
                                    ->label('Valor')
                                    ->numeric()
                                    ->required(),
                            ]),
                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'iniciado' => 'Iniciado',
                                'pendiente_docs' => 'Pendiente Documentos',
                                'completado' => 'Completado',
                            ])
                            ->default('iniciado')
                            ->required(),
                    ])->columns(2),
            ]);
    }
}
