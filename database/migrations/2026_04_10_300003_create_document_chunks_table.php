<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                  ->constrained('knowledge_documents')
                  ->cascadeOnDelete();
            $table->unsignedSmallInteger('chunk_index');
            $table->text('content');
            $table->mediumText('embedding')->nullable();        // JSON float[] — cosine similarity in PHP
            $table->unsignedSmallInteger('token_estimate')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['document_id', 'chunk_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
