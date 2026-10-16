<?php

namespace Tests\Unit;

use App\Models\AccreditationEvidence;
use App\Models\AccreditationEvidenceAnnotation;
use App\Models\AccreditationFolder;
use App\Models\User;
use App\Support\AccreditationEvidenceAnnotationExport;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AccreditationEvidenceAnnotationExportTest extends TestCase
{
    public function test_it_maps_annotations_into_export_rows(): void
    {
        $annotation = $this->makeAnnotationSample();
        $export = new AccreditationEvidenceAnnotationExport();

        $rows = $export->rows(new Collection([$annotation]), fn ($evidence) => '/admin/eviden-akreditasi/' . $evidence->id);

        $this->assertCount(1, $rows);
        $this->assertSame('Belum Ditindaklanjuti', $rows[0][0]);
        $this->assertSame('Ya', $rows[0][1]);
        $this->assertSame('Kriteria 1. Visi Misi', $rows[0][2]);
        $this->assertSame('Rencana tindak lanjut AMI', $rows[0][4]);
        $this->assertSame('/admin/eviden-akreditasi/77', $rows[0][15]);
    }

    public function test_it_builds_a_spreadsheet_with_headers_and_rows(): void
    {
        $annotation = $this->makeAnnotationSample();
        $export = new AccreditationEvidenceAnnotationExport();

        $spreadsheet = $export->spreadsheet(new Collection([$annotation]), fn ($evidence) => '/admin/eviden-akreditasi/' . $evidence->id);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('Status Tindak Lanjut', $sheet->getCell('A1')->getValue());
        $this->assertSame('Pinned', $sheet->getCell('B1')->getValue());
        $this->assertSame('Belum Ditindaklanjuti', $sheet->getCell('A2')->getValue());
        $this->assertSame('Rencana tindak lanjut AMI', $sheet->getCell('E2')->getValue());
        $this->assertSame('/admin/eviden-akreditasi/77', $sheet->getCell('P2')->getValue());

        $spreadsheet->disconnectWorksheets();
    }

    private function makeAnnotationSample(): AccreditationEvidenceAnnotation
    {
        $folder = new AccreditationFolder([
            'code' => '1',
            'title' => 'Visi Misi',
        ]);

        $evidence = new AccreditationEvidence([
            'title' => 'Rencana tindak lanjut AMI',
            'subcriterion_code' => '1.2.3',
            'verification_status' => AccreditationEvidence::STATUS_READY,
            'pic_name' => 'Pusat Mutu',
            'review_due_at' => '2026-04-30',
            'expires_at' => '2026-12-31',
        ]);
        $evidence->id = 77;
        $evidence->exists = true;
        $evidence->setRelation('folder', $folder);

        $creator = new User(['name' => 'Reviewer A']);
        $editor = new User(['name' => 'Reviewer B']);

        $annotation = new AccreditationEvidenceAnnotation([
            'title' => 'Lampiran tindak lanjut belum lengkap',
            'excerpt' => 'Lampiran matriks masih parsial.',
            'note' => 'Perlu diperbarui sebelum simulasi asesmen.',
            'status' => AccreditationEvidenceAnnotation::STATUS_OPEN,
            'is_pinned' => true,
            'updated_at' => '2026-04-21 10:15:00',
        ]);

        $annotation->setRelation('evidence', $evidence);
        $annotation->setRelation('creator', $creator);
        $annotation->setRelation('editor', $editor);

        return $annotation;
    }
}