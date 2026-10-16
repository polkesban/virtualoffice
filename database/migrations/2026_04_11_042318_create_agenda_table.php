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
        Schema::create('agenda', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->string('waktu_mulai', 10)->nullable();  // HH:MM
            $table->string('waktu_selesai', 10)->nullable();
            $table->string('lokasi')->nullable();
            $table->string('kategori', 30)->default('umum'); // umum, akademik, kemahasiswaan, penelitian, wisuda
            $table->string('warna', 20)->default('blue');    // blue, green, red, yellow, purple
            $table->boolean('publik')->default(true);        // tampil di halaman publik
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda');
    }
};
