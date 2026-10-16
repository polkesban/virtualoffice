<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimoni_alumni', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 120);
            $table->string('email', 180);
            $table->string('telepon', 30)->nullable();
            $table->string('program_studi', 160);
            $table->unsignedSmallInteger('tahun_lulus');
            $table->string('instansi', 180)->nullable();
            $table->string('jabatan', 180)->nullable();
            $table->string('kota_domisili', 120)->nullable();
            $table->string('portofolio_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->text('testimoni');
            $table->boolean('bersedia_dihubungi')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['program_studi', 'tahun_lulus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimoni_alumni');
    }
};