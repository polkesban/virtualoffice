<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('osdm_kebugaran_submissions', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_periksa');
            $table->string('periode')->comment('Triwulan I s.d Triwulan IV');
            $table->string('nip')->index();
            $table->string('nama_pegawai');
            $table->string('tanggal_lahir')->nullable();
            $table->string('unit_kerja');
            $table->string('nik')->nullable();
            $table->unsignedSmallInteger('tensi_sistolik');
            $table->unsignedSmallInteger('tensi_diastolik');
            $table->decimal('berat_badan', 5, 1);
            $table->decimal('tinggi_badan', 5, 1);
            $table->decimal('lingkar_pinggang', 5, 1);
            $table->string('tipe_gula_darah')->comment('GDS | GDP');
            $table->unsignedSmallInteger('gula_darah');
            $table->unsignedSmallInteger('kolesterol');
            $table->string('nomor_wa')->nullable();
            $table->string('fasyankes')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('osdm_kebugaran_submissions');
    }
};
