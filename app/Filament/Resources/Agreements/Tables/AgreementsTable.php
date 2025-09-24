<?php

namespace App\Filament\Resources\Agreements;


use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;


class AgreementsTable
{
    public static function table(Table $table): Table

    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('edit')
                    ->icon('heroicon-o-pencil'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
