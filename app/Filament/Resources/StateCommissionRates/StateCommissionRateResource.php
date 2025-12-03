<?php

namespace App\Filament\Resources\StateCommissionRates;

use App\Filament\Resources\StateCommissionRates\Pages\CreateStateCommissionRate;
use App\Filament\Resources\StateCommissionRates\Pages\EditStateCommissionRate;
use App\Filament\Resources\StateCommissionRates\Pages\ListStateCommissionRates;
use App\Filament\Resources\StateCommissionRates\Schemas\StateCommissionRateForm;
use App\Filament\Resources\StateCommissionRates\Tables\StateCommissionRatesTable;
use App\Models\StateCommissionRate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StateCommissionRateResource extends Resource
{
    protected static ?string $model = StateCommissionRate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    
    protected static \UnitEnum|string|null $navigationGroup = 'Configuraciones';
    
    protected static ?string $navigationLabel = 'Gastos notariales (GE)';
    
    protected static ?string $modelLabel = 'Gasto notarial (GE)';
    
    protected static ?string $pluralModelLabel = 'Gastos notariales (GE)';
    
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'state_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make()
                    ->schema([
                        \Filament\Forms\Components\Select::make('state_name')
                            ->label('Nombre del Estado')
                            ->options([
                                'Aguascalientes' => 'Aguascalientes',
                                'Baja California' => 'Baja California',
                                'Baja California Sur' => 'Baja California Sur',
                                'Campeche' => 'Campeche',
                                'Chiapas' => 'Chiapas',
                                'Chihuahua' => 'Chihuahua',
                                'Ciudad de México' => 'Ciudad de México',
                                'Coahuila' => 'Coahuila',
                                'Colima' => 'Colima',
                                'Durango' => 'Durango',
                                'Guanajuato' => 'Guanajuato',
                                'Guerrero' => 'Guerrero',
                                'Hidalgo' => 'Hidalgo',
                                'Jalisco' => 'Jalisco',
                                'Estado de México' => 'Estado de México',
                                'Michoacán' => 'Michoacán',
                                'Morelos' => 'Morelos',
                                'Nayarit' => 'Nayarit',
                                'Nuevo León' => 'Nuevo León',
                                'Oaxaca' => 'Oaxaca',
                                'Puebla' => 'Puebla',
                                'Querétaro' => 'Querétaro',
                                'Quintana Roo' => 'Quintana Roo',
                                'San Luis Potosí' => 'San Luis Potosí',
                                'Sinaloa' => 'Sinaloa',
                                'Sonora' => 'Sonora',
                                'Tabasco' => 'Tabasco',
                                'Tamaulipas' => 'Tamaulipas',
                                'Tlaxcala' => 'Tlaxcala',
                                'Veracruz' => 'Veracruz',
                                'Yucatán' => 'Yucatán',
                                'Zacatecas' => 'Zacatecas',
                            ])
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $codes = [
                                    'Aguascalientes' => 'AGS', 'Baja California' => 'BC', 'Baja California Sur' => 'BCS',
                                    'Campeche' => 'CAM', 'Chiapas' => 'CHIS', 'Chihuahua' => 'CHIH',
                                    'Ciudad de México' => 'CDMX', 'Coahuila' => 'COAH', 'Colima' => 'COL',
                                    'Durango' => 'DGO', 'Guanajuato' => 'GTO', 'Guerrero' => 'GRO',
                                    'Hidalgo' => 'HGO', 'Jalisco' => 'JAL', 'Estado de México' => 'MEX',
                                    'Michoacán' => 'MICH', 'Morelos' => 'MOR', 'Nayarit' => 'NAY',
                                    'Nuevo León' => 'NL', 'Oaxaca' => 'OAX', 'Puebla' => 'PUE',
                                    'Querétaro' => 'QRO', 'Quintana Roo' => 'QROO', 'San Luis Potosí' => 'SLP',
                                    'Sinaloa' => 'SIN', 'Sonora' => 'SON', 'Tabasco' => 'TAB',
                                    'Tamaulipas' => 'TAM', 'Tlaxcala' => 'TLAX', 'Veracruz' => 'VER',
                                    'Yucatán' => 'YUC', 'Zacatecas' => 'ZAC',
                                ];
                                $set('state_code', $codes[$state] ?? null);
                            }),
                        \Filament\Forms\Components\TextInput::make('state_code')
                            ->label('Código del Estado (Abreviatura)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(10)
                            ->disabled()
                            ->dehydrated(),
                        \Filament\Forms\Components\TextInput::make('commission_percentage')
                            ->label('Porcentaje GE')
                            ->numeric()
                            ->suffix('%')
                            ->required()
                            ->step(0.01),
                        \Filament\Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('is_active', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->orderBy('is_active', 'desc')->orderBy('commission_percentage', 'desc'))
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('state_name')
                    ->label('Estado')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('state_code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('commission_percentage')
                    ->label('GE %')
                    ->suffix('%')
                    ->sortable(),
                \Filament\Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                \Filament\Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\Action::make('edit')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record): string => StateCommissionRateResource::getUrl('edit', ['record' => $record])),
                \Filament\Actions\Action::make('delete')
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->delete()),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => ListStateCommissionRates::route('/'),
            'create' => CreateStateCommissionRate::route('/create'),
            'edit' => EditStateCommissionRate::route('/{record}/edit'),
        ];
    }
}
