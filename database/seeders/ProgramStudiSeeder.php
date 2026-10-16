<?php

namespace Database\Seeders;

use App\Models\ProgramStudi;
use Illuminate\Database\Seeder;

class ProgramStudiSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'nama'             => 'D4 Keperawatan',
                'singkatan'        => 'D4 Kep',
                'jenjang'          => 'D4',
                'slug'             => 'keperawatan',
                'icon_emoji'       => '🏥',
                'akreditasi'       => 'Unggul',
                'durasi_semester'  => 8,
                'kuota'            => 80,
                'gelar'            => 'S.Tr.Kep',
                'deskripsi_singkat'=> 'Menghasilkan tenaga keperawatan profesional dengan kompetensi klinis dan manajemen keperawatan tingkat lanjut.',
                'urutan'           => 1,
            ],
            [
                'nama'             => 'D4 Kebidanan',
                'singkatan'        => 'D4 Keb',
                'jenjang'          => 'D4',
                'slug'             => 'kebidanan',
                'icon_emoji'       => '👶',
                'akreditasi'       => 'Unggul',
                'durasi_semester'  => 8,
                'kuota'            => 60,
                'gelar'            => 'S.Tr.Keb',
                'deskripsi_singkat'=> 'Mencetak bidan profesional yang kompeten dalam pelayanan kesehatan ibu dan anak.',
                'urutan'           => 2,
            ],
            [
                'nama'             => 'D4 Gizi',
                'singkatan'        => 'D4 Gizi',
                'jenjang'          => 'D4',
                'slug'             => 'gizi',
                'icon_emoji'       => '🥗',
                'akreditasi'       => 'A',
                'durasi_semester'  => 8,
                'kuota'            => 50,
                'gelar'            => 'S.Tr.Gz',
                'deskripsi_singkat'=> 'Menghasilkan ahli gizi yang mampu mengelola program gizi masyarakat dan klinik.',
                'urutan'           => 3,
            ],
            [
                'nama'             => 'D4 Fisioterapi',
                'singkatan'        => 'D4 Fisio',
                'jenjang'          => 'D4',
                'slug'             => 'fisioterapi',
                'icon_emoji'       => '🦴',
                'akreditasi'       => 'A',
                'durasi_semester'  => 8,
                'kuota'            => 50,
                'gelar'            => 'S.Tr.Fis',
                'deskripsi_singkat'=> 'Mencetak fisioterapis profesional yang kompeten dalam rehabilitasi fisik pasien.',
                'urutan'           => 4,
            ],
            [
                'nama'             => 'D4 Kesehatan Lingkungan',
                'singkatan'        => 'D4 Kesling',
                'jenjang'          => 'D4',
                'slug'             => 'kesehatan-lingkungan',
                'icon_emoji'       => '🌿',
                'akreditasi'       => 'A',
                'durasi_semester'  => 8,
                'kuota'            => 40,
                'gelar'            => 'S.Tr.KL',
                'deskripsi_singkat'=> 'Menghasilkan tenaga ahli di bidang sanitasi dan kesehatan lingkungan.',
                'urutan'           => 5,
            ],
            [
                'nama'             => 'D3 Analis Kesehatan',
                'singkatan'        => 'D3 AK',
                'jenjang'          => 'D3',
                'slug'             => 'analis-kesehatan',
                'icon_emoji'       => '🔬',
                'akreditasi'       => 'Unggul',
                'durasi_semester'  => 6,
                'kuota'            => 80,
                'gelar'            => 'A.Md.AK',
                'deskripsi_singkat'=> 'Mencetak analis laboratorium medis yang terampil dalam pemeriksaan diagnostik klinis.',
                'urutan'           => 6,
            ],
            [
                'nama'             => 'D3 Farmasi',
                'singkatan'        => 'D3 Farm',
                'jenjang'          => 'D3',
                'slug'             => 'farmasi',
                'icon_emoji'       => '💊',
                'akreditasi'       => 'A',
                'durasi_semester'  => 6,
                'kuota'            => 60,
                'gelar'            => 'A.Md.Farm',
                'deskripsi_singkat'=> 'Menghasilkan tenaga teknis kefarmasian yang kompeten dalam pengelolaan obat.',
                'urutan'           => 7,
            ],
            [
                'nama'             => 'D3 Rekam Medis',
                'singkatan'        => 'D3 RM',
                'jenjang'          => 'D3',
                'slug'             => 'rekam-medis',
                'icon_emoji'       => '📋',
                'akreditasi'       => 'A',
                'durasi_semester'  => 6,
                'kuota'            => 60,
                'gelar'            => 'A.Md.RMIK',
                'deskripsi_singkat'=> 'Mencetak tenaga ahli rekam medis dan informasi kesehatan yang terampil.',
                'urutan'           => 8,
            ],
        ];

        foreach ($data as $item) {
            ProgramStudi::updateOrCreate(['slug' => $item['slug']], $item);
        }
    }
}
