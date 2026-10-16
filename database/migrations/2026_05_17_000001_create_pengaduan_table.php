<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaduan', function (Blueprint $table) {
            $table->id();

            // Pengirim
            $table->string('nama', 100);
            $table->string('email', 255)->nullable();
            $table->string('telepon', 30)->nullable();

            // Tujuan (unit/bagian/prodi)
            $table->string('tujuan_unit', 100)->comment('role/key of unit page or prodi');
            $table->string('tujuan_label', 150)->comment('Human-readable label of destination');

            // Isi
            $table->enum('jenis', ['pengaduan', 'kritik', 'saran', 'keluhan'])->default('pengaduan');
            $table->string('subjek', 200);
            $table->text('isi');

            // Anonim flag
            $table->boolean('anonim')->default(false);

            // Teknis
            $table->string('ip_address', 45)->nullable();
            $table->string('nomor_tiket', 20)->unique()->comment('Auto-generated ticket number');

            // Status lifecycle
            $table->enum('status', ['masuk', 'diproses', 'selesai', 'ditolak'])->default('masuk');
            $table->timestamp('dibaca_at')->nullable();
            $table->timestamp('diproses_at')->nullable();
            $table->timestamp('diselesaikan_at')->nullable();

            // Tindak lanjut / balasan
            $table->text('catatan_internal')->nullable()->comment('Internal notes, not shown to submitter');
            $table->text('tindak_lanjut')->nullable()->comment('Action taken, shown to submitter');
            $table->foreignId('ditangani_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditangani_at')->nullable();

            $table->timestamps();

            $table->index('tujuan_unit');
            $table->index('status');
            $table->index('jenis');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaduan');
    }
};
