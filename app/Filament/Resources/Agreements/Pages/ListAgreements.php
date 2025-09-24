<?php

namespace App\Filament\Resources\Agreements\Pages;

use App\Filament\Resources\Agreements\AgreementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAgreements extends ListRecords
{
    protected static string $resource = AgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
