<?php

namespace App\Filament\Resources\StateBankAccounts\Pages;

use App\Filament\Resources\StateBankAccounts\StateBankAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStateBankAccount extends CreateRecord
{
    protected static string $resource = StateBankAccountResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
