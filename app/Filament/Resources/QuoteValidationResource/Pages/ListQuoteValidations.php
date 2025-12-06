<?php

namespace App\Filament\Resources\QuoteValidationResource\Pages;

use App\Filament\Resources\QuoteValidationResource;
use Filament\Resources\Pages\ListRecords;

class ListQuoteValidations extends ListRecords
{
    protected static string $resource = QuoteValidationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
