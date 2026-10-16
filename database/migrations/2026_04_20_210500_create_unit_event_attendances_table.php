<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unit_events') || ! Schema::hasTable('unit_event_participants')) {
            return;
        }

        Schema::create('unit_event_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_event_id')->constrained('unit_events')->cascadeOnDelete();
            $table->foreignId('unit_event_participant_id')->constrained('unit_event_participants')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->dateTime('signed_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamps();

            $table->unique(['unit_event_participant_id', 'attendance_date'], 'unit_event_attendance_unique_day');
            $table->index(['unit_event_id', 'attendance_date'], 'unit_event_attendance_event_date');
        });

        $legacyAttendances = DB::table('unit_event_participants')
            ->join('unit_events', 'unit_events.id', '=', 'unit_event_participants.unit_event_id')
            ->whereNotNull('unit_event_participants.attended_at')
            ->select([
                'unit_event_participants.id as participant_id',
                'unit_event_participants.unit_event_id',
                'unit_event_participants.attended_at',
                'unit_event_participants.attendance_notes',
                'unit_events.mulai_at',
                'unit_events.selesai_at',
            ])
            ->orderBy('unit_event_participants.id')
            ->get();

        foreach ($legacyAttendances as $legacyAttendance) {
            $signedAt = Carbon::parse($legacyAttendance->attended_at);
            $attendanceDate = $signedAt->copy()->toDateString();
            $eventStart = Carbon::parse($legacyAttendance->mulai_at)->startOfDay();
            $eventEnd = $legacyAttendance->selesai_at
                ? Carbon::parse($legacyAttendance->selesai_at)->startOfDay()
                : $eventStart->copy();

            if ($signedAt->copy()->startOfDay()->lt($eventStart) || $signedAt->copy()->startOfDay()->gt($eventEnd)) {
                $attendanceDate = $eventStart->toDateString();
            }

            DB::table('unit_event_attendances')->updateOrInsert(
                [
                    'unit_event_participant_id' => $legacyAttendance->participant_id,
                    'attendance_date' => $attendanceDate,
                ],
                [
                    'unit_event_id' => $legacyAttendance->unit_event_id,
                    'signed_at' => $legacyAttendance->attended_at,
                    'notes' => $legacyAttendance->attendance_notes,
                    'signature_path' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_event_attendances');
    }
};