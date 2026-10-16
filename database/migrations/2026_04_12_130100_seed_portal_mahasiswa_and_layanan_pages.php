<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unit_pages')) {
            return;
        }

        $now = now();

        $pages = [
            ['role' => 'org_bem_km', 'slug' => 'bem-km-polkesban', 'judul' => 'BEM KM Poltekkes Bandung', 'sub_judul' => 'Halaman resmi Badan Eksekutif Mahasiswa Keluarga Mahasiswa Poltekkes Bandung.', 'warna_tema' => '#7c3aed'],
            ['role' => 'org_hima_keperawatan', 'slug' => 'hima-keperawatan', 'judul' => 'HIMA Keperawatan', 'sub_judul' => 'Halaman resmi Himpunan Mahasiswa Keperawatan Poltekkes Bandung.', 'warna_tema' => '#0f766e'],
            ['role' => 'org_hima_kebidanan', 'slug' => 'hima-kebidanan', 'judul' => 'HIMA Kebidanan', 'sub_judul' => 'Halaman resmi Himpunan Mahasiswa Kebidanan Poltekkes Bandung.', 'warna_tema' => '#0f766e'],
            ['role' => 'org_hima_gizi', 'slug' => 'hima-gizi', 'judul' => 'HIMA Gizi', 'sub_judul' => 'Halaman resmi Himpunan Mahasiswa Gizi Poltekkes Bandung.', 'warna_tema' => '#0f766e'],
            ['role' => 'org_hima_kesehatan-lingkungan', 'slug' => 'hima-kesehatan-lingkungan', 'judul' => 'HIMA Kesehatan Lingkungan', 'sub_judul' => 'Halaman resmi Himpunan Mahasiswa Kesehatan Lingkungan Poltekkes Bandung.', 'warna_tema' => '#0f766e'],
            ['role' => 'org_hima_teknologi-laboratorium-medis', 'slug' => 'hima-teknologi-laboratorium-medis', 'judul' => 'HIMA Teknologi Laboratorium Medis', 'sub_judul' => 'Halaman resmi Himpunan Mahasiswa Teknologi Laboratorium Medis Poltekkes Bandung.', 'warna_tema' => '#0f766e'],
            ['role' => 'org_hima_kesehatan-gigi', 'slug' => 'hima-kesehatan-gigi', 'judul' => 'HIMA Kesehatan Gigi', 'sub_judul' => 'Halaman resmi Himpunan Mahasiswa Kesehatan Gigi Poltekkes Bandung.', 'warna_tema' => '#0f766e'],
            ['role' => 'org_hima_farmasi', 'slug' => 'hima-farmasi', 'judul' => 'HIMA Farmasi', 'sub_judul' => 'Halaman resmi Himpunan Mahasiswa Farmasi Poltekkes Bandung.', 'warna_tema' => '#0f766e'],
            ['role' => 'org_hima_promkes', 'slug' => 'hima-promkes', 'judul' => 'HIMA Promosi Kesehatan', 'sub_judul' => 'Halaman resmi Himpunan Mahasiswa Promosi Kesehatan Poltekkes Bandung.', 'warna_tema' => '#0f766e'],
            ['role' => 'unit_upk', 'slug' => 'unit-pengembangan-kompetensi', 'judul' => 'Unit Pengembangan Kompetensi', 'sub_judul' => 'Pusat layanan pengembangan kompetensi, pelatihan, dan peningkatan kapasitas.', 'warna_tema' => '#9333ea'],
            ['role' => 'sub_p3_kurikulum', 'slug' => 'pengembangan-kurikulum', 'judul' => 'Pengembangan Kurikulum', 'sub_judul' => 'Inisiatif dan pengembangan kurikulum pembelajaran Poltekkes Bandung.', 'warna_tema' => '#be185d'],
            ['role' => 'sub_p3_ipe', 'slug' => 'interprofessional-education', 'judul' => 'Interprofessional Education', 'sub_judul' => 'Kolaborasi pendidikan lintas profesi untuk pembelajaran dan layanan kesehatan.', 'warna_tema' => '#db2777'],
            ['role' => 'sub_p3_puiptek', 'slug' => 'pusat-unggulan-iptek', 'judul' => 'Pusat Unggulan IPTEK', 'sub_judul' => 'Ruang pengembangan pusat unggulan ilmu pengetahuan, teknologi, dan inovasi terapan.', 'warna_tema' => '#e11d48'],
        ];

        foreach ($pages as $page) {
            $exists = DB::table('unit_pages')->where('role', $page['role'])->exists();

            if ($exists) {
                continue;
            }

            DB::table('unit_pages')->insert(array_merge($page, [
                'aktif' => true,
                'cover_foto' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('unit_pages')) {
            return;
        }

        DB::table('unit_pages')->whereIn('role', [
            'org_bem_km',
            'org_hima_keperawatan',
            'org_hima_kebidanan',
            'org_hima_gizi',
            'org_hima_kesehatan-lingkungan',
            'org_hima_teknologi-laboratorium-medis',
            'org_hima_kesehatan-gigi',
            'org_hima_farmasi',
            'org_hima_promkes',
            'unit_upk',
            'sub_p3_kurikulum',
            'sub_p3_ipe',
            'sub_p3_puiptek',
        ])->delete();
    }
};