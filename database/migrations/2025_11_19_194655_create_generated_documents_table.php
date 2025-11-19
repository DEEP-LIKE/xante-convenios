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
            $table->foreignId('agreement_id')->constrained()->onDelete('cascade');
            
            $table->string('document_type'); // 'convenio', 'checklist', 'anexo'
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index('agreement_id');
            $table->index('document_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
