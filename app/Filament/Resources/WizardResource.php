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
use Filament\Tables\Actions\HeaderAction;
use Illuminate\Support\Facades\Artisan;



class WizardResource extends Resource
{
    protected static ?string $model = Agreement::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Convenios';
    
    protected static ?string $modelLabel = 'Convenio';
    
    protected static ?string $pluralModelLabel = 'Convenios';
    
    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client_name')
                    ->label('Cliente')
                    ->getStateUsing(function (Agreement $record) {
                        // 1. Buscar en la relación client
                        if ($record->client && $record->client->name) {
                            return $record->client->name;
                        }
                        
                        // 2. Buscar en el campo directo holder_name
                        if (!empty($record->holder_name)) {
                            return $record->holder_name;
                        }
                        
                        // 3. Buscar en wizard_data
                        $wizardData = $record->wizard_data ?? [];
                        if (!empty($wizardData['holder_name'])) {
                            return $wizardData['holder_name'];
                        }
                        // 4. Buscar en otros campos de wizard_data
                        if (!empty($wizardData['name'])) {
                            return $wizardData['name'];
                        }
                        
                        return 'Pendiente';
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('xante_id')
                    ->label('ID Xante')
                    ->getStateUsing(function (Agreement $record) {
                        // 1. Buscar en la relación client
                        if ($record->client && $record->client->xante_id) {
                            return $record->client->xante_id;
                        }
                        
                        // 2. Buscar en client_xante_id directo
                        if (!empty($record->client_xante_id)) {
                            return $record->client_xante_id;
                        }
                        
                        // 3. Buscar en wizard_data
                        $wizardData = $record->wizard_data ?? [];
                        if (!empty($wizardData['xante_id'])) {
                            return $wizardData['xante_id'];
                        }
                        
                        // Si no hay ID, mostrar "Sin asignar"
                        return 'Sin asignar';
                    })
                    ->color(function (Agreement $record) {
                        // Verificar si tiene ID Xante
                        $hasXanteId = false;
                        
                        if ($record->client && $record->client->xante_id) {
                            $hasXanteId = true;
                        } else {
                            $wizardData = $record->wizard_data ?? [];
                            if (!empty($wizardData['xante_id'])) {
                                $hasXanteId = true;
                            }
                        }
                        
                        return $hasXanteId ? 'primary' : 'gray';
                    })
                    ->searchable()
                    ->sortable(),
                    
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->default('Sistema')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('current_step')
                    ->label('Paso Actual')
                    ->options([
                        1 => 'Paso 1: Identificación',
                        2 => 'Paso 2: Cliente',
                        3 => 'Paso 3: Propiedad',
                        4 => 'Paso 4: Calculadora',
                        5 => 'Paso 5: Validación',
                    ]),
                    
                SelectFilter::make('current_wizard')
                    ->label('Etapa Actual')
                    ->options([
                        1 => 'Etapa I: Captura de Información',
                        2 => 'Etapa II: Gestión Documental',
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
                // Botón único que se adapta al wizard actual
                Action::make('continue')
                    ->label(fn (Agreement $record): string => 
                        $record->status === 'completed' ? 'Ver' : 'Continuar'
                    )
                    ->icon(fn (Agreement $record): string => 
                        $record->status === 'completed' ? 'heroicon-o-eye' : 'heroicon-o-play'
                    )
                    ->color('primary')
                    ->url(function (Agreement $record): string {
                        // Si está completado, ir al resumen
                        if ($record->status === 'completed') {
                            return "/admin/manage-documents/{$record->id}";
                        }
                        
                        // Si está en Wizard 1 y puede regresar
                        if ($record->current_wizard === 1 && $record->can_return_to_wizard1 === true) {
                            return "/admin/convenios/crear?agreement={$record->id}";
                        }
                        
                        // Si está en Wizard 2 o documentos generados
                        if ($record->current_wizard === 2 || $record->status === 'documents_generated') {
                            return "/admin/manage-documents/{$record->id}";
                        }
                        
                        // Por defecto, ir al Wizard 1
                        return "/admin/convenios/crear?agreement={$record->id}";
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateActions([
                Action::make('create_first_agreement')
                    ->label('Nuevo Convenio')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->url('/admin/convenios/crear')
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
