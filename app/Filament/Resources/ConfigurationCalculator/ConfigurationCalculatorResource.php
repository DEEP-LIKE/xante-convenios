<?php

namespace App\Filament\Resources\ConfigurationCalculator;

use App\Filament\Resources\ConfigurationCalculator\Pages;
use App\Models\ConfigurationCalculator;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;


class ConfigurationCalculatorResource extends Resource

{
    protected static ?string $model = ConfigurationCalculator::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-calculator';

    protected static UnitEnum | string | null $navigationGroup = 'Configuraciones';

    protected static ?string $navigationLabel = '% Calculadora';
    
    protected static ?string $modelLabel = '% Calculadora';
    
    protected static ?string $pluralModelLabel = '% Calculadora';

    protected static ?int $navigationSort = 999;


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de la Configuración')
                    ->schema([
                        TextInput::make('key')
                            ->label('Clave')
                            ->required(),
                        
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required(),
                        
                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(2),
                        
                        Select::make('group')
                            ->label('Grupo')
                            ->options([
                                'comisiones' => 'Comisiones',
                                'gastos' => 'Gastos',
                                'creditos' => 'Créditos',
                                'general' => 'General',
                            ])
                            ->required(),
                        
                        Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'text' => 'Texto',
                                'number' => 'Número Entero',
                                'decimal' => 'Número Decimal',
                                'boolean' => 'Verdadero/Falso',
                            ])
                            ->required(),
                        
                        TextInput::make('value')
                            ->label('Valor')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group')->label('Grupo'),
                TextColumn::make('name')->label('Nombre'),
                TextColumn::make('key')->label('Clave'),
                TextColumn::make('value')->label('Valor'),
                TextColumn::make('updated_at')->label('Actualizado')->dateTime(),
            ])
            ->recordActions([
                Action::make('edit')
                    ->url(fn (ConfigurationCalculator $record): string => static::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-o-pencil'),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConfigurationCalculator::route('/'),
            'create' => Pages\CreateConfigurationCalculator::route('/create'),
            'edit' => Pages\EditConfigurationCalculator::route('/{record}/edit'),
        ];
    }
}