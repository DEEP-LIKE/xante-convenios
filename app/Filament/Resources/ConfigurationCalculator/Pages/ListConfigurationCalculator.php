<?php

namespace App\Filament\Resources\ConfigurationCalculator\Pages;

use App\Filament\Resources\ConfigurationCalculator\ConfigurationCalculatorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConfigurationCalculator extends ListRecords
{
    protected static string $resource = ConfigurationCalculatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
