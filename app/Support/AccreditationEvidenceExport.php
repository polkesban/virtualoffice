<?php

namespace App\Support;

use App\Models\AccreditationEvidence;
use App\Models\AccreditationEvidenceAnnotation;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AccreditationEvidenceExport
{
    private const COLUMNS = [
        ['header' => 'Kriteria', 'width' => 34],
        ['header' => 'Butir', 'width' => 14],
        ['header' => 'Judul Eviden', 'width' => 40],
        ['header' => 'Status Eviden', 'width' => 18],
        ['header' => 'PIC', 'width' => 24],
        ['header' => 'Status Review', 'width' => 22],
        ['header' => 'Review Berikutnya', 'width' => 18],
        ['header' => 'Status Masa Berlaku', 'width' => 22],
        ['header' => 'Masa Berlaku', 'width' => 18],
        ['header' => 'Sumber Eviden', 'width' => 26],
        ['header' => 'Catatan Aktif', 'width' => 16],
        ['header' => 'Total Anotasi', 'width' => 16],
        ['header' => 'Ringkasan', 'width' => 52],
        ['header' => 'Tautan Detail Eviden', 'width' => 48],
    ];

    public function headings(): array
    {
        return array_column(self::COLUMNS, 'header');
    }

    public function rows(Collection $evidences, ?callable $detailUrlResolver = null): array
    {
        return $evidences
            ->map(fn (AccreditationEvidence $evidence) => $this->mapRow($evidence, $detailUrlResolver))
            ->values()
            ->all();
    }

    public function spreadsheet(Collection $evidences, ?callable $detailUrlResolver = null): Spreadsheet
    {
        $rows = $this->rows($evidences, $detailUrlResolver);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar Eviden');

        $sheet->fromArray($this->headings(), null, 'A1');

        if ($rows !== []) {
            $sheet->fromArray($rows, null, 'A2');
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count(self::COLUMNS));
        $lastRow = max(1, count($rows) + 1);

        foreach (self::COLUMNS as $index => $column) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index + 1))->setWidth($column['width']);
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $lastColumn . $lastRow);
        $sheet->getDefaultRowDimension()->setRowHeight(22);

        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F766E'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        $sheet->getStyle('A2:' . $lastColumn . $lastRow)->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E5E7EB'],
                ],
            ],
        ]);

        return $spreadsheet;
    }

    private function mapRow(AccreditationEvidence $evidence, ?callable $detailUrlResolver = null): array
    {
        return [
            $evidence->folder?->display_label,
            $evidence->subcriterion_code,
            $evidence->title,
            $evidence->verification_status_label,
            $evidence->pic_name,
            $this->reviewStatusLabel($evidence),
            $evidence->review_due_at?->format('Y-m-d'),
            $this->expiryStatusLabel($evidence),
            $evidence->expires_at?->format('Y-m-d'),
            $this->sourceLabel($evidence),
            $this->activeAnnotationCount($evidence),
            $this->totalAnnotationCount($evidence),
            $evidence->summary,
            $this->resolveDetailUrl($evidence, $detailUrlResolver),
        ];
    }

    private function reviewStatusLabel(AccreditationEvidence $evidence): string
    {
        if ($evidence->review_overdue) {
            return 'Lewat jatuh tempo';
        }

        if ($evidence->review_due_soon) {
            return 'Review <= 30 hari';
        }

        if ($evidence->review_due_at) {
            return 'Terjadwal';
        }

        return 'Belum dijadwalkan';
    }

    private function expiryStatusLabel(AccreditationEvidence $evidence): string
    {
        if ($evidence->is_expired) {
            return 'Kadaluarsa';
        }

        if ($evidence->expires_soon) {
            return 'Habis <= 30 hari';
        }

        if ($evidence->expires_at) {
            return 'Masih berlaku';
        }

        return 'Tidak dibatasi';
    }

    private function sourceLabel(AccreditationEvidence $evidence): string
    {
        $sources = [];

        if ($evidence->hasDokumenMutuLink()) {
            $sources[] = 'Dokumen Mutu';
        }

        if ($evidence->hasUploadedFile()) {
            $sources[] = 'File Privat';
        }

        if ($evidence->hasExternalUrl()) {
            $sources[] = 'URL Eksternal';
        }

        return $sources === [] ? 'Belum ada sumber' : implode('; ', $sources);
    }

    private function activeAnnotationCount(AccreditationEvidence $evidence): int
    {
        if ($this->hasModelAttribute($evidence, 'open_annotations_count') || $this->hasModelAttribute($evidence, 'in_progress_annotations_count')) {
            return (int) $evidence->getAttribute('open_annotations_count')
                + (int) $evidence->getAttribute('in_progress_annotations_count');
        }

        if ($evidence->relationLoaded('annotations')) {
            return $evidence->annotations
                ->whereIn('status', [
                    AccreditationEvidenceAnnotation::STATUS_OPEN,
                    AccreditationEvidenceAnnotation::STATUS_IN_PROGRESS,
                ])
                ->count();
        }

        return 0;
    }

    private function totalAnnotationCount(AccreditationEvidence $evidence): int
    {
        if ($this->hasModelAttribute($evidence, 'annotations_count')) {
            return (int) $evidence->getAttribute('annotations_count');
        }

        if ($evidence->relationLoaded('annotations')) {
            return $evidence->annotations->count();
        }

        return 0;
    }

    private function hasModelAttribute(AccreditationEvidence $evidence, string $attribute): bool
    {
        return array_key_exists($attribute, $evidence->getAttributes());
    }

    private function resolveDetailUrl(AccreditationEvidence $evidence, ?callable $detailUrlResolver = null): ?string
    {
        if (! $detailUrlResolver) {
            return null;
        }

        return (string) $detailUrlResolver($evidence);
    }
}