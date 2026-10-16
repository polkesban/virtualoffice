<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_rubrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('unit_page_id')->nullable()->constrained('unit_pages')->nullOnDelete();
            $table->foreignId('unit_event_id')->nullable()->constrained('unit_events')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('judul', 255);
            $table->string('slug', 255)->unique();
            $table->text('deskripsi')->nullable();
            $table->string('kategori', 50)->default('umum'); // pbm, seminar, praktikum, kinerja, umum
            $table->unsignedTinyInteger('skala_max')->default(4); // e.g. 4 (1-4) or 5 (1-5) or 100
            $table->boolean('is_template')->default(false);
            $table->timestamps();
        });

        Schema::create('evaluation_rubric_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evaluation_rubric_id')->constrained('evaluation_rubrics')->cascadeOnDelete();
            $table->string('nama_kriteria', 255);
            $table->text('deskripsi')->nullable();
            $table->decimal('bobot', 5, 2)->default(0.00); // percentage weight e.g. 25.00
            $table->unsignedInteger('urutan')->default(0);
            $table->json('levels')->nullable(); // [{"skor": 4, "label": "Sangat Baik", "deskripsi": "..."}, ...]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_rubric_items');
        Schema::dropIfExists('evaluation_rubrics');
    }
};
