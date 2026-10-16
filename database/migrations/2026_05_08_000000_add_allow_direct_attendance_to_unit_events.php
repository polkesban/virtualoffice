<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_events', function (Blueprint $table) {
            $table->boolean('allow_direct_attendance')->default(false)->after('daftar_hadir_aktif')->comment('Izinkan peserta langsung isi daftar hadir tanpa registrasi');
        });
    }

    public function down(): void
    {
        Schema::table('unit_events', function (Blueprint $table) {
            $table->dropColumn('allow_direct_attendance');
        });
    }
};
