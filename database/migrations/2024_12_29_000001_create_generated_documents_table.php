<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained('agreements')->onDelete('cascade');
            $table->string('document_type', 50)->index(); // acuerdo_promocion, datos_generales, etc.
            $table->string('document_name');
            $table->string('file_path', 500);
            $table->integer('file_size')->nullable();
            $table->string('template_used', 100)->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index(['agreement_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
