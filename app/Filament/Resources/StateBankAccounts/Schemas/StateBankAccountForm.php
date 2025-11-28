<?php

namespace App\Filament\Resources\StateBankAccounts\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class StateBankAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('state_name')
                    ->required(),
                TextInput::make('state_code')
                    ->required(),
                TextInput::make('account_holder')
                    ->required(),
                TextInput::make('bank_name')
                    ->required(),
                TextInput::make('account_number')
                    ->required(),
                TextInput::make('clabe')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
