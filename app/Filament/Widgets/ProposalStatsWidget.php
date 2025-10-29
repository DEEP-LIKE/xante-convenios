<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Agreement;

class ProposalStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        // Total de convenios completados (todos)
        $totalClosures = Agreement::where('status', 'completed')->count();
        
        // Valor total usando proposal_value si existe, si no usar valor_convenio del wizard_data
        $completedAgreements = Agreement::where('status', 'completed')->get();
        $totalValue = 0;
        $validValuesCount = 0;
        
        foreach ($completedAgreements as $agreement) {
            $value = null;
            
            // Priorizar proposal_value si existe
            if ($agreement->proposal_value) {
                $value = $agreement->proposal_value;
            } else {
                // Si no hay proposal_value, usar valor_convenio del wizard_data
                $wizardData = $agreement->wizard_data ?? [];
                $value = $wizardData['valor_convenio'] ?? null;
            }
            
            if ($value && is_numeric($value)) {
                $totalValue += (float) $value;
                $validValuesCount++;
            }
        }
        
        $averageValue = $validValuesCount > 0 ? $totalValue / $validValuesCount : 0;
        
        return [
            Stat::make('Total de Cierres', $totalClosures)
                ->description('Convenios completados')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
                
            Stat::make('Valor Total', '$' . number_format($totalValue, 2))
                ->description('Suma de todos los cierres')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('primary'),
                
            Stat::make('Promedio por Cierre', '$' . number_format($averageValue, 2))
                ->description('Valor promedio de cierres')
                ->descriptionIcon('heroicon-o-calculator')
                ->color('info'),
        ];
    }
}
