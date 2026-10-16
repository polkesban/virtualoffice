<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitEventMaterial extends Model
{
    protected $fillable = [
        'unit_event_id',
        'judul',
        'deskripsi',
        'tipe',
        'file_path',
        'link_url',
        'embed_url',
        'downloadable',
        'publik',
        'urutan',
    ];

    public static array $tipeLabels = [
        'file' => 'File Unduhan',
        'link' => 'Tautan Eksternal',
        'embed' => 'Embed Materi',
    ];

    protected function casts(): array
    {
        return [
            'downloadable' => 'boolean',
            'publik' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function event()
    {
        return $this->belongsTo(UnitEvent::class, 'unit_event_id');
    }
}