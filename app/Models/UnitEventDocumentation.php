<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitEventDocumentation extends Model
{
    protected $fillable = [
        'unit_event_id',
        'transcript_text',
        'minutes_html',
        'ai_summary_html',
        'report_html',
        'report_notes',
        'ai_model_used',
        'ai_summary_generated_at',
        'report_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_summary_generated_at' => 'datetime',
            'report_generated_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(UnitEvent::class, 'unit_event_id');
    }
}
