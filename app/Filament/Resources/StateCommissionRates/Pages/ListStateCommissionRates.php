<?php

namespace App\Filament\Resources\StateCommissionRates\Pages;

use App\Filament\Resources\StateCommissionRates\StateCommissionRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStateCommissionRates extends ListRecords
{
    protected static string $resource = StateCommissionRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
