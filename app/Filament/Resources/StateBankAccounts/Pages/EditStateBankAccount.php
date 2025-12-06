<?php

namespace App\Filament\Resources\StateBankAccounts\Pages;

use App\Filament\Resources\StateBankAccounts\StateBankAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStateBankAccount extends EditRecord
{
    protected static string $resource = StateBankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
