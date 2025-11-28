<?php

namespace App\Filament\Resources\StateCommissionRates\Pages;

use App\Filament\Resources\StateCommissionRates\StateCommissionRateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStateCommissionRate extends EditRecord
{
    protected static string $resource = StateCommissionRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
