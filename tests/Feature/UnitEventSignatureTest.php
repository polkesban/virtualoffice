<?php

namespace Tests\Feature;

use App\Models\UnitEvent;
use App\Models\UnitPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitEventSignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_penandatangan_details(): void
    {
        [$admin, $page, $event] = $this->createAdminEventContext();

        $response = $this->actingAs($admin)->put(route('admin.unit-events.update', [$page, $event]), [
            'judul' => 'Event Terkustomisasi',
            'slug' => $event->slug,
            'jenis_kegiatan' => $event->jenis_kegiatan,
            'metode' => $event->metode,
            'status' => $event->status,
            'lokasi' => $event->lokasi,
            'mulai_at' => $event->mulai_at->toDateTimeString(),
            'selesai_at' => $event->selesai_at?->toDateTimeString(),
            'registrasi_aktif' => '1',
            'daftar_hadir_aktif' => '1',
            'berita_acara_form_aktif' => '1',
            'penandatangan_jabatan' => 'Ketua Panitia Pelaksana',
            'penandatangan_nama' => 'Dr. H. Ahmad Fauzi, M.T.',
            'penandatangan_nip' => '198504122010121003',
            'berita_acara_judul' => 'Berita Acara Sosialisasi Panduan',
            'berita_acara_ringkasan' => 'Kegiatan sosialisasi berjalan lancar.',
        ]);

        $response->assertRedirect(route('admin.unit-events.edit', [$page, $event]));
        $event->refresh();

        $this->assertTrue($event->berita_acara_form_aktif);
        $this->assertSame('Ketua Panitia Pelaksana', $event->penandatangan_jabatan);
        $this->assertSame('Dr. H. Ahmad Fauzi, M.T.', $event->penandatangan_nama);
        $this->assertSame('198504122010121003', $event->penandatangan_nip);
        $this->assertSame('Berita Acara Sosialisasi Panduan', $event->berita_acara_judul);
        $this->assertSame('Kegiatan sosialisasi berjalan lancar.', $event->berita_acara_ringkasan);
    }

    public function test_attendance_print_displays_custom_penandatangan_details(): void
    {
        [$admin, $page, $event] = $this->createAdminEventContext();

        // Update with custom details
        $event->update([
            'penandatangan_jabatan' => 'Ketua Panitia Pelaksana',
            'penandatangan_nama' => 'Dr. H. Ahmad Fauzi, M.T.',
            'penandatangan_nip' => '198504122010121003',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.unit-events.participants.attendance-print', [$page, $event]));

        $response->assertOk();
        $response->assertSee('Mengetahui,');
        $response->assertSee('Ketua Panitia Pelaksana');
        $response->assertSee('Dr. H. Ahmad Fauzi, M.T.');
        $response->assertSee('198504122010121003');
    }

    public function test_attendance_print_falls_back_to_defaults_when_empty(): void
    {
        [$admin, $page, $event] = $this->createAdminEventContext();

        // Ensure signature details are null
        $event->update([
            'penandatangan_jabatan' => null,
            'penandatangan_nama' => null,
            'penandatangan_nip' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.unit-events.participants.attendance-print', [$page, $event]));

        $response->assertOk();
        $response->assertSee('Mengetahui,');
        $response->assertSee('Panitia / Penanggung Jawab');
        $response->assertSee('..............................................');
    }

    public function test_overtime_attendance_print_displays_custom_penandatangan_details(): void
    {
        [$admin, $page, $event] = $this->createAdminEventContext();

        // Update with custom details
        $event->update([
            'penandatangan_jabatan' => 'Kabag OSDM Poltekkes Bandung',
            'penandatangan_nama' => 'Hj. Siti Aminah, S.Sos.',
            'penandatangan_nip' => '197708152002122002',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.unit-events.participants.attendance-print', [$page, $event, 'format' => 'lembur']));

        $response->assertOk();
        $response->assertSee('Mengetahui,');
        $response->assertSee('Kabag OSDM Poltekkes Bandung');
        $response->assertSee('Hj. Siti Aminah, S.Sos.');
        $response->assertSee('197708152002122002');
    }

    public function test_overtime_attendance_print_falls_back_to_defaults_when_empty(): void
    {
        [$admin, $page, $event] = $this->createAdminEventContext();

        // Ensure signature details are null
        $event->update([
            'penandatangan_jabatan' => null,
            'penandatangan_nama' => null,
            'penandatangan_nip' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.unit-events.participants.attendance-print', [$page, $event, 'format' => 'lembur']));

        $response->assertOk();
        $response->assertSee('Mengetahui,');
        $response->assertSee('Direktur Poltekkes Kemenkes Bandung');
        $response->assertSee('Dr. Pramita Iriana, S.Kep., M.Biomed');
        $response->assertSee('197009211996032001');
    }

    public function test_berita_acara_displays_custom_penandatangan_details(): void
    {
        [$admin, $page, $event] = $this->createAdminEventContext();

        // Update with custom details
        $event->update([
            'penandatangan_jabatan' => 'Ketua Jurusan Kesehatan',
            'penandatangan_nama' => 'Dr. Budi Hartono, M.T.',
            'penandatangan_nip' => '198811122015041002',
        ]);

        $participant = \App\Models\UnitEventParticipant::create([
            'unit_event_id' => $event->id,
            'peran' => 'participant',
            'participant_category' => 'public',
            'nama' => 'Test Participant',
            'email' => 'participant@test.com',
            'kode_registrasi' => 'EVT-PART01',
            'access_token' => 'token-part-01',
            'registered_at' => now(),
        ]);

        \App\Models\UnitEventAttendance::create([
            'unit_event_id' => $event->id,
            'unit_event_participant_id' => $participant->id,
            'attendance_date' => $event->mulai_at->toDateString(),
            'signed_at' => now(),
            'nama' => 'Test Direct Attendee',
            'attendance_status' => 'hadir',
        ]);

        $response = $this->get(route('unit.events.berita-acara', [$page->slug, $event->slug]));

        $response->assertOk();
        $response->assertSee('Mengetahui,');
        $response->assertSee('Ketua Jurusan Kesehatan');
        $response->assertSee('Dr. Budi Hartono, M.T.');
        $response->assertSee('198811122015041002');
    }

    public function test_berita_acara_falls_back_to_defaults_when_empty(): void
    {
        [$admin, $page, $event] = $this->createAdminEventContext();

        // Ensure signature details are null
        $event->update([
            'penandatangan_jabatan' => null,
            'penandatangan_nama' => null,
            'penandatangan_nip' => null,
        ]);

        $participant = \App\Models\UnitEventParticipant::create([
            'unit_event_id' => $event->id,
            'peran' => 'participant',
            'participant_category' => 'public',
            'nama' => 'Test Participant',
            'email' => 'participant@test.com',
            'kode_registrasi' => 'EVT-PART01',
            'access_token' => 'token-part-01',
            'registered_at' => now(),
        ]);

        \App\Models\UnitEventAttendance::create([
            'unit_event_id' => $event->id,
            'unit_event_participant_id' => $participant->id,
            'attendance_date' => $event->mulai_at->toDateString(),
            'signed_at' => now(),
            'nama' => 'Test Direct Attendee',
            'attendance_status' => 'hadir',
        ]);

        $response = $this->get(route('unit.events.berita-acara', [$page->slug, $event->slug]));

        $response->assertOk();
        $response->assertSee('Mengetahui,');
        $response->assertSee('Panitia / Penanggung Jawab');
        $response->assertSee('..............................................');
    }

    public function test_direct_berita_acara_signing_form_is_accessible(): void
    {
        [$admin, $page, $event] = $this->createAdminEventContext();

        // Enable berita_acara_form_aktif but keep allow_direct_attendance false
        $event->update([
            'daftar_hadir_aktif' => false,
            'allow_direct_attendance' => false,
            'berita_acara_form_aktif' => true,
        ]);

        $response = $this->get(route('unit.events.attendance.direct.show', [
            'unitSlug' => $page->slug,
            'eventSlug' => $event->slug,
            'form_type' => 'berita_acara'
        ]));

        $response->assertOk();
        $response->assertSee('Penandatanganan Berita Acara');
    }



    private function createAdminEventContext(): array
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $page = UnitPage::create([
            'role' => 'unit_ti',
            'slug' => 'unit-ti',
            'judul' => 'Unit Teknologi Informasi',
            'warna_tema' => '#2563eb',
            'aktif' => true,
        ]);

        $event = UnitEvent::create([
            'unit_page_id' => $page->id,
            'created_by' => $admin->id,
            'judul' => 'Seminar Manajemen Event',
            'slug' => 'seminar-manajemen-event',
            'jenis_kegiatan' => 'webinar',
            'metode' => 'offline',
            'status' => 'published',
            'lokasi' => 'Aula Utama',
            'mulai_at' => now()->addDay(),
            'selesai_at' => now()->addDay()->addHours(3),
            'registrasi_aktif' => true,
            'daftar_hadir_aktif' => true,
            'published_at' => now(),
        ]);

        return [$admin, $page, $event];
    }
}
