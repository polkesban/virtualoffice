<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['mahasiswa_id','periode','periode_label','catatan_admin','aktif','urutan'])]
class MahasiswaBerprestasi extends Model
{
    protected $table = 'mahasiswa_berprestasi';

    protected function casts(): array
    {
        return ['aktif' => 'boolean', 'urutan' => 'integer'];
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function scopeAktif(Builder $q): Builder
    {
        return $q->where('aktif', true);
    }

    public static array $periodeLabels = [
        'bulan'    => 'Student of the Month',
        'semester' => 'Student of the Semester',
        'tahun'    => 'Student of the Year',
    ];
}
