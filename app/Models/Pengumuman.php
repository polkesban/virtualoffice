<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengumuman extends Model
{
    protected $table = 'pengumuman';

    protected $fillable = [
        'user_id', 'judul', 'slug', 'isi', 'file_lampiran',
        'prioritas', 'aktif', 'tanggal_mulai', 'tanggal_selesai',
    ];

    protected $casts = [
        'aktif'           => 'boolean',
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function penulis(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true)
                     ->where('tanggal_mulai', '<=', now())
                     ->where(function (Builder $q) {
                         $q->whereNull('tanggal_selesai')
                           ->orWhere('tanggal_selesai', '>=', now());
                     })
                     ->orderByRaw("FIELD(prioritas, 'urgent', 'penting', 'normal')");
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
