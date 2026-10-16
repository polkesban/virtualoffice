<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unit_events')) {
            return;
        }

        Schema::table('unit_events', function (Blueprint $table) {
            $table->string('attendance_print_start_time', 5)->nullable()->after('selesai_at');
            $table->string('attendance_print_end_time', 5)->nullable()->after('attendance_print_start_time');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('unit_events')) {
            return;
        }

        Schema::table('unit_events', function (Blueprint $table) {
            $table->dropColumn([
                'attendance_print_start_time',
                'attendance_print_end_time',
            ]);
        });
    }
};
