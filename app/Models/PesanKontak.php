<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesanKontak extends Model
{
    protected $table = 'pesan_kontak';

    protected $fillable = [
        'nama', 'email', 'telepon', 'topik',
        'subjek', 'pesan', 'ip_address',
    ];

    protected $casts = [
        'sudah_dibaca' => 'boolean',
        'dibaca_at'    => 'datetime',
        'dibalas_at'   => 'datetime',
    ];

    public function scopeBelumDibaca($query)
    {
        return $query->where('sudah_dibaca', false);
    }
}
