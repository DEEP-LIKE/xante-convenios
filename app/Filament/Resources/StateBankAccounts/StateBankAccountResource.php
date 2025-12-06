<?php

namespace App\Filament\Resources\StateBankAccounts;

use App\Filament\Resources\StateBankAccounts\Pages\CreateStateBankAccount;
use App\Filament\Resources\StateBankAccounts\Pages\EditStateBankAccount;
use App\Filament\Resources\StateBankAccounts\Pages\ListStateBankAccounts;
use App\Filament\Resources\StateBankAccounts\Schemas\StateBankAccountForm;
use App\Filament\Resources\StateBankAccounts\Tables\StateBankAccountsTable;
use App\Models\StateBankAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StateBankAccountResource extends Resource
{
    protected static ?string $model = StateBankAccount::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-library';
    
    protected static \UnitEnum|string|null $navigationGroup = 'Configuraciones';
    
    protected static ?string $navigationLabel = 'Cuentas Bancarias por Estado';
    
    protected static ?string $modelLabel = 'Cuenta Bancaria por Estado';
    
    protected static ?string $pluralModelLabel = 'Cuentas Bancarias por Estado';
    
    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'state_name';

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->user()?->role, ['gerencia', 'coordinador_fi']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make()
                    ->schema([
                        \Filament\Forms\Components\Select::make('state_name')
                            ->label('Nombre del Estado')
                            ->options(\App\Models\StateCommissionRate::where('is_active', true)->pluck('state_name', 'state_name'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $codes = [
                                    'Aguascalientes' => 'AGS', 'Baja California' => 'BC', 'Baja California Sur' => 'BCS',
                                    'Campeche' => 'CAM', 'Chiapas' => 'CHIS', 'Chihuahua' => 'CHIH',
                                    'Ciudad de México' => 'CDMX', 'Coahuila' => 'COAH', 'Colima' => 'COL',
                                    'Durango' => 'DGO', 'Guanajuato' => 'GTO', 'Guerrero' => 'GRO',
                                    'Hidalgo' => 'HGO', 'Jalisco' => 'JAL', 'México' => 'MEX',
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
                            // ->disabled()
                            ->dehydrated(),
                        \Filament\Forms\Components\TextInput::make('municipality')
                            ->label('Municipio (Opcional)')
                            ->maxLength(100)
                            ->helperText('Ej: Pachuca, Tula. Dejar vacío si no aplica.'),
                        \Filament\Forms\Components\TextInput::make('account_holder')
                            ->label('Nombre del Titular')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('bank_name')
                            ->label('Nombre del Banco')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('account_number')
                            ->label('Número de Cuenta')
                            ->required()
                            ->maxLength(255)
                            ->regex('/^[0-9]+$/')
                            ->inputMode('numeric'),
                        \Filament\Forms\Components\TextInput::make('clabe')
                            ->label('CLABE')
                            ->required()
                            ->length(18)
                            ->regex('/^[0-9]+$/')
                            ->inputMode('numeric'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query
                ->join('state_commission_rates', function ($join) {
                    $join->on('state_bank_accounts.state_code', '=', 'state_commission_rates.state_code')
                        ->where(function ($query) {
                            $query->whereColumn('state_bank_accounts.municipality', 'state_commission_rates.municipality')
                                ->orWhereNull('state_commission_rates.municipality');
                        });
                })
                ->orderBy('state_commission_rates.is_active', 'desc')
                ->orderBy('state_commission_rates.commission_percentage', 'desc')
                ->select('state_bank_accounts.*')
            )
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('state_name')
                    ->label('Estado')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('bank_name')
                    ->label('Banco')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('account_number')
                    ->label('Cuenta')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('clabe')
                    ->label('CLABE')
                    ->searchable(),
                \Filament\Tables\Columns\IconColumn::make('commissionRate.is_active')
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
                    ->url(fn ($record): string => StateBankAccountResource::getUrl('edit', ['record' => $record])),
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
            'index' => ListStateBankAccounts::route('/'),
            'create' => CreateStateBankAccount::route('/create'),
            'edit' => EditStateBankAccount::route('/{record}/edit'),
        ];
    }
}
