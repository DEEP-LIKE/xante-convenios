<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Agreement;

class ProposalClosuresWidget extends ChartWidget
{
    protected static ?int $sort = 2;
    
    public static function canView(): bool
    {
        return false;
    }
    
    public function getHeading(): string
    {
        return 'Cierres con Propuesta Final Ofrecida';
    }

    protected function getData(): array
    {
        // Obtener convenios completados que tienen proposal_value
        $closures = Agreement::whereNotNull('proposal_value')
            ->where('status', 'completed')
            ->orderBy('proposal_saved_at', 'desc')
            ->take(10)
            ->get(); // Cambiar count() por get()
        
        // Si solo quieres mostrar el conteo como un número simple
        $count = $closures->count();
        
        // Extraer los valores de proposal_value para el gráfico
        $values = $closures->pluck('proposal_value')->map(function ($value) {
            return is_numeric($value) ? (float) $value : 0;
        })->toArray();
        
        return [
            'datasets' => [
                [
                    'label' => 'Valor de Propuesta Final ($)',
                    'data' => $values,
                    'backgroundColor' => [
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(251, 146, 60, 0.7)',
                        'rgba(168, 85, 247, 0.7)',
                        'rgba(236, 72, 153, 0.7)',
                        'rgba(34, 197, 94, 0.7)',
                        'rgba(239, 68, 68, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(139, 92, 246, 0.7)',
                        'rgba(20, 184, 166, 0.7)',
                    ],
                ],
            ],
            'labels' => $closures->map(function ($agreement) {
                return 'Convenio #' . $agreement->id;
            })->toArray(),
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
                    ]
                ],
                'x' => [
                    'grid' => [
                        'display' => false, // Oculta las líneas de la cuadrícula vertical
                    ]
                ]
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