<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitEventAttendance extends Model
{
    protected $fillable = [
        'unit_event_id',
        'unit_event_participant_id',
        'attendance_date',
        'shift_name',
        'signed_at',
        'check_out_at',
        'notes',
        'signature_path',
        'check_out_signature_path',
        'nama',
        'email',
        'no_hp',
        'instansi',
        'unit_kerja',
        'attendance_status',
        'berita_acara_judul',
        'berita_acara_ringkasan',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'signed_at' => 'datetime',
            'check_out_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(UnitEvent::class, 'unit_event_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(UnitEventParticipant::class, 'unit_event_participant_id');
    }
}