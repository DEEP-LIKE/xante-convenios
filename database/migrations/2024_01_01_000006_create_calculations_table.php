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
        Schema::create('calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained()->onDelete('cascade');
            $table->decimal('value_without_escrow', 15, 2);
            $table->decimal('notarial_expenses', 15, 2);
            $table->decimal('purchase_value', 15, 2);
            $table->boolean('is_isr_exempt')->default(false);
            $table->decimal('difference_value', 15, 2)->nullable();
            $table->decimal('total_payment', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calculations');
    }
};
