<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitEventAiDocument extends Model
{
    protected $fillable = [
        'unit_event_id',
        'title',
        'content_html',
        'ai_prompt',
        'reference_filename',
        'reference_text',
    ];

    public function event()
    {
        return $this->belongsTo(UnitEvent::class, 'unit_event_id');
    }
}
