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
        Schema::create('quote_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('agreement_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('authorized_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('change_type', ['commission', 'price', 'both']);
            
            // Campos para cambio de comisión
            $table->decimal('old_commission_percentage', 5, 2)->nullable();
            $table->decimal('new_commission_percentage', 5, 2)->nullable();
            
            // Campos para cambio de precio
            $table->decimal('old_price', 15, 2)->nullable();
            $table->decimal('new_price', 15, 2)->nullable();
            $table->decimal('discount_amount', 15, 2)->nullable();
            $table->text('discount_reason')->nullable();
            
            // Campos de respuesta
            $table->text('rejection_reason')->nullable();
            $table->timestamp('authorized_at')->nullable();
            
            $table->timestamps();
            
            // Índices para mejorar rendimiento
            $table->index(['status', 'change_type']);
            $table->index('requested_by');
            $table->index('authorized_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_authorizations');
    }
};
