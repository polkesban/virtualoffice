<?php

namespace Tests\Feature;

use App\Models\UnitEvent;
use App\Models\UnitEventAttendance;
use App\Models\UnitEventParticipant;
use App\Models\UnitPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PbmPraktikAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function getSamplePngDataUri(): string
    {
        $img = imagecreatetruecolor(200, 100);
        $bg = imagecolorallocate($img, 255, 255, 255);
        $fg = imagecolorallocate($img, 15, 23, 42);
        imagefilledrectangle($img, 0, 0, 200, 100, $bg);
        for ($i = 0; $i < 50; $i++) {
            imageline($img, $i * 4, 10 + ($i % 30), 200 - $i * 3, 80 - ($i % 25), $fg);
        }
        ob_start();
        imagepng($img);
        $binary = ob_get_clean();
        imagedestroy($img);
        return 'data:image/png;base64,' . base64_encode($binary);
    }

    public function test_pbm_praktik_attendance_form_loads_and_submits_datang_and_pulang(): void
    {
        $unitPage = UnitPage::create([
            'slug' => 'kaprodi',
            'judul' => 'Prodi Profesi Bidan',
            'tipe' => 'unit',
            'aktif' => true,
        ]);

        $event = UnitEvent::create([
            'unit_page_id' => $unitPage->id,
            'slug' => 'pbm-praktik-test',
            'judul' => 'PBM Praktik Kebidanan Komprehensif',
            'jenis_kegiatan' => 'pbm',
            'sub_jenis_kegiatan' => 'praktik',
            'status' => 'published',
            'daftar_hadir_aktif' => true,
            'allow_direct_attendance' => true,
            'mulai_at' => now()->subDays(2),
            'selesai_at' => now()->addDays(5),
        ]);

        $participant = UnitEventParticipant::create([
            'unit_event_id' => $event->id,
            'nama' => 'Siti Mahasiswi',
            'email' => 'siti@example.com',
            'kode_registrasi' => 'REG-SITI-01',
            'access_token' => 'test-token-siti-12345',
            'status' => 'confirmed',
        ]);

        // 1. Check form page renders successfully
        $formResponse = $this->get(route('unit.events.attendance.show', [
            'unitSlug' => $unitPage->slug,
            'eventSlug' => $event->slug,
            'token' => $participant->access_token,
        ]));
        $formResponse->assertOk();
        $formResponse->assertSee('Tanda Tangan Datang (Masuk)');
        $formResponse->assertSee('Tanda Tangan Pulang (Keluar)');

        $todayStr = now()->toDateString();
        $sampleSignature = $this->getSamplePngDataUri();

        // 2. Submit Presensi Datang
        $responseDatang = $this->post(route('unit.events.attendance.submit', [
            'unitSlug' => $unitPage->slug,
            'eventSlug' => $event->slug,
            'token' => $participant->access_token,
        ]), [
            'attendance_date' => $todayStr,
            'tipe_presensi' => 'datang',
            'shift_name' => 'Pagi (07.00 - 14.00)',
            'signature_data_datang' => $sampleSignature,
        ]);

        $responseDatang->assertSessionHasNoErrors();
        $responseDatang->assertRedirect();
        $attendance = UnitEventAttendance::where('unit_event_id', $event->id)
            ->where('unit_event_participant_id', $participant->id)
            ->whereDate('attendance_date', $todayStr)
            ->first();

        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->signature_path);
        $this->assertNotNull($attendance->signed_at);
        $this->assertNull($attendance->check_out_signature_path);
        $this->assertEquals('Pagi (07.00 - 14.00)', $attendance->shift_name);

        // 3. Submit Presensi Pulang
        $responsePulang = $this->post(route('unit.events.attendance.submit', [
            'unitSlug' => $unitPage->slug,
            'eventSlug' => $event->slug,
            'token' => $participant->access_token,
        ]), [
            'attendance_date' => $todayStr,
            'tipe_presensi' => 'pulang',
            'shift_name' => 'Pagi (07.00 - 14.00)',
            'signature_data_pulang' => $sampleSignature,
        ]);

        $responsePulang->assertRedirect();
        $attendance->refresh();

        $this->assertNotNull($attendance->check_out_signature_path);
        $this->assertNotNull($attendance->check_out_at);
    }

    public function test_pbm_praktik_validates_missing_signature_based_on_tipe_presensi(): void
    {
        $unitPage = UnitPage::create([
            'slug' => 'kaprodi',
            'judul' => 'Prodi Profesi Bidan',
            'tipe' => 'unit',
            'aktif' => true,
        ]);

        $event = UnitEvent::create([
            'unit_page_id' => $unitPage->id,
            'slug' => 'pbm-praktik-validation-test',
            'judul' => 'PBM Praktik Kebidanan Komprehensif',
            'jenis_kegiatan' => 'pbm',
            'sub_jenis_kegiatan' => 'praktik',
            'status' => 'published',
            'daftar_hadir_aktif' => true,
            'allow_direct_attendance' => true,
            'mulai_at' => now()->subDays(2),
            'selesai_at' => now()->addDays(5),
        ]);

        $participant = UnitEventParticipant::create([
            'unit_event_id' => $event->id,
            'nama' => 'Ayu Mahasiswi',
            'email' => 'ayu@example.com',
            'kode_registrasi' => 'REG-AYU-01',
            'access_token' => 'test-token-ayu-12345',
            'status' => 'confirmed',
        ]);

        $todayStr = now()->toDateString();

        // Submitting datang without signature triggers error on signature_data_datang
        $response = $this->from(route('unit.events.attendance.show', [
            'unitSlug' => $unitPage->slug,
            'eventSlug' => $event->slug,
            'token' => $participant->access_token,
        ]))->post(route('unit.events.attendance.submit', [
            'unitSlug' => $unitPage->slug,
            'eventSlug' => $event->slug,
            'token' => $participant->access_token,
        ]), [
            'attendance_date' => $todayStr,
            'tipe_presensi' => 'datang',
            'shift_name' => 'Pagi',
            'signature_data_datang' => '',
        ]);

        $response->assertSessionHasErrors(['signature_data_datang']);

        // Submitting pulang without signature triggers error on signature_data_pulang
        $responsePulang = $this->from(route('unit.events.attendance.show', [
            'unitSlug' => $unitPage->slug,
            'eventSlug' => $event->slug,
            'token' => $participant->access_token,
        ]))->post(route('unit.events.attendance.submit', [
            'unitSlug' => $unitPage->slug,
            'eventSlug' => $event->slug,
            'token' => $participant->access_token,
        ]), [
            'attendance_date' => $todayStr,
            'tipe_presensi' => 'pulang',
            'shift_name' => 'Pagi',
            'signature_data_pulang' => '',
        ]);

        $responsePulang->assertSessionHasErrors(['signature_data_pulang']);
    }
}
