<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbmLogbook extends Model
{

    protected $guarded = ['id'];
    protected $casts = ['activity_date' => 'date'];
    public function clinicalClass() { return $this->belongsTo(ClinicalClass::class); }
    public function mahasiswa() { return $this->belongsTo(Mahasiswa::class); }
    public function ci() { return $this->belongsTo(User::class, 'ci_id'); }
    
    //
}
