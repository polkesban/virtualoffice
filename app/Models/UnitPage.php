<?php

namespace App\Models;

use App\Support\CampusStructure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UnitPage extends Model
{
    protected $table = 'unit_pages';

    protected $fillable = [
        'role', 'slug', 'judul', 'sub_judul', 'cover_foto', 'warna_tema', 'aktif',
    ];

    protected $casts = ['aktif' => 'boolean'];

    // Default metadata keyed by role
    public static array $defaults = [
        'direktur'     => ['slug' => 'direktur',                 'judul' => 'Direktur',                     'sub_judul' => 'Halaman resmi Direktur Poltekkes Bandung untuk arahan strategis, kebijakan, dan agenda pimpinan.', 'warna_tema' => '#0f172a'],
        'wadir_1'      => ['slug' => 'bidang-akademik',         'judul' => 'Bidang Akademik',               'warna_tema' => '#1d4ed8'],
        'wadir_2'      => ['slug' => 'bidang-administrasi-umum','judul' => 'Bagian Keuangan',               'warna_tema' => '#0f766e'],
        'wadir_3'      => ['slug' => 'bidang-kemahasiswaan',    'judul' => 'Bidang Kemahasiswaan',          'warna_tema' => '#7c3aed'],
        'kabag_adum'   => ['slug' => 'bagian-administrasi-umum','judul' => 'Bagian Administrasi Umum',      'sub_judul' => 'Halaman resmi Bagian Administrasi Umum untuk layanan administrasi, tata usaha, dan dukungan operasional.', 'warna_tema' => '#115e59'],
        'bidang_osdm'  => ['slug' => 'bidang-osdm',             'judul' => 'Bidang OSDM',                   'warna_tema' => '#0284c7'],
        'pusat_mutu'   => ['slug' => 'penjaminan-mutu',         'judul' => 'Pusat Penjaminan Mutu',         'warna_tema' => '#059669'],
        'pusat_litmas' => ['slug' => 'penelitian-pengabmas',    'judul' => 'Pusat Litmas & Pengabmas',      'warna_tema' => '#b45309'],
        'pusat_p3'     => ['slug' => 'pengembangan-pendidikan', 'judul' => 'Pusat Pengembangan Pendidikan', 'warna_tema' => '#be185d'],
        'unit_ti'      => ['slug' => 'teknologi-informasi',     'judul' => 'Unit Teknologi Informasi',      'warna_tema' => '#2563eb'],
        'unit_humas_kerjasama' => ['slug' => 'unit-humas-kerjasama', 'judul' => 'Unit Humas dan Kerjasama', 'warna_tema' => '#0891b2'],
        'unit_perpus'  => ['slug' => 'perpustakaan',            'judul' => 'Unit Perpustakaan Terpadu',     'warna_tema' => '#92400e'],
        'unit_lab'     => ['slug' => 'laboratorium',            'judul' => 'Unit Laboratorium Terpadu',     'warna_tema' => '#0f766e'],
        'unit_bahasa'  => ['slug' => 'bahasa',                  'judul' => 'Unit Bahasa',                   'warna_tema' => '#1d4ed8'],
        'unit_bisnis'  => ['slug' => 'unit-bisnis',             'judul' => 'Unit Bisnis',                   'warna_tema' => '#166534'],

        'spi'          => ['slug' => 'satuan-pengawas-internal','judul' => 'Satuan Pengawas Internal',      'warna_tema' => '#374151'],
    ];

    public function sections()
    {
        return $this->hasMany(PageSection::class)->orderBy('urutan');
    }

    public function events()
    {
        return $this->hasMany(UnitEvent::class)->orderByDesc('mulai_at');
    }

    public function programStudi(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProgramStudi::class);
    }

    public function scopePortalMahasiswa(Builder $query): Builder
    {
        return $query->where('role', 'like', 'org\_%');
    }

    public function scopeLayananRoot(Builder $query): Builder
    {
        return $query
            ->whereDoesntHave('programStudi')
            ->where('role', 'not like', 'org\_%')
            ->where('role', 'not like', 'sub\_p3\_%')
            ->where('role', '!=', 'unit_asrama');
    }

    public function scopePublicNonProgram(Builder $query): Builder
    {
        return $query
            ->whereDoesntHave('programStudi')
            ->where('role', '!=', 'unit_asrama');
    }

    public function publicPath(): string
    {
        if ($this->isPortalMahasiswaPage()) {
            return '/portal-mahasiswa/' . trim((string) $this->slug, '/');
        }

        return '/unit/' . trim((string) $this->slug, '/');
    }

    public function publicUrl(): string
    {
        return url($this->publicPath());
    }

    public function isPortalMahasiswaPage(): bool
    {
        return str_starts_with((string) $this->role, 'org_');
    }

    public function isPegawaiOrDosenPage(): bool
    {
        return str_starts_with((string) $this->role, 'user_') || in_array($this->role, ['dosen', 'employee', 'pegawai'], true);
    }

    public function isJurusanOrProdiPage(): bool
    {
        return ($this->programStudi !== null 
            || str_starts_with((string) $this->role, 'kaprodi_') 
            || str_starts_with((string) $this->role, 'kajur_') 
            || str_starts_with((string) $this->role, 'jurusan_')) 
            && ! $this->isPortalMahasiswaPage() 
            && ! $this->isPegawaiOrDosenPage();
    }

    public function isDirektoratOrUnitKerjaPage(): bool
    {
        return ! $this->isPegawaiOrDosenPage() 
            && ! $this->isJurusanOrProdiPage() 
            && ! $this->isPortalMahasiswaPage() 
            && ! str_starts_with((string) $this->role, 'sub_p3_');
    }

    public function layananMenuOrder(): int
    {
        return CampusStructure::layananOrderForRole((string) $this->role);
    }

    public static function getOrCreateForRole(string $role): self
    {
        $meta = self::$defaults[$role] ?? [
            'slug'      => Str::slug($role),
            'judul'     => ucwords(str_replace('_', ' ', $role)),
            'warna_tema'=> '#1d4ed8',
        ];

        return self::firstOrCreate(
            ['role' => $role],
            array_merge($meta, ['aktif' => true])
        );
    }

    public static function tupoksiServices(): array
    {
        return [
            'direktur' => [
                'Konsultasi Kebijakan Institusi',
                'Audiensi/Pertemuan Pimpinan',
                'Surat Keputusan (SK) Direktur',
                'Lainnya',
            ],
            'wadir_1' => [
                'Layanan Kalender Akademik',
                'Pengajuan Cuti Akademik / Pindah Prodi',
                'Legalisir Ijazah & Transkrip',
                'Penerimaan Mahasiswa Baru (PMB)',
                'Lainnya',
            ],
            'wadir_2' => [
                'Layanan Keuangan & Pembayaran UKT',
                'Urusan Kepegawaian & OSDM',
                'Keamanan & Sarana Prasarana Kampus',
                'Lainnya',
            ],
            'wadir_3' => [
                'Informasi & Pengajuan Beasiswa',
                'Rekomendasi Kegiatan Ormawa / HIMA',
                'Konsultasi Prestasi Mahasiswa',
                'Layanan Konseling Mahasiswa',
                'Lainnya',
            ],
            'kabag_adum' => [
                'Persuratan & Tata Usaha',
                'Peminjaman Ruang Rapat / Aula',
                'Dukungan Rumah Tangga & Protokoler',
                'Lainnya',
            ],
            'bidang_osdm' => [
                'Mutasi & Kenaikan Pangkat Pegawai',
                'Pengembangan Karir & Sertifikasi Dosen',
                'Penilaian Angka Kredit (PAK)',
                'Lainnya',
            ],
            'pusat_mutu' => [
                'Konsultasi Akreditasi Prodi',
                'Audit Mutu Internal (AMI)',
                'Penyusunan Standar & Dokumen Mutu',
                'Lainnya',
            ],
            'pusat_litmas' => [
                'Pengajuan Hibah Penelitian & Pengabmas',
                'Komite Etik Penelitian Kesehatan (KEPK)',
                'Pendampingan Jurnal Ilmiah',
                'Pengusulan HKI / Paten',
                'Lainnya',
            ],
            'pusat_p3' => [
                'Pengembangan Kurikulum Prodi',
                'Produk Unggulan IPTEK',
                'Program Interprofessional Education (IPE)',
                'Lainnya',
            ],
            'unit_ti' => [
                'Gangguan Jaringan & Wi-Fi Kampus',
                'Reset Password SIAK / Akun Pegawai',
                'Permintaan Subdomain / Hosting',
                'Instalasi / Pemeliharaan Perangkat Lunak',
                'Dukungan Zoom & Webinar',
                'Lainnya',
            ],
            'unit_humas_kerjasama' => [
                'Pengajuan Kunjungan Institusi',
                'Kerjasama MoU & PKS',
                'Informasi Publik / PPID',
                'Layanan Tamu Asing / Kemitraan',
                'Lainnya',
            ],
            'unit_perpus' => [
                'Pembuatan Kartu Anggota Perpustakaan',
                'Peminjaman & Pengembalian Buku',
                'Pengurusan Surat Bebas Pustaka',
                'Akses E-Journal & Repositori',
                'Lainnya',
            ],
            'unit_lab' => [
                'Pengujian Sampel Laboratorium',
                'Peminjaman Alat Laboratorium',
                'Kalibrasi & Pemeliharaan Alat',
                'Pelatihan / Praktikum Eksternal',
                'Lainnya',
            ],
            'unit_bahasa' => [
                'Ujian TOEFL / TOEIC Prediction',
                'Kursus Bahasa Inggris / Asing',
                'Penerjemahan Dokumen Resmi',
                'Proofreading Jurnal Ilmiah',
                'Lainnya',
            ],
            'unit_bisnis' => [
                'Penyewaan Lapangan & Aula',
                'Pemesanan Produk & Jasa Bisnis',
                'Kantin & Kemitraan Usaha',
                'Lainnya',
            ],

            'spi' => [
                'Pengaduan Penyimpangan Keuangan',
                'Audit Kinerja Internal',
                'Konsultasi Pengendalian Gratifikasi',
                'Lainnya',
            ],
        ];
    }
}
