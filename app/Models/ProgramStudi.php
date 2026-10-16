<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ProgramStudi extends Model
{
    protected $table = 'program_studi';

    protected $fillable = [
        'jurusan_id', 'unit_page_id', 'nama', 'singkatan', 'jenjang', 'slug', 'icon_emoji',
        'deskripsi_singkat', 'deskripsi', 'akreditasi',
        'durasi_semester', 'kuota', 'gelar', 'gambar', 'aktif', 'urutan',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'durasi_semester' => 'integer',
        'kuota' => 'integer',
        'urutan' => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function jurusan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function unitPage(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(UnitPage::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true)->orderBy('urutan');
    }

    public function scopeJenjang(Builder $query, string $jenjang): Builder
    {
        return $query->where('jenjang', $jenjang);
    }

    // ── Accessors ───────────────────────────────────────────────────────────

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getDurasiTahunAttribute(): string
    {
        $tahun = intdiv($this->durasi_semester, 2);
        return $tahun . ' Tahun';
    }

    public static function getProdisInSameDepartment(int $prodiId): array
    {
        $prodi = self::find($prodiId);
        if (!$prodi) return [];

        $name = strtolower($prodi->nama);

        $keyword = '';
        if (str_contains($name, 'keperawatan') || str_contains($name, 'ners')) {
            $keyword = 'keperawatan';
        } elseif (str_contains($name, 'kebidanan') || str_contains($name, 'bidan')) {
            $keyword = 'kebidanan';
        } elseif (str_contains($name, 'gigi')) {
            $keyword = 'gigi';
        } elseif (str_contains($name, 'gizi') || str_contains($name, 'dietisien')) {
            $keyword = 'gizi';
        } elseif (str_contains($name, 'lingkungan') || str_contains($name, 'sanitasi')) {
            $keyword = 'lingkungan';
        } elseif (str_contains($name, 'analis') || str_contains($name, 'tlm')) {
            $keyword = 'analis';
        } elseif (str_contains($name, 'farmasi')) {
            $keyword = 'farmasi';
        } elseif (str_contains($name, 'fisioterapi')) {
            $keyword = 'fisioterapi';
        } elseif (str_contains($name, 'rekam medis')) {
            $keyword = 'rekam';
        }

        if (!$keyword) {
            return [$prodi->id];
        }

        $query = self::query();
        if ($keyword === 'keperawatan') {
            $query->where(function($q) {
                $q->where('nama', 'like', '%keperawatan%')
                  ->orWhere('nama', 'like', '%ners%');
            });
        } elseif ($keyword === 'kebidanan') {
            $query->where(function($q) {
                $q->where('nama', 'like', '%kebidanan%')
                  ->orWhere('nama', 'like', '%bidan%');
            });
        } elseif ($keyword === 'gizi') {
            $query->where(function($q) {
                $q->where('nama', 'like', '%gizi%')
                  ->orWhere('nama', 'like', '%dietisien%');
            });
        } elseif ($keyword === 'lingkungan') {
            $query->where(function($q) {
                $q->where('nama', 'like', '%lingkungan%')
                  ->orWhere('nama', 'like', '%sanitasi%');
            });
        } elseif ($keyword === 'analis') {
            $query->where(function($q) {
                $q->where('nama', 'like', '%analis%')
                  ->orWhere('nama', 'like', '%tlm%');
            });
        } else {
            $query->where('nama', 'like', '%' . $keyword . '%');
        }

        return $query->pluck('id')->all();
    }
}
