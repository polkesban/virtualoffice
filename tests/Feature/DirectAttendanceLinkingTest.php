<?php

namespace Tests\Feature;

use App\Models\UnitEvent;
use App\Models\UnitEventAttendance;
use App\Models\UnitEventParticipant;
use App\Models\UnitPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectAttendanceLinkingTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitting_direct_attendance_creates_participant_and_links_attendance(): void
    {
        $unitPage = UnitPage::create([
            'slug' => 'test-unit',
            'judul' => 'Test Unit',
            'tipe' => 'unit',
            'aktif' => true,
        ]);

        $event = UnitEvent::create([
            'unit_page_id' => $unitPage->id,
            'slug' => 'test-event-direct',
            'judul' => 'Test Event Direct',
            'status' => 'published',
            'daftar_hadir_aktif' => true,
            'allow_direct_attendance' => true,
            'mulai_at' => now(),
            'selesai_at' => now()->addDay(),
        ]);

        $todayStr = now()->toDateString();
        $sampleSignature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->post(route('unit.events.attendance.direct.submit', ['unitSlug' => $unitPage->slug, 'eventSlug' => $event->slug]), [
            'form_type' => 'daftar_hadir',
            'nama' => 'Budi Santoso Direct',
            'email' => 'budi.direct@example.com',
            'no_hp' => '081234567890',
            'instansi_type' => 'poltekkes',
            'unit_kerja' => 'Subbag ADAK',
            'attendance_status' => 'hadir',
            'attendance_date' => $todayStr,
            'signature_data' => $sampleSignature,
        ]);

        $response->assertRedirect();

        $participant = UnitEventParticipant::where('unit_event_id', $event->id)
            ->where('email', 'budi.direct@example.com')
            ->first();

        $this->assertNotNull($participant);
        $this->assertEquals('Budi Santoso Direct', $participant->nama);

        $attendance = UnitEventAttendance::where('unit_event_id', $event->id)
            ->where('unit_event_participant_id', $participant->id)
            ->first();

        $this->assertNotNull($attendance);
        $this->assertEquals($todayStr, $attendance->attendance_date->toDateString());
    }
}
