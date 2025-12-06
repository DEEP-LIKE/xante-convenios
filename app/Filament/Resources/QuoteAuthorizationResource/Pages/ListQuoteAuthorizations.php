<?php

namespace App\Filament\Resources\QuoteAuthorizationResource\Pages;

use App\Filament\Resources\QuoteAuthorizationResource;
use Filament\Resources\Pages\ListRecords;

class ListQuoteAuthorizations extends ListRecords
{
    protected static string $resource = QuoteAuthorizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action needed as per requirements
        ];
    }
}
