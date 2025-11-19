<?php

namespace App\Services;

use App\Models\Agreement;
use App\DTOs\WizardStateDTO;
use Filament\Notifications\Notification;

/**
 * Servicio para gestionar el estado del wizard de convenios
 * 
 * Responsabilidades:
 * - Inicializar el wizard (nuevo o existente)
 * - Gestionar la sesión del wizard
 * - Cargar y recuperar datos del convenio
 */
class WizardStateManager
{
    private const SESSION_KEY = 'wizard_agreement_id';

    /**
     * Inicializa el wizard con un convenio existente o nuevo
     */
    public function initializeWizard(?int $agreementId, ?int $clientId): WizardStateDTO
    {
        // Prioridad 1: Recuperar desde la sesión
        $sessionAgreementId = $this->loadFromSession();

        // Prioridad 2: Usar el parámetro proporcionado
        $finalAgreementId = $agreementId ?? $sessionAgreementId;

        if ($finalAgreementId) {
            return $this->loadExistingAgreement($finalAgreementId);
        }

        // Nuevo convenio
        return $this->createNewWizardState($clientId);
    }

    /**
     * Carga un convenio existente
     */
    private function loadExistingAgreement(int $agreementId): WizardStateDTO
    {
        $agreement = Agreement::find($agreementId);

        if (!$agreement) {
            // Si el ID no es válido, limpiar sesión y empezar de nuevo
            $this->clearSession();
            return $this->createNewWizardState(null);
        }

        // Guardar en sesión para persistencia
        $this->saveToSession($agreementId);

        return new WizardStateDTO(
            agreementId: $agreementId,
            currentStep: $agreement->current_step ?? 1,
            data: $agreement->wizard_data ?? [],
            hasExistingProposal: false // Se determinará después
        );
    }

    /**
     * Crea un estado inicial para un nuevo wizard
     */
    private function createNewWizardState(?int $clientId): WizardStateDTO
    {
        $data = [];

        // Si hay un clientId en la URL, mostrar notificación
        if ($clientId) {
            Notification::make()
                ->title('Funcionalidad en desarrollo')
                ->body('La preselección de clientes desde la URL se activará después de guardar el primer paso.')
                ->warning()
                ->send();
        }

        return new WizardStateDTO(
            agreementId: null,
            currentStep: 1,
            data: $data,
            hasExistingProposal: false
        );
    }

    /**
     * Carga el ID del convenio desde la sesión
     */
    public function loadFromSession(): ?int
    {
        return session(self::SESSION_KEY);
    }

    /**
     * Guarda el ID del convenio en la sesión
     */
    public function saveToSession(int $agreementId): void
    {
        session([self::SESSION_KEY => $agreementId]);
    }

    /**
     * Limpia la sesión del wizard
     */
    public function clearSession(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * Carga los datos completos de un convenio
     */
    public function loadAgreementData(int $agreementId): array
    {
        $agreement = Agreement::find($agreementId);
        
        return $agreement ? ($agreement->wizard_data ?? []) : [];
    }

    /**
     * Actualiza el paso actual del wizard
     */
    public function updateCurrentStep(int $agreementId, int $step): void
    {
        Agreement::where('id', $agreementId)->update([
            'current_step' => $step
        ]);
    }
}
