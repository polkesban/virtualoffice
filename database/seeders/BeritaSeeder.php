<?php

namespace Database\Seeders;

use App\Models\Berita;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BeritaSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        if (! $admin) return;

        $beritaData = [
            [
                'judul'      => 'Poltekkes Bandung Raih Akreditasi Unggul dari LAM-PTKes',
                'kategori'   => 'prestasi',
                'ringkasan'  => 'Politeknik Kesehatan Kemenkes Bandung berhasil meraih akreditasi Unggul dari Lembaga Akreditasi Mandiri Perguruan Tinggi Kesehatan.',
            ],
            [
                'judul'      => 'Penerimaan Mahasiswa Baru 2026/2027 Resmi Dibuka',
                'kategori'   => 'pengumuman',
                'ringkasan'  => 'Poltekkes Bandung membuka pendaftaran mahasiswa baru untuk tahun akademik 2026/2027 melalui jalur PMDP dan UTBK-SNBT.',
            ],
            [
                'judul'      => 'Wisuda ke-52: 480 Lulusan Siap Mengabdi',
                'kategori'   => 'kegiatan',
                'ringkasan'  => 'Sebanyak 480 mahasiswa Poltekkes Bandung resmi diwisuda dalam Upacara Wisuda ke-52 yang berlangsung di Graha Sabha Poltekkes Bandung.',
            ],
            [
                'judul'      => 'Seminar Nasional Kesehatan: Inovasi di Era Digital',
                'kategori'   => 'kegiatan',
                'ringkasan'  => 'Poltekkes Bandung menyelenggarakan Seminar Nasional Kesehatan dengan tema Transformasi Digital dalam Pelayanan Kesehatan.',
            ],
            [
                'judul'      => 'Mahasiswa Farmasi Juara 1 Lomba Inovasi Nasional',
                'kategori'   => 'prestasi',
                'ringkasan'  => 'Tim mahasiswa Program Studi D3 Farmasi Poltekkes Bandung berhasil meraih Juara 1 dalam Lomba Inovasi Produk Farmasi Tingkat Nasional.',
            ],
            [
                'judul'      => 'Kerjasama Poltekkes Bandung dengan RSHS Bandung Diperkuat',
                'kategori'   => 'berita',
                'ringkasan'  => 'Poltekkes Bandung dan RSUP Dr. Hasan Sadikin Bandung menandatangani MoU perpanjangan kerjasama pendidikan klinis.',
            ],
        ];

        foreach ($beritaData as $i => $data) {
            $judul = $data['judul'];
            Berita::updateOrCreate(
                ['slug' => Str::slug($judul)],
                [
                    'user_id'      => $admin->id,
                    'judul'        => $judul,
                    'slug'         => Str::slug($judul),
                    'ringkasan'    => $data['ringkasan'],
                    'isi'          => '<p>' . $data['ringkasan'] . '</p><p>Informasi selengkapnya akan segera tersedia.</p>',
                    'kategori'     => $data['kategori'],
                    'status'       => 'published',
                    'published_at' => now()->subDays($i * 3),
                ]
            );
        }
    }
}
