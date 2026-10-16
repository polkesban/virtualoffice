<?php

namespace App\Support;

use InvalidArgumentException;

class LakipDocumentCatalog
{
    public static function documents(): array
    {
        return [
            '2023' => [
                'year' => '2023',
                'label' => 'LAKIP 2023',
                'document_label' => 'LAKIP 2023 Poltekkes Kemenkes Bandung',
                'main_route_name' => 'unit.administrasi-umum.lakip.2023',
                'summary_view' => 'unit.lakip-administrasi-umum-2023',
                'source_pdf' => 'LAKIP Poltekkes Bdg 2023.pdf',
                'legacy_section_anchors' => [
                    'halaman-awal' => '#dokumen-pengantar',
                    'bab-i' => '#dokumen-bab-1',
                    'bab-ii' => '#dokumen-bab-2',
                    'bab-iii' => '#dokumen-bab-3',
                    'bab-iv' => '#dokumen-bab-4',
                ],
            ],
            '2025' => [
                'year' => '2025',
                'label' => 'LAKIP 2025',
                'document_label' => 'LAKIP 2025 Poltekkes Kemenkes Bandung',
                'main_route_name' => 'unit.administrasi-umum.lakip',
                'summary_view' => 'unit.lakip-administrasi-umum',
                'source_pdf' => 'LAKIP POLTEKKES BANDUNG TAHUN 2025 FINAL dan LAMPIRAN.pdf',
                'legacy_section_anchors' => [
                    'halaman-awal' => '#dokumen-pengantar',
                    'bab-i' => '#dokumen-bab-1',
                    'bab-ii' => '#dokumen-bab-2',
                    'bab-iii' => '#dokumen-bab-3',
                    'bab-iv' => '#dokumen-bab-4',
                    'lampiran' => '#dokumen-lampiran',
                ],
            ],
            '2024' => [
                'year' => '2024',
                'label' => 'LAKIP 2024',
                'document_label' => 'LAKIP 2024 Poltekkes Kemenkes Bandung',
                'main_route_name' => 'unit.administrasi-umum.lakip.2024',
                'summary_view' => 'unit.lakip-administrasi-umum-2024',
                'source_pdf' => 'LKJ 2024 BANDUNG_31 Januari 2025.pdf',
                'legacy_section_anchors' => [
                    'halaman-awal' => '#dokumen-pengantar',
                    'bab-i' => '#dokumen-bab-1',
                    'bab-ii' => '#dokumen-bab-2',
                    'bab-iii' => '#dokumen-bab-3',
                    'bab-iv' => '#dokumen-bab-4',
                ],
            ],
        ];
    }

    public static function forYear(string $year): array
    {
        $documents = self::documents();

        if (! array_key_exists($year, $documents)) {
            throw new InvalidArgumentException("Unknown LAKIP year [{$year}].");
        }

        return $documents[$year];
    }
}