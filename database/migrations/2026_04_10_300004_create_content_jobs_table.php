<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();                // admin-provided title hint
            $table->string('media_path');                       // storage path (local disk)
            $table->enum('media_type', ['image', 'video']);
            $table->json('metadata');                           // {place, date, subject, kategori, notes, tags}
            $table->enum('status', ['pending', 'processing', 'done', 'error', 'published'])
                  ->default('pending');
            $table->text('ai_image_description')->nullable();   // vision API output
            $table->string('generated_title')->nullable();
            $table->longText('generated_article')->nullable();  // Markdown article
            $table->text('generated_caption')->nullable();      // Instagram caption
            $table->json('generated_hashtags')->nullable();
            $table->string('model_used')->nullable();
            $table->unsignedInteger('tokens_used')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_jobs');
    }
};
