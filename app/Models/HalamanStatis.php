<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HalamanStatis extends Model
{
    protected $table = 'halaman_statis';

    protected $fillable = ['kunci', 'judul', 'menu_label', 'menu_icon', 'konten', 'data_ekstra', 'updated_by'];

    protected $casts = ['data_ekstra' => 'array'];

    public static array $kunciLabels = [
        'visi_misi'    => 'Visi & Misi',
        'sejarah'      => 'Sejarah',
        'akreditasi'   => 'Akreditasi',
        'kerjasama'    => 'Kerjasama',
        'perpustakaan' => 'Perpustakaan',
        'penelitian'   => 'Penelitian & Pengabmas',
        'asrama'       => 'Asrama',
        'laboratorium' => 'Laboratorium',
        'penerimaan'   => 'Informasi Penerimaan',
    ];

    public static function systemKeys(): array
    {
        return ['beranda', 'site_header', 'site_footer'];
    }

    public function isSystemPage(): bool
    {
        return in_array($this->kunci, self::systemKeys(), true);
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
