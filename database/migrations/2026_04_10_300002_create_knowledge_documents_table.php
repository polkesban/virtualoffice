<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->string('name');                             // display name (admin-given)
            $table->string('original_filename');                // original upload filename
            $table->string('file_path');                        // storage path (local disk)
            $table->enum('file_type', ['pdf', 'md', 'txt']);
            $table->enum('status', ['pending', 'indexing', 'indexed', 'error'])->default('pending');
            $table->unsignedSmallInteger('chunk_count')->default(0);
            $table->text('description')->nullable();            // admin notes
            $table->text('error_message')->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_documents');
    }
};
