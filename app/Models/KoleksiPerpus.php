<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KoleksiPerpus extends Model
{
    protected $table = 'koleksi_perpus';

    protected $fillable = [
        'judul', 'penulis', 'penerbit', 'tahun_terbit', 'isbn',
        'kategori', 'stok_total', 'stok_tersedia',
        'lokasi_rak', 'cover', 'deskripsi', 'link_digital', 'aktif',
    ];

    protected $casts = [
        'aktif'          => 'boolean',
        'stok_total'     => 'integer',
        'stok_tersedia'  => 'integer',
    ];

    public static array $kategoriLabels = [
        'umum'           => 'Umum',
        'kedokteran'     => 'Kedokteran & Kesehatan',
        'keperawatan'    => 'Keperawatan',
        'kebidanan'      => 'Kebidanan',
        'gizi'           => 'Gizi & Dietika',
        'farmasi'        => 'Farmasi',
        'kesling'        => 'Kesehatan Lingkungan',
        'rmik'           => 'Rekam Medis',
        'penelitian'     => 'Metodologi Penelitian',
        'jurnal'         => 'Jurnal / Majalah',
        'referensi'      => 'Referensi',
        'lainnya'        => 'Lainnya',
    ];

    public function getStatusStokAttribute(): string
    {
        if ($this->stok_tersedia <= 0) return 'Habis';
        if ($this->stok_tersedia <= 2) return 'Terbatas';
        return 'Tersedia';
    }
}
