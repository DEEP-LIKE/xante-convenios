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
        Schema::table('quote_authorizations', function (Blueprint $table) {
            $table->string('change_type')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_authorizations', function (Blueprint $table) {
            $table->enum('change_type', ['commission', 'price', 'both'])->change();
        });
    }
};
