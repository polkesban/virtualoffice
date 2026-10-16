<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('unit_pages')) {
            return;
        }

        Schema::create('unit_pages', function (Blueprint $table) {
            $table->id();
            $table->string('role')->nullable()->unique();
            $table->string('slug')->unique();
            $table->string('judul');
            $table->string('sub_judul', 500)->nullable();
            $table->string('cover_foto')->nullable();
            $table->string('warna_tema', 20)->nullable();
            $table->boolean('aktif')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_pages');
    }
};