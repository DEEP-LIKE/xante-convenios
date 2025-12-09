<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuoteValidationResource\Pages;
use App\Models\QuoteValidation;
use App\Services\ValidationService;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;

class QuoteValidationResource extends Resource
{
    protected static ?string $model = QuoteValidation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static ?string $navigationLabel = 'Validaciones';
    
    protected static ?string $modelLabel = 'Validación';
    
    protected static ?string $pluralModelLabel = 'Validaciones';
    
    protected static \UnitEnum|string|null $navigationGroup = 'Cotizaciones';
    
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        // Solo mostrar badge a coordinadores FI y gerencia
        if (!auth()->check() || !in_array(auth()->user()->role, ['coordinador_fi', 'gerencia'])) {
            return null;
        }

        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make()
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('rejection_alert')
                            ->hiddenLabel()
                            ->content(fn ($record) => new \Illuminate\Support\HtmlString('
                                <div style="background-color: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; border: 1px solid #fca5a5;" role="alert">
                                    <div style="display: flex; align-items: center; margin-bottom: 0.25rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; margin-right: 0.5rem;">
                                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                        </svg>
                                        <span style="font-weight: bold; font-size: 1.1em;">Solicitud de Autorización Rechazada</span>
                                    </div>
                                    <div style="margin-left: 2rem;">
                                        <strong>Motivo:</strong> ' . ($record?->latestAuthorization?->rejection_reason ?? 'No especificado') . '
                                    </div>
                                </div>
                            '))
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record?->latestAuthorization?->status === 'rejected' && in_array($record->status, ['pending', 'rejected']))
                    ->columnSpanFull(),

                \Filament\Schemas\Components\Section::make()
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('status_alert')
                            ->hiddenLabel()
                            ->content(fn ($record) => new \Illuminate\Support\HtmlString('
                                <div style="background-color: #eff6ff; color: #1e40af; padding: 1rem; border-radius: 0.5rem; border: 1px solid #60a5fa;" role="alert">
                                    <div style="display: flex; align-items: center; margin-bottom: 0.25rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; margin-right: 0.5rem;">
                                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        <span style="font-weight: bold; font-size: 1.1em;">En Espera de Autorización</span>
                                    </div>
                                    Esta validación tiene cambios pendientes de aprobar por gerencia.
                                </div>
                            '))
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record?->status === 'awaiting_management_authorization')
                    ->columnSpanFull(),

                \Filament\Schemas\Components\Section::make('Información de la Validación')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('agreement_id')
                            ->label('ID del Convenio')
                            ->disabled(),
                        
                        \Filament\Forms\Components\TextInput::make('requested_by_display')
                            ->label('Solicitado por')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(fn ($component, $record) => $component->state($record?->requestedBy?->name ?? 'N/A')),
                        
                        \Filament\Forms\Components\TextInput::make('revision_number')
                            ->label('Número de Revisión')
                            ->disabled(),
                        
                        \Filament\Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendiente',
                                'approved' => 'Aprobada',
                                'rejected' => 'Rechazada',
                                'with_observations' => 'Con Observaciones',
                                'awaiting_management_authorization' => 'En Espera de Autorización',
                            ])
                            ->disabled(),
                    ]),

                \Filament\Schemas\Components\Section::make('Valor Principal del Convenio')
                    ->description('Campo principal que rige todos los cálculos financieros')
                    ->schema([
                        \Filament\Forms\Components\Hidden::make('original_valor_convenio')
                            ->default(fn ($record) => $record?->calculator_snapshot['valor_convenio'] ?? 0),
                        
                        \Filament\Forms\Components\Hidden::make('original_porcentaje_comision')
                            ->default(fn ($record) => $record?->calculator_snapshot['porcentaje_comision_sin_iva'] ?? 0),
                            
                        \Filament\Forms\Components\TextInput::make('calculator_snapshot.valor_convenio')
                            ->label('Valor Convenio')
                            ->prefix('$')
                            ->disabled(fn () => !auth()->check() || auth()->user()->role !== 'coordinador_fi')
                            ->formatStateUsing(fn ($state) => number_format((float) str_replace([',', '$'], '', $state), 2))
                            ->helperText('Ingrese el valor del convenio para activar todos los cálculos automáticos')
                            ->columnSpanFull()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Recalcular valores si es coordinador FI
                                if (auth()->check() && auth()->user()->role === 'coordinador_fi') {
                                    $calculatorService = app(\App\Services\AgreementCalculatorService::class);
                                    $snapshot = $get('calculator_snapshot');
                                    
                                    $recalculated = $calculatorService->calculateAllFinancials(
                                        (float) str_replace([',', '$'], '', $state),
                                        $snapshot
                                    );
                                    
                                    // Actualizar campos calculados
                                    foreach ($recalculated as $key => $value) {
                                        $set("calculator_snapshot.{$key}", $value);
                                    }
                                    
                                    // Asegurar actualización de comision IVA incluido
                                    $set('calculator_snapshot.comision_iva_incluido', $recalculated['parametros_utilizados']['porcentaje_comision_iva_incluido'] ?? 0);
                                }
                            }),
                    ]),

                \Filament\Schemas\Components\Section::make('Parámetros de Cálculo')
                    ->description('Configuración de porcentajes y valores base')
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(3)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.porcentaje_comision_sin_iva')
                                    ->label('% Comisión (Sin IVA)')
                                    ->suffix('%')
                                    ->disabled(fn () => !auth()->check() || auth()->user()->role !== 'coordinador_fi')
                                    ->helperText('Valor variable desde configuración')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Recalcular si cambia la comisión
                                        if (auth()->check() && auth()->user()->role === 'coordinador_fi') {
                                            $calculatorService = app(\App\Services\AgreementCalculatorService::class);
                                            $snapshot = $get('calculator_snapshot');
                                            $snapshot['porcentaje_comision_sin_iva'] = $state;
                                            
                                            $recalculated = $calculatorService->calculateAllFinancials(
                                                (float) str_replace([',', '$'], '', $snapshot['valor_convenio'] ?? 0),
                                                $snapshot
                                            );
                                            
                                            // Actualizar campos calculados
                                            foreach ($recalculated as $key => $value) {
                                                $set("calculator_snapshot.{$key}", $value);
                                            }
                                            
                                            // Asegurar actualización de comision IVA incluido
                                            $set('calculator_snapshot.comision_iva_incluido', $recalculated['parametros_utilizados']['porcentaje_comision_iva_incluido'] ?? 0);
                                        }
                                    }),
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.comision_iva_incluido')
                                    ->label('Comisión IVA incluido')
                                    ->suffix('%')
                                    ->disabled()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state) => number_format((float) str_replace([',', '$'], '', $state), 2))
                                    ->helperText('Comisión sin IVA × (1 + % IVA)'),
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.multiplicador_estado')
                                    ->label('% Multiplicador por estado')
                                    ->suffix('%')
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText(fn ($record) => ($state = $record?->agreement?->wizard_data['estado_propiedad'] ?? $record?->agreement?->wizard_data['property_state'] ?? $record?->agreement?->wizard_data['estado'] ?? $record?->agreement?->wizard_data['state'] ?? $record?->calculator_snapshot['estado_propiedad'] ?? null) ? '% de comisión por estado: ' . $state : null),
                            ]),
                        
                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.monto_credito')
                                    ->label('Monto de Crédito')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state) => number_format((float) str_replace([',', '$'], '', $state), 2))
                                    ->helperText('Valor editable - precargado desde configuración'),
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.tipo_credito')
                                    ->label('Tipo de Crédito')
                                    ->disabled()
                                    ->dehydrated(),
                            ]),
                    ]),

                \Filament\Schemas\Components\Section::make('Valores Calculados')
                    ->description('Estos valores se calculan automáticamente al ingresar el Valor Convenio')
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.valor_compraventa')
                                    ->label('Valor CompraVenta')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state) => number_format((float) str_replace([',', '$'], '', $state), 2))
                                    ->helperText('Espejo del Valor Convenio'),
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.precio_promocion')
                                    ->label('Precio Promoción')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state) => number_format((float) str_replace([',', '$'], '', $state), 2))
                                    ->helperText('Valor Convenio × % Multiplicador por estado'),
                            ]),
                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.monto_comision_sin_iva')
                                    ->label('Monto Comisión (Sin IVA)')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state) => number_format((float) str_replace([',', '$'], '', $state), 2))
                                    ->helperText('Valor Convenio × % Comisión'),
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.comision_total')
                                    ->label('Comisión Total a Pagar')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state) => number_format((float) str_replace([',', '$'], '', $state), 2))
                                    ->helperText('Monto Comisión (Sin IVA) + IVA'),
                            ]),
                    ]),

                \Filament\Schemas\Components\Section::make('Costos de Operación')
                    ->description('Campos editables para gastos adicionales')
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(3)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.isr')
                                    ->label('ISR')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state) => number_format((float) str_replace([',', '$'], '', $state), 2)),
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.cancelacion_hipoteca')
                                    ->label('Cancelación de Hipoteca')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state) => number_format((float) str_replace([',', '$'], '', $state), 2)),
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.total_gastos_fi')
                                    ->label('Total Gastos FI (Venta)')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state) => number_format((float) str_replace([',', '$'], '', $state), 2))
                                    ->helperText('ISR + Cancelación de Hipoteca'),
                            ]),
                        \Filament\Forms\Components\TextInput::make('calculator_snapshot.ganancia_final')
                            ->label('Ganancia Final (Est.)')
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated()
                            ->formatStateUsing(fn ($state) => number_format((float) str_replace([',', '$'], '', $state), 2))
                            ->helperText('Valor CompraVenta - ISR - Cancelación - Comisión Total - Monto Crédito')
                            ->columnSpanFull(),
                    ]),
                
                \Filament\Schemas\Components\Section::make('Observaciones')
                    ->schema([
                        \Filament\Forms\Components\Textarea::make('observations')
                            ->label('Observaciones del Coordinador')
                            ->rows(4)
                            ->disabled()
                            ->visible(fn ($record) => $record?->observations !== null),
                    ])
                    ->visible(fn ($record) => $record?->observations !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (QuoteValidation $record): string => 
                auth()->user()->can('update', $record) 
                    ? Pages\EditQuoteValidation::getUrl(['record' => $record]) 
                    : Pages\ViewQuoteValidation::getUrl(['record' => $record])
            )
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                
                \Filament\Tables\Columns\TextColumn::make('agreement_id')
                    ->label('Convenio')
                    ->sortable()
                    ->searchable(),
                
                \Filament\Tables\Columns\TextColumn::make('requestedBy.name')
                    ->label('Ejecutivo')
                    ->searchable()
                    ->sortable(),
                
                \Filament\Tables\Columns\TextColumn::make('revision_number')
                    ->label('Revisión')
                    ->badge()
                    ->color('info'),
                
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'with_observations' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        'with_observations' => 'Con Observaciones',
                        'awaiting_management_authorization' => 'En Espera de Autorización',
                        default => $state,
                    }),
                
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Solicitud')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                
                \Filament\Tables\Columns\TextColumn::make('validated_at')
                    ->label('Fecha Validación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        'with_observations' => 'Con Observaciones',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar Validación')
                    ->modalDescription('¿Está seguro de que desea aprobar esta validación?')
                    ->visible(fn (QuoteValidation $record): bool => 
                        $record->isPending() && 
                        auth()->user()->can('approve', $record) &&
                        !$record->hasValueChanges(
                            (float) ($record->calculator_snapshot['valor_convenio'] ?? 0),
                            (float) ($record->calculator_snapshot['porcentaje_comision_sin_iva'] ?? 0)
                        ))
                    ->action(function (QuoteValidation $record) {
                        app(ValidationService::class)->approveValidation($record, auth()->user());
                        
                        Notification::make()
                            ->title('Validación Aprobada')
                            ->success()
                            ->send();
                    }),

                Action::make('approve_with_changes')
                    ->label('Solicitar Autorización')
                    ->icon('heroicon-o-shield-check')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Solicitar Autorización de Cambios')
                    ->modalDescription(function (QuoteValidation $record) {
                        $snapshot = $record->calculator_snapshot;
                        $oldPrice = number_format((float) ($snapshot['valor_convenio'] ?? 0), 2);
                        $oldCommission = number_format((float) ($snapshot['porcentaje_comision_sin_iva'] ?? 0), 2);
                        
                        return "Se han detectado cambios en los valores originales.\n\n" .
                               "Estos cambios requieren autorización de gerencia antes de proceder.\n\n" .
                               "¿Desea enviar una solicitud de autorización?";
                    })
                    ->visible(fn (QuoteValidation $record): bool => 
                        $record->isPending() && 
                        auth()->user()->role === 'coordinador_fi' &&
                        $record->hasValueChanges(
                            (float) ($record->calculator_snapshot['valor_convenio'] ?? 0),
                            (float) ($record->calculator_snapshot['porcentaje_comision_sin_iva'] ?? 0)
                        ))
                    ->action(function (QuoteValidation $record) {
                        $snapshot = $record->calculator_snapshot;
                        $newPrice = (float) ($snapshot['valor_convenio'] ?? 0);
                        $newCommission = (float) ($snapshot['porcentaje_comision_sin_iva'] ?? 0);
                        
                        $record->requestAuthorization(
                            auth()->id(),
                            $newPrice,
                            $newCommission
                        );
                        
                        Notification::make()
                            ->title('Autorización Solicitada')
                            ->body('Se ha enviado la solicitud a gerencia.')
                            ->success()
                            ->send();
                    }),

                
                Action::make('request_changes')
                    ->label('Solicitar Cambios')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('observations')
                            ->label('Observaciones')
                            ->required()
                            ->rows(5)
                            ->placeholder('Describe los cambios que necesitas que realice el ejecutivo...'),
                    ])
                    ->visible(fn (QuoteValidation $record): bool => 
                        $record->isPending() && auth()->user()->can('requestChanges', $record))
                    ->action(function (QuoteValidation $record, array $data) {
                        app(ValidationService::class)->requestChanges(
                            $record, 
                            auth()->user(), 
                            $data['observations']
                        );
                        
                        Notification::make()
                            ->title('Observaciones Enviadas')
                            ->success()
                            ->send();
                    }),
                
                Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Motivo del Rechazo')
                            ->required()
                            ->rows(4),
                    ])
                    ->visible(fn (QuoteValidation $record): bool => 
                        $record->isPending() && auth()->user()->can('reject', $record))
                    ->action(function (QuoteValidation $record, array $data) {
                        app(ValidationService::class)->rejectValidation(
                            $record, 
                            auth()->user(), 
                            $data['reason']
                        );
                        
                        Notification::make()
                            ->title('Validación Rechazada')
                            ->danger()
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuoteValidations::route('/'),
            'view' => Pages\ViewQuoteValidation::route('/{record}'),
            'edit' => Pages\EditQuoteValidation::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Las validaciones se crean desde el wizard
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Visible solo para coordinadores FI y gerencia
        return auth()->check() && in_array(auth()->user()->role, ['coordinador_fi', 'gerencia']);
    }
}
