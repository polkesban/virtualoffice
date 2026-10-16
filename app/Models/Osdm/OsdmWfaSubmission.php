<?php

namespace App\Models\Osdm;

use Illuminate\Database\Eloquent\Model;

class OsdmWfaSubmission extends Model
{
    protected $table = 'osdm_wfa_submissions';

    protected $fillable = [
        'nip',
        'employee_name',
        'unit_kerja',
        'jabatan',
        'nomor_wa',
        'tanggal_wfa',
        'nama_kegiatan',
        'lokasi_kegiatan',
        'lokasi_lahan_bimbingan',
        'status_wfa',
        'link_surat_tugas',
        'status',
        'catatan_pengelola',
        'validated_at',
        'validated_by',
    ];

    protected $casts = [
        'tanggal_wfa' => 'date',
        'validated_at' => 'datetime',
    ];
}
