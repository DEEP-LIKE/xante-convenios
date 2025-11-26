<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Agreement;

class ProposalClosuresStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    public static function canView(): bool
    {
        return false;
    }
    
    protected function getStats(): array
    {
        // Contar convenios completados que tienen proposal_value
        $closuresWithProposal = Agreement::whereNotNull('proposal_value')
            ->where('status', 'completed')
            ->count();
            
        return [
            Stat::make('Cierres con Propuesta Final Ofrecida', $closuresWithProposal)
                ->description('Convenios finalizados con valor de propuesta')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17])
        ];
    }
}
