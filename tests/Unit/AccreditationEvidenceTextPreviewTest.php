<?php

namespace Tests\Unit;

use App\Models\AccreditationEvidence;
use App\Support\AccreditationEvidencePdfOcr;
use App\Support\AccreditationEvidenceTextPreview;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AccreditationEvidenceTextPreviewTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_uses_optional_ocr_when_pdf_has_no_embedded_text(): void
    {
        Cache::flush();
        Storage::fake('local');
        Storage::disk('local')->put('accreditation-evidence/scanned-ocr.pdf', 'fake-pdf-binary');

        $evidence = new AccreditationEvidence([
            'file_path' => 'accreditation-evidence/scanned-ocr.pdf',
            'file_name' => 'scanned-ocr.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $ocr = Mockery::mock(AccreditationEvidencePdfOcr::class);
        $ocr->shouldReceive('isAvailable')->once()->andReturn(true);
        $ocr->shouldReceive('extractFromPdf')->once()->andReturn("Hasil OCR dokumen scan\nTemuan penting asesor.");

        $preview = new class($ocr) extends AccreditationEvidenceTextPreview {
            public function __construct(AccreditationEvidencePdfOcr $ocr)
            {
                parent::__construct($ocr);
            }

            protected function extractPdfText(string $absolutePath): string
            {
                return '';
            }
        };

        $result = $preview->build($evidence);

        $this->assertSame('ready', $result['status']);
        $this->assertSame('ocr', $result['method']);
        $this->assertSame('OCR', $result['method_label']);
        $this->assertStringContainsString('Hasil OCR dokumen scan', (string) $result['content']);
    }

    public function test_it_reports_when_pdf_scan_cannot_be_read_without_optional_ocr(): void
    {
        Cache::flush();
        Storage::fake('local');
        Storage::disk('local')->put('accreditation-evidence/scanned-no-ocr.pdf', 'fake-pdf-binary');

        $evidence = new AccreditationEvidence([
            'file_path' => 'accreditation-evidence/scanned-no-ocr.pdf',
            'file_name' => 'scanned-no-ocr.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $ocr = Mockery::mock(AccreditationEvidencePdfOcr::class);
        $ocr->shouldReceive('isAvailable')->once()->andReturn(false);
        $ocr->shouldNotReceive('extractFromPdf');

        $preview = new class($ocr) extends AccreditationEvidenceTextPreview {
            public function __construct(AccreditationEvidencePdfOcr $ocr)
            {
                parent::__construct($ocr);
            }

            protected function extractPdfText(string $absolutePath): string
            {
                return '';
            }
        };

        $result = $preview->build($evidence);

        $this->assertSame('empty_text', $result['status']);
        $this->assertStringContainsString('OCR opsional belum tersedia', (string) $result['message']);
    }
}