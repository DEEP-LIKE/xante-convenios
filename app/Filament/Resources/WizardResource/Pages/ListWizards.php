<?php

namespace App\Filament\Resources\WizardResource\Pages;

use App\Filament\Resources\WizardResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListWizards extends ListRecords
{
    protected static string $resource = WizardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new_wizard')
                ->label('Nuevo Convenio')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url('/admin/convenios/crear')
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
