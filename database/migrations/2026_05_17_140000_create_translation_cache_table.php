<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_cache', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 10)->index();
            $table->char('source_hash', 64);          // SHA-256 of trimmed source text
            $table->text('source_text');
            $table->text('translated_text');
            $table->string('source', 64)->default('ai')->comment('ai | glossary | manual');
            $table->timestamps();

            // Unique lookup key
            $table->unique(['locale', 'source_hash'], 'translation_cache_locale_hash_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_cache');
    }
};
