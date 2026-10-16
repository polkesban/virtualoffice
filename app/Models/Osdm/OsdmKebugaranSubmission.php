<?php

namespace App\Models\Osdm;

use Illuminate\Database\Eloquent\Model;

class OsdmKebugaranSubmission extends Model
{
    protected $table = 'osdm_kebugaran_submissions';

    protected $fillable = [
        'tanggal_periksa',
        'periode',
        'nip',
        'nama_pegawai',
        'tanggal_lahir',
        'unit_kerja',
        'nik',
        'tensi_sistolik',
        'tensi_diastolik',
        'berat_badan',
        'tinggi_badan',
        'lingkar_pinggang',
        'tipe_gula_darah',
        'gula_darah',
        'kolesterol',
        'nomor_wa',
        'fasyankes',
        'catatan',
    ];

    protected $casts = [
        'tanggal_periksa' => 'date',
        'berat_badan' => 'decimal:1',
        'tinggi_badan' => 'decimal:1',
        'lingkar_pinggang' => 'decimal:1',
    ];
}
