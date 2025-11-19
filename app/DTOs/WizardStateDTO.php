<?php

namespace App\DTOs;

/**
 * DTO para representar el estado del wizard
 */
class WizardStateDTO
{
    public function __construct(
        public readonly ?int $agreementId,
        public readonly int $currentStep,
        public readonly array $data,
        public readonly bool $hasExistingProposal = false
    ) {}

    /**
     * Crea una instancia desde un array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            agreementId: $data['agreement_id'] ?? null,
            currentStep: $data['current_step'] ?? 1,
            data: $data['data'] ?? [],
            hasExistingProposal: $data['has_existing_proposal'] ?? false
        );
    }

    /**
     * Convierte el DTO a array
     */
    public function toArray(): array
    {
        return [
            'agreement_id' => $this->agreementId,
            'current_step' => $this->currentStep,
            'data' => $this->data,
            'has_existing_proposal' => $this->hasExistingProposal,
        ];
    }

    /**
     * Verifica si el wizard está en un nuevo convenio
     */
    public function isNewAgreement(): bool
    {
        return $this->agreementId === null;
    }

    /**
     * Verifica si el wizard está en el paso de calculadora o posterior
     */
    public function isInCalculatorStep(): bool
    {
        return $this->currentStep >= 4;
    }
}
