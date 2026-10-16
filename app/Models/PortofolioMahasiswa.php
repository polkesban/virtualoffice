<?php

namespace App\Models;

use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['mahasiswa_id','judul','kategori','deskripsi','institusi','tanggal','lampiran','url_eksternal','tingkat','tampilkan'])]
class PortofolioMahasiswa extends Model
{
    protected $table = 'portofolio_mahasiswa';

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'tampilkan' => 'boolean'];
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public static array $kategoriLabels = [
        'prestasi'     => 'Prestasi',
        'organisasi'   => 'Organisasi',
        'penelitian'   => 'Penelitian',
        'magang'       => 'Magang / PKL',
        'karya'        => 'Karya / Publikasi',
        'sertifikasi'  => 'Sertifikasi',
        'lainnya'      => 'Lainnya',
    ];

    public static array $tingkatLabels = [
        'kampus'        => 'Tingkat Kampus',
        'kota'          => 'Tingkat Kota/Kab.',
        'provinsi'      => 'Tingkat Provinsi',
        'nasional'      => 'Tingkat Nasional',
        'internasional' => 'Tingkat Internasional',
    ];

    public function getLampiranUrlAttribute(): ?string
    {
        return MediaStorage::url($this->lampiran);
    }
}
