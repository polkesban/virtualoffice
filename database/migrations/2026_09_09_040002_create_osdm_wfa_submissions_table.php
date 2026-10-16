<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('osdm_wfa_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->index();
            $table->string('employee_name');
            $table->string('unit_kerja')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('nomor_wa')->nullable();
            $table->date('tanggal_wfa')->index();
            $table->text('nama_kegiatan');
            $table->string('lokasi_kegiatan');
            $table->string('lokasi_lahan_bimbingan');
            $table->string('status_wfa')->comment('WFA Datang | WFA Pulang | WFA Datang & WFA Pulang');
            $table->text('link_surat_tugas')->nullable();
            $table->string('status')->default('Menunggu Validasi')->comment('Menunggu Validasi | Valid | Ditolak');
            $table->text('catatan_pengelola')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->string('validated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('osdm_wfa_submissions');
    }
};
