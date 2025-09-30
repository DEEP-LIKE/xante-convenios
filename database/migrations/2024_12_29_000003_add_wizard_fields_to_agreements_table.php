<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            // Campos para el sistema de dos wizards
            $table->timestamp('documents_generated_at')->nullable()->after('completed_at');
            $table->timestamp('documents_sent_at')->nullable()->after('documents_generated_at');
            $table->boolean('can_return_to_wizard1')->default(true)->after('documents_sent_at');
            $table->tinyInteger('current_wizard')->default(1)->after('can_return_to_wizard1');
            $table->tinyInteger('wizard2_current_step')->default(1)->after('current_wizard');
            
            // Índices para optimizar consultas
            $table->index(['status', 'current_wizard']);
            $table->index('can_return_to_wizard1');
        });
    }

    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropIndex(['status', 'current_wizard']);
            $table->dropIndex(['can_return_to_wizard1']);
            
            $table->dropColumn([
                'documents_generated_at',
                'documents_sent_at', 
                'can_return_to_wizard1',
                'current_wizard',
                'wizard2_current_step'
            ]);
        });
    }
};
