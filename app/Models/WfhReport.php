<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WfhReport extends Model
{
    protected $table = 'wfh_reports';

    protected $fillable = [
        'user_id',
        'tanggal',
        'catatan_wadir',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(WfhActivity::class, 'wfh_report_id');
    }
}
