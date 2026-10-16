<?php

namespace App\Models\Osdm;

use Illuminate\Database\Eloquent\Model;

class OsdmEmployee extends Model
{
    protected $table = 'osdm_employees';

    protected $fillable = [
        'nip',
        'name',
        'unit_kerja',
        'jabatan',
        'jenis_jabatan',
        'jabatan_pengelola',
        'email',
        'nomor_wa',
        'tanggal_lahir',
        'nik',
        'status',
        'jenis_kepegawaian',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];
}
