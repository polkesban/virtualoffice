<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Pengaduan extends Model
{
    protected $table = 'pengaduan';

    protected $fillable = [
        'nama', 'email', 'telepon',
        'tujuan_unit', 'tujuan_label',
        'jenis', 'subjek', 'isi', 'file_lampiran',
        'anonim', 'ip_address', 'nomor_tiket',
        'status',
        'catatan_internal', 'tindak_lanjut',
        'ditangani_oleh', 'ditangani_at',
        'dibaca_at', 'diproses_at', 'diselesaikan_at',
    ];

    protected $casts = [
        'anonim'          => 'boolean',
        'dibaca_at'       => 'datetime',
        'diproses_at'     => 'datetime',
        'diselesaikan_at' => 'datetime',
        'ditangani_at'    => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function penanggungJawab()
    {
        return $this->belongsTo(User::class, 'ditangani_oleh');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForUnit(Builder $query, string $unitKey): Builder
    {
        return $query->where('tujuan_unit', $unitKey);
    }

    public function scopeMasuk(Builder $query): Builder
    {
        return $query->where('status', 'masuk');
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public static function generateNomorTiket(): string
    {
        do {
            $ticket = 'PKB-' . strtoupper(Str::random(8));
        } while (self::where('nomor_tiket', $ticket)->exists());

        return $ticket;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'masuk'     => 'Masuk',
            'diproses'  => 'Diproses',
            'selesai'   => 'Selesai',
            'ditolak'   => 'Ditolak',
            default     => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'masuk'     => 'yellow',
            'diproses'  => 'blue',
            'selesai'   => 'green',
            'ditolak'   => 'red',
            default     => 'gray',
        };
    }

    public function jenisLabel(): string
    {
        return match ($this->jenis) {
            'pengaduan' => 'Pengaduan',
            'kritik'    => 'Kritik',
            'saran'     => 'Saran',
            'keluhan'   => 'Keluhan',
            default     => ucfirst($this->jenis),
        };
    }

    public function jenisIcon(): string
    {
        return match ($this->jenis) {
            'pengaduan' => '🚨',
            'kritik'    => '💬',
            'saran'     => '💡',
            'keluhan'   => '⚠️',
            default     => '📝',
        };
    }

    public function markDibaca(): void
    {
        if (! $this->dibaca_at) {
            $this->update(['dibaca_at' => now()]);
        }
    }

    /**
     * All selectable destination units for the complaint form.
     * Returns array of ['key' => ..., 'label' => ..., 'group' => ...]
     */
    public static function tujuanOptions(): array
    {
        return [
            // Pimpinan
            ['key' => 'wadir_1',        'label' => 'Bidang Akademik (Wadir I)',              'group' => 'Pimpinan'],
            ['key' => 'wadir_2',        'label' => 'Bidang Administrasi Umum (Wadir II)',    'group' => 'Pimpinan'],
            ['key' => 'wadir_3',        'label' => 'Bidang Kemahasiswaan (Wadir III)',       'group' => 'Pimpinan'],
            // Bagian
            ['key' => 'kabag_adum',     'label' => 'Bagian Administrasi Umum',               'group' => 'Bagian'],
            ['key' => 'bidang_osdm',    'label' => 'Bidang OSDM',                            'group' => 'Bagian'],
            ['key' => 'spi',            'label' => 'Satuan Pengawas Internal (SPI)',          'group' => 'Bagian'],
            // Unit Layanan
            ['key' => 'pusat_mutu',     'label' => 'Pusat Penjaminan Mutu',                  'group' => 'Unit Layanan'],
            ['key' => 'pusat_litmas',   'label' => 'Pusat Penelitian & Pengabmas',           'group' => 'Unit Layanan'],
            ['key' => 'pusat_p3',       'label' => 'Kepala Pusat Pengembangan Pendidikan',          'group' => 'Unit Layanan'],
            ['key' => 'unit_upk',       'label' => 'Unit Pengembangan Kompetensi',           'group' => 'Unit Layanan'],
            ['key' => 'unit_bisnis',    'label' => 'Unit Bisnis',                            'group' => 'Unit Layanan'],
            ['key' => 'unit_lab',       'label' => 'Unit Laboratorium Terpadu',              'group' => 'Unit Layanan'],
            ['key' => 'unit_perpus',    'label' => 'Unit Perpustakaan Terpadu',              'group' => 'Unit Layanan'],
            ['key' => 'unit_bahasa',    'label' => 'Unit Bahasa',                            'group' => 'Unit Layanan'],
            ['key' => 'unit_ti',        'label' => 'Unit Teknologi Informasi',               'group' => 'Unit Layanan'],

            ['key' => 'unit_humas_kerjasama', 'label' => 'Unit Humas dan Kerjasama',        'group' => 'Unit Layanan'],
            // Program Studi
            ['key' => 'prodi_keperawatan',    'label' => 'Jurusan/Prodi Keperawatan',       'group' => 'Program Studi'],
            ['key' => 'prodi_kebidanan',      'label' => 'Jurusan/Prodi Kebidanan',         'group' => 'Program Studi'],
            ['key' => 'prodi_gizi',           'label' => 'Jurusan/Prodi Gizi',              'group' => 'Program Studi'],
            ['key' => 'prodi_kesling',        'label' => 'Prodi Kesehatan Lingkungan',      'group' => 'Program Studi'],
            ['key' => 'prodi_tlm',            'label' => 'Prodi Teknologi Laboratorium Medis', 'group' => 'Program Studi'],
            ['key' => 'prodi_kesgi',          'label' => 'Prodi Kesehatan Gigi',            'group' => 'Program Studi'],
            ['key' => 'prodi_farmasi',        'label' => 'Prodi Farmasi',                   'group' => 'Program Studi'],
            ['key' => 'prodi_promkes',        'label' => 'Prodi Promosi Kesehatan',         'group' => 'Program Studi'],
        ];
    }

    /**
     * Map from unit page role to tujuan keys that the unit can view.
     */
    public static function unitAccessKeys(string $role): array
    {
        $map = [
            'wadir_1'         => ['wadir_1', 'pusat_p3', 'pusat_litmas', 'pusat_mutu', 'unit_upk',
                                   'prodi_keperawatan', 'prodi_kebidanan', 'prodi_gizi',
                                   'prodi_kesling', 'prodi_tlm', 'prodi_kesgi', 'prodi_farmasi', 'prodi_promkes'],
            'wadir_2'         => ['wadir_2', 'kabag_adum', 'bidang_osdm', 'unit_bisnis', 'spi'],
            'wadir_3'         => ['wadir_3', 'unit_humas_kerjasama', 'unit_bahasa'],
            'kabag_adum'      => ['kabag_adum'],
            'bidang_osdm'     => ['bidang_osdm'],
            'spi'             => ['spi'],
            'pusat_mutu'      => ['pusat_mutu'],
            'pusat_litmas'    => ['pusat_litmas'],
            'pusat_p3'        => ['pusat_p3'],
            'unit_upk'        => ['unit_upk'],
            'unit_bisnis'     => ['unit_bisnis'],
            'unit_lab'        => ['unit_lab'],
            'unit_perpus'     => ['unit_perpus'],
            'unit_bahasa'     => ['unit_bahasa'],
            'unit_ti'         => ['unit_ti'],

            'unit_humas_kerjasama' => ['unit_humas_kerjasama'],
        ];

        return $map[$role] ?? [$role];
    }

    public function getFileLampiranUrlAttribute(): ?string
    {
        return $this->file_lampiran ? \App\Support\MediaStorage::url($this->file_lampiran) : null;
    }
}
