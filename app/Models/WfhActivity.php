<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WfhActivity extends Model
{
    protected $table = 'wfh_activities';

    protected $fillable = [
        'wfh_report_id',
        'waktu',
        'kegiatan',
        'hasil',
        'kendala',
        'rtl',
        'penanggung_jawab',
        'target_waktu',
        'keterangan',
    ];

    public function wfhReport(): BelongsTo
    {
        return $this->belongsTo(WfhReport::class, 'wfh_report_id');
    }
}
