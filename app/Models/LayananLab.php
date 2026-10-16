<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LayananLab extends Model
{
    protected $table = 'layanan_lab';

    protected $fillable = [
        'nama', 'deskripsi', 'tarif', 'satuan', 'kategori',
        'lama_pengerjaan', 'syarat', 'urutan', 'aktif',
    ];

    protected function casts(): array
    {
        return [
            'tarif' => 'decimal:2',
            'aktif' => 'boolean',
        ];
    }

    public static array $kategoriLabels = [
        'kimia'          => 'Kimia Klinik',
        'hematologi'     => 'Hematologi',
        'mikrobiologi'   => 'Mikrobiologi',
        'biologi'        => 'Biologi Molekuler',
        'fisika'         => 'Fisika Kesehatan',
        'kesling'        => 'Kesehatan Lingkungan',
        'gizi'           => 'Gizi',
        'umum'           => 'Umum',
    ];

    public function getTarifFormatAttribute(): string
    {
        if (!$this->tarif) return 'Hubungi kami';
        return 'Rp ' . number_format($this->tarif, 0, ',', '.') . ($this->satuan ? ' / ' . $this->satuan : '');
    }
}
