<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unit_events')) {
            return;
        }

        Schema::create('unit_event_documentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_event_id')->unique()->constrained('unit_events')->cascadeOnDelete();
            $table->longText('transcript_text')->nullable();
            $table->longText('minutes_html')->nullable();
            $table->longText('ai_summary_html')->nullable();
            $table->longText('report_html')->nullable();
            $table->text('report_notes')->nullable();
            $table->string('ai_model_used', 120)->nullable();
            $table->dateTime('ai_summary_generated_at')->nullable();
            $table->dateTime('report_generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('unit_event_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_event_id')->constrained('unit_events')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('caption')->nullable();
            $table->string('image_path');
            $table->dateTime('taken_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->timestamps();

            $table->index(['unit_event_id', 'is_cover']);
            $table->index(['unit_event_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_event_photos');
        Schema::dropIfExists('unit_event_documentations');
    }
};
