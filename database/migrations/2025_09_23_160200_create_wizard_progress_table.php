<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wizard_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained()->onDelete('cascade');
            $table->integer('step_number');
            $table->string('step_name');
            $table->boolean('is_completed')->default(false);
            $table->integer('completion_percentage')->default(0);
            $table->json('step_data')->nullable();
            $table->json('validation_errors')->nullable();
            $table->timestamp('last_saved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            // Índices
            $table->unique(['agreement_id', 'step_number']);
            $table->index(['agreement_id', 'is_completed']);
            $table->index('step_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wizard_progress');
    }
};
