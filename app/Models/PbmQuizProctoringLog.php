<?php

namespace App\Models;

use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Model;

class PbmQuizProctoringLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'details' => 'array',
    ];

    public function attempt()
    {
        return $this->belongsTo(PbmQuizAttempt::class, 'pbm_quiz_attempt_id');
    }

    public function getSnapshotUrlAttribute(): ?string
    {
        if (!$this->snapshot_path) {
            return null;
        }

        return MediaStorage::url($this->snapshot_path);
    }
}
