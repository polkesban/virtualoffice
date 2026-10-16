<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitEventSubmission extends Model
{
    protected $fillable = [
        'unit_event_id',
        'unit_event_participant_id',
        'fase',
        'jawaban',
        'nilai',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'jawaban' => 'array',
            'nilai' => 'float',
            'submitted_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(UnitEvent::class, 'unit_event_id');
    }

    public function participant()
    {
        return $this->belongsTo(UnitEventParticipant::class, 'unit_event_participant_id');
    }
}