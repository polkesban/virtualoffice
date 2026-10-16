<?php

namespace App\Models;

use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MahasiswaObeEvidence extends Model
{
    protected $table = 'mahasiswa_obe_evidences';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_ada' => 'boolean',
        ];
    }

    public function portofolio(): BelongsTo
    {
        return $this->belongsTo(MahasiswaObePortofolio::class, 'obe_portofolio_id');
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? MediaStorage::url($this->file_path) : null;
    }
}
