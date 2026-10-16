<?php

namespace Tests\Feature;

use App\Models\AccreditationEvidence;
use App\Models\AccreditationEvidenceAnnotation;
use App\Models\AccreditationFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccreditationEvidenceAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_pusat_mutu_can_open_accreditation_evidence_room(): void
    {
        $admin = $this->makeAdmin('pusat_mutu');

        $response = $this->actingAs($admin)->get(route('admin.eviden-akreditasi.index'));

        $response->assertOk();
        $response->assertSee('Ruang Eviden Akreditasi');
    }

    public function test_admin_without_menu_access_cannot_open_accreditation_evidence_room(): void
    {
        $admin = $this->makeAdmin('unit_ti');

        $response = $this->actingAs($admin)->get(route('admin.eviden-akreditasi.index'));

        $response->assertForbidden();
    }

    public function test_dashboard_shows_criteria_coverage_summary(): void
    {
        $admin = $this->makeAdmin('pusat_mutu');
        $folders = AccreditationFolder::query()->orderBy('code')->take(2)->get();

        AccreditationEvidence::query()->create([
            'folder_id' => $folders[0]->id,
            'title' => 'SK tim penjaminan mutu',
            'verification_status' => AccreditationEvidence::STATUS_VERIFIED,
            'external_url' => 'https://example.test/sk-tim-penjaminan-mutu.pdf',
        ]);

        AccreditationEvidence::query()->create([
            'folder_id' => $folders[1]->id,
            'title' => 'Jadwal review kurikulum semester ganjil',
            'verification_status' => AccreditationEvidence::STATUS_DRAFT,
            'external_url' => 'https://example.test/jadwal-review-kurikulum.pdf',
            'review_due_at' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.eviden-akreditasi.index'));

        $response->assertOk();
        $response->assertSee('Cakupan Per Kriteria');
        $response->assertSee('2 / 8 kriteria sudah terisi');
        $response->assertSee('Kriteria kosong: 6');
    }

    public function test_authorized_admin_can_create_evidence_with_private_upload(): void
    {
        Storage::fake('local');

        $admin = $this->makeAdmin('pusat_mutu');
        $folder = AccreditationFolder::query()->firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.eviden-akreditasi.store'), [
            'folder_id' => $folder->id,
            'title' => 'Notulen review kurikulum eksternal',
            'subcriterion_code' => '2.1.2',
            'pic_name' => 'Pusat Penjaminan Mutu',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'summary' => 'Notulen rapat bersama stakeholder eksternal terkait review kurikulum.',
            'review_due_at' => now()->addMonths(6)->format('Y-m-d'),
            'evidence_file' => UploadedFile::fake()->create('notulen-review.pdf', 256, 'application/pdf'),
        ]);

        $response->assertRedirect(route('admin.eviden-akreditasi.index'));
        $response->assertSessionHas('success');

        $evidence = AccreditationEvidence::query()->firstOrFail();

        $this->assertSame($folder->id, $evidence->folder_id);
        $this->assertSame('Notulen review kurikulum eksternal', $evidence->title);
        $this->assertSame(AccreditationEvidence::STATUS_READY, $evidence->verification_status);
        $this->assertNotNull($evidence->file_path);
        Storage::disk('local')->assertExists($evidence->file_path);

        $showResponse = $this->actingAs($admin)->get(route('admin.eviden-akreditasi.show', $evidence));
        $showResponse->assertOk();
        $showResponse->assertSee('Pratinjau Isi Eviden');

        $previewResponse = $this->actingAs($admin)->get(route('admin.eviden-akreditasi.preview', $evidence));
        $previewResponse->assertOk();
        $previewResponse->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('inline', (string) $previewResponse->headers->get('content-disposition'));

        $downloadResponse = $this->actingAs($admin)->get(route('admin.eviden-akreditasi.download', $evidence));
        $downloadResponse->assertOk();
    }

    public function test_evidence_requires_at_least_one_source(): void
    {
        $admin = $this->makeAdmin('pusat_mutu');
        $folder = AccreditationFolder::query()->firstOrFail();

        $response = $this->from(route('admin.eviden-akreditasi.create'))
            ->actingAs($admin)
            ->post(route('admin.eviden-akreditasi.store'), [
                'folder_id' => $folder->id,
                'title' => 'Daftar hadir sosialisasi VMTS',
                'verification_status' => AccreditationEvidence::STATUS_DRAFT,
            ]);

        $response->assertRedirect(route('admin.eviden-akreditasi.create'));
        $response->assertSessionHasErrors('source');
    }

    public function test_detail_page_shows_extracted_text_for_private_text_file(): void
    {
        Storage::fake('local');

        $admin = $this->makeAdmin('pusat_mutu');
        $folder = AccreditationFolder::query()->firstOrFail();

        Storage::disk('local')->put('accreditation-evidence/evaluasi-ami.txt', "Ringkasan AMI 2026\nTemuan utama: tindak lanjut kurikulum perlu dipercepat.");

        $evidence = AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Evaluasi tindak lanjut AMI',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'file_path' => 'accreditation-evidence/evaluasi-ami.txt',
            'file_name' => 'evaluasi-ami.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 88,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.eviden-akreditasi.show', [
            'accreditationEvidence' => $evidence,
            'source' => 'private_file',
            'text_q' => 'kurikulum',
        ]));

        $response->assertOk();
        $response->assertSee('Teks Terbaca');
        $response->assertSee('Cari di Teks Terbaca');
        $response->assertSee('1 kecocokan ditemukan');
        $response->assertSee('Hasil Sebelumnya');
        $response->assertSee('Hasil Berikutnya');
        $response->assertSee('Potongan Relevan');
        $response->assertSee('Ringkasan AMI 2026');
        $response->assertSee('tindak lanjut kurikulum perlu dipercepat');
    }

    public function test_authorized_admin_can_add_reviewer_annotation(): void
    {
        $admin = $this->makeAdmin('pusat_mutu');
        $folder = AccreditationFolder::query()->firstOrFail();

        $evidence = AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Evaluasi kurikulum profesi',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'external_url' => 'https://example.test/evaluasi-kurikulum-profesi.pdf',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.eviden-akreditasi.annotations.store', $evidence), [
            'title' => 'Butir penting untuk visitasi',
            'excerpt' => 'Ada evaluasi kurikulum bersama stakeholder eksternal.',
            'note' => 'Tunjukkan ini saat asesor bertanya tentang bukti tindak lanjut review kurikulum.',
            'status' => 'in_progress',
            'is_pinned' => '1',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('accreditation_evidence_annotations', [
            'accreditation_evidence_id' => $evidence->id,
            'title' => 'Butir penting untuk visitasi',
            'status' => 'in_progress',
            'is_pinned' => true,
        ]);
    }

    public function test_dashboard_shows_pending_reviewer_annotation_summary(): void
    {
        $admin = $this->makeAdmin('pusat_mutu');
        $folder = AccreditationFolder::query()->firstOrFail();

        $pendingEvidence = AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Notulen evaluasi dosen praktisi',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'external_url' => 'https://example.test/notulen-evaluasi-dosen-praktisi.pdf',
        ]);

        $resolvedEvidence = AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Berita acara review VMTS',
            'verification_status' => AccreditationEvidence::STATUS_VERIFIED,
            'external_url' => 'https://example.test/berita-acara-review-vmts.pdf',
        ]);

        AccreditationEvidenceAnnotation::query()->create([
            'accreditation_evidence_id' => $pendingEvidence->id,
            'title' => 'Butuh tindak lanjut rapat lanjutan',
            'status' => AccreditationEvidenceAnnotation::STATUS_OPEN,
        ]);

        AccreditationEvidenceAnnotation::query()->create([
            'accreditation_evidence_id' => $pendingEvidence->id,
            'title' => 'Sedang dipetakan ke butir instrumen',
            'status' => AccreditationEvidenceAnnotation::STATUS_IN_PROGRESS,
        ]);

        AccreditationEvidenceAnnotation::query()->create([
            'accreditation_evidence_id' => $resolvedEvidence->id,
            'title' => 'Sudah diverifikasi tim mutu',
            'status' => AccreditationEvidenceAnnotation::STATUS_RESOLVED,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.eviden-akreditasi.index'));

        $response->assertOk();
        $response->assertSee('Tindak Lanjut Catatan Reviewer');
        $response->assertSee('1 eviden punya catatan aktif');
        $response->assertSee('Notulen evaluasi dosen praktisi');
    }

    public function test_dashboard_can_filter_evidence_by_reviewer_follow_up_status(): void
    {
        $admin = $this->makeAdmin('pusat_mutu');
        $folder = AccreditationFolder::query()->firstOrFail();

        $pendingEvidence = AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Bukti tindak lanjut tracer study',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'external_url' => 'https://example.test/tracer-study.pdf',
        ]);

        $resolvedEvidence = AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Bukti rapat tinjauan manajemen',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'external_url' => 'https://example.test/rtm.pdf',
        ]);

        AccreditationEvidenceAnnotation::query()->create([
            'accreditation_evidence_id' => $pendingEvidence->id,
            'title' => 'Belum dipetakan ke butir instrumen',
            'status' => AccreditationEvidenceAnnotation::STATUS_OPEN,
        ]);

        AccreditationEvidenceAnnotation::query()->create([
            'accreditation_evidence_id' => $resolvedEvidence->id,
            'title' => 'Sudah tuntas',
            'status' => AccreditationEvidenceAnnotation::STATUS_RESOLVED,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.eviden-akreditasi.index', [
            'annotation_status' => 'needs_follow_up',
        ]));

        $response->assertOk();
        $response->assertSee('Bukti tindak lanjut tracer study');
        $response->assertDontSee('Bukti rapat tinjauan manajemen');
    }

    public function test_dashboard_can_filter_evidence_by_missing_source_state(): void
    {
        $admin = $this->makeAdmin('pusat_mutu');
        $folder = AccreditationFolder::query()->firstOrFail();

        AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Daftar eviden belum lengkap sumbernya',
            'verification_status' => AccreditationEvidence::STATUS_DRAFT,
        ]);

        AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Daftar eviden dengan sumber URL',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'external_url' => 'https://example.test/eviden-dengan-sumber.pdf',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.eviden-akreditasi.index', [
            'source_state' => 'missing',
        ]));

        $response->assertOk();
        $response->assertSee('Daftar eviden belum lengkap sumbernya');
        $response->assertDontSee('Daftar eviden dengan sumber URL');
    }

    public function test_dashboard_can_filter_evidence_by_pic_name(): void
    {
        $admin = $this->makeAdmin('pusat_mutu');
        $folder = AccreditationFolder::query()->firstOrFail();

        AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Rencana tindak lanjut audit Pusat Mutu',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'pic_name' => 'Pusat Mutu',
            'external_url' => 'https://example.test/pusat-mutu.pdf',
        ]);

        AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Rencana tindak lanjut audit Prodi',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'pic_name' => 'Tim Prodi',
            'external_url' => 'https://example.test/tim-prodi.pdf',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.eviden-akreditasi.index', [
            'pic' => 'Pusat Mutu',
        ]));

        $response->assertOk();
        $response->assertSee('Rencana tindak lanjut audit Pusat Mutu');
        $response->assertDontSee('Rencana tindak lanjut audit Prodi');
    }

    public function test_dashboard_can_filter_evidence_by_overdue_review(): void
    {
        $admin = $this->makeAdmin('pusat_mutu');
        $folder = AccreditationFolder::query()->firstOrFail();

        AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Eviden review terlambat',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'review_due_at' => now()->subDays(5)->format('Y-m-d'),
            'external_url' => 'https://example.test/review-terlambat.pdf',
        ]);

        AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Eviden review bulan depan',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'review_due_at' => now()->addDays(10)->format('Y-m-d'),
            'external_url' => 'https://example.test/review-bulan-depan.pdf',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.eviden-akreditasi.index', [
            'review' => 'review_overdue',
        ]));

        $response->assertOk();
        $response->assertSee('Eviden review terlambat');
        $response->assertDontSee('Eviden review bulan depan');
    }

    public function test_dashboard_can_filter_evidence_by_combined_expiry_attention(): void
    {
        $admin = $this->makeAdmin('pusat_mutu');
        $folder = AccreditationFolder::query()->firstOrFail();

        AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Eviden sudah kadaluarsa',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'expires_at' => now()->subDays(3)->format('Y-m-d'),
            'external_url' => 'https://example.test/eviden-kadaluarsa.pdf',
        ]);

        AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Eviden segera habis masa berlaku',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'expires_at' => now()->addDays(12)->format('Y-m-d'),
            'external_url' => 'https://example.test/eviden-segera-habis.pdf',
        ]);

        AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Eviden masih aman',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'expires_at' => now()->addDays(90)->format('Y-m-d'),
            'external_url' => 'https://example.test/eviden-masih-aman.pdf',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.eviden-akreditasi.index', [
            'review' => 'expiry_attention',
        ]));

        $response->assertOk();
        $response->assertSee('Eviden sudah kadaluarsa');
        $response->assertSee('Eviden segera habis masa berlaku');
        $response->assertDontSee('Eviden masih aman');
    }

    public function test_authorized_admin_can_export_filtered_evidence_queue_csv(): void
    {
        $admin = $this->makeAdmin('pusat_mutu');
        $folder = AccreditationFolder::query()->firstOrFail();

        AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Backlog review Pusat Mutu',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'pic_name' => 'Pusat Mutu',
            'review_due_at' => now()->subDays(7)->format('Y-m-d'),
            'external_url' => 'https://example.test/backlog-pusat-mutu.pdf',
        ]);

        AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Backlog review Tim Prodi',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'pic_name' => 'Tim Prodi',
            'review_due_at' => now()->subDays(4)->format('Y-m-d'),
            'external_url' => 'https://example.test/backlog-tim-prodi.pdf',
        ]);

        AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Review upcoming Pusat Mutu',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'pic_name' => 'Pusat Mutu',
            'review_due_at' => now()->addDays(8)->format('Y-m-d'),
            'external_url' => 'https://example.test/review-upcoming-pusat-mutu.pdf',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.eviden-akreditasi.export', [
            'review' => 'review_overdue',
            'pic' => 'Pusat Mutu',
        ]));

        $response->assertOk();

        $contentDisposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('.csv', $contentDisposition);

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Status Review', $csv);
        $this->assertStringContainsString('Backlog review Pusat Mutu', $csv);
        $this->assertStringNotContainsString('Backlog review Tim Prodi', $csv);
        $this->assertStringNotContainsString('Review upcoming Pusat Mutu', $csv);
    }

    public function test_authorized_admin_can_export_reviewer_follow_up_csv(): void
    {
        $admin = $this->makeAdmin('pusat_mutu');
        $folder = AccreditationFolder::query()->firstOrFail();

        $pendingEvidence = AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Laporan tindak lanjut audit mutu internal',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'external_url' => 'https://example.test/laporan-tindak-lanjut-ami.pdf',
            'pic_name' => 'Pusat Mutu',
        ]);

        $resolvedEvidence = AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Berita acara monitoring pembelajaran',
            'verification_status' => AccreditationEvidence::STATUS_VERIFIED,
            'external_url' => 'https://example.test/monitoring-pembelajaran.pdf',
            'pic_name' => 'Tim Prodi',
        ]);

        AccreditationEvidenceAnnotation::query()->create([
            'accreditation_evidence_id' => $pendingEvidence->id,
            'title' => 'Belum dilampirkan matriks tindak lanjut',
            'status' => AccreditationEvidenceAnnotation::STATUS_OPEN,
            'note' => 'Perlu dibawa saat visitasi lapangan.',
        ]);

        AccreditationEvidenceAnnotation::query()->create([
            'accreditation_evidence_id' => $resolvedEvidence->id,
            'title' => 'Sudah ditutup',
            'status' => AccreditationEvidenceAnnotation::STATUS_RESOLVED,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.eviden-akreditasi.annotations.export', [
            'annotation_status' => 'needs_follow_up',
        ]));

        $response->assertOk();

        $contentDisposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('.csv', $contentDisposition);

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Status Tindak Lanjut', $csv);
        $this->assertStringContainsString('Laporan tindak lanjut audit mutu internal', $csv);
        $this->assertStringNotContainsString('Berita acara monitoring pembelajaran', $csv);
    }

    public function test_authorized_admin_can_export_reviewer_follow_up_xlsx(): void
    {
        $admin = $this->makeAdmin('pusat_mutu');
        $folder = AccreditationFolder::query()->firstOrFail();

        $evidence = AccreditationEvidence::query()->create([
            'folder_id' => $folder->id,
            'title' => 'Daftar tindak lanjut evaluasi pembelajaran',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'external_url' => 'https://example.test/evaluasi-pembelajaran.pdf',
        ]);

        AccreditationEvidenceAnnotation::query()->create([
            'accreditation_evidence_id' => $evidence->id,
            'title' => 'Masih butuh bukti pengesahan',
            'status' => AccreditationEvidenceAnnotation::STATUS_IN_PROGRESS,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.eviden-akreditasi.annotations.export', [
            'format' => 'xlsx',
        ]));

        $response->assertOk();

        $contentDisposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('.xlsx', $contentDisposition);

        $contentType = (string) $response->headers->get('content-type');
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $contentType);

        $xlsx = $response->streamedContent();

        $this->assertStringStartsWith('PK', $xlsx);
    }

    private function makeAdmin(string $role): User
    {
        return User::query()->create([
            'name' => 'Admin Test',
            'email' => $role . '@example.test',
            'password' => 'Password123!',
            'is_admin' => true,
            'role' => $role,
        ]);
    }
}