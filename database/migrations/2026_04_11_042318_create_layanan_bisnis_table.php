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
        Schema::create('layanan_bisnis', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('slug')->unique();
            $table->text('deskripsi')->nullable();
            $table->decimal('harga', 12, 2)->nullable();
            $table->string('satuan_harga', 50)->nullable();  // per orang, per paket, per jam
            $table->string('gambar')->nullable();
            $table->string('link_pesan')->nullable();        // WhatsApp / form
            $table->string('kategori', 50)->default('layanan'); // layanan, produk, catering, dll
            $table->tinyInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layanan_bisnis');
    }
};
