<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbmQuestion extends Model
{

    protected $guarded = ['id'];
    public function quiz() { return $this->belongsTo(PbmQuiz::class, 'pbm_quiz_id'); }
    public function options() { return $this->hasMany(PbmQuestionOption::class); }
    
    //
}
