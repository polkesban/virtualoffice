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
        Schema::table('layanan_bisnis', function (Blueprint $table) {
            if (! Schema::hasColumn('layanan_bisnis', 'is_tersedia')) {
                $table->boolean('is_tersedia')->default(true)->after('aktif');
            }
            if (! Schema::hasColumn('layanan_bisnis', 'stok')) {
                $table->integer('stok')->nullable()->after('is_tersedia');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('layanan_bisnis', function (Blueprint $table) {
            if (Schema::hasColumn('layanan_bisnis', 'stok')) {
                $table->dropColumn('stok');
            }
            if (Schema::hasColumn('layanan_bisnis', 'is_tersedia')) {
                $table->dropColumn('is_tersedia');
            }
        });
    }
};
