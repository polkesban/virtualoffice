<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Osdm\OsdmEmployee;
use App\Models\Osdm\OsdmKebugaranSubmission;
use App\Models\Osdm\OsdmPortalConfig;
use App\Models\Osdm\OsdmWfaSubmission;

class OsdmSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedEmployees();
        $this->seedKebugaranSubmissions();
        $this->seedPortalConfig();
        $this->seedWfaSubmissions();
    }

    private function seedEmployees(): void
    {
        $jsonPath = __DIR__ . '/osdm_employees.json';
        if (!file_exists($jsonPath)) {
            $this->command->warn('osdm_employees.json not found, skipping.');
            return;
        }

        $employees = json_decode(file_get_contents($jsonPath), true);
        $count = 0;

        foreach ($employees as $emp) {
            OsdmEmployee::updateOrCreate(
                ['nip' => $emp['nip']],
                [
                    'name' => $emp['name'],
                    'unit_kerja' => $emp['unitKerja'] ?? '',
                    'jabatan' => $emp['jabatan'] ?? '',
                    'email' => $emp['email'] ?? null,
                    'nomor_wa' => $emp['nomorWa'] ?? null,
                    'tanggal_lahir' => isset($emp['tanggalLahir']) ? $emp['tanggalLahir'] : null,
                    'nik' => $emp['nik'] ?? null,
                ]
            );
            $count++;
        }

        $this->command->info("Seeded {$count} employees.");
    }

    private function seedKebugaranSubmissions(): void
    {
        $jsonPath = __DIR__ . '/osdm_kebugaran.json';
        if (!file_exists($jsonPath)) {
            $this->command->warn('osdm_kebugaran.json not found, skipping.');
            return;
        }

        $submissions = json_decode(file_get_contents($jsonPath), true);
        $count = 0;

        foreach ($submissions as $sub) {
            // Skip if already exists (by nip + periode)
            $exists = OsdmKebugaranSubmission::where('nip', $sub['nip'])
                ->where('periode', $sub['periode'])
                ->exists();

            if ($exists) {
                continue;
            }

            OsdmKebugaranSubmission::create([
                'tanggal_periksa' => $sub['tanggalPeriksa'],
                'periode' => $sub['periode'],
                'nip' => $sub['nip'],
                'nama_pegawai' => $sub['namaPegawai'],
                'tanggal_lahir' => $sub['tanggalLahir'] ?? null,
                'unit_kerja' => $sub['unitKerja'] ?? '',
                'nik' => $sub['nik'] ?? '',
                'tensi_sistolik' => $sub['tensiSistolik'] ?? 120,
                'tensi_diastolik' => $sub['tensiDiastolik'] ?? 80,
                'berat_badan' => $sub['beratBadan'] ?? 60,
                'tinggi_badan' => $sub['tinggiBadan'] ?? 160,
                'lingkar_pinggang' => $sub['lingkarPinggang'] ?? 75,
                'tipe_gula_darah' => $sub['tipeGulaDarah'] ?? 'GDS',
                'gula_darah' => $sub['gulaDarah'] ?? 100,
                'kolesterol' => $sub['kolesterol'] ?? 180,
                'nomor_wa' => $sub['nomorWa'] ?? '',
                'fasyankes' => $sub['fasyankes'] ?? 'Klinik Pratama Poltekkes Kemenkes Bandung',
                'catatan' => $sub['catatan'] ?? null,
                'created_at' => $sub['createdAt'] ?? now()->toISOString(),
            ]);
            $count++;
        }

        $this->command->info("Seeded {$count} kebugaran submissions.");
    }

    private function seedPortalConfig(): void
    {
        // Seed initial portal config with default menus and profile
        if (OsdmPortalConfig::where('config_key', 'live')->exists()) {
            $this->command->info('Portal config already seeded, skipping.');
            return;
        }

        $initialMenus = [
            [
                'id' => 'menu-dashboard-pegawai',
                'title' => 'Dashboard Pegawai',
                'subtitle' => 'Realtime Seluruh Pegawai Poltekkes Kemenkes Bandung',
                'url' => '#dashboard-pegawai',
                'type' => 'custom',
                'size' => 'featured',
                'bgColor' => '#0284c7',
                'textColor' => '#ffffff',
                'borderColor' => '#38bdf8',
                'isGradient' => true,
                'gradientTo' => '#0369a1',
                'gradientAngle' => 135,
                'iconName' => 'LayoutDashboard',
                'badgeText' => '',
                'isActive' => true,
                'order' => 1,
                'animation' => 'pulse',
                'clickCount' => 850,
                'category' => 'Kepegawaian & HR',
                'openInNewTab' => false,
            ],
            [
                'id' => 'menu-wfa-bimbingan',
                'title' => 'Formulir Pengajuan WFA Bimbingan',
                'subtitle' => 'Pengajuan jadwal WFA bimbingan dosen di Kota/Kabupaten',
                'url' => '#wfa-bimbingan',
                'type' => 'custom',
                'size' => 'featured',
                'bgColor' => '#0f766e',
                'textColor' => '#ffffff',
                'borderColor' => '#14b8a6',
                'isGradient' => true,
                'gradientTo' => '#042f2e',
                'gradientAngle' => 135,
                'iconName' => 'FileText',
                'badgeText' => 'POPULER',
                'badgeBgColor' => '#8b5cf6',
                'badgeTextColor' => '#ffffff',
                'isActive' => true,
                'order' => 5,
                'animation' => 'pulse',
                'clickCount' => 620,
                'category' => 'Kepegawaian & HR',
                'openInNewTab' => false,
                'isProtected' => true,
                'pinCode' => '1234',
                'pinHint' => 'PIN Pengajuan WFA Bimbingan',
            ],
            [
                'id' => 'menu-kebugaran-jasmani',
                'title' => 'Input Hasil Kebugaran Pegawai',
                'subtitle' => 'Lengkapi Data Kebugaran Terakhir Anda',
                'url' => '#input-kebugaran',
                'type' => 'custom',
                'size' => 'featured',
                'bgColor' => '#0f766e',
                'textColor' => '#ffffff',
                'borderColor' => '#14b8a6',
                'isGradient' => true,
                'gradientTo' => '#042f2e',
                'gradientAngle' => 135,
                'iconName' => 'Shield',
                'badgeText' => 'NEWS',
                'badgeBgColor' => '#ef4444',
                'badgeTextColor' => '#ffffff',
                'isActive' => true,
                'order' => 6,
                'animation' => 'pulse',
                'clickCount' => 450,
                'category' => 'Kepegawaian & HR',
                'openInNewTab' => false,
                'isProtected' => true,
                'pinCode' => '1234',
                'pinHint' => 'PIN Input Hasil Kebugaran',
            ],
        ];

        $initialProfile = [
            'name' => 'Portal Layanan Pegawai',
            'tagline' => 'PUSAT AKSES LAYANAN INTERNAL',
            'bio' => 'Portal satu pintu bagi seluruh pegawai Poltekkes Kemenkes Bandung',
            'avatarUrl' => 'https://poltekkesbandung.ac.id/wp-content/uploads/2026/05/cropped-logo-transparan-2.png',
            'logoShape' => 'landscape',
            'logoHeight' => 74,
            'logoBackground' => 'white',
            'isVerified' => true,
            'location' => 'Jl. Padjadjaran No. 56 Bandung',
            'openingHours' => 'Layanan : 08.00 - 16.00 WIB',
            'socialLinks' => [
                ['id' => '1', 'platform' => 'whatsapp', 'url' => 'https://wa.me/6281234567890', 'label' => 'Helpdesk WhatsApp OSDM', 'isActive' => true],
                ['id' => '2', 'platform' => 'email', 'url' => 'mailto:kepegawaian@poltekkesbandung.ac.id', 'label' => 'Email Kepegawaian', 'isActive' => true],
                ['id' => '3', 'platform' => 'phone', 'url' => 'tel:+62224231627', 'label' => 'Telepon Kantor (022-4231627)', 'isActive' => true],
                ['id' => '4', 'platform' => 'website', 'url' => 'https://www.poltekkesbandung.ac.id', 'label' => 'Portal Utama Poltekkes Bandung', 'isActive' => true],
                ['id' => '5', 'platform' => 'maps', 'url' => 'https://maps.google.com/?q=Politeknik+Kesehatan+Kemenkes+Bandung+Jl+Pajajaran+No+56', 'label' => 'Lokasi Kampus Direktorat', 'isActive' => true],
            ],
            'footerBadgeText' => 'Portal Resmi OSDM • Poltekkes Kemenkes Bandung • Kemenkes RI',
            'footerCopyright' => '© 2026 OSDM Poltekkes Kemenkes Bandung. Hak Cipta Dilindungi.',
            'tabTitle' => 'OSDM Poltekkes Kemenkes Bandung - Portal Layanan Pegawai',
            'faviconUrl' => '',
            'osdmContactWa' => '08119712525',
            'theme' => [
                'id' => 'poltekkes-emerald',
                'name' => 'Poltekkes Kemenkes Emerald (Resmi)',
                'bgType' => 'mesh',
                'primaryBg' => '#042f2e',
                'secondaryBg' => '#0f766e',
                'accentColor' => '#10b981',
                'textColor' => '#ffffff',
                'subtextColor' => '#ccfbf1',
                'cardRadius' => 'rounded-xl',
                'cardGlassEffect' => true,
                'fontFamily' => 'sans',
                'layoutStyle' => 'stacked',
            ],
        ];

        $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        OsdmPortalConfig::create([
            'config_key' => 'live',
            'menus' => json_encode($initialMenus, $jsonFlags),
            'profile' => json_encode($initialProfile, $jsonFlags),
            'published_at' => now(),
        ]);

        OsdmPortalConfig::create([
            'config_key' => 'draft',
            'menus' => json_encode($initialMenus, $jsonFlags),
            'profile' => json_encode($initialProfile, $jsonFlags),
        ]);

        OsdmPortalConfig::create([
            'config_key' => 'security',
            'admin_pin' => 'admin123',
        ]);

        $this->command->info('Seeded portal config (live + draft + security).');
    }

    private function seedWfaSubmissions(): void
    {
        if (OsdmWfaSubmission::exists()) {
            $this->command->info('WFA submissions already exist, skipping.');
            return;
        }

        $samples = [
            [
                'nip' => '197009211996032001',
                'employee_name' => 'Dr. Pramita Iriana, S,Kp., M.Biomed',
                'unit_kerja' => 'Keperawatan Bandung',
                'jabatan' => 'Dosen Lektor Kepala (JFT)',
                'nomor_wa' => '081234567890',
                'tanggal_wfa' => '2026-09-10',
                'nama_kegiatan' => 'Supervisi & Evaluasi Praktik Klinik Keperawatan Mahasiswa Semester V di RSHS',
                'lokasi_kegiatan' => 'Kota Bandung',
                'lokasi_lahan_bimbingan' => 'RSUP Hasan Sadikin Bandung',
                'status_wfa' => 'WFA Datang & WFA Pulang',
                'link_surat_tugas' => 'https://drive.google.com/file/example',
                'status' => 'Valid',
                'validated_at' => now(),
                'validated_by' => 'Tim OSDM Poltekkes',
            ],
            [
                'nip' => '198101122005012002',
                'employee_name' => 'Dr. Tatik Kusyanti, S.ST., M.Keb',
                'unit_kerja' => 'Kebidanan Bandung',
                'jabatan' => 'Dosen Asisten Ahli (JFT)',
                'nomor_wa' => '082112345678',
                'tanggal_wfa' => '2026-09-11',
                'nama_kegiatan' => 'Supervisi & Ujian Asuhan Persalinan Normal (APN)',
                'lokasi_kegiatan' => 'Kabupaten Bandung',
                'lokasi_lahan_bimbingan' => 'RSUD Al-Ihsan Baleendah',
                'status_wfa' => 'WFA Datang & WFA Pulang',
                'link_surat_tugas' => 'https://drive.google.com/file/example2',
                'status' => 'Menunggu Validasi',
            ],
        ];

        foreach ($samples as $data) {
            OsdmWfaSubmission::create($data);
        }

        $this->command->info('Seeded 2 sample WFA submissions.');
    }
}
