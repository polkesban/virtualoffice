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
        Schema::table('unit_events', function (Blueprint $table) {
            $table->boolean('berita_acara_form_aktif')->default(false)->after('berita_acara_ringkasan');
            $table->string('penandatangan_jabatan')->nullable()->after('berita_acara_form_aktif');
            $table->string('penandatangan_nama')->nullable()->after('penandatangan_jabatan');
            $table->string('penandatangan_nip')->nullable()->after('penandatangan_nama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_events', function (Blueprint $table) {
            $table->dropColumn(['berita_acara_form_aktif', 'penandatangan_jabatan', 'penandatangan_nama', 'penandatangan_nip']);
        });
    }
};
