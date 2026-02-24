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
        Schema::table('agreement_recalculations', function (Blueprint $table) {
            $table->foreignId('authorized_by')->nullable()->constrained('users')->onDelete('set null')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreement_recalculations', function (Blueprint $table) {
            $table->dropForeign(['authorized_by']);
            $table->dropColumn('authorized_by');
        });
    }
};
