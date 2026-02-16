<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuoteAuthorizationResource\Pages;
use App\Models\QuoteAuthorization;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuoteAuthorizationResource extends Resource
{
    protected static ?string $model = QuoteAuthorization::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Autorizaciones';

    protected static ?string $modelLabel = 'Autorización';

    protected static ?string $pluralModelLabel = 'Autorizaciones';

    protected static \UnitEnum|string|null $navigationGroup = 'Cotizaciones';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make('Información de la Solicitud')
                    ->schema([
                        \Filament\Forms\Components\Select::make('change_type')
                            ->label('Tipo de Cambio')
                            ->options([
                                'commission' => 'Comisión',
                                'price' => 'Precio',
                                'both' => 'Ambos',
                            ])
                            ->required()
                            ->disabled(fn ($record) => $record !== null),

                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('old_commission_percentage')
                                    ->label('% Comisión Anterior')
                                    ->numeric()
                                    ->suffix('%')
                                    ->disabled(),
                                \Filament\Forms\Components\TextInput::make('new_commission_percentage')
                                    ->label('% Comisión Nueva')
                                    ->numeric()
                                    ->suffix('%'),
                            ]),

                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('old_price')
                                    ->label('Valor Convenio Anterior')
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled(),
                                \Filament\Forms\Components\TextInput::make('new_price')
                                    ->label('Valor Convenio Nuevo')
                                    ->numeric()
                                    ->prefix('$'),
                            ]),

                        \Filament\Forms\Components\Textarea::make('discount_reason')
                            ->label('Motivo del Descuento')
                            ->rows(3),
                    ])
                    ->columnSpanFull(),

                \Filament\Schemas\Components\Group::make()
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('agreement_summary')
                            ->hiddenLabel()
                            ->content(function ($record) {
                                if (! $record || ! $record->quoteValidation || ! $record->quoteValidation->agreement) {
                                    return '';
                                }

                                $data = $record->quoteValidation->agreement->wizard_data;
                                $renderer = app(\App\Services\WizardSummaryRenderer::class);

                                return new \Illuminate\Support\HtmlString(
                                    $renderer->renderPropertySummary($data).
                                    '<div class="mt-4"></div>'.
                                    $renderer->renderHolderSummary($data)
                                );
                            })
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                // \Filament\Schemas\Components\Section::make('Estado')
                //     ->schema([
                //         \Filament\Forms\Components\Select::make('status')
                //             ->label('Estado')
                //             ->options([
                //                 'pending' => 'Pendiente',
                //                 'approved' => 'Aprobada',
                //                 'rejected' => 'Rechazada',
                //             ])
                //             ->disabled(),

                //         \Filament\Forms\Components\Textarea::make('rejection_reason')
                //             ->label('Motivo de Rechazo')
                //             ->rows(3)
                //             ->visible(fn ($record) => $record?->status === 'rejected'),
                //     ])
                //     ->visible(fn ($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('quoteValidation.agreement_id')
                    ->label('Convenio')
                    ->sortable()
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('quoteValidation.status')
                    ->label('Estado Validación')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'with_observations' => 'info',
                        'awaiting_management_authorization' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        'with_observations' => 'Con Observaciones',
                        'awaiting_management_authorization' => 'Esperando Autorización',
                        default => $state,
                    })
                    ->toggleable(),

                \Filament\Tables\Columns\TextColumn::make('requestedBy.name')
                    ->label('Solicitado por')
                    ->searchable()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('change_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'commission' => 'info',
                        'price' => 'warning',
                        'both' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'commission' => 'Comisión',
                        'price' => 'Precio',
                        'both' => 'Ambos',
                        default => $state,
                    }),

                \Filament\Tables\Columns\TextColumn::make('new_commission_percentage')
                    ->label('Nueva Comisión')
                    ->suffix('%')
                    ->toggleable(),

                \Filament\Tables\Columns\TextColumn::make('new_price')
                    ->label('Nuevo Valor Convenio')
                    ->money('MXN')
                    ->toggleable(),

                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        default => $state,
                    }),

                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                    ]),

                \Filament\Tables\Filters\SelectFilter::make('change_type')
                    ->label('Tipo de Cambio')
                    ->options([
                        'commission' => 'Comisión',
                        'price' => 'Precio',
                        'both' => 'Ambos',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar Solicitud')
                    ->modalDescription('¿Está seguro de que desea aprobar esta solicitud?')
                    ->visible(fn (QuoteAuthorization $record): bool => $record->isPending() && auth()->user()->can('approve', $record))
                    ->action(function (QuoteAuthorization $record) {
                        $record->approve(auth()->id());

                        Notification::make()
                            ->title('Solicitud Aprobada')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rechazar Solicitud')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motivo del Rechazo')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn (QuoteAuthorization $record): bool => $record->isPending() && auth()->user()->can('reject', $record))
                    ->action(function (QuoteAuthorization $record, array $data) {
                        $record->reject(auth()->id(), $data['rejection_reason']);

                        Notification::make()
                            ->title('Solicitud Rechazada')
                            ->danger()
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();

                if ($user->role === 'ejecutivo') {
                    $query->where('requested_by', $user->id);
                }

                return $query;
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuoteAuthorizations::route('/'),
            'view' => Pages\ViewQuoteAuthorization::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
