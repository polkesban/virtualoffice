@extends('admin.layouts.admin')

@section('title', 'Detail Pesanan Bisnis')
@section('page-title', 'Detail Pesanan Unit Bisnis')
@section('page-subtitle', $order->kode_pesanan)

@section('content')
<div class="mx-auto max-w-7xl space-y-6 p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('admin.layanan-bisnis.index') }}#orders" class="text-sm font-medium text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">← Kembali ke dashboard pesanan</a>
            <h1 class="mt-2 text-2xl font-black text-gray-900 dark:text-white">{{ $order->kode_pesanan }}</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $order->status_pesanan_badge_class }}">{{ $order->status_pesanan_label }}</span>
            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $order->status_pembayaran_badge_class }}">{{ $order->status_pembayaran_label }}</span>
            @if($order->status_pembayaran !== 'lunas')
            <form action="{{ route('admin.layanan-bisnis.orders.quick-verify', $order) }}" method="POST" class="inline" onsubmit="return confirm('Verifikasi pembayaran pesanan {{ $order->kode_pesanan }} sebagai LUNAS?')">
                @csrf
                @method('PATCH')
                <button type="submit" class="rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1.5 text-xs font-bold transition shadow-sm">
                    ✓ Verifikasi Lunas
                </button>
            </form>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/40 dark:bg-green-950/20 dark:text-green-300">
        {{ session('success') }}
    </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-7">
            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">Informasi Pemesan</h2>
                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Nama</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $order->nama_pemesan }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-400">WhatsApp</p>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->nomor_wa) }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex text-sm font-semibold text-brand-700 hover:underline dark:text-brand-300">{{ $order->nomor_wa }}</a>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Email</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $order->email ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Kategori Referensi</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ \App\Models\LayananBisnis::$kategoriLabels[$order->kategori_referensi] ?? ($order->kategori_referensi ?: '—') }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Alamat / Lokasi</p>
                        <p class="mt-1 text-sm leading-6 text-gray-700 dark:text-gray-300">{{ $order->alamat ?: '—' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Catatan Pemesan</p>
                        <p class="mt-1 text-sm leading-6 text-gray-700 dark:text-gray-300">{{ $order->catatan_pemesan ?: '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">Item Pesanan</h2>
                    <span class="text-sm font-black text-brand-700 dark:text-brand-300">{{ $order->total_bayar_format }}</span>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wide text-gray-500 dark:bg-gray-800/60">
                            <tr>
                                <th class="px-4 py-3 text-left">Item</th>
                                <th class="px-4 py-3 text-left">Jenis / Tipe / Ukuran</th>
                                <th class="px-4 py-3 text-left">Jumlah</th>
                                <th class="px-4 py-3 text-left">Harga</th>
                                <th class="px-4 py-3 text-left">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($order->items as $item)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $item->nama_item }}</p>
                                    <p class="text-xs text-gray-400">{{ \App\Models\LayananBisnis::$kategoriLabels[$item->kategori] ?? $item->kategori }}</p>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $item->jenis_tipe_ukuran ?: '—' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $item->jumlah }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $item->harga_satuan_format }}</td>
                                <td class="px-4 py-3 font-bold text-brand-700 dark:text-brand-300">{{ $item->subtotal_format }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">Bukti Pembayaran</h2>
                @if($order->bukti_pembayaran_url)
                    @php $ext = strtolower(pathinfo($order->bukti_pembayaran ?? '', PATHINFO_EXTENSION)); @endphp
                    <div class="mt-4 space-y-4">
                        <a href="{{ $order->bukti_pembayaran_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-full bg-brand-50 px-4 py-2 text-sm font-bold text-brand-700 hover:bg-brand-100 dark:bg-brand-900/30 dark:text-brand-300">
                            Lihat / Unduh Bukti Pembayaran
                        </a>
                        @if(in_array($ext, ['jpg', 'jpeg', 'png', 'webp']))
                        <img src="{{ $order->bukti_pembayaran_url }}" alt="Bukti pembayaran {{ $order->kode_pesanan }}" class="max-h-[480px] rounded-2xl border border-gray-200 object-contain dark:border-gray-700">
                        @endif
                    </div>
                @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Belum ada bukti pembayaran yang diunggah.</p>
                @endif
            </div>
        </div>

        <div class="space-y-6 xl:col-span-5">
            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">Update Status</h2>
                <form action="{{ route('admin.layanan-bisnis.orders.update', $order) }}" method="POST" class="mt-5 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Status Pesanan</label>
                        <select name="status_pesanan" class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('status_pesanan', $order->status_pesanan) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Status Pembayaran</label>
                        <select name="status_pembayaran" class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            @foreach(\App\Models\LayananBisnisOrder::$paymentStatusLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('status_pembayaran', $order->status_pembayaran) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Link QRIS</label>
                        <input type="url" name="qris_link" value="{{ old('qris_link', $order->qris_link) }}"
                               class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Catatan Admin</label>
                        <textarea name="catatan_admin" rows="6"
                                  class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">{{ old('catatan_admin', $order->catatan_admin) }}</textarea>
                    </div>

                    <button type="submit" class="w-full rounded-2xl bg-brand-600 px-5 py-3 text-sm font-black text-white transition hover:bg-brand-700">
                        Simpan Update Pesanan
                    </button>
                </form>
            </div>

            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">Rekap Singkat</h2>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3 rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800/60">
                        <span class="text-gray-500 dark:text-gray-400">Total Bayar</span>
                        <span class="font-black text-gray-900 dark:text-white">{{ $order->total_bayar_format }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800/60">
                        <span class="text-gray-500 dark:text-gray-400">Dibuat</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $order->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800/60">
                        <span class="text-gray-500 dark:text-gray-400">Lunas Pada</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $order->lunas_pada?->format('d M Y H:i') ?: '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-2xl bg-gray-50 px-4 py-3 dark:bg-gray-800/60">
                        <span class="text-gray-500 dark:text-gray-400">Diverifikasi Oleh</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $order->verifier?->name ?: '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection