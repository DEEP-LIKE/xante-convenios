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
                    
                TextColumn::make('current_wizard')
                    ->label('Etapa Actual')
                    ->formatStateUsing(function ($state, $record) {
                        $wizardNumber = $state ?? 1;
                        $etapaNumero = $wizardNumber === 1 ? 'I' : 'II';
                        $wizardName = $wizardNumber === 1 ? 'Captura de Información' : 'Gestión Documental';
                        return "Etapa {$etapaNumero}: {$wizardName}";
                    })
                    ->badge()
                    ->color(function ($state) {
                        return ($state ?? 1) === 1 ? 'info' : 'success';
                    }),
                    
                TextColumn::make('current_step')
                    ->label('Paso Actual')
                    ->formatStateUsing(function ($state, $record) {
                        if (($record->current_wizard ?? 1) === 1) {
                            return "Paso {$state}: " . ($record->getCurrentStepName() ?? 'Desconocido');
                        } else {
                            $wizard2Steps = $record->getWizard2Steps();
                            $stepName = $wizard2Steps[$record->wizard2_current_step ?? 1] ?? 'Desconocido';
                            return "Paso {$record->wizard2_current_step}: {$stepName}";
                        }
                    })
                    ->badge()
                    ->color(function ($state, $record) {
                        $currentWizard = $record->current_wizard ?? 1;
                        $currentStep = $currentWizard === 1 ? $state : ($record->wizard2_current_step ?? 1);
                        
                        return match($currentStep) {
                            1 => 'gray',
                            2 => 'blue', 
                            3 => 'info',
                            4 => 'yellow',
                            5 => 'green',
                            default => 'gray'
                        };
                    }),
                    
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        // Estados originales
                        'sin_convenio' => 'gray',
                        'expediente_incompleto' => 'warning',
                        'expediente_completo' => 'success',
                        'convenio_proceso' => 'info',
                        'convenio_firmado' => 'success',
                        // Nuevos estados del sistema de dos wizards
                        'draft' => 'gray',
                        'pending_validation' => 'warning',
                        'documents_generating' => 'info',
                        'documents_generated' => 'success',
                        'documents_sent' => 'info',
                        'awaiting_client_docs' => 'warning',
                        'documents_complete' => 'success',
                        'completed' => 'success',
                        'error_generating_documents' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(function (string $state, Agreement $record): string {
                        return $record->status_label;
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
                        5 => 'Paso 5: Resumen y Validación',
                    ]),
                    
                SelectFilter::make('current_wizard')
                    ->label('Wizard Actual')
                    ->options([
                        1 => 'Wizard 1: Captura de Información',
                        2 => 'Wizard 2: Gestión Documental',
                    ]),
                    
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        // Estados originales
                        'sin_convenio' => 'Sin Convenio',
                        'expediente_incompleto' => 'Expediente Incompleto',
                        'expediente_completo' => 'Expediente Completo',
                        'convenio_proceso' => 'Convenio en Proceso',
                        'convenio_firmado' => 'Convenio Firmado',
                        // Nuevos estados del sistema de dos wizards
                        'draft' => 'Borrador',
                        'pending_validation' => 'Pendiente de Validación',
                        'documents_generating' => 'Generando Documentos',
                        'documents_generated' => 'Documentos Generados',
                        'documents_sent' => 'Documentos Enviados',
                        'awaiting_client_docs' => 'Esperando Documentos del Cliente',
                        'documents_complete' => 'Documentos Completos',
                        'completed' => 'Completado',
                        'error_generating_documents' => 'Error al Generar Documentos',
                    ]),
            ])
            ->actions([
                // Botón para Wizard 1
                Action::make('continue_wizard1')
                    ->label('Continuar')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->url(fn (Agreement $record): string => 
                        "/admin/create-agreement-wizard?agreement={$record->id}"
                    )
                    ->visible(fn (Agreement $record): bool => 
                        $record->current_wizard === 1 && $record->can_return_to_wizard1 === true
                    ),
                    
                // Botón para Wizard 2
                Action::make('continue_wizard2')
                    ->label('Continuar')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->url(fn (Agreement $record): string => 
                        "/admin/manage-agreement-documents/{$record->id}"
                    )
                    ->visible(fn (Agreement $record): bool => 
                        $record->current_wizard === 2 || $record->status === 'documents_generated'
                    ),
                    
                // Botón de Ver Resumen (solo para completados)
                Action::make('view_summary')
                    ->label('Continuar')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (Agreement $record): string => 
                        "/admin/manage-agreement-documents/{$record->id}"
                    )
                    ->visible(fn (Agreement $record): bool => 
                        $record->status === 'completed'
                    ),
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
                    ->url('/admin/create-agreement-wizard')
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
        $incomplete = static::getModel()::whereNotIn('status', ['completed'])->count();
        return $incomplete > 0 ? (string) $incomplete : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $incomplete = static::getModel()::whereNotIn('status', ['completed'])->count();
        
        return match(true) {
            $incomplete > 10 => 'danger',
            $incomplete > 5 => 'warning',
            $incomplete > 0 => 'info',
            default => 'success'
        };
    }
}
