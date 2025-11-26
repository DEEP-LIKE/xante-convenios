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
// use Filament\Tables\Filters\SelectFilter; // <-- Importación eliminada por filtro de búsqueda global

class ConfigurationCalculatorResource extends Resource

{
    protected static ?string $model = ConfigurationCalculator::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-calculator';

    protected static UnitEnum | string | null $navigationGroup = 'Configuraciones';

    protected static ?string $navigationLabel = 'Calculadora';
    
    protected static ?string $modelLabel = '% Calculadora';
    
    protected static ?string $pluralModelLabel = 'Valores de la Calculadora';

    protected static ?int $navigationSort = 999;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de la Configuración')
                    ->schema([
                        TextInput::make('key')
                            ->label('Clave')
                            ->required()
                            ->disabled(), // Clave no debería ser editable
                        
                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(2)
                            ->columnSpanFull(),
                        
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
                // TextColumn::make('group')->label('Grupo')->badge(), // <-- Mostrar el grupo como "badge"
                TextColumn::make('description')
                    ->label('Descripción')
                    ->sortable()
                    ->searchable(), // <-- Ahora la descripción es searchable
                TextColumn::make('value')
                    ->sortable()
                    ->label('Valor'),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->sortable()
                    ->searchable() // <-- Ahora el nombre es searchable
                    ->dateTime(),
            ])
            ->filters([
                // Dejamos este arreglo vacío para evitar el botón de filtros y usar solo el buscador global.
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
            // 'create' => Pages\CreateConfigurationCalculator::route('/create'),
            'edit' => Pages\EditConfigurationCalculator::route('/{record}/edit'),
        ];
    }
}
