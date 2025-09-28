<?php

namespace App\Filament\Resources;

use App\Models\Agreement;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\WizardResource\Pages;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;



class WizardResource extends Resource
{
    protected static ?string $model = Agreement::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Wizard de Convenios';
    
    protected static ?string $modelLabel = 'Wizard de Convenio';
    
    protected static ?string $pluralModelLabel = 'Wizard de Convenios';
    
    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->default('Sin cliente'),
                    
                TextColumn::make('current_step')
                    ->label('Paso Actual')
                    ->formatStateUsing(fn ($state, $record) => 
                        "Paso {$state}: " . ($record->getCurrentStepName() ?? 'Desconocido')
                    )
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        1 => 'gray',
                        2 => 'blue',
                        3 => 'info',
                        4 => 'yellow',
                        5 => 'green',
                        default => 'gray'
                    }),
                    
                // Columna de progreso simplificada para Filament 4
                TextColumn::make('completion_percentage')
                    ->label('Progreso')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state < 25 => 'danger',
                        $state < 50 => 'warning',
                        $state < 75 => 'info',
                        $state < 100 => 'success',
                        $state == 100 => 'success',
                        default => 'gray'
                    }),
                    
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sin_convenio' => 'gray',
                        'expediente_incompleto' => 'warning',
                        'expediente_completo' => 'success',
                        'convenio_proceso' => 'info',
                        'convenio_firmado' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sin_convenio' => 'Sin Convenio',
                        'expediente_incompleto' => 'Expediente Incompleto',
                        'expediente_completo' => 'Expediente Completo',
                        'convenio_proceso' => 'Convenio en Proceso',
                        'convenio_firmado' => 'Convenio Firmado',
                        default => $state,
                    }),
                    
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->default('Sistema')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('current_step')
                    ->label('Paso Actual')
                    ->options([
                        1 => 'Paso 1: Búsqueda e Identificación',
                        2 => 'Paso 2: Datos del Cliente',
                        3 => 'Paso 3: Datos de la propiedad',
                        4 => 'Paso 4: Calculadora Financiera',
                        5 => 'Paso 5: Envio de documentación',
                    ]),
                    
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'sin_convenio' => 'Sin Convenio',
                        'expediente_incompleto' => 'Expediente Incompleto',
                        'expediente_completo' => 'Expediente Completo',
                        'convenio_proceso' => 'Convenio en Proceso',
                        'convenio_firmado' => 'Convenio Firmado',
                    ]),
                    
                SelectFilter::make('completion_percentage')
                    ->label('Progreso')
                    ->options([
                        '0-25' => '0-25%',
                        '26-50' => '26-50%',
                        '51-75' => '51-75%',
                        '76-99' => '76-99%',
                        '100' => '100%',
                    ])
                    ->query(function ($query, array $data) {
                        if (!$data['value']) return $query;
                        
                        return match($data['value']) {
                            '0-25' => $query->whereBetween('completion_percentage', [0, 25]),
                            '26-50' => $query->whereBetween('completion_percentage', [26, 50]),
                            '51-75' => $query->whereBetween('completion_percentage', [51, 75]),
                            '76-99' => $query->whereBetween('completion_percentage', [76, 99]),
                            '100' => $query->where('completion_percentage', 100),
                            default => $query
                        };
                    }),
            ])
            ->actions([
                // CORREGIDO: Botones con estilos Filament 4 unificados
                Action::make('continue')
                    ->label('Continuar')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->url(fn (Agreement $record): string => 
                        \App\Filament\Pages\CreateAgreementWizard::getUrl(['agreement' => $record->id])
                    )
                    ->visible(fn (Agreement $record): bool => $record->status !== 'expediente_completo'),

                Action::make('view_summary')
                    ->label('Resumen')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalContent(fn (Agreement $record) => view('filament.modals.wizard-summary', [
                        'agreement' => $record,
                        'summary' => app(\App\Services\WizardService::class)->getWizardSummary($record->id)
                    ]))
                    ->modalHeading(fn (Agreement $record) => "Resumen del Convenio #{$record->id}")
                    ->modalWidth('4xl'),

                Action::make('clone')
                    ->label('Clonar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (Agreement $record) {
                        $wizardService = app(\App\Services\WizardService::class);
                        $newAgreement = $wizardService->cloneAgreement($record->id);
                        
                        return redirect(\App\Filament\Pages\CreateAgreementWizard::getUrl(['agreement' => $newAgreement->id]));
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Clonar Convenio')
                    ->modalDescription('¿Está seguro de que desea clonar este convenio? Se creará una nueva instancia con los mismos datos.'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateActions([
                Action::make('create_first_agreement')
                    ->label('Crear Primer Convenio')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->url(fn (): string => \App\Filament\Pages\CreateAgreementWizard::getUrl())
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s') // Auto-refresh cada 30 segundos
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWizards::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('completion_percentage', '<', 100)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $incomplete = static::getModel()::where('completion_percentage', '<', 100)->count();
        
        return match(true) {
            $incomplete > 10 => 'danger',
            $incomplete > 5 => 'warning',
            $incomplete > 0 => 'info',
            default => 'success'
        };
    }
}
