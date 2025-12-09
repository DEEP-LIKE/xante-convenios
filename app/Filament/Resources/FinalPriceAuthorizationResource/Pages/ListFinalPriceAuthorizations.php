<?php

namespace App\Filament\Resources\FinalPriceAuthorizationResource\Pages;

use App\Filament\Resources\FinalPriceAuthorizationResource;
use Filament\Resources\Pages\ListRecords;

class ListFinalPriceAuthorizations extends ListRecords
{
    protected static string $resource = FinalPriceAuthorizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No hay acción de crear - las solicitudes se crean desde ManageDocumentsPage
        ];
    }
}
