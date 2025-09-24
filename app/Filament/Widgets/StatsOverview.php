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
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Convenios Activos', Agreement::where('status', '!=', 'convenio_firmado')->count())
                ->description('Convenios en proceso')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Convenios Completados', Agreement::where('status', 'convenio_firmado')->count())
                ->description('Convenios finalizados')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Usuarios del Sistema', User::count())
                ->description('Usuarios registrados')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),
        ];
    }
}
