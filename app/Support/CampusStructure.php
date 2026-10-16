<?php

namespace App\Support;

class CampusStructure
{
    public static function programStudyGroups(): array
    {
        return [
            [
                'key' => 'keperawatan',
                'label' => 'Keperawatan',
                'order' => 10,
                'direct' => false,
                'prodi_slugs' => ['keperawatan', 'keperawatan-bandung', 'keperawatan-bogor', 'profesi-ners'],
            ],
            [
                'key' => 'kebidanan',
                'label' => 'Kebidanan',
                'order' => 20,
                'direct' => false,
                'prodi_slugs' => ['kebidanan', 'kebidanan-bandung', 'kebidanan-karawang', 'kebidanan-bogor', 'profesi-bidan'],
            ],
            [
                'key' => 'gizi',
                'label' => 'Gizi',
                'order' => 30,
                'direct' => false,
                'prodi_slugs' => ['gizi', 'gizi-d3', 'profesi-dietisien'],
            ],
            [
                'key' => 'kesehatan-lingkungan',
                'label' => 'Kesehatan Lingkungan',
                'order' => 40,
                'direct' => false,
                'prodi_slugs' => ['kesehatan-lingkungan', 'sanitasi-kesehatan-lingkungan'],
            ],
            [
                'key' => 'teknologi-laboratorium-medis',
                'label' => 'Teknologi Laboratorium Medis',
                'order' => 50,
                'direct' => false,
                'prodi_slugs' => ['sarjana-terapan-tlm', 'analis-kesehatan'],
            ],
            [
                'key' => 'kesehatan-gigi',
                'label' => 'Kesehatan Gigi',
                'order' => 60,
                'direct' => false,
                'prodi_slugs' => ['terapi-gigi', 'kesehatan-gigi'],
            ],
            [
                'key' => 'farmasi',
                'label' => 'Farmasi',
                'order' => 70,
                'direct' => true,
                'prodi_slugs' => ['farmasi'],
            ],
            [
                'key' => 'promkes',
                'label' => 'Promosi Kesehatan',
                'order' => 80,
                'direct' => true,
                'prodi_slugs' => ['promosi-kesehatan'],
            ],
        ];
    }

    public static function studentOrganizationPages(): array
    {
        $pages = [
            [
                'role' => 'org_bem_km',
                'slug' => 'bem-km-polkesban',
                'judul' => 'BEM KM Poltekkes Bandung',
                'sub_judul' => 'Halaman resmi Badan Eksekutif Mahasiswa Keluarga Mahasiswa Poltekkes Bandung.',
                'warna_tema' => '#7c3aed',
            ],
        ];

        foreach (self::programStudyGroups() as $group) {
            $pages[] = [
                'role' => 'org_hima_' . $group['key'],
                'slug' => 'hima-' . $group['key'],
                'judul' => 'HIMA ' . $group['label'],
                'sub_judul' => 'Halaman resmi Himpunan Mahasiswa ' . $group['label'] . ' Poltekkes Bandung.',
                'warna_tema' => '#0f766e',
            ];
        }

        return $pages;
    }

    public static function additionalManagedPages(): array
    {
        return [
            [
                'role' => 'bidang_osdm',
                'slug' => 'bidang-osdm',
                'judul' => 'Bidang OSDM',
                'sub_judul' => 'Halaman publik untuk pengelolaan organisasi, sumber daya manusia, dan pengembangan internal.',
                'warna_tema' => '#0284c7',
            ],
            [
                'role' => 'unit_upk',
                'slug' => 'unit-pengembangan-kompetensi',
                'judul' => 'Unit Pengembangan Kompetensi',
                'sub_judul' => 'Pusat layanan pengembangan kompetensi, pelatihan, dan peningkatan kapasitas.',
                'warna_tema' => '#9333ea',
            ],
            [
                'role' => 'sub_p3_kurikulum',
                'slug' => 'pengembangan-kurikulum',
                'judul' => 'Pengembangan Kurikulum',
                'sub_judul' => 'Inisiatif, kebijakan, dan inovasi pengembangan kurikulum di Poltekkes Bandung.',
                'warna_tema' => '#be185d',
            ],
            [
                'role' => 'sub_p3_ipe',
                'slug' => 'interprofessional-education',
                'judul' => 'Interprofessional Education',
                'sub_judul' => 'Kolaborasi pembelajaran lintas profesi untuk penguatan layanan kesehatan.',
                'warna_tema' => '#db2777',
            ],
            [
                'role' => 'sub_p3_puiptek',
                'slug' => 'pusat-unggulan-iptek',
                'judul' => 'Pusat Unggulan IPTEK',
                'sub_judul' => 'Program unggulan pengembangan ilmu pengetahuan, teknologi, dan inovasi terapan.',
                'warna_tema' => '#e11d48',
            ],
        ];
    }

    public static function layananOrderForRole(string $role): int
    {
        return [
            'direktur' => 5,
            'wadir_1' => 10,
            'wadir_3' => 20,
            'wadir_2' => 30,
            'kabag_adum' => 31,
            'unit_humas_kerjasama' => 32,
            'bidang_osdm' => 35,
            'pusat_litmas' => 40,
            'pusat_p3' => 50,
            'unit_upk' => 60,
            'pusat_mutu' => 70,
            'spi' => 80,
            'unit_bisnis' => 90,
            'unit_lab' => 110,
            'unit_perpus' => 120,
            'unit_bahasa' => 125,
            'unit_ti' => 130,
        ][$role] ?? 999;
    }

    public static function layananChildLinks(): array
    {
        return [
            'wadir_2' => [
                ['label' => 'LAKIP 2025', 'href' => '/unit/bidang-administrasi-umum/lakip'],
                ['label' => 'LAKIP 2024', 'href' => '/unit/bidang-administrasi-umum/lakip-2024'],
                ['label' => 'LAKIP 2023', 'href' => '/unit/bidang-administrasi-umum/lakip-2023'],
            ],
            'unit_bisnis' => [
                ['label' => 'Layanan Produk dan Bisnis', 'href' => '/layanan-bisnis'],
                ['label' => 'Fasilitas Asrama', 'href' => '/fasilitas-asrama'],
                ['label' => 'Koperasi', 'href' => '#'],
            ],
            'unit_lab' => [
                ['label' => 'Layanan dan Tarif Lab', 'href' => '/layanan-lab'],
            ],
            'unit_perpus' => [
                ['label' => 'Koleksi Perpustakaan', 'href' => '/koleksi-perpustakaan'],
            ],
            'pusat_litmas' => [
                ['label' => 'Publikasi dan Penelitian', 'href' => '/publikasi'],
                ['label' => 'Pengabmas/Pengabdian kepada Masyarakat', 'href' => '#'],
            ],
            'pusat_mutu' => [
                ['label' => 'Dokumen Mutu', 'href' => '/dokumen-mutu'],
            ],
            'unit_ti' => [
                ['label' => 'Layanan IT', 'href' => '/layanan-ti'],
            ],
            'unit_upk' => [
                ['label' => 'Program Pelatihan', 'href' => '/pelatihan'],
                ['label' => 'SIPANDU', 'href' => '#'],
            ],
            'pusat_p3' => [
                ['label' => 'Pengembangan Kurikulum', 'href' => '/unit/pengembangan-kurikulum'],
                ['label' => 'Interprofessional Education', 'href' => '/unit/interprofessional-education'],
                ['label' => 'Pusat Unggulan IPTEK', 'href' => '/unit/pusat-unggulan-iptek'],
            ],
        ];
    }
}