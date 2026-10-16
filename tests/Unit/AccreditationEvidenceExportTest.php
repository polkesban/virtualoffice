<?php

namespace Tests\Unit;

use App\Models\AccreditationEvidence;
use App\Models\AccreditationFolder;
use App\Models\DokumenMutu;
use App\Support\AccreditationEvidenceExport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AccreditationEvidenceExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-21 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_maps_evidence_rows_into_export_output(): void
    {
        $evidence = $this->makeEvidenceSample();
        $export = new AccreditationEvidenceExport();

        $rows = $export->rows(new Collection([$evidence]), fn ($item) => '/admin/eviden-akreditasi/' . $item->id);

        $this->assertCount(1, $rows);
        $this->assertSame('Kriteria 1. Visi Misi', $rows[0][0]);
        $this->assertSame('Rencana tindak lanjut audit Pusat Mutu', $rows[0][2]);
        $this->assertSame('Lewat jatuh tempo', $rows[0][5]);
        $this->assertSame('Habis <= 30 hari', $rows[0][7]);
        $this->assertSame('Dokumen Mutu; File Privat; URL Eksternal', $rows[0][9]);
        $this->assertSame(3, $rows[0][10]);
        $this->assertSame(4, $rows[0][11]);
        $this->assertSame('/admin/eviden-akreditasi/88', $rows[0][13]);
    }

    public function test_it_builds_a_spreadsheet_with_headers_and_rows(): void
    {
        $evidence = $this->makeEvidenceSample();
        $export = new AccreditationEvidenceExport();

        $spreadsheet = $export->spreadsheet(new Collection([$evidence]), fn ($item) => '/admin/eviden-akreditasi/' . $item->id);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('Kriteria', $sheet->getCell('A1')->getValue());
        $this->assertSame('Status Review', $sheet->getCell('F1')->getValue());
        $this->assertSame('Kriteria 1. Visi Misi', $sheet->getCell('A2')->getValue());
        $this->assertSame('Lewat jatuh tempo', $sheet->getCell('F2')->getValue());
        $this->assertSame('Dokumen Mutu; File Privat; URL Eksternal', $sheet->getCell('J2')->getValue());
        $this->assertSame('/admin/eviden-akreditasi/88', $sheet->getCell('N2')->getValue());

        $spreadsheet->disconnectWorksheets();
    }

    private function makeEvidenceSample(): AccreditationEvidence
    {
        $folder = new AccreditationFolder([
            'code' => '1',
            'title' => 'Visi Misi',
        ]);

        $dokumenMutu = new DokumenMutu();
        $dokumenMutu->judul = 'SK Penjaminan Mutu';
        $dokumenMutu->file_url = 'https://example.test/sk-penjaminan-mutu.pdf';

        $evidence = new AccreditationEvidence([
            'title' => 'Rencana tindak lanjut audit Pusat Mutu',
            'subcriterion_code' => '1.2.3',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'pic_name' => 'Pusat Mutu',
            'summary' => 'Rangkuman backlog tindak lanjut audit internal yang harus diselesaikan oleh Pusat Mutu.',
            'review_due_at' => '2026-04-15',
            'expires_at' => '2026-05-10',
            'external_url' => 'https://example.test/backlog-pusat-mutu.pdf',
            'file_path' => 'accreditation-evidence/backlog-pusat-mutu.pdf',
            'file_name' => 'backlog-pusat-mutu.pdf',
        ]);

        $evidence->id = 88;
        $evidence->exists = true;
        $evidence->setRelation('folder', $folder);
        $evidence->setRelation('dokumenMutu', $dokumenMutu);
        $evidence->setAttribute('open_annotations_count', 2);
        $evidence->setAttribute('in_progress_annotations_count', 1);
        $evidence->setAttribute('annotations_count', 4);

        return $evidence;
    }
}