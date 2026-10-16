<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbmQuizAnswer extends Model
{

    protected $guarded = ['id'];
    protected $casts = ['is_correct' => 'boolean'];
    public function attempt() { return $this->belongsTo(PbmQuizAttempt::class, 'pbm_quiz_attempt_id'); }
    public function question() { return $this->belongsTo(PbmQuestion::class, 'pbm_question_id'); }
    public function option() { return $this->belongsTo(PbmQuestionOption::class, 'pbm_question_option_id'); }
    
    //
}
