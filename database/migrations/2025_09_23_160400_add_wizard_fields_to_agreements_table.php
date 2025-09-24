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
            // Campos del wizard
            $table->integer('current_step')->default(1)->after('status');
            $table->json('wizard_data')->nullable()->after('current_step');
            $table->integer('completion_percentage')->default(0)->after('wizard_data');
            $table->foreignId('created_by')->nullable()->constrained('users')->after('completion_percentage');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->after('created_by');
            $table->timestamp('completed_at')->nullable()->after('assigned_to');

            // Índices para optimización
            $table->index('current_step');
            $table->index('completion_percentage');
            $table->index('created_by');
            $table->index('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['assigned_to']);
            $table->dropIndex(['current_step']);
            $table->dropIndex(['completion_percentage']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['assigned_to']);
            $table->dropColumn([
                'current_step',
                'wizard_data',
                'completion_percentage',
                'created_by',
                'assigned_to',
                'completed_at'
            ]);
        });
    }
};
