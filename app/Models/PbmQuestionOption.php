<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbmQuestionOption extends Model
{

    protected $guarded = ['id'];
    protected $casts = ['is_correct' => 'boolean', 'is_functioning_distractor' => 'boolean'];
    public function question() { return $this->belongsTo(PbmQuestion::class, 'pbm_question_id'); }
    
    //
}
