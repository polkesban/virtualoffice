<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agenda extends Model
{
    protected $table = 'agenda';

    protected $fillable = [
        'judul', 'deskripsi', 'tanggal_mulai', 'tanggal_selesai',
        'waktu_mulai', 'waktu_selesai', 'lokasi', 'kategori',
        'warna', 'publik', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai'    => 'date',
            'tanggal_selesai'  => 'date',
            'publik'           => 'boolean',
        ];
    }

    public static array $kategoriLabels = [
        'umum'           => 'Umum',
        'akademik'       => 'Akademik',
        'kemahasiswaan'  => 'Kemahasiswaan',
        'penelitian'     => 'Penelitian & Pengabmas',
        'wisuda'         => 'Wisuda',
        'olahraga'       => 'Olahraga & Seni',
    ];

    public static array $warnaOptions = [
        'blue'   => 'Biru',
        'green'  => 'Hijau',
        'red'    => 'Merah',
        'yellow' => 'Kuning',
        'purple' => 'Ungu',
        'orange' => 'Oranye',
        'gray'   => 'Abu-abu',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublik($query)
    {
        return $query->where('publik', true);
    }

    public function scopeAkan($query)
    {
        return $query->where('tanggal_mulai', '>=', now()->toDateString());
    }

    public function getIsMultiDayAttribute(): bool
    {
        return $this->tanggal_selesai && $this->tanggal_selesai->gt($this->tanggal_mulai);
    }
}
