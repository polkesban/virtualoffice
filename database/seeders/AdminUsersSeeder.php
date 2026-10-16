<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUsersSeeder extends Seeder
{
    /**
     * Daftar semua akun admin pengelola beserta password sementara.
     * Setelah login pertama, masing-masing pengelola sebaiknya mengganti password.
     */
    public function run(): void
    {
        $users = [
            // ── Struktural ──────────────────────────────────────────────────
            [
                'name'     => 'Wakil Direktur I',
                'email'    => 'wadir1@polkesban.ac.id',
                'password' => 'Polkesban@Wadir1',
                'role'     => User::ROLE_WADIR_1,
            ],
            [
                'name'     => 'Wakil Direktur II',
                'email'    => 'wadir2@polkesban.ac.id',
                'password' => 'Polkesban@Wadir2',
                'role'     => User::ROLE_WADIR_2,
            ],
            [
                'name'     => 'Unit Humas dan Kerjasama',
                'email'    => 'humas.kerjasama@polkesban.ac.id',
                'password' => 'Polkesban@Humas26',
                'role'     => User::ROLE_UNIT_HUMAS_KERJASAMA,
            ],
            [
                'name'     => 'Wakil Direktur III',
                'email'    => 'wadir3@polkesban.ac.id',
                'password' => 'Polkesban@Wadir3',
                'role'     => User::ROLE_WADIR_3,
            ],
            // ── Pusat ────────────────────────────────────────────────────────
            [
                'name'     => 'Pusat Penjaminan Mutu',
                'email'    => 'mutu@polkesban.ac.id',
                'password' => 'Polkesban@Mutu24',
                'role'     => User::ROLE_PUSAT_MUTU,
            ],
            [
                'name'     => 'Pusat Litmas & Pengabmas',
                'email'    => 'litmas@polkesban.ac.id',
                'password' => 'Polkesban@Litmas',
                'role'     => User::ROLE_PUSAT_LITMAS,
            ],
            // ── Unit ─────────────────────────────────────────────────────────
            [
                'name'     => 'Unit Teknologi Informasi',
                'email'    => 'ti@polkesban.ac.id',
                'password' => 'Polkesban@UnitTI',
                'role'     => User::ROLE_UNIT_TI,
            ],
            [
                'name'     => 'Unit Perpustakaan Terpadu',
                'email'    => 'perpus@polkesban.ac.id',
                'password' => 'Polkesban@Perpus',
                'role'     => User::ROLE_UNIT_PERPUS,
            ],
            [
                'name'     => 'Unit Laboratorium Terpadu',
                'email'    => 'lab@polkesban.ac.id',
                'password' => 'Polkesban@UnitLab',
                'role'     => User::ROLE_UNIT_LAB,
            ],
            [
                'name'     => 'Unit Bahasa',
                'email'    => 'bahasa@polkesban.ac.id',
                'password' => 'Polkesban@Bahasa',
                'role'     => User::ROLE_UNIT_BAHASA,
            ],
            [
                'name'     => 'Unit Bisnis',
                'email'    => 'bisnis@polkesban.ac.id',
                'password' => 'Polkesban@Bisnis',
                'role'     => User::ROLE_UNIT_BISNIS,
            ],
            [
                'name'     => 'Unit Asrama',
                'email'    => 'asrama@polkesban.ac.id',
                'password' => 'Polkesban@Asrama',
                'role'     => User::ROLE_UNIT_ASRAMA,
            ],
            // ── SPI ──────────────────────────────────────────────────────────
            [
                'name'     => 'Satuan Pengawas Internal',
                'email'    => 'spi@polkesban.ac.id',
                'password' => 'Polkesban@SPI247',
                'role'     => User::ROLE_SPI,
            ],
            // ── Kaprodi (1 akun per 22 prodi) ────────────────────────────────
            ['name' => 'Kaprodi D4 Keperawatan',    'email' => 'kaprodi.kep.d4@polkesban.ac.id',      'password' => 'Kaprodi@Kep01',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 1],
            ['name' => 'Kaprodi D4 Kebidanan',       'email' => 'kaprodi.keb.d4@polkesban.ac.id',      'password' => 'Kaprodi@Keb02',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 2],
            ['name' => 'Kaprodi D4 Gizi',            'email' => 'kaprodi.gizi.d4@polkesban.ac.id',     'password' => 'Kaprodi@Giz03',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 3],
            ['name' => 'Kaprodi D4 Fisioterapi',     'email' => 'kaprodi.fisio.d4@polkesban.ac.id',    'password' => 'Kaprodi@Fis04',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 4],
            ['name' => 'Kaprodi D4 Sanitasi',        'email' => 'kaprodi.sl.d4@polkesban.ac.id',       'password' => 'Kaprodi@SL005',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 5],
            ['name' => 'Kaprodi D3 TLM',             'email' => 'kaprodi.tlm.d3@polkesban.ac.id',      'password' => 'Kaprodi@TLM06',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 6],
            ['name' => 'Kaprodi D3 Farmasi',         'email' => 'kaprodi.farmasi@polkesban.ac.id',     'password' => 'Kaprodi@Far07',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 7],
            ['name' => 'Kaprodi D3 RMIK',            'email' => 'kaprodi.rmik@polkesban.ac.id',        'password' => 'Kaprodi@RMIK08', 'role' => User::ROLE_KAPRODI, 'program_studi_id' => 8],
            ['name' => 'Kaprodi D4 Promosi Kes.',    'email' => 'kaprodi.prokes@polkesban.ac.id',      'password' => 'Kaprodi@PK009',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 9],
            ['name' => 'Kaprodi D4 TLM',             'email' => 'kaprodi.tlm.d4@polkesban.ac.id',      'password' => 'Kaprodi@TLM10',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 10],
            ['name' => 'Kaprodi D4 Terapi Gigi',     'email' => 'kaprodi.tg@polkesban.ac.id',          'password' => 'Kaprodi@TG011',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 11],
            ['name' => 'Kaprodi D3 Kep Bandung',     'email' => 'kaprodi.kep.bdg@polkesban.ac.id',     'password' => 'Kaprodi@KB012',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 12],
            ['name' => 'Kaprodi D3 Kep Bogor',       'email' => 'kaprodi.kep.bgr@polkesban.ac.id',     'password' => 'Kaprodi@KBg13',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 13],
            ['name' => 'Kaprodi D3 Keb Bandung',     'email' => 'kaprodi.keb.bdg@polkesban.ac.id',     'password' => 'Kaprodi@KB014',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 14],
            ['name' => 'Kaprodi D3 Keb Karawang',    'email' => 'kaprodi.keb.krw@polkesban.ac.id',     'password' => 'Kaprodi@KKr15',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 15],
            ['name' => 'Kaprodi D3 Keb Bogor',       'email' => 'kaprodi.keb.bgr@polkesban.ac.id',     'password' => 'Kaprodi@KBo16',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 16],
            ['name' => 'Kaprodi D3 Kesehatan Gigi',  'email' => 'kaprodi.kg@polkesban.ac.id',          'password' => 'Kaprodi@KG017',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 17],
            ['name' => 'Kaprodi D3 Sanitasi',        'email' => 'kaprodi.sanit@polkesban.ac.id',       'password' => 'Kaprodi@San18',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 18],
            ['name' => 'Kaprodi Profesi Ners',        'email' => 'kaprodi.ners@polkesban.ac.id',        'password' => 'Kaprodi@Ner19',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 19],
            ['name' => 'Kaprodi Profesi Bidan',       'email' => 'kaprodi.bidan@polkesban.ac.id',       'password' => 'Kaprodi@Bid20',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 20],
            ['name' => 'Kaprodi Profesi Dietisien',   'email' => 'kaprodi.dietisien@polkesban.ac.id',   'password' => 'Kaprodi@Die21',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 21],
            ['name' => 'Kaprodi D3 Gizi',             'email' => 'kaprodi.gizi.d3@polkesban.ac.id',    'password' => 'Kaprodi@Giz22',  'role' => User::ROLE_KAPRODI, 'program_studi_id' => 22],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name'             => $u['name'],
                    'password'         => Hash::make($u['password']),
                    'role'             => $u['role'],
                    'is_admin'         => true,
                    'program_studi_id' => $u['program_studi_id'] ?? null,
                ]
            );
        }

        $this->command->info('✅ ' . count($users) . ' akun admin pengelola berhasil dibuat/diperbarui.');
    }
}
