<?php

namespace Tests\Feature;

use App\Models\LayananBisnis;
use App\Models\LayananBisnisOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class LayananBisnisOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_order_page_is_accessible(): void
    {
        $response = $this->get(route('layanan-bisnis.order'));

        $response->assertOk();
        $response->assertSeeText('Form Pemesanan Layanan & Produk');
    }

    public function test_public_order_submission_creates_order_and_items(): void
    {
        config()->set('media.disk', 'local');
        Storage::fake('public');

        $layanan = LayananBisnis::create([
            'nama' => 'Snack Box Seminar',
            'slug' => 'snack-box-seminar',
            'deskripsi' => 'Paket konsumsi acara.',
            'harga' => 15000,
            'satuan_harga' => 'per box',
            'opsi_varian' => [
                ['label' => 'Premium', 'harga' => 18000, 'satuan_harga' => 'per box'],
            ],
            'qris_link' => 'https://example.com/qris/snack-box',
            'qris_label' => 'QRIS Snack Box',
            'kategori' => 'produk',
            'urutan' => 1,
            'aktif' => true,
        ]);

        $response = $this->post(route('layanan-bisnis.order.store'), [
            'nama_pemesan' => 'Pemesan Tes',
            'nomor_wa' => '08123456789',
            'email' => 'pemesan@example.com',
            'alamat' => 'Jl. Uji Integrasi No. 1',
            'catatan_pemesan' => 'Mohon dikirim pagi hari.',
            'items' => [
                [
                    'layanan_bisnis_id' => $layanan->id,
                    'jenis_tipe_ukuran' => 'Premium',
                    'jumlah' => 3,
                ],
            ],
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti.png'),
        ]);

        $response->assertRedirect(route('layanan-bisnis.order'));
        $response->assertSessionHas('success');

        $order = LayananBisnisOrder::with('items')->first();

        $this->assertNotNull($order);
        $this->assertSame('Pemesan Tes', $order->nama_pemesan);
        $this->assertSame('produk', $order->kategori_referensi);
        $this->assertSame('baru', $order->status_pesanan);
        $this->assertSame('belum_lunas', $order->status_pembayaran);
        $this->assertSame(54000.0, (float) $order->total_bayar);
        $this->assertCount(1, $order->items);
        $this->assertSame('Premium', $order->items->first()->jenis_tipe_ukuran);
        $this->assertSame(54000.0, (float) $order->items->first()->subtotal);
        Storage::disk('public')->assertExists($order->bukti_pembayaran);
    }

    public function test_cannot_order_out_of_stock_product(): void
    {
        $layanan = LayananBisnis::create([
            'nama' => 'Produk Habis',
            'slug' => 'produk-habis',
            'harga' => 10000,
            'kategori' => 'produk',
            'aktif' => true,
            'is_tersedia' => false,
        ]);

        $response = $this->from(route('layanan-bisnis.order'))->post(route('layanan-bisnis.order.store'), [
            'nama_pemesan' => 'Budi',
            'nomor_wa' => '08123456789',
            'items' => [
                [
                    'layanan_bisnis_id' => $layanan->id,
                    'jenis_tipe_ukuran' => '',
                    'jumlah' => 1,
                ],
            ],
        ]);

        $response->assertRedirect(route('layanan-bisnis.order'));
        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('layanan_bisnis_orders', 0);
    }

    public function test_cannot_order_out_of_stock_variant(): void
    {
        $layanan = LayananBisnis::create([
            'nama' => 'Kantin Sehat',
            'slug' => 'kantin-sehat',
            'harga' => 10000,
            'kategori' => 'catering',
            'aktif' => true,
            'is_tersedia' => true,
            'opsi_varian' => [
                ['label' => 'Susu Panas', 'harga' => 10000, 'tersedia' => true],
                ['label' => 'Susu Dingin', 'harga' => 10000, 'tersedia' => false],
            ],
        ]);

        $response = $this->from(route('layanan-bisnis.order'))->post(route('layanan-bisnis.order.store'), [
            'nama_pemesan' => 'Budi',
            'nomor_wa' => '08123456789',
            'items' => [
                [
                    'layanan_bisnis_id' => $layanan->id,
                    'jenis_tipe_ukuran' => 'Susu Dingin',
                    'jumlah' => 1,
                ],
            ],
        ]);

        $response->assertRedirect(route('layanan-bisnis.order'));
        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('layanan_bisnis_orders', 0);
    }

    public function test_cannot_order_exceeding_stock_quantity(): void
    {
        $layanan = LayananBisnis::create([
            'nama' => 'Buku Panduan',
            'slug' => 'buku-panduan',
            'harga' => 50000,
            'kategori' => 'produk',
            'aktif' => true,
            'is_tersedia' => true,
            'stok' => 2,
        ]);

        $response = $this->from(route('layanan-bisnis.order'))->post(route('layanan-bisnis.order.store'), [
            'nama_pemesan' => 'Budi',
            'nomor_wa' => '08123456789',
            'items' => [
                [
                    'layanan_bisnis_id' => $layanan->id,
                    'jenis_tipe_ukuran' => '',
                    'jumlah' => 5,
                ],
            ],
        ]);

        $response->assertRedirect(route('layanan-bisnis.order'));
        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('layanan_bisnis_orders', 0);
    }

    public function test_admin_can_toggle_tersedia(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'superadmin', 'is_admin' => true]);

        $layanan = LayananBisnis::create([
            'nama' => 'Katering Diet',
            'slug' => 'katering-diet',
            'harga' => 25000,
            'kategori' => 'catering',
            'aktif' => true,
            'is_tersedia' => true,
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.layanan-bisnis.toggle-tersedia', $layanan));

        $response->assertRedirect();
        $this->assertFalse($layanan->fresh()->is_tersedia);

        $response = $this->actingAs($admin)
            ->patch(route('admin.layanan-bisnis.toggle-tersedia', $layanan));

        $response->assertRedirect();
        $this->assertTrue($layanan->fresh()->is_tersedia);
    }

    public function test_admin_can_save_product_with_base64_screenshot_and_variant_screenshot(): void
    {
        config()->set('media.disk', 'local');
        Storage::fake('public');

        $admin = \App\Models\User::factory()->create(['role' => 'superadmin', 'is_admin' => true]);

        // 1x1 transparent png
        $fakeScreenshotBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($admin)
            ->post(route('admin.layanan-bisnis.store'), [
                'nama' => 'Menu Sarapan Sehat',
                'kategori' => 'catering',
                'harga' => 20000,
                'satuan_harga' => 'per porsi',
                'gambar_base64' => $fakeScreenshotBase64,
                'opsi_varian' => [
                    [
                        'label' => 'Bubur Ayam Komplit',
                        'harga' => 15000,
                        'satuan_harga' => 'per porsi',
                        'gambar' => $fakeScreenshotBase64,
                        'stok' => 10,
                        'tersedia' => '1',
                    ],
                ],
                'aktif' => '1',
                'is_tersedia' => '1',
            ]);

        $response->assertRedirect(route('admin.layanan-bisnis.index'));
        $response->assertSessionHas('success');

        $product = LayananBisnis::where('nama', 'Menu Sarapan Sehat')->first();
        $this->assertNotNull($product);
        $this->assertNotNull($product->gambar);
        $this->assertStringStartsWith('bisnis/', $product->gambar);
        Storage::disk('public')->assertExists($product->gambar);

        $this->assertCount(1, $product->opsi_varian);
        $variant = $product->opsi_varian[0];
        $this->assertSame('Bubur Ayam Komplit', $variant['label']);
        $this->assertNotNull($variant['gambar']);
        $this->assertStringContainsString('varian', $variant['gambar']);

        // Test remove photo via hapus_gambar
        $responseUpdate = $this->actingAs($admin)
            ->put(route('admin.layanan-bisnis.update', $product), [
                'nama' => 'Menu Sarapan Sehat',
                'kategori' => 'catering',
                'hapus_gambar' => '1',
                'aktif' => '1',
                'is_tersedia' => '1',
            ]);

        $responseUpdate->assertRedirect(route('admin.layanan-bisnis.index'));
        $this->assertNull($product->fresh()->gambar);
    }
}