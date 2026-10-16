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
        Schema::create('mahasiswa_berprestasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->enum('periode', ['bulan','semester','tahun'])->default('bulan');
            $table->string('periode_label');               // e.g. 'April 2026', 'Ganjil 2025/2026'
            $table->text('catatan_admin')->nullable();     // alasan/highlight dari admin
            $table->boolean('aktif')->default(true);       // yang tampil di beranda
            $table->unsignedTinyInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mahasiswa_berprestasi');
    }
};
