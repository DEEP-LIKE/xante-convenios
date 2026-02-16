<?php

namespace App\Filament\Resources\ConfigurationCalculator;

use App\Models\ConfigurationCalculator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

// use Filament\Tables\Filters\SelectFilter; // <-- Importación eliminada por filtro de búsqueda global

class ConfigurationCalculatorResource extends Resource
{
    protected static ?string $model = ConfigurationCalculator::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static UnitEnum|string|null $navigationGroup = 'Configuraciones';

    protected static ?string $navigationLabel = 'Calculadora';

    protected static ?string $modelLabel = '% Calculadora';

    protected static ?string $pluralModelLabel = 'Valores de la Calculadora';

    protected static ?int $navigationSort = 999;

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->user()?->role, ['gerencia']);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('key', [
                'comision_sin_iva_default', 
                'iva_valor',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de la Configuración')
                    ->schema([
                        TextInput::make('description')
                            ->label('Descripción')
                            ->required()
                            ->disabled(), // Descripción no editable para evitar cambios accidentales en la lógica

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
                TextColumn::make('description')
                    ->label('Descripción')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('value')
                    ->sortable()
                    ->label('Valor'),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->sortable()
                    ->dateTime(),
            ])
            ->filters([
                //
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
            'edit' => Pages\EditConfigurationCalculator::route('/{record}/edit'),
        ];
    }
}
