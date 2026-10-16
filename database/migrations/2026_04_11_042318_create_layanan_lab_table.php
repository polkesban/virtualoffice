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
        Schema::create('layanan_lab', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->decimal('tarif', 12, 2)->nullable();
            $table->string('satuan', 50)->nullable();         // per sampel, per pemeriksaan, per jam
            $table->string('kategori', 50)->default('umum'); // kimia, biologi, mikrobiologi, fisika
            $table->string('lama_pengerjaan')->nullable();   // "1-2 hari kerja"
            $table->string('syarat')->nullable();            // persyaratan pengajuan
            $table->tinyInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layanan_lab');
    }
};
