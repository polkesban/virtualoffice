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
        Schema::create('program_studi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jurusan_id')->nullable();
            $table->string('nama');                        // D4 Keperawatan
            $table->string('singkatan', 20);              // D4 Kep
            $table->enum('jenjang', ['D3', 'D4']);
            $table->string('slug')->unique();
            $table->string('icon_emoji', 10)->nullable();
            $table->text('deskripsi_singkat')->nullable();
            $table->longText('deskripsi')->nullable();
            $table->string('akreditasi', 10)->default('A'); // A, B, Unggul
            $table->unsignedSmallInteger('durasi_semester')->default(6);
            $table->unsignedInteger('kuota')->default(60);
            $table->string('gelar', 30)->nullable();       // S.Tr.Kep
            $table->string('gambar')->nullable();
            $table->boolean('aktif')->default(true);
            $table->unsignedTinyInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_studi');
    }
};
