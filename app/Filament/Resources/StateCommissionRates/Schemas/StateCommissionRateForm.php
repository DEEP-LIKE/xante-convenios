<?php

namespace App\Filament\Resources\StateCommissionRates\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class StateCommissionRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('state_name')
                    ->required(),
                TextInput::make('state_code')
                    ->required(),
                TextInput::make('commission_percentage')
                    ->required()
                    ->numeric(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
