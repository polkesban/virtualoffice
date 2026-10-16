<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Jurusan extends Model
{
    protected $table = 'jurusan';

    protected $fillable = [
        'program_studi_id',
        'nama', 'nama_inggris', 'singkatan', 'slug', 'jenjang', 'gelar', 'akreditasi',
        'deskripsi_singkat', 'deskripsi', 'gambar', 'gambar_sampul', 'sumber_url',
        'visi', 'misi', 'tujuan', 'sasaran',
        'durasi_semester', 'total_sks', 'mata_kuliah_unggulan', 'kurikulum_deskripsi',
        'prospek_kerja', 'kompetensi_lulusan',
        'fasilitas', 'jumlah_dosen', 'jumlah_mahasiswa_aktif',
        'no_sk_akreditasi', 'tgl_akreditasi', 'tgl_kadaluarsa_akreditasi',
        'kuota_per_tahun', 'biaya_sks',
        'email_prodi', 'website_prodi', 'sosial_media',
        'urutan', 'aktif', 'tampil_di_beranda',
        'meta_title', 'meta_description',
    ];

    protected $casts = [
        'misi'                    => 'array',
        'sasaran'                 => 'array',
        'mata_kuliah_unggulan'    => 'array',
        'prospek_kerja'           => 'array',
        'kompetensi_lulusan'      => 'array',
        'fasilitas'               => 'array',
        'sosial_media'            => 'array',
        'aktif'                   => 'boolean',
        'tampil_di_beranda'       => 'boolean',
        'tgl_akreditasi'          => 'date',
        'tgl_kadaluarsa_akreditasi' => 'date',
        'durasi_semester'         => 'integer',
        'total_sks'               => 'integer',
        'kuota_per_tahun'         => 'integer',
        'jumlah_dosen'            => 'integer',
        'jumlah_mahasiswa_aktif'  => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'program_studi_id');
    }

    public function programStudis(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProgramStudi::class, 'jurusan_id')->orderBy('urutan');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    public function scopeByJenjang(Builder $query, string $jenjang): Builder
    {
        return $query->where('jenjang', $jenjang);
    }

    public function scopeTampilDiBeranda(Builder $query): Builder
    {
        return $query->where('tampil_di_beranda', true)->orderBy('urutan');
    }

    /** Full-text search pada nama dan deskripsi (MariaDB FULLTEXT) */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->whereRaw(
            'MATCH(nama, deskripsi_singkat, deskripsi) AGAINST(? IN BOOLEAN MODE)',
            ['+' . implode('* +', explode(' ', trim($term))) . '*']
        );
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getSeoTitleAttribute(): string
    {
        return $this->meta_title
            ?: "{$this->nama} — Poltekkes Bandung";
    }

    public function getSeoDescriptionAttribute(): string
    {
        return $this->meta_description
            ?: Str::limit($this->deskripsi_singkat ?? '', 160);
    }

    /** true jika akreditasi masih berlaku */
    public function getAkreditasiAktifAttribute(): bool
    {
        if (! $this->tgl_kadaluarsa_akreditasi) {
            return true;
        }
        return $this->tgl_kadaluarsa_akreditasi->isFuture();
    }

    // ── Mutators ─────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Jurusan $jurusan) {
            if (empty($jurusan->slug)) {
                $jurusan->slug = Str::slug($jurusan->nama);
            }
        });
    }
}
