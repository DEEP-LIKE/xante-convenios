<?php

namespace App\Filament\Resources\ConfigurationCalculator\Pages;
use App\Filament\Resources\ConfigurationCalculator\ConfigurationCalculatorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConfigurationCalculator extends EditRecord
{
    
    protected static string $resource = ConfigurationCalculatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(), // Eliminado para evitar borrado accidental
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
