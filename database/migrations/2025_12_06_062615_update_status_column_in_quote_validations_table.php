<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Usamos raw SQL para modificar el enum, es más seguro que Doctrine para enums en MySQL
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE quote_validations MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'with_observations', 'awaiting_management_authorization') NOT NULL DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE quote_validations MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'with_observations') NOT NULL DEFAULT 'pending'");
        }
    }
};
