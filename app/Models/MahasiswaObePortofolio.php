<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MahasiswaObePortofolio extends Model
{
    protected $table = 'mahasiswa_obe_portofolios';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'sks' => 'integer',
            'cpl_list' => 'array',
            'cpmk_list' => 'array',
            'sub_cpmk_list' => 'array',
            'materi_pembelajaran' => 'array',
            'bobot_penilaian' => 'array',
            'nilai_angka' => 'float',
            'nilai_mutu' => 'float',
        ];
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(MahasiswaObeEvidence::class, 'obe_portofolio_id');
    }
}
