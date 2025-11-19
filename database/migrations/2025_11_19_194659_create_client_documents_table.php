<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained()->onDelete('cascade');
            
            $table->string('document_type'); // 'titular_ine', 'propiedad_predial', etc.
            $table->string('document_name');
            $table->string('category'); // 'titular', 'propiedad'
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->nullable();
            
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index('agreement_id');
            $table->index('document_type');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_documents');
    }
};
