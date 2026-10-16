<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Publikasi extends Model
{
    protected $table = 'publikasi';

    protected $fillable = [
        'judul', 'penulis', 'institusi', 'jenis', 'tahun',
        'volume', 'nomor', 'halaman', 'doi',
        'abstrak', 'file_url', 'link_url', 'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'tahun' => 'integer',
    ];

    public static array $jenisLabels = [
        'jurnal'     => 'Artikel Jurnal',
        'prosiding'  => 'Prosiding Konferensi',
        'skripsi'    => 'Skripsi / Tugas Akhir',
        'tesis'      => 'Tesis',
        'disertasi'  => 'Disertasi',
        'laporan'    => 'Laporan Penelitian',
        'buku'       => 'Buku / Monograf',
    ];

    public function getJenisLabelAttribute(): string
    {
        return self::$jenisLabels[$this->jenis] ?? $this->jenis;
    }
}
