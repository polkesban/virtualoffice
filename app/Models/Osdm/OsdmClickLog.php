<?php

namespace App\Models\Osdm;

use Illuminate\Database\Eloquent\Model;

class OsdmClickLog extends Model
{
    protected $table = 'osdm_click_logs';

    protected $fillable = [
        'menu_id',
        'menu_title',
        'category',
        'device',
        'browser',
        'referrer',
    ];
}
