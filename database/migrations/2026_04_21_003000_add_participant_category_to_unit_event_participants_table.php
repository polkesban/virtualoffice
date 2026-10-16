<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unit_event_participants')) {
            return;
        }

        Schema::table('unit_event_participants', function (Blueprint $table) {
            $table->string('participant_category', 20)->default('public')->after('peran')->index();
        });

        DB::table('unit_event_participants')
            ->whereNotNull('linked_mahasiswa_id')
            ->update(['participant_category' => 'student']);

        DB::table('unit_event_participants')
            ->whereNull('linked_mahasiswa_id')
            ->whereNotNull('linked_user_id')
            ->update(['participant_category' => 'employee']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('unit_event_participants')) {
            return;
        }

        Schema::table('unit_event_participants', function (Blueprint $table) {
            $table->dropIndex(['participant_category']);
            $table->dropColumn('participant_category');
        });
    }
};