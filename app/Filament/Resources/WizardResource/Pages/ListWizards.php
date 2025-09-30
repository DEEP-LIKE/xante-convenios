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
                ->url('/admin/create-agreement-wizard')
                ->keyBindings(['cmd+n', 'ctrl+n']),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Aquí se pueden agregar widgets de estadísticas
        ];
    }

}
