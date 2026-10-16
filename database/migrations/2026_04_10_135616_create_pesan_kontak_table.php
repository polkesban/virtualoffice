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
        Schema::create('pesan_kontak', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('email', 255);
            $table->string('telepon', 20)->nullable();
            $table->enum('topik', ['umum', 'pendaftaran', 'akademik', 'kerjasama', 'lainnya'])->default('umum');
            $table->string('subjek', 200);
            $table->text('pesan');
            $table->string('ip_address', 45)->nullable();
            $table->boolean('sudah_dibaca')->default(false);
            $table->timestamp('dibaca_at')->nullable();
            $table->text('balasan')->nullable();
            $table->timestamp('dibalas_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesan_kontak');
    }
};
