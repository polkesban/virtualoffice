<?php

namespace App\Models\Osdm;

use Illuminate\Database\Eloquent\Model;

class OsdmPortalConfig extends Model
{
    protected $table = 'osdm_portal_configs';

    protected $fillable = [
        'config_key',
        'menus',
        'profile',
        'admin_pin',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Get menus as decoded array.
     */
    public function getMenusArrayAttribute(): array
    {
        return json_decode($this->menus ?? '[]', true) ?: [];
    }

    /**
     * Get profile as decoded array.
     */
    public function getProfileArrayAttribute(): array
    {
        return json_decode($this->profile ?? '{}', true) ?: [];
    }
}
