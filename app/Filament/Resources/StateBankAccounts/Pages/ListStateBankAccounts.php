<?php

namespace App\Filament\Resources\StateBankAccounts\Pages;

use App\Filament\Resources\StateBankAccounts\StateBankAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStateBankAccounts extends ListRecords
{
    protected static string $resource = StateBankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getSubheading(): ?string
    {
        return 'Las cuentas activas están atados a los gastos notariales.';
    }
}
