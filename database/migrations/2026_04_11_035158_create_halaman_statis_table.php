<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('halaman_statis', function (Blueprint $table) {
            $table->id();
            $table->string('kunci', 60)->unique(); // visi_misi, sejarah, akreditasi, kerjasama, perpustakaan, penelitian, asrama
            $table->string('judul');
            $table->longText('konten')->nullable();
            $table->json('data_ekstra')->nullable(); // flexible JSON untuk konten terstruktur
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('halaman_statis');
    }
};
