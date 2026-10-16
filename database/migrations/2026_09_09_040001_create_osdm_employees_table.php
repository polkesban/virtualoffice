<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('osdm_employees', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->unique()->index();
            $table->string('name');
            $table->string('unit_kerja');
            $table->string('jabatan');
            $table->string('email')->nullable();
            $table->string('nomor_wa')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('nik')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('osdm_employees');
    }
};
