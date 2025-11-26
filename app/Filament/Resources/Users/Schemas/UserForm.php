<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                \Filament\Forms\Components\Select::make('role')
                    ->label('Rol')
                    ->options([
                        'admin' => 'Administrador',
                        'asesor' => 'Asesor',
                    ])
                    ->default('asesor')
                    ->required(),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->revealable(),
                TextInput::make('password_confirmation')
                    ->label('Confirmar Contraseña')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->same('password')
                    ->dehydrated(false)
                    ->revealable(),
                DateTimePicker::make('email_verified_at')
                    ->default(now())
                    ->dehydrated()
                    ->hidden(),
            ]);
    }
}
