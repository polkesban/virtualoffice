<?php

namespace Tests\Unit;

use App\Models\AccreditationEvidenceAnnotation;
use PHPUnit\Framework\TestCase;

class AccreditationEvidenceAnnotationTest extends TestCase
{
    public function test_status_options_are_available(): void
    {
        $options = AccreditationEvidenceAnnotation::statusOptions();

        $this->assertArrayHasKey(AccreditationEvidenceAnnotation::STATUS_OPEN, $options);
        $this->assertArrayHasKey(AccreditationEvidenceAnnotation::STATUS_IN_PROGRESS, $options);
        $this->assertArrayHasKey(AccreditationEvidenceAnnotation::STATUS_RESOLVED, $options);
    }

    public function test_status_label_and_badge_class_follow_status(): void
    {
        $annotation = new AccreditationEvidenceAnnotation([
            'title' => 'Tindak lanjut kurikulum',
            'status' => AccreditationEvidenceAnnotation::STATUS_IN_PROGRESS,
        ]);

        $this->assertSame('Sedang Ditindaklanjuti', $annotation->status_label);
        $this->assertStringContainsString('amber', $annotation->status_badge_class);

        $annotation->status = AccreditationEvidenceAnnotation::STATUS_RESOLVED;

        $this->assertSame('Selesai', $annotation->status_label);
        $this->assertStringContainsString('emerald', $annotation->status_badge_class);
    }
}