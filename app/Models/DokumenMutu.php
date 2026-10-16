<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DokumenMutu extends Model
{
    protected $table = 'dokumen_mutu';

    protected $fillable = [
        'judul', 'jenis', 'nomor_dok', 'tanggal_terbit',
        'berlaku_sampai', 'versi', 'file_url', 'keterangan', 'aktif',
    ];

    protected $casts = [
        'aktif'          => 'boolean',
        'tanggal_terbit' => 'date',
        'berlaku_sampai' => 'date',
    ];

    public static array $jenisLabels = [
        'kebijakan'  => 'Kebijakan Mutu',
        'standar'    => 'Standar Mutu',
        'prosedur'   => 'Prosedur (SOP)',
        'formulir'   => 'Formulir',
        'akreditasi' => 'Akreditasi',
        'lap_audit'  => 'Laporan Audit',
        'lainnya'    => 'Lainnya',
    ];

    public function getJenisLabelAttribute(): string
    {
        return self::$jenisLabels[$this->jenis] ?? $this->jenis;
    }

    public function getMasaBerlakuAttribute(): string
    {
        if (!$this->berlaku_sampai) return 'Tidak terbatas';
        return $this->berlaku_sampai->isPast()
            ? '❌ Kadaluarsa ' . $this->berlaku_sampai->format('d/m/Y')
            : 'Berlaku s.d. ' . $this->berlaku_sampai->format('d/m/Y');
    }
}
