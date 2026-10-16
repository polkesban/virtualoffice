<?php

namespace Tests\Feature;

use App\Models\UnitEvent;
use App\Models\UnitEventParticipant;
use App\Models\UnitPage;
use App\Models\User;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class UnitEventParticipantImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_participant_import_template(): void
    {
        [$admin, $page, $event] = $this->createAdminEventContext();

        $response = $this->actingAs($admin)->get(route('admin.unit-events.participants.import-template', [$page, $event]));

        $response->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
    }

    public function test_admin_can_import_participants_from_excel(): void
    {
        [$admin, $page, $event] = $this->createAdminEventContext();

        $upload = $this->makeSpreadsheetUpload([
            ['Nama', 'NIP/NIS/NIK', 'Kategori Peserta', 'Unit Kerja', 'Jabatan', 'Peran', 'Email', 'No HP', 'Catatan'],
            ['Yuliyani Dewi Sartika', '2204010007', 'Non Pegawai / Umum', 'Jurusan Kesehatan Lingkungan', 'Admin Prodi', 'Peserta', 'yuli@example.test', '081234567890', 'Peserta umum'],
            ['Ulfah Utami, M.Kes', '', 'Pegawai Poltekkes Bandung', 'Jurusan Kesehatan Gigi', 'PJ Adak Jurusan / prodi', 'Narasumber', '', '081211122233', 'Sesi keynote'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.unit-events.participants.import', [$page, $event]), [
            'participants_file' => $upload,
        ]);

        $response->assertRedirect(route('admin.unit-events.participants.index', [$page, $event]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('unit_event_participants', [
            'unit_event_id' => $event->id,
            'nama' => 'Yuliyani Dewi Sartika',
            'identity_number' => '2204010007',
            'participant_category' => 'public',
            'instansi' => 'Jurusan Kesehatan Lingkungan',
            'jabatan' => 'Admin Prodi',
            'peran' => 'participant',
            'sumber_registrasi' => 'admin_import',
        ]);

        $this->assertDatabaseHas('unit_event_participants', [
            'unit_event_id' => $event->id,
            'nama' => 'Ulfah Utami, M.Kes',
            'participant_category' => 'employee',
            'instansi' => 'Jurusan Kesehatan Gigi',
            'jabatan' => 'PJ Adak Jurusan / prodi',
            'peran' => 'speaker',
            'sumber_registrasi' => 'admin_import',
        ]);

        $participant = UnitEventParticipant::query()
            ->where('unit_event_id', $event->id)
            ->where('nama', 'Yuliyani Dewi Sartika')
            ->firstOrFail();

        $this->assertNotNull($participant->access_password);
        $this->assertNotNull($participant->temporary_access_password);
    }

    public function test_participant_can_login_using_generated_credentials(): void
    {
        [$admin, $page, $event] = $this->createAdminEventContext();

        $upload = $this->makeSpreadsheetUpload([
            ['Nama', 'NIP/NIS/NIK', 'Kategori Peserta', 'Unit Kerja', 'Jabatan', 'Peran', 'Email', 'No HP', 'Catatan'],
            ['Yuliyani Dewi Sartika', '', 'Non Pegawai / Umum', 'Jurusan Kesehatan Lingkungan', 'Admin Prodi', 'Peserta', 'yuli@example.test', '081234567890', 'Peserta umum'],
        ]);

        $this->actingAs($admin)->post(route('admin.unit-events.participants.import', [$page, $event]), [
            'participants_file' => $upload,
        ]);

        $participant = UnitEventParticipant::query()
            ->where('unit_event_id', $event->id)
            ->where('nama', 'Yuliyani Dewi Sartika')
            ->firstOrFail();

        $response = $this->post(route('unit.events.login.submit', [$page->slug, $event->slug]), [
            'kode_registrasi' => $participant->participant_login_username,
            'password' => $participant->temporary_access_password,
        ]);

        $response->assertRedirect(route('unit.events.attendance.show', [$page->slug, $event->slug, $participant->access_token]));
    }

    public function test_import_links_existing_mahasiswa_account_and_reuses_application_access(): void
    {
        [$admin, $page, $event] = $this->createAdminEventContext();
        $programStudi = $this->createProgramStudi();

        $mahasiswa = Mahasiswa::create([
            'nim' => '2204010007',
            'name' => 'Yuliyani Dewi Sartika',
            'email' => 'yuli@example.test',
            'password' => 'Password123!',
            'program_studi_id' => $programStudi->id,
        ]);

        $upload = $this->makeSpreadsheetUpload([
            ['Nama', 'NIP/NIS/NIK', 'Kategori Peserta', 'Unit Kerja', 'Jabatan', 'Peran', 'Email', 'No HP', 'Catatan'],
            ['Yuliyani Dewi Sartika', '2204010007', '', 'Jurusan Kesehatan Lingkungan', 'Admin Prodi', 'Peserta', 'yuli@example.test', '081234567890', 'Peserta umum'],
        ]);

        $this->actingAs($admin)->post(route('admin.unit-events.participants.import', [$page, $event]), [
            'participants_file' => $upload,
        ]);

        $participant = UnitEventParticipant::query()
            ->where('unit_event_id', $event->id)
            ->where('nama', 'Yuliyani Dewi Sartika')
            ->firstOrFail();

        $this->assertSame($mahasiswa->id, $participant->linked_mahasiswa_id);
        $this->assertNull($participant->access_password);
        $this->assertSame('student', $participant->participant_category);
    }

    public function test_public_registration_is_bypassed_when_managed_participants_exist(): void
    {
        [, $page, $event] = $this->createAdminEventContext();

        UnitEventParticipant::create([
            'unit_event_id' => $event->id,
            'peran' => 'participant',
            'participant_category' => 'public',
            'sumber_registrasi' => 'admin_import',
            'nama' => 'Roster Peserta',
            'email' => 'roster@example.test',
            'kode_registrasi' => 'EVT-ROSTER01',
            'access_token' => 'roster-token-001',
            'registered_at' => now(),
        ]);

        $response = $this->post(route('unit.events.register', [$page->slug, $event->slug]), [
            'nama' => 'Pendaftar Baru',
            'email' => 'baru@example.test',
        ]);

        $response->assertRedirect(route('unit.events.login.show', [$page->slug, $event->slug, 'redirect' => 'attendance']));
    }

    public function test_logged_in_mahasiswa_can_open_direct_attendance_for_linked_participant(): void
    {
        [, $page, $event] = $this->createAdminEventContext();
        $programStudi = $this->createProgramStudi();

        $mahasiswa = Mahasiswa::create([
            'nim' => '2204010007',
            'name' => 'Yuliyani Dewi Sartika',
            'email' => 'yuli@example.test',
            'password' => 'Password123!',
            'program_studi_id' => $programStudi->id,
        ]);

        $participant = UnitEventParticipant::create([
            'unit_event_id' => $event->id,
            'peran' => 'participant',
            'participant_category' => 'student',
            'sumber_registrasi' => 'admin_import',
            'nama' => 'Yuliyani Dewi Sartika',
            'email' => 'yuli@example.test',
            'identity_number' => '2204010007',
            'kode_registrasi' => 'EVT-LINK001',
            'access_token' => 'linked-token-001',
            'registered_at' => now(),
            'linked_mahasiswa_id' => $mahasiswa->id,
        ]);

        $response = $this->actingAs($mahasiswa, 'mahasiswa')->get(route('unit.events.attendance.direct', [
            $page->slug,
            $event->slug,
            'kode_registrasi' => $participant->participant_login_username,
            'portal' => 'mahasiswa',
        ]));

        $response->assertRedirect(route('unit.events.attendance.show', [$page->slug, $event->slug, $participant->access_token]));
    }

    public function test_admin_can_upload_assignment_letter_and_participant_can_view_it(): void
    {
        Storage::fake('public');

        [$admin, $page, $event] = $this->createAdminEventContext();

        $response = $this->actingAs($admin)->post(route('admin.unit-events.participants.store', [$page, $event]), [
            'peran' => 'participant',
            'participant_category' => 'public',
            'nama' => 'Peserta Dengan Surat Tugas',
            'email' => 'surat@example.test',
            'surat_tugas_file' => UploadedFile::fake()->create('surat-tugas.pdf', 256, 'application/pdf'),
        ]);

        $participant = UnitEventParticipant::query()
            ->where('unit_event_id', $event->id)
            ->where('nama', 'Peserta Dengan Surat Tugas')
            ->firstOrFail();

        $response->assertRedirect(route('admin.unit-events.participants.index', [$page, $event]));
        $this->assertNotNull($participant->surat_tugas_path);
        $this->assertNotNull($participant->surat_tugas_uploaded_at);
        Storage::disk('public')->assertExists($participant->surat_tugas_path);

        $portalResponse = $this->get(route('unit.events.access', [$page->slug, $event->slug, $participant->access_token]));

        $portalResponse->assertOk();
        $portalResponse->assertSee('Surat Tugas / SK');
        $portalResponse->assertSee(route('unit.events.assignment-letter.show', [
            'unitSlug' => $page->slug,
            'eventSlug' => $event->slug,
            'token' => $participant->access_token,
        ]), false);

        $fileResponse = $this->get(route('unit.events.assignment-letter.show', [
            'unitSlug' => $page->slug,
            'eventSlug' => $event->slug,
            'token' => $participant->access_token,
        ]));

        $fileResponse->assertOk();
        $fileResponse->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_can_upload_event_assignment_letter_and_public_event_page_shows_it(): void
    {
        Storage::fake('public');

        [$admin, $page, $event] = $this->createAdminEventContext();

        $response = $this->actingAs($admin)->put(route('admin.unit-events.update', [$page, $event]), [
            'judul' => $event->judul,
            'slug' => $event->slug,
            'jenis_kegiatan' => $event->jenis_kegiatan,
            'metode' => $event->metode,
            'status' => $event->status,
            'lokasi' => $event->lokasi,
            'mulai_at' => $event->mulai_at->toDateTimeString(),
            'selesai_at' => $event->selesai_at?->toDateTimeString(),
            'registrasi_aktif' => '1',
            'daftar_hadir_aktif' => '1',
            'surat_tugas_event_file' => UploadedFile::fake()->create('surat-tugas-kegiatan.pdf', 256, 'application/pdf'),
        ]);

        $event->refresh();

        $response->assertRedirect(route('admin.unit-events.edit', [$page, $event]));
        $this->assertNotNull($event->surat_tugas_event_path);
        $this->assertNotNull($event->surat_tugas_event_uploaded_at);
        Storage::disk('public')->assertExists($event->surat_tugas_event_path);

        $publicResponse = $this->get(route('unit.events.show', [$page->slug, $event->slug]));

        $publicResponse->assertOk();
        $publicResponse->assertSee('Surat Tugas Kegiatan');
        $publicResponse->assertSee(route('unit.events.event-assignment-letter.show', [
            'unitSlug' => $page->slug,
            'eventSlug' => $event->slug,
        ]), false);

        $participant = UnitEventParticipant::create([
            'unit_event_id' => $event->id,
            'peran' => 'participant',
            'participant_category' => 'employee',
            'sumber_registrasi' => 'admin',
            'nama' => 'Pegawai Poltekkes Bandung',
            'email' => 'pegawai@example.test',
            'kode_registrasi' => 'EVT-EVENTDOC1',
            'access_token' => 'event-doc-token-001',
            'registered_at' => now(),
        ]);

        $portalResponse = $this->get(route('unit.events.access', [$page->slug, $event->slug, $participant->access_token]));

        $portalResponse->assertOk();
        $portalResponse->assertSee('Surat Tugas Kegiatan');

        $fileResponse = $this->get(route('unit.events.event-assignment-letter.show', [
            'unitSlug' => $page->slug,
            'eventSlug' => $event->slug,
        ]));

        $fileResponse->assertOk();
        $fileResponse->assertHeader('content-type', 'application/pdf');
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

    private function createProgramStudi(): ProgramStudi
    {
        return ProgramStudi::create([
            'nama' => 'D4 Keperawatan',
            'singkatan' => 'D4 Kep',
            'jenjang' => 'D4',
            'slug' => 'd4-keperawatan-' . uniqid(),
            'aktif' => true,
        ]);
    }

    private function makeSpreadsheetUpload(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValue(chr(65 + $columnIndex) . ($rowIndex + 1), $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'unit-event-participants-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile(
            $path,
            'participants.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }
}