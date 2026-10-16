<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbmQuizAttempt extends Model
{

    protected $guarded = ['id'];
    protected $casts = [
        'started_at' => 'datetime', 
        'finished_at' => 'datetime',
        'is_locked' => 'boolean',
        'violation_count' => 'integer',
    ];

    public function quiz() { return $this->belongsTo(PbmQuiz::class, 'pbm_quiz_id'); }
    public function user() { return $this->belongsTo(User::class); }
    public function mahasiswa() { return $this->belongsTo(Mahasiswa::class); }
    public function answers() { return $this->hasMany(PbmQuizAnswer::class); }
    public function proctoringLogs() { return $this->hasMany(PbmQuizProctoringLog::class); }
}
