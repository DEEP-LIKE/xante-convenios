<?php

namespace App\Filament\Resources\StateCommissionRates\Pages;

use App\Filament\Resources\StateCommissionRates\StateCommissionRateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStateCommissionRate extends CreateRecord
{
    protected static string $resource = StateCommissionRateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
