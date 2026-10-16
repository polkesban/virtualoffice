<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccreditationFolder extends Model
{
    protected $table = 'accreditation_folders';

    protected $fillable = [
        'code',
        'title',
        'description',
        'sort_order',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function evidences(): HasMany
    {
        return $this->hasMany(AccreditationEvidence::class, 'folder_id');
    }

    public function getDisplayLabelAttribute(): string
    {
        return 'Kriteria ' . $this->code . '. ' . $this->title;
    }
}