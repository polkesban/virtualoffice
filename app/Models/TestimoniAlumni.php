<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestimoniAlumni extends Model
{
    protected $table = 'testimoni_alumni';

    protected $fillable = [
        'nama',
        'email',
        'telepon',
        'program_studi',
        'tahun_lulus',
        'instansi',
        'jabatan',
        'kota_domisili',
        'portofolio_url',
        'linkedin_url',
        'testimoni',
        'bersedia_dihubungi',
        'ip_address',
        'status',
    ];

    protected $casts = [
        'bersedia_dihubungi' => 'boolean',
        'tahun_lulus' => 'integer',
    ];
}