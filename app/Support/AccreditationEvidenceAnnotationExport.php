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

class AccreditationEvidenceAnnotationExport
{
    private const COLUMNS = [
        ['header' => 'Status Tindak Lanjut', 'width' => 24],
        ['header' => 'Pinned', 'width' => 12],
        ['header' => 'Kriteria', 'width' => 34],
        ['header' => 'Butir', 'width' => 14],
        ['header' => 'Judul Eviden', 'width' => 40],
        ['header' => 'Status Eviden', 'width' => 18],
        ['header' => 'PIC', 'width' => 24],
        ['header' => 'Review Berikutnya', 'width' => 18],
        ['header' => 'Masa Berlaku', 'width' => 18],
        ['header' => 'Judul Anotasi', 'width' => 34],
        ['header' => 'Kutipan', 'width' => 46],
        ['header' => 'Catatan Reviewer', 'width' => 52],
        ['header' => 'Dibuat Oleh', 'width' => 22],
        ['header' => 'Diperbarui Oleh', 'width' => 22],
        ['header' => 'Diperbarui Pada', 'width' => 22],
        ['header' => 'Tautan Detail Eviden', 'width' => 48],
    ];

    public function headings(): array
    {
        return array_column(self::COLUMNS, 'header');
    }

    public function rows(Collection $annotations, ?callable $detailUrlResolver = null): array
    {
        return $annotations
            ->map(fn (AccreditationEvidenceAnnotation $annotation) => $this->mapRow($annotation, $detailUrlResolver))
            ->values()
            ->all();
    }

    public function spreadsheet(Collection $annotations, ?callable $detailUrlResolver = null): Spreadsheet
    {
        $rows = $this->rows($annotations, $detailUrlResolver);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tindak Reviewer');

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

    private function mapRow(AccreditationEvidenceAnnotation $annotation, ?callable $detailUrlResolver = null): array
    {
        $evidence = $annotation->evidence;

        return [
            $annotation->status_label,
            $annotation->is_pinned ? 'Ya' : 'Tidak',
            $evidence?->folder?->display_label,
            $evidence?->subcriterion_code,
            $evidence?->title,
            $evidence?->verification_status_label,
            $evidence?->pic_name,
            $evidence?->review_due_at?->format('Y-m-d'),
            $evidence?->expires_at?->format('Y-m-d'),
            $annotation->title,
            $annotation->excerpt,
            $annotation->note,
            $annotation->creator?->name,
            $annotation->editor?->name,
            $annotation->updated_at?->format('Y-m-d H:i:s'),
            $this->resolveDetailUrl($evidence, $detailUrlResolver),
        ];
    }

    private function resolveDetailUrl(?AccreditationEvidence $evidence, ?callable $detailUrlResolver = null): ?string
    {
        if (! $evidence || ! $detailUrlResolver) {
            return null;
        }

        return (string) $detailUrlResolver($evidence);
    }
}