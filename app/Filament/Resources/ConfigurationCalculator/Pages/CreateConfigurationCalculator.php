<?php

namespace App\Filament\Resources\ConfigurationCalculator\Pages;
use App\Filament\Resources\ConfigurationCalculator\ConfigurationCalculatorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateConfigurationCalculator  extends CreateRecord
{

    protected static string $resource = ConfigurationCalculatorResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
