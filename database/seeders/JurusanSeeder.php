<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\ProgramStudi;
use App\Models\UnitPage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JurusanSeeder extends Seeder
{
    public function run(): void
    {
        // Cleanup deleted jurusans (Fisioterapi and Rekam Medis)
        $deletedSlugs = ['fisioterapi', 'rekam-medis-dan-informasi-kesehatan'];
        $deletedRoles = ['jurusan_fisio', 'jurusan_rmik'];
        
        // Remove from DB
        Jurusan::whereIn('slug', $deletedSlugs)->delete();
        UnitPage::whereIn('role', $deletedRoles)->delete();
        
        // Clear association
        ProgramStudi::whereIn('id', [4, 8])->update(['jurusan_id' => null]);

        $jurusans = [
            'Keperawatan' => [
                'singkatan' => 'Kep',
                'jenjang' => 'D4',
                'prodis' => [1, 12, 13, 19]
            ],
            'Kebidanan' => [
                'singkatan' => 'Keb',
                'jenjang' => 'D4',
                'prodis' => [2, 14, 15, 16, 20]
            ],
            'Gizi' => [
                'singkatan' => 'Gizi',
                'jenjang' => 'D4',
                'prodis' => [3, 21, 22]
            ],
            'Kesehatan Lingkungan' => [
                'singkatan' => 'Kesling',
                'jenjang' => 'D4',
                'prodis' => [5, 18]
            ],
            'Kesehatan Gigi' => [
                'singkatan' => 'Kesgigi',
                'jenjang' => 'D3',
                'prodis' => [11, 17]
            ],
            'Teknologi Laboratorium Medis' => [
                'singkatan' => 'TLM',
                'jenjang' => 'D4',
                'prodis' => [6, 10]
            ],
            'Farmasi' => [
                'singkatan' => 'Farm',
                'jenjang' => 'D3',
                'prodis' => [7]
            ],
            'Promosi Kesehatan' => [
                'singkatan' => 'Promkes',
                'jenjang' => 'D4',
                'prodis' => [9]
            ]
        ];

        foreach ($jurusans as $nama => $info) {
            $slug = Str::slug($nama);
            
            // Create or update Jurusan record
            $jurusan = Jurusan::updateOrCreate(
                ['slug' => $slug],
                [
                    'nama' => 'Jurusan ' . $nama,
                    'singkatan' => $info['singkatan'],
                    'jenjang' => $info['jenjang'],
                    'aktif' => true,
                    'tampil_di_beranda' => true,
                ]
            );

            // Create or update UnitPage record representing the Jurusan public page
            $pageRole = 'jurusan_' . strtolower($info['singkatan']);
            UnitPage::updateOrCreate(
                ['role' => $pageRole],
                [
                    'slug' => 'jurusan-' . $slug,
                    'judul' => 'Jurusan ' . $nama,
                    'sub_judul' => 'Halaman resmi Jurusan ' . $nama . ' Poltekkes Bandung.',
                    'aktif' => true,
                    'warna_tema' => '#1e3a8a',
                ]
            );

            // Map the program studi records to this Jurusan
            ProgramStudi::whereIn('id', $info['prodis'])->update([
                'jurusan_id' => $jurusan->id
            ]);
        }
    }
}
