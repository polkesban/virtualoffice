<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbmSubmission extends Model
{

    protected $guarded = ['id'];
    protected $casts = ['submitted_at' => 'datetime'];
    public function assignment() { return $this->belongsTo(PbmAssignment::class, 'pbm_assignment_id'); }
    public function mahasiswa() { return $this->belongsTo(Mahasiswa::class); }
    public function grader() { return $this->belongsTo(User::class, 'graded_by'); }
    
    //
}
