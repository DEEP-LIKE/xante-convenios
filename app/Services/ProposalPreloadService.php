<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Proposal;
use Filament\Notifications\Notification;

/**
 * Servicio para detectar y precargar propuestas existentes
 *
 * Responsabilidades:
 * - Detectar si un cliente tiene una propuesta previa enlazada
 * - Precargar datos de la propuesta en el wizard
 * - Determinar si se debe precargar automáticamente
 */
class ProposalPreloadService
{
    /**
     * Verifica si el cliente tiene una propuesta existente enlazada
     *
     * @return array|null Información de la propuesta o null si no existe
     */
    public function hasExistingProposal(int $clientId): ?array
    {
        $client = Client::find($clientId);

        if (! $client || ! $client->xante_id) {
            return null;
        }

        // Buscar propuesta enlazada
        $proposal = Proposal::where('idxante', $client->xante_id)
            ->where('linked', true)
            ->latest()
            ->first();

        if (! $proposal) {
            return null;
        }

        // Retornar datos de la propuesta
        return [
            'exists' => true,
            'created_at' => $proposal->created_at,
            'valor_convenio' => $proposal->valor_convenio,
            'ganancia_final' => $proposal->ganancia_final,
            'data' => $proposal->data ?? [],
            'resumen' => $proposal->resumen ?? [],
        ];
    }

    /**
     * Precarga los datos de la propuesta si existe
     *
     * @return array|null Datos precargados o null
     */
    public function preloadProposalData(int $clientId): ?array
    {
        $proposalInfo = $this->hasExistingProposal($clientId);

        if (! $proposalInfo || empty($proposalInfo['data'])) {
            return null;
        }

        return $proposalInfo['data'];
    }

    /**
     * Determina si se debe precargar la propuesta automáticamente
     *
     * Solo precarga si los campos de calculadora están vacíos
     */
    public function shouldPreload(array $currentData): bool
    {
        return empty($currentData['valor_convenio']) || $currentData['valor_convenio'] == 0;
    }

    /**
     * Precarga datos de propuesta si existe y los campos están vacíos
     *
     * @return array Datos mezclados (actuales + propuesta)
     */
    public function preloadIfExists(int $clientId, array $currentData): array
    {
        $proposalInfo = $this->hasExistingProposal($clientId);

        if (! $proposalInfo || empty($proposalInfo['data'])) {
            return $currentData;
        }

        // Solo precargar si los campos calculadores están vacíos
        if (! $this->shouldPreload($currentData)) {
            return $currentData;
        }

        // Mezclar datos de la propuesta con datos actuales
        $mergedData = array_merge($currentData, $proposalInfo['data']);

        // Notificar al usuario
        Notification::make()
            ->title('🔄 Pre-cálculo Cargado')
            ->body('Se han precargado los valores de la cotización previa del cliente')
            ->info()
            ->duration(5000)
            ->send();

        return $mergedData;
    }

    /**
     * Fuerza la sobrescritura de los datos actuales en el formulario referenciado (Wizard)
     * usando la última propuesta enlazada (Calculadora independiente).
     */
    public function forcePreload(int $clientId, callable $set, callable $get, $livewire): void
    {
        $proposalInfo = $this->hasExistingProposal($clientId);

        if (! $proposalInfo || empty($proposalInfo['data'])) {
            Notification::make()
                ->title('Sin actualización')
                ->body('No se encontró información reciente en la Calculadora. Guarde un cálculo primero.')
                ->warning()
                ->send();
            return;
        }

        // Cargar los campos calculadores básicos
        $fields = [
            'valor_convenio',
            'porcentaje_comision_sin_iva',
            'isr',
            'cancelacion_hipoteca',
            'monto_credito'
        ];

        foreach ($fields as $field) {
            if (isset($proposalInfo['data'][$field])) {
                $set($field, $proposalInfo['data'][$field]);
            }
        }

        // Recalcular
        if (method_exists($livewire, 'recalculateAllFinancials')) {
            $livewire->recalculateAllFinancials($set, $get);
        }

        Notification::make()
            ->title('🔄 Datos Actualizados')
            ->body('Los valores del convenio se han actualizado usando el último cálculo independiente.')
            ->success()
            ->duration(5000)
            ->send();
    }

    /**
     * Obtiene información formateada de la propuesta para mostrar en UI
     */
    public function getProposalDisplayInfo(?array $proposalInfo): ?array
    {
        if (! $proposalInfo) {
            return null;
        }

        return [
            'valor_convenio' => $proposalInfo['valor_convenio'] ?? 0,
            'ganancia_final' => $proposalInfo['ganancia_final'] ?? 0,
            'fecha_calculo' => $proposalInfo['created_at']->format('d/m/Y H:i'),
        ];
    }
}
