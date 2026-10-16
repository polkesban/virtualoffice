<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_event_ai_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_event_id')->constrained('unit_events')->cascadeOnDelete();
            $table->string('title');
            $table->longText('content_html')->nullable();
            $table->text('ai_prompt')->nullable();
            $table->string('reference_filename')->nullable();
            $table->longText('reference_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_event_ai_documents');
    }
};
