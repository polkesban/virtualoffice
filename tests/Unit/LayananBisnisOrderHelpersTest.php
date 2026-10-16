<?php

namespace Tests\Unit;

use App\Models\LayananBisnis;
use App\Models\LayananBisnisOrder;
use PHPUnit\Framework\TestCase;

class LayananBisnisOrderHelpersTest extends TestCase
{
    public function test_variant_price_is_used_when_selected(): void
    {
        $layanan = new LayananBisnis([
            'harga' => 15000,
            'opsi_varian' => [
                ['label' => 'Ukuran M', 'harga' => 18000, 'satuan_harga' => 'per box'],
            ],
        ]);

        $this->assertSame(18000.0, $layanan->resolveHarga('ukuran m'));
    }

    public function test_base_price_is_used_when_variant_has_no_custom_price(): void
    {
        $layanan = new LayananBisnis([
            'harga' => 25000,
            'opsi_varian' => [
                ['label' => 'Paket Reguler', 'harga' => null],
            ],
        ]);

        $this->assertSame(25000.0, $layanan->resolveHarga('Paket Reguler'));
    }

    public function test_order_status_options_follow_category_workflow(): void
    {
        $produkStatuses = LayananBisnisOrder::statusOptionsForCategory('produk');
        $pelatihanStatuses = LayananBisnisOrder::statusOptionsForCategory('pelatihan');
        $sewaStatuses = LayananBisnisOrder::statusOptionsForCategory('sewa');

        $this->assertArrayHasKey('dikirim', $produkStatuses);
        $this->assertSame('menunggu_verifikasi', LayananBisnisOrder::defaultStatusForCategory('pelatihan'));
        $this->assertArrayHasKey('dikembalikan', $sewaStatuses);
    }
}