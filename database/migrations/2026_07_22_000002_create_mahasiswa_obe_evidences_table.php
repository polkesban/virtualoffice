<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahasiswa_obe_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obe_portofolio_id')->constrained('mahasiswa_obe_portofolios')->onDelete('cascade');
            $table->string('nama_dokumen', 200);
            $table->boolean('is_ada')->default(false);
            $table->string('file_path')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mahasiswa_obe_evidences');
    }
};
