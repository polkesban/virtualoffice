<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelatihan extends Model
{
    protected $table = 'pelatihan';

    protected $fillable = [
        'judul', 'jenis', 'penyelenggara',
        'tanggal_mulai', 'tanggal_selesai', 'lokasi',
        'deskripsi', 'peserta_target', 'jumlah_peserta',
        'hasil', 'foto', 'aktif',
    ];

    protected $casts = [
        'aktif'           => 'boolean',
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
        'jumlah_peserta'  => 'integer',
    ];

    public static array $jenisLabels = [
        'pelatihan'      => 'Pelatihan',
        'workshop'       => 'Workshop',
        'seminar'        => 'Seminar',
        'bimtek'         => 'Bimtek',
        'studi_banding'  => 'Studi Banding',
        'lainnya'        => 'Lainnya',
    ];

    public function getJenisLabelAttribute(): string
    {
        return self::$jenisLabels[$this->jenis] ?? $this->jenis;
    }

    public function getDurasiAttribute(): string
    {
        if (!$this->tanggal_mulai) return '—';
        if (!$this->tanggal_selesai || $this->tanggal_mulai->eq($this->tanggal_selesai)) {
            return $this->tanggal_mulai->format('d M Y');
        }
        return $this->tanggal_mulai->format('d M') . ' – ' . $this->tanggal_selesai->format('d M Y');
    }
}
