<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicalAttendance extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'ci_validated_at' => 'datetime',
        'dosen_override_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(ClinicalSession::class, 'clinical_session_id');
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id');
    }

    public function ciValidator()
    {
        return $this->belongsTo(User::class, 'ci_validated_by');
    }

    public function dosenOverride()
    {
        return $this->belongsTo(User::class, 'dosen_override_by');
    }
}
