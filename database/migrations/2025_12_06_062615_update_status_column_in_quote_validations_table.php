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
        // Usamos raw SQL para modificar el enum, es más seguro que Doctrine para enums en MySQL
        DB::statement("ALTER TABLE quote_validations MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'with_observations', 'awaiting_management_authorization') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE quote_validations MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'with_observations') NOT NULL DEFAULT 'pending'");
    }
};
