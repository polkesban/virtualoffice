<?php

namespace App\Http\Controllers;

use App\Models\LayananBisnis;
use App\Models\LayananBisnisOrder;
use App\Support\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LayananBisnisOrderController extends Controller
{
    public function create(Request $request): View
    {
        $layananList = LayananBisnis::where('aktif', true)
            ->orderBy('kategori')
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();

        $selectedSlug = trim((string) $request->query('layanan'));
        $selected = $selectedSlug !== '' ? $layananList->firstWhere('slug', $selectedSlug) : null;
        $defaultItems = old('items');

        if (! is_array($defaultItems) || $defaultItems === []) {
            $defaultItems = [];
            if ($selected) {
                if ($selected->aktif_varian_opsi !== []) {
                    foreach ($selected->aktif_varian_opsi as $idx => $v) {
                        $defaultItems[] = [
                            'layanan_bisnis_id' => $selected->id,
                            'jenis_tipe_ukuran' => $v['label'],
                            'jumlah' => $idx === 0 ? 1 : 0,
                        ];
                    }
                } else {
                    $defaultItems[] = [
                        'layanan_bisnis_id' => $selected->id,
                        'jenis_tipe_ukuran' => '',
                        'jumlah' => 1,
                    ];
                }
            }
        }

        $latestOrder = null;
        if (session()->has('layanan_bisnis_order_id')) {
            $latestOrder = LayananBisnisOrder::with('items')->find(session('layanan_bisnis_order_id'));
        }

        return view('layanan-bisnis.order', compact('layananList', 'defaultItems', 'latestOrder'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_pemesan'      => 'required|string|max:150',
            'nomor_wa'          => 'required|string|max:30',
            'email'             => 'nullable|email|max:150',
            'alamat'            => 'nullable|string|max:2000',
            'catatan_pemesan'   => 'nullable|string|max:3000',
            'items'             => 'required|array|min:1',
            'items.*.layanan_bisnis_id' => 'required|integer|exists:layanan_bisnis,id',
            'items.*.jenis_tipe_ukuran' => 'nullable|string|max:150',
            'items.*.jumlah'    => 'required|integer|min:0|max:999',
            'bukti_pembayaran'  => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        $normalizedItems = collect($validated['items'])
            ->map(function ($item) {
                return [
                    'layanan_bisnis_id' => (int) ($item['layanan_bisnis_id'] ?? 0),
                    'jenis_tipe_ukuran' => trim((string) ($item['jenis_tipe_ukuran'] ?? '')) ?: null,
                    'jumlah'            => max(0, (int) ($item['jumlah'] ?? 0)),
                ];
            })
            ->filter(fn ($item) => $item['layanan_bisnis_id'] > 0 && $item['jumlah'] > 0)
            ->values();

        if ($normalizedItems->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Silakan tentukan kuantiti minimal 1 pada salah satu pilihan menu atau varian.',
            ]);
        }

        $layananMap = LayananBisnis::where('aktif', true)
            ->whereIn('id', $normalizedItems->pluck('layanan_bisnis_id')->unique())
            ->get()
            ->keyBy('id');

        $order = DB::transaction(function () use ($request, $validated, $normalizedItems, $layananMap) {
            $preparedItems = [];
            $categories = [];
            $totalBayar = 0;
            $qrisLink = null;
            $qrisLabel = null;

            foreach ($normalizedItems as $item) {
                $layanan = $layananMap->get($item['layanan_bisnis_id']);

                if (! $layanan || ! $layanan->isAvailable()) {
                    throw ValidationException::withMessages([
                        'items' => 'Produk atau layanan ' . ($layanan?->nama ?? '') . ' saat ini sedang tidak tersedia atau stok habis.',
                    ]);
                }

                $varian = $layanan->resolveVariant($item['jenis_tipe_ukuran']);

                if ($layanan->aktif_varian_opsi !== []) {
                    if (! $varian) {
                        throw ValidationException::withMessages([
                            'items' => 'Pilih jenis, tipe, atau ukuran yang valid untuk ' . $layanan->nama . '.',
                        ]);
                    }

                    if (! ($varian['is_available'] ?? true)) {
                        throw ValidationException::withMessages([
                            'items' => 'Varian ' . $varian['label'] . ' pada ' . $layanan->nama . ' saat ini sedang habis stok.',
                        ]);
                    }

                    if (isset($varian['stok']) && $varian['stok'] !== null && $item['jumlah'] > (int) $varian['stok']) {
                        throw ValidationException::withMessages([
                            'items' => 'Jumlah pesanan untuk varian ' . $varian['label'] . ' melebihi sisa stok yang tersedia (' . $varian['stok'] . ').',
                        ]);
                    }
                } else {
                    if ($layanan->stok !== null && $item['jumlah'] > (int) $layanan->stok) {
                        throw ValidationException::withMessages([
                            'items' => 'Jumlah pesanan untuk ' . $layanan->nama . ' melebihi sisa stok yang tersedia (' . $layanan->stok . ').',
                        ]);
                    }
                }

                $hargaSatuan = $layanan->resolveHarga($item['jenis_tipe_ukuran']);
                $subtotal = $hargaSatuan * $item['jumlah'];

                $preparedItems[] = [
                    'layanan_bisnis_id' => $layanan->id,
                    'nama_item'         => $layanan->nama,
                    'kategori'          => $layanan->kategori,
                    'jenis_tipe_ukuran' => $varian['label'] ?? $item['jenis_tipe_ukuran'],
                    'harga_satuan'      => $hargaSatuan,
                    'jumlah'            => $item['jumlah'],
                    'subtotal'          => $subtotal,
                ];

                $categories[] = $layanan->kategori;
                $totalBayar += $subtotal;
                $qrisLink ??= $layanan->qris_link;
                $qrisLabel ??= $layanan->qris_label;
            }

            $kategoriReferensi = $this->resolveReferenceCategory($categories);
            $buktiPembayaran = $request->hasFile('bukti_pembayaran')
                ? MediaStorage::storeUploadedFile($request->file('bukti_pembayaran'), 'bukti-pembayaran-bisnis')
                : null;

            $order = LayananBisnisOrder::create([
                'nama_pemesan'      => $validated['nama_pemesan'],
                'nomor_wa'          => $validated['nomor_wa'],
                'email'             => $validated['email'] ?? null,
                'alamat'            => $validated['alamat'] ?? null,
                'catatan_pemesan'   => $validated['catatan_pemesan'] ?? null,
                'kategori_referensi'=> $kategoriReferensi,
                'status_pesanan'    => LayananBisnisOrder::defaultStatusForCategory($kategoriReferensi),
                'status_pembayaran' => 'belum_lunas',
                'total_bayar'       => $totalBayar,
                'qris_link'         => $qrisLink,
                'qris_label'        => $qrisLabel,
                'bukti_pembayaran'  => $buktiPembayaran,
            ]);

            $order->items()->createMany($preparedItems);
            $order->recalculateTotals();

            return $order;
        });

        return redirect()->route('layanan-bisnis.order')
            ->with('success', 'Pesanan berhasil dikirim. Silakan lanjutkan pembayaran dan simpan kode pesanan Anda.')
            ->with('layanan_bisnis_order_id', $order->id)
            ->with('layanan_bisnis_order_code', $order->kode_pesanan);
    }

    private function resolveReferenceCategory(array $categories): ?string
    {
        if ($categories === []) {
            return null;
        }

        $counts = array_count_values($categories);
        arsort($counts);

        return array_key_first($counts);
    }
}