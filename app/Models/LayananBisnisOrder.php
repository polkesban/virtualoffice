<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LayananBisnisOrder extends Model
{
    protected $table = 'layanan_bisnis_orders';

    protected $fillable = [
        'kode_pesanan',
        'nama_pemesan',
        'nomor_wa',
        'email',
        'alamat',
        'catatan_pemesan',
        'kategori_referensi',
        'status_pesanan',
        'status_pembayaran',
        'total_bayar',
        'qris_link',
        'qris_label',
        'bukti_pembayaran',
        'lunas_pada',
        'status_diperbarui_pada',
        'verified_by',
        'catatan_admin',
    ];

    protected function casts(): array
    {
        return [
            'total_bayar'           => 'decimal:2',
            'lunas_pada'            => 'datetime',
            'status_diperbarui_pada'=> 'datetime',
        ];
    }

    public static array $paymentStatusLabels = [
        'belum_lunas' => 'Belum Lunas',
        'lunas'       => 'Lunas',
    ];

    protected static function booted(): void
    {
        static::created(function (self $order) {
            if (! $order->kode_pesanan) {
                $order->forceFill([
                    'kode_pesanan' => 'BIS-' . now()->format('ymd') . '-' . str_pad((string) $order->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(LayananBisnisOrderItem::class, 'layanan_bisnis_order_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public static function statusOptionsForCategory(?string $category): array
    {
        return match ($category) {
            'produk', 'catering' => [
                'baru'     => 'Pesanan Baru',
                'diproses' => 'Diproses',
                'dikirim'  => 'Dikirim',
            ],
            'pelatihan' => [
                'menunggu_verifikasi' => 'Menunggu Verifikasi',
                'peserta_aktif'       => 'Peserta Aktif',
            ],
            'sewa' => [
                'diproses'    => 'Diproses',
                'disetujui'   => 'Disetujui',
                'dikembalikan'=> 'Dikembalikan',
            ],
            default => [
                'baru'     => 'Pesanan Baru',
                'diproses' => 'Diproses',
                'selesai'  => 'Selesai',
            ],
        };
    }

    public static function defaultStatusForCategory(?string $category): string
    {
        return array_key_first(self::statusOptionsForCategory($category));
    }

    public function statusOptions(): array
    {
        return self::statusOptionsForCategory($this->kategori_referensi);
    }

    public function recalculateTotals(): void
    {
        $total = (float) $this->items()->sum('subtotal');

        $this->forceFill([
            'total_bayar' => $total,
        ])->saveQuietly();
    }

    public function getStatusPesananLabelAttribute(): string
    {
        return $this->statusOptions()[$this->status_pesanan] ?? ucfirst(str_replace('_', ' ', $this->status_pesanan));
    }

    public function getStatusPembayaranLabelAttribute(): string
    {
        if ($this->status_pembayaran === 'belum_lunas' && !empty($this->bukti_pembayaran)) {
            return 'Menunggu Verifikasi';
        }

        return self::$paymentStatusLabels[$this->status_pembayaran] ?? ucfirst(str_replace('_', ' ', $this->status_pembayaran));
    }

    public function getStatusPesananBadgeClassAttribute(): string
    {
        return match ($this->status_pesanan) {
            'baru', 'menunggu_verifikasi' => 'bg-amber-50 text-amber-700',
            'diproses', 'peserta_aktif', 'disetujui' => 'bg-blue-50 text-blue-700',
            'dikirim', 'selesai', 'dikembalikan' => 'bg-green-50 text-green-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    public function getStatusPembayaranBadgeClassAttribute(): string
    {
        if ($this->status_pembayaran === 'lunas') {
            return 'bg-green-50 text-green-700';
        }

        if (!empty($this->bukti_pembayaran)) {
            return 'bg-amber-50 text-amber-700';
        }

        return 'bg-red-50 text-red-700';
    }

    public function getBuktiPembayaranUrlAttribute(): ?string
    {
        return \App\Support\MediaStorage::url($this->bukti_pembayaran);
    }

    public function getTotalBayarFormatAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->total_bayar, 0, ',', '.');
    }
}