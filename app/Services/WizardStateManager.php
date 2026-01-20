<?php

namespace App\Services;

use App\DTOs\WizardStateDTO;
use App\Models\Agreement;
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
        // $sessionAgreementId = $this->loadFromSession();

        // Prioridad 2: Usar el parámetro proporcionado
        // $finalAgreementId = $agreementId ?? $sessionAgreementId;

        // CORRECCIÓN: Si no viene ID en la URL, asumimos que es NUEVO convenio.
        // No recuperamos de sesión para evitar que "Crear Nuevo" te mande al anterior.
        $finalAgreementId = $agreementId;

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

        if (! $agreement) {
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

        // Si hay un clientId en la URL, precargar los datos del cliente
        if ($clientId) {
            $client = \App\Models\Client::where('xante_id', $clientId)->first();

            if ($client) {
                // Preseleccionar el cliente en el formulario (usar ID interno de BD)
                $data['client_id'] = $client->id; // ID interno para el selector
                $data['xante_id'] = $client->xante_id; // ID de Xante para referencia

                // Precargar fecha de registro (fecha del Deal en HubSpot)
                if ($client->fecha_registro) {
                    $data['fecha_registro'] = $client->fecha_registro->format('Y-m-d');
                }

                // Precargar datos del titular
                $data['holder_name'] = $client->name;
                $data['holder_email'] = $client->email;
                $data['holder_phone'] = $client->phone;
                $data['holder_office_phone'] = $client->office_phone;
                $data['holder_curp'] = $client->curp;
                $data['holder_rfc'] = $client->rfc;
                $data['holder_civil_status'] = $client->civil_status;
                $data['holder_occupation'] = $client->occupation;

                // Precargar domicilio del titular
                $data['current_address'] = $client->current_address;
                $data['neighborhood'] = $client->neighborhood;
                $data['postal_code'] = $client->postal_code;
                $data['municipality'] = $client->municipality;
                $data['state'] = $client->state;

                // Precargar datos del cónyuge si existe
                if ($client->spouse) {
                    $data['spouse_name'] = $client->spouse->name;
                    $data['spouse_email'] = $client->spouse->email;
                    $data['spouse_phone'] = $client->spouse->phone;
                    $data['spouse_curp'] = $client->spouse->curp;
                    $data['spouse_current_address'] = $client->spouse->current_address;
                    $data['spouse_neighborhood'] = $client->spouse->neighborhood;
                    $data['spouse_postal_code'] = $client->spouse->postal_code;
                    $data['spouse_municipality'] = $client->spouse->municipality;
                    $data['spouse_state'] = $client->spouse->state;
                }

                // --- NUEVO: Precargar datos de Propiedad y Financieros desde el último convenio ---
                $latestAgreement = \App\Models\Agreement::where('client_id', $client->id)
                    ->latest()
                    ->first();

                if ($latestAgreement && ! empty($latestAgreement->wizard_data)) {
                    // Mezclar datos del convenio (Propiedad, Financieros) con los datos actuales
                    // Usamos array_merge para que los datos del convenio NO sobrescriban los del cliente
                    // (aunque en teoría son complementarios)
                    $agreementData = $latestAgreement->wizard_data;

                    // Filtrar solo campos relevantes para evitar sobrescribir IDs o datos sensibles
                    $allowedFields = [
                        // Propiedad
                        'property_address', 'community', 'housing_type', 'prototype',
                        'lot', 'block', 'stage', 'property_municipality', 'property_state',
                        // Financieros
                        'agreement_value', 'promotion_price', 'total_commission', 'final_profit',
                    ];

                    foreach ($allowedFields as $field) {
                        if (isset($agreementData[$field]) && ! empty($agreementData[$field])) {
                            $data[$field] = $agreementData[$field];
                        }
                    }
                }

                Notification::make()
                    ->title('Cliente Preseleccionado')
                    ->body("Los datos de {$client->name} han sido precargados. Puedes continuar con el siguiente paso.")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Cliente no encontrado')
                    ->body('No se pudo encontrar el cliente seleccionado.')
                    ->warning()
                    ->send();
            }
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
            'current_step' => $step,
        ]);
    }
}
