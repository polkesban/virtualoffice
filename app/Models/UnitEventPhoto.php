<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitEventPhoto extends Model
{
    protected $fillable = [
        'unit_event_id',
        'title',
        'caption',
        'image_path',
        'taken_at',
        'sort_order',
        'is_cover',
    ];

    protected function casts(): array
    {
        return [
            'taken_at' => 'datetime',
            'is_cover' => 'boolean',
        ];
    }

    public function event()
    {
        return $this->belongsTo(UnitEvent::class, 'unit_event_id');
    }
}
