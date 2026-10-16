<?php

namespace App\Models;

use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LayananBisnis extends Model
{
    protected $table = 'layanan_bisnis';

    protected $fillable = [
        'nama', 'slug', 'deskripsi', 'harga', 'satuan_harga',
        'opsi_varian', 'gambar', 'link_pesan', 'qris_link', 'qris_label', 'kategori', 'urutan', 'aktif',
        'is_tersedia', 'stok',
    ];

    protected function casts(): array
    {
        return [
            'harga'       => 'decimal:2',
            'aktif'       => 'boolean',
            'is_tersedia' => 'boolean',
            'stok'        => 'integer',
            'opsi_varian' => 'array',
        ];
    }

    public static array $kategoriLabels = [
        'layanan'   => 'Jasa Layanan',
        'produk'    => 'Produk',
        'catering'  => 'Catering / Konsumsi',
        'sewa'      => 'Sewa / Rental',
        'pelatihan' => 'Pelatihan',
    ];

    public function isAvailable(): bool
    {
        if (! $this->aktif) {
            return false;
        }

        if (! $this->is_tersedia) {
            return false;
        }

        if ($this->stok !== null && $this->stok <= 0) {
            return false;
        }

        return true;
    }

    public function getStatusStokLabelAttribute(): string
    {
        if (! $this->is_tersedia || ($this->stok !== null && $this->stok <= 0)) {
            return 'Stok Habis';
        }

        if ($this->stok !== null) {
            return 'Ready (' . $this->stok . ')';
        }

        return 'Ready';
    }

    public function getStatusStokBadgeClassAttribute(): string
    {
        return $this->isAvailable()
            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
            : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300';
    }

    public function getUrlGambarAttribute(): ?string
    {
        return MediaStorage::url($this->gambar, 'bisnis');
    }

    public function getAktifVarianOpsiAttribute(): array
    {
        return collect($this->opsi_varian ?? [])
            ->map(function ($option) {
                $label = trim((string) ($option['label'] ?? ''));

                if ($label === '') {
                    return null;
                }

                $gambar = isset($option['gambar']) && $option['gambar'] !== '' ? (string) $option['gambar'] : null;
                if ($gambar) {
                    if (str_contains($gambar, 'drive.google.com')) {
                        $gambar = \App\Support\GoogleDriveEmbed::directImageUrl($gambar) ?: $gambar;
                    } elseif (\App\Support\MediaStorage::isGoogleReference($gambar)) {
                        $gambar = \App\Support\MediaStorage::url($gambar);
                    } elseif (! \Illuminate\Support\Str::startsWith($gambar, ['http://', 'https://', '/'])) {
                        $gambar = \App\Support\MediaStorage::url($gambar, 'bisnis') ?: ('/storage/' . ltrim($gambar, '/'));
                    }
                }

                $tersedia = filter_var($option['tersedia'] ?? true, FILTER_VALIDATE_BOOLEAN);
                $stok = isset($option['stok']) && $option['stok'] !== '' && $option['stok'] !== null ? (int) $option['stok'] : null;
                $isVariantAvailable = $tersedia && ($stok === null || $stok > 0);

                return [
                    'label'              => $label,
                    'harga'              => isset($option['harga']) && $option['harga'] !== '' ? (float) $option['harga'] : null,
                    'satuan_harga'       => trim((string) ($option['satuan_harga'] ?? '')) ?: null,
                    'gambar'             => $gambar,
                    'tersedia'           => $tersedia,
                    'stok'               => $stok,
                    'is_available'       => $isVariantAvailable,
                    'status_stok_label'  => ! $isVariantAvailable ? 'Stok Habis' : ($stok !== null ? 'Ready (' . $stok . ')' : 'Ready'),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function resolveVariant(?string $selected): ?array
    {
        $needle = trim((string) $selected);

        if ($needle === '') {
            return null;
        }

        foreach ($this->aktif_varian_opsi as $option) {
            if (strcasecmp($option['label'], $needle) === 0) {
                return $option;
            }
        }

        return null;
    }

    public function resolveHarga(?string $selectedVariant = null): float
    {
        $variant = $this->resolveVariant($selectedVariant);

        if ($variant && $variant['harga'] !== null) {
            return (float) $variant['harga'];
        }

        return (float) ($this->harga ?? 0);
    }

    public function getHargaFormatAttribute(): string
    {
        if (!$this->harga) return 'Hubungi kami';
        return 'Rp ' . number_format($this->harga, 0, ',', '.') . ($this->satuan_harga ? ' / ' . $this->satuan_harga : '');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->slug) {
                $model->slug = Str::slug($model->nama);
            }
        });
    }
}
