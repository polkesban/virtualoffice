<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FasilitasAsrama extends Model
{
    protected $table = 'fasilitas_asrama';

    protected $fillable = [
        'nama', 'jenis', 'lantai', 'kapasitas', 'terisi',
        'harga_per_bulan', 'harga_per_semester',
        'fasilitas', 'foto', 'keterangan', 'aktif', 'urutan',
    ];

    protected $casts = [
        'aktif'    => 'boolean',
        'lantai'   => 'integer',
        'kapasitas'=> 'integer',
        'terisi'   => 'integer',
        'urutan'   => 'integer',
    ];

    public static array $jenisLabels = [
        'putra' => 'Putra',
        'putri' => 'Putri',
        'mix'   => 'Campuran',
    ];

    public function getHargaBulanFormatAttribute(): string
    {
        return $this->harga_per_bulan
            ? 'Rp ' . number_format($this->harga_per_bulan, 0, ',', '.')
            : 'Hubungi kami';
    }

    public function getHargaSemesterFormatAttribute(): string
    {
        return $this->harga_per_semester
            ? 'Rp ' . number_format($this->harga_per_semester, 0, ',', '.')
            : 'Hubungi kami';
    }

    public function getFasilitasArrayAttribute(): array
    {
        return $this->fasilitas
            ? array_map('trim', explode(',', $this->fasilitas))
            : [];
    }

    public function getSisaKamarAttribute(): int
    {
        return max(0, $this->kapasitas - $this->terisi);
    }
}
