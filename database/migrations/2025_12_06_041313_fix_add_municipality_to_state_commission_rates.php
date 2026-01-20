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
        Schema::table('state_commission_rates', function (Blueprint $table) {
            if (! Schema::hasColumn('state_commission_rates', 'municipality')) {
                $table->string('municipality', 100)->nullable()->after('state_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('state_commission_rates', function (Blueprint $table) {
            if (Schema::hasColumn('state_commission_rates', 'municipality')) {
                $table->dropColumn('municipality');
            }
        });
    }
};
