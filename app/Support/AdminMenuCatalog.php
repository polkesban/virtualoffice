<?php

namespace App\Support;

use App\Models\HalamanStatis;

class AdminMenuCatalog
{
    public static function grouped(): array
    {
        $staticPages = HalamanStatis::whereNotIn('kunci', HalamanStatis::systemKeys())
            ->orderBy('kunci')
            ->get()
            ->mapWithKeys(fn (HalamanStatis $page) => [
                'halaman-statis:' . $page->kunci => ($page->menu_label ?: $page->judul),
            ])
            ->toArray();

        return [
            'Konten' => [
                'berita' => 'Berita',
                'pengumuman' => 'Pengumuman',
                'galeri' => 'Galeri',
                'agenda' => 'Agenda & Kalender',
            ],
            'Akademik & Kemahasiswaan' => [
                'program-studi' => 'Program Studi',
                'berprestasi' => 'Mahasiswa Berprestasi',
            ],
            'Mutu & Akreditasi' => [
                'dokumen-mutu' => 'Dokumen Mutu',
                'eviden-akreditasi' => 'Ruang Eviden Akreditasi',
            ],
            'Unit Khusus' => [
                'layanan-bisnis' => 'Layanan & Produk Bisnis',
                'layanan-lab' => 'Layanan & Tarif Lab',
                'fasilitas-asrama' => 'Fasilitas Asrama',
                'koleksi-perpus' => 'Koleksi Perpustakaan',
                'publikasi' => 'Publikasi & Penelitian',
                'layanan-ti' => 'Layanan IT',
                'pelatihan' => 'Program Pelatihan',
            ],
            'Halaman Unit (Page Builder)' => [
                'halaman-unit' => 'Halaman Unit Saya',
            ],
            'Halaman Statis' => $staticPages,
        ];
    }

    public static function flat(): array
    {
        return collect(self::grouped())
            ->reduce(function (array $carry, array $group): array {
                foreach ($group as $key => $label) {
                    $carry[$key] = $label;
                }

                return $carry;
            }, []);
    }

    public static function keys(): array
    {
        return array_keys(self::flat());
    }
}