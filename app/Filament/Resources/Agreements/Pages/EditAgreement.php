<?php

namespace App\Filament\Resources\Agreements\Pages;

use App\Filament\Resources\Agreements\AgreementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAgreement extends EditRecord
{
    protected static string $resource = AgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
