<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahasiswa_obe_portofolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->onDelete('cascade');
            $table->string('tahun_akademik', 20)->default('2025/2026');
            $table->integer('semester')->default(1);
            $table->string('kelas', 50)->nullable();
            $table->string('dosen_pa', 150)->nullable();
            $table->string('lahan_praktik', 200)->nullable();
            $table->string('pembimbing_lahan', 150)->nullable();
            
            // Pengesahan
            $table->string('ketua_jurusan', 150)->nullable();
            $table->string('nip_ketua_jurusan', 50)->nullable();
            $table->string('ketua_prodi', 150)->nullable();
            $table->string('nip_ketua_prodi', 50)->nullable();

            // Profil MK & OBE Data (bridging-ready JSON fields)
            $table->string('kode_mk', 50)->nullable();
            $table->string('nama_mk', 200)->nullable();
            $table->integer('sks')->default(3);
            $table->text('deskripsi_mk')->nullable();
            $table->text('metode_pembelajaran')->nullable();
            $table->text('metode_penilaian')->nullable();
            $table->json('cpl_list')->nullable();
            $table->json('cpmk_list')->nullable();
            $table->json('sub_cpmk_list')->nullable();
            $table->json('materi_pembelajaran')->nullable();
            $table->json('bobot_penilaian')->nullable();

            // Nilai MK
            $table->decimal('nilai_angka', 5, 2)->nullable();
            $table->decimal('nilai_mutu', 3, 2)->nullable();
            $table->string('nilai_lambang', 5)->nullable();

            // Umpan Balik Pembimbing
            $table->text('kelebihan_mahasiswa')->nullable();
            $table->text('aspek_perlu_ditingkatkan')->nullable();
            $table->text('rekomendasi_pembimbing')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mahasiswa_obe_portofolios');
    }
};
