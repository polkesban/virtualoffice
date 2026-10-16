<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wfh_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('tanggal');
            $table->text('catatan_wadir')->nullable()->comment('Catatan/feedback dari Wadir I/Administrator');
            $table->timestamps();

            // A user can only submit one report per day
            $table->unique(['user_id', 'tanggal']);
            $table->index('tanggal');
        });

        Schema::create('wfh_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wfh_report_id')->constrained('wfh_reports')->onDelete('cascade');
            $table->string('waktu', 50)->nullable()->comment('Waktu kegiatan (e.g. 07.30 - 09.00)');
            $table->text('kegiatan')->comment('Kegiatan / agenda');
            $table->text('hasil')->nullable()->comment('Hasil kegiatan');
            $table->text('kendala')->nullable()->comment('Kendala / masalah');
            $table->text('rtl')->nullable()->comment('Rencana Tindak Lanjut');
            $table->string('penanggung_jawab', 100)->nullable()->comment('Penanggung jawab');
            $table->string('target_waktu', 50)->nullable()->comment('Target waktu (e.g. 11.30)');
            $table->text('keterangan')->nullable()->comment('Keterangan tambahan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wfh_activities');
        Schema::dropIfExists('wfh_reports');
    }
};
