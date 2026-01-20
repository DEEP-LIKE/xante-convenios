<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinalPriceAuthorizationResource\Pages;
use App\Models\FinalPriceAuthorization;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class FinalPriceAuthorizationResource extends Resource
{
    protected static ?string $model = FinalPriceAuthorization::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Autorizaciones Precio Final';

    protected static ?string $modelLabel = 'Autorización Precio Final';

    protected static ?string $pluralModelLabel = 'Autorizaciones Precio Final';

    protected static UnitEnum|string|null $navigationGroup = 'Cotizaciones';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        // Solo visible para administradores
        return auth()->check() && auth()->user()->role === 'gerencia';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make('Información del Convenio')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('agreement_id')
                            ->label('ID Convenio')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('agreement.client.name')
                            ->label('Cliente')
                            ->disabled(),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Solicitud')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('final_price')
                            ->label('Precio Final Solicitado')
                            ->prefix('$')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => number_format($state, 2)),
                        \Filament\Forms\Components\Textarea::make('justification')
                            ->label('Justificación')
                            ->disabled()
                            ->rows(4),
                        \Filament\Forms\Components\TextInput::make('requester.name')
                            ->label('Solicitado por')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('agreement_id')
                    ->label('Convenio')
                    ->sortable()
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('agreement.client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('requester.name')
                    ->label('Solicitado por')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('previous_price')
                    ->label('Precio Anterior')
                    ->state(fn (FinalPriceAuthorization $record): ?string => $record->agreement->wizard_data['valor_compraventa']
                        ?? $record->agreement->wizard_data['valor_convenio']
                        ?? null
                    )
                    ->money('MXN')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('final_price')
                    ->label('Precio Final')
                    ->money('MXN')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                    }),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Solicitud')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar Precio Final')
                    ->modalDescription('¿Está seguro de que desea aprobar este precio final?')
                    ->visible(fn (FinalPriceAuthorization $record): bool => $record->status === 'pending' && auth()->user()->can('approve', $record))
                    ->action(function (FinalPriceAuthorization $record) {
                        $record->approve(auth()->user());

                        // Notificar al ejecutivo
                        $record->requester->notify(
                            new \App\Notifications\FinalPriceAuthorizationApprovedNotification($record->id)
                        );

                        // Sincronizar precio actualizado con HubSpot (precio_comercial)
                        if ($record->agreement->client && $record->agreement->client->hubspot_deal_id) {
                            try {
                                $hubspotService = app(\App\Services\HubspotSyncService::class);
                                $hubspotService->updateHubspotDeal($record->agreement->client->hubspot_deal_id, [
                                    'precio_comercial' => $record->final_price,
                                ]);
                                \Log::info('Precio comercial actualizado en HubSpot tras aprobación', [
                                    'deal_id' => $record->agreement->client->hubspot_deal_id,
                                    'price' => $record->final_price,
                                ]);
                            } catch (\Exception $e) {
                                \Log::error('Error actualizando precio comercial en HubSpot', ['error' => $e->getMessage()]);
                            }
                        }

                        Notification::make()
                            ->title('Precio Final Aprobado')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rechazar Precio Final')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motivo del Rechazo')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn (FinalPriceAuthorization $record): bool => $record->status === 'pending' && auth()->user()->can('reject', $record))
                    ->action(function (FinalPriceAuthorization $record, array $data) {
                        $record->reject(auth()->user(), $data['rejection_reason']);

                        // Notificar al ejecutivo
                        $record->requester->notify(
                            new \App\Notifications\FinalPriceAuthorizationRejectedNotification($record->id)
                        );

                        Notification::make()
                            ->title('Precio Final Rechazado')
                            ->danger()
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinalPriceAuthorizations::route('/'),
        ];
    }
}
