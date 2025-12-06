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
    
    protected static ?int $navigationSort = 3;

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
                                    
                                    $recalculated = $calculatorService->calculateFromConvenioValue(
                                        (float) str_replace([',', '$'], '', $state),
                                        $snapshot['estado_propiedad'] ?? 'CDMX',
                                        $snapshot['monto_credito'] ?? 0,
                                        $snapshot['tipo_credito'] ?? 'ninguno',
                                        (float) ($snapshot['isr'] ?? 0),
                                        (float) ($snapshot['cancelacion_hipoteca'] ?? 0)
                                    );
                                    
                                    // Actualizar campos calculados
                                    foreach ($recalculated as $key => $value) {
                                        $set("calculator_snapshot.{$key}", $value);
                                    }
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
                                            $valConvenio = (float) str_replace([',', '$'], '', $get('calculator_snapshot.valor_convenio'));
                                            $comision = (float) $state;
                                            
                                            $montoComision = $valConvenio * ($comision / 100);
                                            $iva = $montoComision * 0.16;
                                            $total = $montoComision + $iva;
                                            
                                            $set('calculator_snapshot.monto_comision_sin_iva', $montoComision);
                                            $set('calculator_snapshot.comision_total', $total);
                                            
                                            // Recalcular ganancia final
                                            $isr = (float) str_replace([',', '$'], '', $get('calculator_snapshot.isr') ?? 0);
                                            $cancelacion = (float) str_replace([',', '$'], '', $get('calculator_snapshot.cancelacion_hipoteca') ?? 0);
                                            $credito = (float) str_replace([',', '$'], '', $get('calculator_snapshot.monto_credito') ?? 0);
                                            
                                            $ganancia = $valConvenio - $isr - $cancelacion - $total - $credito;
                                            $set('calculator_snapshot.ganancia_final', $ganancia);
                                        }
                                    }),
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.comision_iva_incluido')
                                    ->label('Comisión IVA incluido')
                                    ->suffix('%')
                                    ->disabled()
                                    ->formatStateUsing(fn ($state) => number_format((float) str_replace([',', '$'], '', $state), 2))
                                    ->helperText('Comisión sin IVA × (1 + % IVA)'),
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.multiplicador_estado')
                                    ->label('% Multiplicador por estado')
                                    ->suffix('%')
                                    ->disabled()
                                    ->helperText(fn ($record) => '% de comisión por estado: ' . ($record?->calculator_snapshot['estado_propiedad'] ?? 'Desconocido')),
                            ]),
                        
                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.monto_credito')
                                    ->label('Monto de Crédito')
                                    ->prefix('$')
                                    ->disabled()
                                    ->formatStateUsing(fn ($state) => number_format((float) str_replace([',', '$'], '', $state), 2))
                                    ->helperText('Valor editable - precargado desde configuración'),
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.tipo_credito')
                                    ->label('Tipo de Crédito')
                                    ->disabled(),
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
                                    ->formatStateUsing(fn ($state) => number_format((float) str_replace([',', '$'], '', $state), 2))
                                    ->helperText('Espejo del Valor Convenio'),
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.precio_promocion')
                                    ->label('Precio Promoción')
                                    ->prefix('$')
                                    ->disabled()
                                    ->formatStateUsing(fn ($state) => number_format((float) str_replace([',', '$'], '', $state), 2))
                                    ->helperText('Valor Convenio × % Multiplicador por estado'),
                            ]),
                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.monto_comision_sin_iva')
                                    ->label('Monto Comisión (Sin IVA)')
                                    ->prefix('$')
                                    ->disabled()
                                    ->formatStateUsing(fn ($state) => number_format((float) str_replace([',', '$'], '', $state), 2))
                                    ->helperText('Valor Convenio × % Comisión'),
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.comision_total')
                                    ->label('Comisión Total a Pagar')
                                    ->prefix('$')
                                    ->disabled()
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
                                    ->formatStateUsing(fn ($state) => number_format((float) str_replace([',', '$'], '', $state), 2)),
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.cancelacion_hipoteca')
                                    ->label('Cancelación de Hipoteca')
                                    ->prefix('$')
                                    ->disabled()
                                    ->formatStateUsing(fn ($state) => number_format((float) str_replace([',', '$'], '', $state), 2)),
                                \Filament\Forms\Components\TextInput::make('calculator_snapshot.total_gastos_fi')
                                    ->label('Total Gastos FI (Venta)')
                                    ->prefix('$')
                                    ->disabled()
                                    ->formatStateUsing(fn ($state) => number_format((float) str_replace([',', '$'], '', $state), 2))
                                    ->helperText('ISR + Cancelación de Hipoteca'),
                            ]),
                        \Filament\Forms\Components\TextInput::make('calculator_snapshot.ganancia_final')
                            ->label('Ganancia Final (Est.)')
                            ->prefix('$')
                            ->disabled()
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
