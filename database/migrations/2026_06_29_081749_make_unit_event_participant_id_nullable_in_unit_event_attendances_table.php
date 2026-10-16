<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_event_attendances', function (Blueprint $table) {
            $table->dropForeign(['unit_event_participant_id']);
        });

        Schema::table('unit_event_attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_event_participant_id')->nullable()->change();
        });

        Schema::table('unit_event_attendances', function (Blueprint $table) {
            $table->foreign('unit_event_participant_id')
                ->references('id')
                ->on('unit_event_participants')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_event_attendances', function (Blueprint $table) {
            $table->dropForeign(['unit_event_participant_id']);
        });

        Schema::table('unit_event_attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_event_participant_id')->nullable(false)->change();
        });

        Schema::table('unit_event_attendances', function (Blueprint $table) {
            $table->foreign('unit_event_participant_id')
                ->references('id')
                ->on('unit_event_participants')
                ->cascadeOnDelete();
        });
    }
};
