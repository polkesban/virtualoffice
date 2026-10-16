<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbmQuiz extends Model
{

    protected $guarded = ['id'];
    protected $casts = [
        'start_time' => 'datetime', 
        'end_time' => 'datetime', 
        'shuffle_questions' => 'boolean', 
        'show_results' => 'boolean',
        'enable_proctoring' => 'boolean',
        'require_webcam' => 'boolean',
        'require_microphone' => 'boolean',
        'enable_browser_lockdown' => 'boolean',
        'require_seb' => 'boolean',
        'max_violations' => 'integer',
    ];

    public function clinicalSession() { return $this->belongsTo(ClinicalSession::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function questions() { return $this->hasMany(PbmQuestion::class); }
    public function attempts() { return $this->hasMany(PbmQuizAttempt::class); }
    public function proctoringLogs() { return $this->hasManyThrough(PbmQuizProctoringLog::class, PbmQuizAttempt::class); }
}
