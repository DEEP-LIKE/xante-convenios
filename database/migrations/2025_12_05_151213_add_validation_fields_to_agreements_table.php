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
        Schema::table('agreements', function (Blueprint $table) {
            $table->enum('validation_status', [
                'not_required',
                'pending',
                'approved',
                'rejected',
                'with_observations',
            ])->default('not_required')->after('status');

            $table->foreignId('current_validation_id')->nullable()->constrained('quote_validations')->onDelete('set null')->after('validation_status');
            $table->boolean('can_generate_documents')->default(false)->after('current_validation_id');

            // Índice para búsquedas por estado de validación
            $table->index('validation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropForeign(['current_validation_id']);
            $table->dropColumn(['validation_status', 'current_validation_id', 'can_generate_documents']);
        });
    }
};
