<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_event_attendances', function (Blueprint $table) {
            // Add new columns for direct attendance
            $table->string('nama')->nullable()->after('unit_event_participant_id')->comment('Nama peserta untuk direct attendance');
            $table->string('email')->nullable()->after('nama');
            $table->string('no_hp')->nullable()->after('email');
            $table->string('instansi')->nullable()->after('no_hp');
            $table->string('unit_kerja')->nullable()->after('instansi')->comment('Unit kerja/departemen peserta');
            $table->string('attendance_status')->default('hadir')->after('unit_kerja')->comment('Status kehadiran: hadir, izin, sakit, tidak_hadir');
        });
    }

    public function down(): void
    {
        Schema::table('unit_event_attendances', function (Blueprint $table) {
            $table->dropColumn(['nama', 'email', 'no_hp', 'instansi', 'unit_kerja', 'attendance_status']);
        });
    }
};
