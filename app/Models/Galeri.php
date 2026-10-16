<?php

namespace App\Models;

use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Model;

class Galeri extends Model
{
    protected $table = 'galeri';

    protected $fillable = ['judul', 'file', 'alt', 'kategori', 'tampil', 'urutan'];

    protected $casts = ['tampil' => 'boolean', 'urutan' => 'integer'];

    public static array $kategoriLabels = [
        'umum'     => 'Umum',
        'kegiatan' => 'Kegiatan',
        'akademik' => 'Akademik',
        'kampus'   => 'Kampus',
        'wisuda'   => 'Wisuda',
    ];

    public function getUrlAttribute(): string
    {
        return MediaStorage::url($this->file, 'galeri') ?: '';
    }
}
