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
        Schema::create('portofolio_mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->string('judul');
            $table->enum('kategori', ['prestasi','organisasi','penelitian','magang','karya','sertifikasi','lainnya']);
            $table->text('deskripsi')->nullable();
            $table->string('institusi')->nullable();       // penyelenggara / mitra
            $table->date('tanggal');
            $table->string('lampiran')->nullable();        // path file/foto bukti
            $table->string('url_eksternal')->nullable();   // link sertifikat online dll
            $table->enum('tingkat', ['kampus','kota','provinsi','nasional','internasional'])->default('kampus');
            $table->boolean('tampilkan')->default(true);   // publik di profil mahasiswa
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portofolio_mahasiswa');
    }
};
