<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbmAssignment extends Model
{

    protected $guarded = ['id'];
    protected $casts = ['due_date' => 'datetime', 'is_practical' => 'boolean'];
    public function clinicalSession() { return $this->belongsTo(ClinicalSession::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function submissions() { return $this->hasMany(PbmSubmission::class); }
    public function evaluationRubric() { return $this->belongsTo(EvaluationRubric::class, 'evaluation_rubric_id'); }
    
    //
}
