<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicalClassMember extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function clinicalClass()
    {
        return $this->belongsTo(ClinicalClass::class, 'clinical_class_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id');
    }
}
