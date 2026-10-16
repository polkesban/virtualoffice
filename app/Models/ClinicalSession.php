<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicalSession extends Model
{
    use HasFactory;

    public function materials() { return $this->hasMany(PbmMaterial::class); }
    public function assignments() { return $this->hasMany(PbmAssignment::class); }
    public function quizzes() { return $this->hasMany(PbmQuiz::class); }


    protected $guarded = ['id'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'require_qr' => 'boolean',
        'require_gps' => 'boolean',
        'shifts' => 'array',
        'lokasi_praktik' => 'array',
    ];

    public function clinicalClass()
    {
        return $this->belongsTo(ClinicalClass::class, 'clinical_class_id');
    }

    public function attendances()
    {
        return $this->hasMany(ClinicalAttendance::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function evaluationRubric()
    {
        return $this->belongsTo(EvaluationRubric::class, 'evaluation_rubric_id');
    }
}
