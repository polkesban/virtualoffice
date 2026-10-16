<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LayananBisnisOrderItem extends Model
{
    protected $table = 'layanan_bisnis_order_items';

    protected $fillable = [
        'layanan_bisnis_order_id',
        'layanan_bisnis_id',
        'nama_item',
        'kategori',
        'jenis_tipe_ukuran',
        'harga_satuan',
        'jumlah',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'harga_satuan' => 'decimal:2',
            'subtotal'     => 'decimal:2',
            'jumlah'       => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(LayananBisnisOrder::class, 'layanan_bisnis_order_id');
    }

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(LayananBisnis::class, 'layanan_bisnis_id');
    }

    public function getSubtotalFormatAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->subtotal, 0, ',', '.');
    }

    public function getHargaSatuanFormatAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->harga_satuan, 0, ',', '.');
    }
}