<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();

            // Relaciones principales
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('property_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('proposal_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('spouse_id')->nullable()->constrained()->onDelete('set null');

            // Usuarios responsables
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');

            // Estado del convenio
            $table->enum('status', [
                'draft',
                'documents_generating',
                'documents_generated',
                'documents_sent',
                'awaiting_client_docs',
                'documents_complete',
                'completed',
                'cancelled',
            ])->default('draft');

            // Control de wizards
            $table->tinyInteger('current_wizard')->default(1);
            $table->tinyInteger('current_step')->default(1);
            $table->tinyInteger('wizard2_current_step')->default(1);
            $table->integer('completion_percentage')->default(0);
            $table->boolean('can_return_to_wizard1')->default(true);

            // Datos financieros finales
            $table->decimal('agreement_value', 15, 2)->nullable();
            $table->decimal('proposal_value', 15, 2)->nullable();
            $table->decimal('commission_total', 15, 2)->nullable();
            $table->decimal('final_profit', 15, 2)->nullable();

            // Wizard data temporal (solo para datos en proceso)
            $table->json('wizard_data')->nullable();

            // Timestamps importantes del flujo
            $table->timestamp('documents_generated_at')->nullable();
            $table->timestamp('documents_sent_at')->nullable();
            $table->timestamp('documents_received_at')->nullable();
            $table->timestamp('proposal_saved_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices estratégicos
            $table->index('status');
            $table->index('current_wizard');
            $table->index(['status', 'current_wizard']);
            $table->index('client_id');
            $table->index('property_id');
            $table->index('created_by');
            $table->index('assigned_to');
            $table->index('completed_at');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};
