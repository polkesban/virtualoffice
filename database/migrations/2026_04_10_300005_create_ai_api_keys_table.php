<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('label');                        // e.g. "Akun Gemini #1"
            $table->string('provider', 20)->default('gemini'); // 'openai' | 'gemini'
            $table->text('api_key');                        // encrypted at-rest via model cast
            $table->unsignedTinyInteger('priority')->default(0); // lower = tried first
            $table->boolean('is_active')->default(true);
            $table->boolean('is_exhausted')->default(false);    // quota/429 hit
            $table->timestamp('exhausted_at')->nullable();      // when it got exhausted
            $table->timestamp('last_used_at')->nullable();      // last successful call
            $table->unsignedInteger('fail_count')->default(0);  // consecutive failures
            $table->timestamps();

            $table->index(['provider', 'is_active', 'is_exhausted', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_api_keys');
    }
};
