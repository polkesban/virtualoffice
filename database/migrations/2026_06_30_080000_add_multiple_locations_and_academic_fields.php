<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_events', function (Blueprint $table) {
            $table->json('lokasi_praktik')->nullable()->after('lokasi');
            $table->string('tahun_akademik')->nullable()->after('selesai_at');
            $table->string('tingkat')->nullable()->after('tahun_akademik');
            $table->string('semester')->nullable()->after('tingkat');
        });

        Schema::table('unit_event_participants', function (Blueprint $table) {
            $table->string('tempat_praktik')->nullable()->after('instansi');
            $table->string('nama_pembimbing')->nullable()->after('tempat_praktik');
        });
    }

    public function down(): void
    {
        Schema::table('unit_events', function (Blueprint $table) {
            $table->dropColumn(['lokasi_praktik', 'tahun_akademik', 'tingkat', 'semester']);
        });

        Schema::table('unit_event_participants', function (Blueprint $table) {
            $table->dropColumn(['tempat_praktik', 'nama_pembimbing']);
        });
    }
};
