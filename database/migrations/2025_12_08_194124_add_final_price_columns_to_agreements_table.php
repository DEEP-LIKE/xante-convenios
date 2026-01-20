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
            $table->unsignedBigInteger('final_price_authorization_id')
                ->nullable()
                ->after('can_generate_documents');

            $table->decimal('final_offer_price', 15, 2)
                ->nullable()
                ->after('final_price_authorization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn(['final_price_authorization_id', 'final_offer_price']);
        });
    }
};
