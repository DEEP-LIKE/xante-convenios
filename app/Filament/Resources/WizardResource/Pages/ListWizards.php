<?php

namespace App\Filament\Resources\WizardResource\Pages;

use App\Filament\Resources\WizardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;

class ListWizards extends ListRecords
{
    protected static string $resource = WizardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new_wizard')
                ->label('Nuevo Convenio Wizard')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(\App\Filament\Pages\CreateAgreementWizard::getUrl())
                ->keyBindings(['cmd+n', 'ctrl+n']),
                
            Action::make('dashboard')
                ->label('Dashboard de Progreso')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->modalContent(view('filament.modals.wizard-dashboard', [
                    'stats' => $this->getWizardStats()
                ]))
                ->modalHeading('Dashboard del Wizard')
                ->modalWidth('6xl'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Aquí se pueden agregar widgets de estadísticas
        ];
    }

    private function getWizardStats(): array
    {
        $model = static::getResource()::getModel();
        
        return [
            'total' => $model::count(),
            'in_progress' => $model::where('completion_percentage', '>', 0)
                ->where('completion_percentage', '<', 100)
                ->count(),
            'completed' => $model::where('completion_percentage', 100)->count(),
            'by_step' => [
                1 => $model::where('current_step', 1)->count(),
                2 => $model::where('current_step', 2)->count(),
                3 => $model::where('current_step', 3)->count(),
                4 => $model::where('current_step', 4)->count(),
                5 => $model::where('current_step', 5)->count(),
                6 => $model::where('current_step', 6)->count(),
            ],
            'by_status' => [
                'sin_convenio' => $model::where('status', 'sin_convenio')->count(),
                'expediente_incompleto' => $model::where('status', 'expediente_incompleto')->count(),
                'expediente_completo' => $model::where('status', 'expediente_completo')->count(),
                'convenio_proceso' => $model::where('status', 'convenio_proceso')->count(),
                'convenio_firmado' => $model::where('status', 'convenio_firmado')->count(),
            ],
            'recent_activity' => $model::with(['createdBy'])
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get(),
        ];
    }
}
