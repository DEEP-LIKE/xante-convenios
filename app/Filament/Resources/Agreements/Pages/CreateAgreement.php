<?php

namespace App\Filament\Resources\Agreements\Pages;

use App\Filament\Resources\Agreements\AgreementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAgreement extends CreateRecord
{
    protected static string $resource = AgreementResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
