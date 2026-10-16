<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LayananBisnis;
use App\Models\LayananBisnisOrder;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LayananBisnisController extends Controller
{
    public function index(Request $request)
    {
        $q = LayananBisnis::orderBy('urutan')->orderBy('nama');

        if ($request->filled('kategori_item')) {
            $q->where('kategori', $request->kategori_item);
        }

        $layanan = $q->get();

        $ordersQuery = LayananBisnisOrder::with(['items', 'verifier'])->latest();

        if ($request->filled('kategori_pesanan')) {
            $ordersQuery->where('kategori_referensi', $request->kategori_pesanan);
        }

        if ($request->filled('status_pembayaran')) {
            $ordersQuery->where('status_pembayaran', $request->status_pembayaran);
        }

        if ($request->filled('status_pesanan')) {
            $ordersQuery->where('status_pesanan', $request->status_pesanan);
        }

        $orders = $ordersQuery->get();

        $orderStats = [
            'total_orders'   => LayananBisnisOrder::count(),
            'paid_total'     => (float) LayananBisnisOrder::where('status_pembayaran', 'lunas')->sum('total_bayar'),
            'unpaid_total'   => (float) LayananBisnisOrder::where('status_pembayaran', 'belum_lunas')->sum('total_bayar'),
        ];

        $monthlyReport = LayananBisnisOrder::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as periode")
            ->selectRaw('COUNT(*) as total_pesanan')
            ->selectRaw('SUM(total_bayar) as total_tagihan')
            ->selectRaw("SUM(CASE WHEN status_pembayaran = 'lunas' THEN total_bayar ELSE 0 END) as total_lunas")
            ->selectRaw("SUM(CASE WHEN status_pembayaran = 'belum_lunas' THEN total_bayar ELSE 0 END) as total_piutang")
            ->groupBy('periode')
            ->orderByDesc('periode')
            ->limit(12)
            ->get();

        return view('admin.layanan-bisnis.index', compact('layanan', 'orders', 'orderStats', 'monthlyReport'));
    }

    public function create()
    {
        $layanan = new LayananBisnis();
        return view('admin.layanan-bisnis.form', compact('layanan'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['slug'] = Str::slug($data['nama']);

        if ($request->hasFile('gambar')) {
            $data['gambar'] = MediaStorage::storeUploadedFile($request->file('gambar'), 'bisnis');
        } elseif ($request->filled('gambar_base64')) {
            $data['gambar'] = $this->storeBase64Image($request->input('gambar_base64'), $data['slug'] ?? 'produk');
        }

        LayananBisnis::create($data);

        return redirect()->route('admin.layanan-bisnis.index')
            ->with('success', 'Layanan berhasil ditambahkan.');
    }

    public function edit(LayananBisnis $layananBisnis)
    {
        return view('admin.layanan-bisnis.form', ['layanan' => $layananBisnis]);
    }

    public function update(Request $request, LayananBisnis $layananBisnis)
    {
        $data = $this->validatedData($request);

        if ($request->boolean('hapus_gambar')) {
            MediaStorage::delete($layananBisnis->gambar, 'bisnis');
            $data['gambar'] = null;
        } elseif ($request->hasFile('gambar')) {
            MediaStorage::delete($layananBisnis->gambar, 'bisnis');
            $data['gambar'] = MediaStorage::storeUploadedFile($request->file('gambar'), 'bisnis');
        } elseif ($request->filled('gambar_base64')) {
            MediaStorage::delete($layananBisnis->gambar, 'bisnis');
            $data['gambar'] = $this->storeBase64Image($request->input('gambar_base64'), $layananBisnis->slug ?: 'produk');
        }

        $layananBisnis->update($data);

        return redirect()->route('admin.layanan-bisnis.index')
            ->with('success', 'Layanan berhasil diperbarui.');
    }

    public function destroy(LayananBisnis $layananBisnis)
    {
        MediaStorage::delete($layananBisnis->gambar, 'bisnis');
        $layananBisnis->delete();
        return back()->with('success', 'Layanan dihapus.');
    }

    public function toggleAktif(LayananBisnis $layananBisnis)
    {
        $layananBisnis->update(['aktif' => !$layananBisnis->aktif]);
        return back()->with('success', 'Status diperbarui.');
    }

    public function toggleTersedia(LayananBisnis $layananBisnis)
    {
        $newTersedia = ! $layananBisnis->is_tersedia;
        $layananBisnis->update(['is_tersedia' => $newTersedia]);
        $statusText = $newTersedia ? 'Ready / Tersedia' : 'Stok Habis';

        return back()->with('success', "Status ketersediaan {$layananBisnis->nama} diubah menjadi {$statusText}.");
    }

    public function showOrder(LayananBisnisOrder $order)
    {
        $order->load(['items.layanan', 'verifier']);

        return view('admin.layanan-bisnis.order-show', [
            'order' => $order,
            'statusOptions' => $order->statusOptions(),
        ]);
    }

    public function updateOrder(Request $request, LayananBisnisOrder $order)
    {
        $statusOptions = array_keys($order->statusOptions());

        $data = $request->validate([
            'status_pesanan'    => ['required', Rule::in($statusOptions)],
            'status_pembayaran' => ['required', Rule::in(array_keys(LayananBisnisOrder::$paymentStatusLabels))],
            'qris_link'         => 'nullable|url|max:500',
            'catatan_admin'     => 'nullable|string|max:4000',
        ]);

        $data['status_diperbarui_pada'] = now();

        if ($data['status_pembayaran'] === 'lunas') {
            $data['lunas_pada'] = $order->lunas_pada ?? now();
            $data['verified_by'] = auth()->id();
        } else {
            $data['lunas_pada'] = null;
            $data['verified_by'] = null;
        }

        $order->update($data);

        return redirect()->route('admin.layanan-bisnis.orders.show', $order)
            ->with('success', 'Status pesanan berhasil diperbarui.');
    }

    public function quickVerifyOrder(LayananBisnisOrder $order)
    {
        $order->update([
            'status_pembayaran'      => 'lunas',
            'lunas_pada'             => now(),
            'verified_by'            => auth()->id(),
            'status_diperbarui_pada' => now(),
            'status_pesanan'         => $order->status_pesanan === 'baru' ? 'diproses' : $order->status_pesanan,
        ]);

        return back()->with('success', "Pesanan {$order->kode_pesanan} berhasil diverifikasi Lunas.");
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'nama'                    => 'required|string|max:255',
            'deskripsi'               => 'nullable|string',
            'harga'                   => 'nullable|numeric|min:0',
            'satuan_harga'            => 'nullable|string|max:50',
            'link_pesan'              => 'nullable|url|max:500',
            'qris_link'               => 'nullable|url|max:500',
            'qris_label'              => 'nullable|string|max:120',
            'kategori'                => 'required|string|max:50',
            'urutan'                  => 'nullable|integer|min:0|max:127',
            'aktif'                   => 'boolean',
            'is_tersedia'             => 'nullable',
            'stok'                    => 'nullable|integer|min:0',
            'gambar'                  => 'nullable|image|max:4096',
            'gambar_base64'           => 'nullable|string',
            'hapus_gambar'            => 'nullable',
            'opsi_varian'             => 'nullable|array',
            'opsi_varian.*.label'     => 'nullable|string|max:120',
            'opsi_varian.*.harga'     => 'nullable|numeric|min:0',
            'opsi_varian.*.satuan_harga' => 'nullable|string|max:50',
            'opsi_varian.*.gambar'    => 'nullable|string',
            'opsi_varian.*.stok'      => 'nullable|integer|min:0',
            'opsi_varian.*.tersedia'  => 'nullable',
        ]);

        $data['aktif'] = $request->boolean('aktif');
        $data['is_tersedia'] = $request->has('is_tersedia') ? $request->boolean('is_tersedia') : true;
        $data['stok'] = $request->filled('stok') ? (int) $request->input('stok') : null;
        $data['opsi_varian'] = $this->sanitizeVariantOptions($request->input('opsi_varian', []));

        return $data;
    }

    private function storeBase64Image(string $base64, string $slug): ?string
    {
        $base64 = trim($base64);
        if (!str_starts_with($base64, 'data:image/')) {
            return null;
        }

        if (preg_match('/^data:image\/(png|jpe?g|webp|gif|svg\+xml);base64,(.+)$/i', $base64, $matches)) {
            $binary = base64_decode($matches[2], true);
            if ($binary !== false && $binary !== '') {
                $ext = strtolower($matches[1]) === 'jpeg' ? 'jpg' : str_replace('+xml', '', strtolower($matches[1]));
                $path = 'bisnis/' . (Str::slug($slug) ?: 'produk') . '-' . Str::uuid() . '.' . $ext;
                $mime = $ext === 'svg' ? 'image/svg+xml' : 'image/' . $ext;
                return MediaStorage::put($path, $binary, $mime);
            }
        }

        return null;
    }

    private function sanitizeVariantOptions(array $options): array
    {
        return collect($options)
            ->map(function ($option) {
                $label = trim((string) ($option['label'] ?? ''));

                if ($label === '') {
                    return null;
                }

                $gambar = trim((string) ($option['gambar'] ?? ''));
                if (str_starts_with($gambar, 'data:image/')) {
                    if (preg_match('/^data:image\/(png|jpe?g|webp|gif|svg\+xml);base64,(.+)$/i', $gambar, $matches)) {
                        $binary = base64_decode($matches[2], true);
                        if ($binary !== false && $binary !== '') {
                            $ext = strtolower($matches[1]) === 'jpeg' ? 'jpg' : str_replace('+xml', '', strtolower($matches[1]));
                            $path = 'bisnis/varian/' . (Str::slug($label) ?: 'varian') . '-' . Str::uuid() . '.' . $ext;
                            $mime = $ext === 'svg' ? 'image/svg+xml' : 'image/' . $ext;
                            $stored = MediaStorage::put($path, $binary, $mime);
                            $gambar = MediaStorage::browserUrl($stored) ?? MediaStorage::url($stored) ?? $stored;
                        }
                    }
                }

                return [
                    'label'        => $label,
                    'harga'        => ($option['harga'] ?? '') !== '' ? (float) $option['harga'] : null,
                    'satuan_harga' => trim((string) ($option['satuan_harga'] ?? '')) ?: null,
                    'gambar'       => $gambar ?: null,
                    'tersedia'     => filter_var($option['tersedia'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'stok'         => isset($option['stok']) && $option['stok'] !== '' && $option['stok'] !== null ? max(0, (int) $option['stok']) : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
