<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_events', function (Blueprint $table) {
            $table->string('sub_jenis_kegiatan')->nullable()->after('jenis_kegiatan');
            $table->json('shifts')->nullable()->after('sub_jenis_kegiatan');
        });

        Schema::table('unit_event_attendances', function (Blueprint $table) {
            $table->string('shift_name')->nullable()->after('attendance_date');
            $table->timestamp('check_out_at')->nullable()->after('signed_at');
            $table->string('check_out_signature_path')->nullable()->after('signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('unit_events', function (Blueprint $table) {
            $table->dropColumn(['sub_jenis_kegiatan', 'shifts']);
        });

        Schema::table('unit_event_attendances', function (Blueprint $table) {
            $table->dropColumn(['shift_name', 'check_out_at', 'check_out_signature_path']);
        });
    }
};
