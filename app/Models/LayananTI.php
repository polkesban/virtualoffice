<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LayananTI extends Model
{
    protected $table = 'layanan_ti';

    protected $fillable = [
        'nama', 'kategori', 'deskripsi', 'prosedur',
        'kontak', 'estimasi_waktu', 'aktif', 'urutan',
    ];

    protected $casts = [
        'aktif'  => 'boolean',
        'urutan' => 'integer',
    ];

    public static array $kategoriLabels = [
        'jaringan'  => 'Jaringan & Internet',
        'hardware'  => 'Hardware & Perangkat',
        'software'  => 'Software & Aplikasi',
        'akun'      => 'Akun & Password',
        'email'     => 'Email & Komunikasi',
        'lainnya'   => 'Lainnya',
    ];

    public function getKategoriLabelAttribute(): string
    {
        return self::$kategoriLabels[$this->kategori] ?? $this->kategori;
    }
}
