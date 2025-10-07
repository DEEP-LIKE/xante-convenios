<?php

namespace App\Filament\Widgets;

use App\Models\Agreement;
use App\Models\Client;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total de Clientes', Client::count())
                ->description('Clientes registrados en el sistema')
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Convenios Activos', Agreement::whereNotIn('status', ['completed', 'convenio_firmado'])->count())
                ->description('Convenios en proceso')
                ->icon('heroicon-o-document-text')
                ->color('warning'),

            Stat::make('Convenios Completados', Agreement::whereIn('status', ['completed', 'convenio_firmado'])->count())
                ->description('Convenios finalizados')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Usuarios del Sistema', User::count())
                ->description('Usuarios registrados')
                ->icon('heroicon-o-user-group')
                ->color('info'),
        ];
    }
}
