<?php

namespace App\Filament\Widgets;

use App\Models\Agreement;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

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
        // Usamos now() para determinar el contexto de los últimos tres meses
        $now = Carbon::now();
        $months = [];
        $newAgreements = [];
        $completedAgreements = [];

        // Generar datos para los últimos 3 meses (i=2 es hace dos meses, i=0 es el mes actual)
        for ($i = 2; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            // Formateo del nombre del mes en español (ej: "Oct 2025")
            $monthName = $month->locale('es')->monthName.' '.$month->format('Y');
            $months[] = $monthName;

            // 1. Convenios nuevos (creados en ese mes)
            $newCount = Agreement::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            $newAgreements[] = $newCount;

            // 2. Convenios cerrados/completados (actualizados en ese mes)
            $completedCount = Agreement::whereIn('status', ['completed', 'convenio_firmado'])
                // Es crucial filtrar por updated_at o el campo que marque el estado de "cerrado"
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
                    'borderWidth' => 3, // Mayor ancho para la línea
                    'fill' => false,
                ],
                [
                    'label' => 'Convenios Cerrados',
                    'data' => $completedAgreements,
                    'backgroundColor' => '#6C2582', // Morado Xante
                    'borderColor' => '#6C2582',
                    'borderWidth' => 3, // Mayor ancho para la línea
                    'fill' => false,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        // CAMBIADO: Usar 'line' para visualizar la tendencia en el tiempo.
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            // Opciones para suavizar la línea y mostrar los puntos de datos
            'elements' => [
                'line' => [
                    'tension' => 0.4, // Suaviza las líneas (efecto "curva")
                ],
                'point' => [
                    'radius' => 4,    // Hace visibles los puntos de datos
                    'hoverRadius' => 6,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        // Asegura que solo se muestren números enteros, ya que son conteos
                        'precision' => 0,
                        'stepSize' => 1,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Cantidad de Convenios',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false, // Oculta las líneas de la cuadrícula vertical
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
