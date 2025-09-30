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
            $table->foreignId('agreement_id')->constrained('agreements')->onDelete('cascade');
            $table->string('document_type', 50)->index(); // titular_ine, propiedad_instrumento_notarial, etc.
            $table->enum('document_category', ['titular', 'propiedad'])->index();
            $table->string('file_name');
            $table->string('file_path', 500);
            $table->integer('file_size')->nullable();
            $table->boolean('is_validated')->default(false);
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validated_at')->nullable();
            $table->text('validation_notes')->nullable();
            $table->timestamps();

            $table->index(['agreement_id', 'document_category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_documents');
    }
};
