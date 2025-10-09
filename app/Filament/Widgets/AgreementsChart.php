<?php

namespace App\Filament\Widgets;

use App\Models\Agreement;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class AgreementsChart extends ChartWidget
{
    protected static ?int $sort = 2;

    public function getHeading(): ?string
    {
        return 'Convenios - Últimos 3 Meses';
    }

    public function getMaxHeight(): ?string
    {
        return '300px';
    }

    protected function getData(): array
    {
        $now = Carbon::now();
        $months = [];
        $newAgreements = [];
        $completedAgreements = [];

        // Generar datos para los últimos 3 meses
        for ($i = 2; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $monthName = $month->locale('es')->format('M Y');
            $months[] = $monthName;

            // Convenios nuevos (creados en ese mes)
            $newCount = Agreement::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            $newAgreements[] = $newCount;

            // Convenios cerrados (completados en ese mes)
            $completedCount = Agreement::whereIn('status', ['completed', 'convenio_firmado'])
                ->whereYear('updated_at', $month->year)
                ->whereMonth('updated_at', $month->month)
                ->count();
            $completedAgreements[] = $completedCount;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Nuevos Convenios',
                    'data' => $newAgreements,
                    'backgroundColor' => '#BDCE0F', // Verde Xante
                    'borderColor' => '#BDCE0F',
                    'borderWidth' => 2,
                    'fill' => false,
                ],
                [
                    'label' => 'Convenios Cerrados',
                    'data' => $completedAgreements,
                    'backgroundColor' => '#6C2582', // Morado Xante
                    'borderColor' => '#6C2582',
                    'borderWidth' => 2,
                    'fill' => false,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
        ];
    }
}
