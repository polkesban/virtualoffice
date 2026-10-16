@extends('admin.layouts.admin')
@section('title', 'Layanan & Produk Unit Bisnis')

@section('content')
<div class="p-6 max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">🏪 Layanan & Produk Unit Bisnis</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola katalog produk, dashboard pemesanan, verifikasi pembayaran, dan laporan keuangan.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('layanan-bisnis.order') }}" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50 px-4 py-2 text-sm font-bold text-brand-700 transition-colors hover:bg-brand-100 dark:border-brand-800 dark:bg-brand-950/30 dark:text-brand-300">
                Buka Form Pemesanan Publik
            </a>
            <a href="{{ route('admin.layanan-bisnis.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
                + Tambah Layanan / Produk
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded-xl bg-green-50 border border-green-200 p-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4 mb-8">
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Total Produk / Layanan</p>
            <p class="mt-2 text-3xl font-black text-gray-900 dark:text-white">{{ $layanan->count() }}</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Total Pesanan</p>
            <p class="mt-2 text-3xl font-black text-gray-900 dark:text-white">{{ $orderStats['total_orders'] }}</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Pembayaran Lunas</p>
            <p class="mt-2 text-3xl font-black text-green-700 dark:text-green-300">Rp {{ number_format($orderStats['paid_total'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Belum Lunas</p>
            <p class="mt-2 text-3xl font-black text-red-700 dark:text-red-300">Rp {{ number_format($orderStats['unpaid_total'], 0, ',', '.') }}</p>
        </div>
    </div>

    <section id="catalog" class="mb-10">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">Katalog Layanan & Produk</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Atur katalog, varian, QRIS, dan media layanan bisnis.</p>
            </div>
        </div>

        <form method="GET" class="flex flex-wrap gap-3 mb-5">
            <input type="hidden" name="status_pembayaran" value="{{ request('status_pembayaran') }}">
            <input type="hidden" name="status_pesanan" value="{{ request('status_pesanan') }}">
            <input type="hidden" name="kategori_pesanan" value="{{ request('kategori_pesanan') }}">
            <select name="kategori_item" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                <option value="">Semua Kategori</option>
                @foreach(\App\Models\LayananBisnis::$kategoriLabels as $k => $v)
                <option value="{{ $k }}" @selected(request('kategori_item')===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium">Filter Katalog</button>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($layanan as $item)
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden {{ $item->aktif ? '' : 'opacity-60' }}">
                @if($item->gambar)
                <img src="{{ \App\Support\MediaStorage::url($item->gambar, 'bisnis') }}" alt="{{ $item->nama }}" class="w-full h-40 object-cover">
                @else
                <div class="w-full h-32 bg-gradient-to-br from-brand-50 to-brand-100 dark:from-brand-900/30 dark:to-brand-800/20 flex items-center justify-center">
                    <span class="text-4xl">🛍️</span>
                </div>
                @endif
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2 mb-1">
                        <h3 class="font-bold text-gray-900 dark:text-white text-sm">{{ $item->nama }}</h3>
                        <div class="flex items-center gap-1 shrink-0">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $item->status_stok_badge_class }}">
                                ● {{ $item->status_stok_label }}
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $item->aktif ? 'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-800' }}">
                                {{ $item->aktif ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mb-2">{{ \App\Models\LayananBisnis::$kategoriLabels[$item->kategori] ?? $item->kategori }}</p>
                    <p class="text-sm font-bold text-brand-600 dark:text-brand-400 mb-2">{{ $item->harga_format }}</p>
                    <div class="mb-3 flex flex-wrap gap-1.5 text-[11px]">
                        @if($item->stok !== null)
                        <span class="rounded-full bg-blue-50 px-2 py-0.5 font-bold text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">
                            Stok: {{ $item->stok }}
                        </span>
                        @endif
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            {{ count($item->aktif_varian_opsi) }} varian
                        </span>
                        @if($item->qris_link)
                        <span class="rounded-full bg-brand-50 px-2 py-0.5 font-bold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">QRIS aktif</span>
                        @endif
                    </div>
                    @if($item->deskripsi)
                    <p class="text-xs text-gray-500 mb-3 line-clamp-2">{{ $item->deskripsi }}</p>
                    @endif
                    <div class="flex items-center gap-1.5 pt-2 border-t border-gray-100 dark:border-gray-800">
                        <a href="{{ route('admin.layanan-bisnis.edit', $item) }}" class="flex-1 text-center px-2.5 py-1.5 rounded-lg text-xs font-bold bg-gray-100 dark:bg-gray-700 hover:bg-brand-50 hover:text-brand-600 transition-colors">Edit</a>
                        <form method="POST" action="{{ route('admin.layanan-bisnis.toggle-tersedia', $item) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs font-bold {{ $item->is_tersedia ? 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/30 dark:text-rose-300' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300' }} transition-colors" title="{{ $item->is_tersedia ? 'Ubah status ke Stok Habis' : 'Ubah status ke Ready' }}">
                                {{ $item->is_tersedia ? 'Set Habis' : 'Set Ready' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.layanan-bisnis.toggle', $item) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-yellow-50 text-yellow-700 hover:bg-yellow-100 dark:bg-yellow-950/30 dark:text-yellow-300 transition-colors">
                                {{ $item->aktif ? 'Nonaktif' : 'Aktifkan' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.layanan-bisnis.destroy', $item) }}" onsubmit="return confirm('Hapus layanan ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-950/30 dark:text-red-300 transition-colors">Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="md:col-span-3 text-center py-16 text-gray-400">
                Belum ada layanan atau produk. Tambahkan katalog pertama untuk Unit Bisnis.
            </div>
            @endforelse
        </div>
    </section>

    <section id="orders" class="mb-10">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">Dashboard Pemesanan / Pembelian</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Tabulasi pemesan, item yang dibeli, bukti pembayaran, dan status pemenuhan pesanan.</p>
            </div>
        </div>

        <form method="GET" class="mb-5 flex flex-wrap gap-3">
            <input type="hidden" name="kategori_item" value="{{ request('kategori_item') }}">
            <select name="kategori_pesanan" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                <option value="">Semua Jenis Pesanan</option>
                @foreach(\App\Models\LayananBisnis::$kategoriLabels as $k => $v)
                <option value="{{ $k }}" @selected(request('kategori_pesanan')===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <select name="status_pembayaran" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                <option value="">Semua Status Bayar</option>
                @foreach(\App\Models\LayananBisnisOrder::$paymentStatusLabels as $k => $v)
                <option value="{{ $k }}" @selected(request('status_pembayaran')===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <select name="status_pesanan" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                <option value="">Semua Status Pesanan</option>
                @foreach(['baru'=>'Pesanan Baru','diproses'=>'Diproses','dikirim'=>'Dikirim','menunggu_verifikasi'=>'Menunggu Verifikasi','peserta_aktif'=>'Peserta Aktif','disetujui'=>'Disetujui','dikembalikan'=>'Dikembalikan','selesai'=>'Selesai'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status_pesanan')===$value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium">Filter Pesanan</button>
        </form>

        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wide text-gray-500 dark:bg-gray-800/60">
                        <tr>
                            <th class="px-4 py-3 text-left">Pemesan</th>
                            <th class="px-4 py-3 text-left">Produk / Item</th>
                            <th class="px-4 py-3 text-left">Status Pesanan</th>
                            <th class="px-4 py-3 text-left">Pembayaran</th>
                            <th class="px-4 py-3 text-left">Total</th>
                            <th class="px-4 py-3 text-left">Bukti</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($orders as $order)
                        <tr class="align-top hover:bg-gray-50 dark:hover:bg-gray-800/30">
                            <td class="px-4 py-4">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $order->nama_pemesan }}</p>
                                <p class="text-xs text-gray-400">{{ $order->kode_pesanan }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $order->nomor_wa }}</p>
                                <p class="text-xs text-gray-500">{{ $order->email ?: 'Tanpa email' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <div class="space-y-2">
                                    @foreach($order->items->take(3) as $item)
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $item->nama_item }}</p>
                                        <p class="text-xs text-gray-400">{{ $item->jenis_tipe_ukuran ?: 'Tanpa varian' }} · {{ $item->jumlah }} x {{ $item->harga_satuan_format }}</p>
                                    </div>
                                    @endforeach
                                    @if($order->items->count() > 3)
                                    <p class="text-xs font-bold text-brand-700 dark:text-brand-300">+{{ $order->items->count() - 3 }} item lainnya</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $order->status_pesanan_badge_class }}">{{ $order->status_pesanan_label }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $order->status_pembayaran_badge_class }}">{{ $order->status_pembayaran_label }}</span>
                                <p class="mt-2 text-xs text-gray-400">{{ $order->lunas_pada?->format('d M Y H:i') ?: 'Belum diverifikasi' }}</p>
                            </td>
                            <td class="px-4 py-4 font-black text-brand-700 dark:text-brand-300">{{ $order->total_bayar_format }}</td>
                            <td class="px-4 py-4 text-xs">
                                @if($order->bukti_pembayaran_url)
                                <a href="{{ $order->bukti_pembayaran_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 font-bold text-brand-700 hover:text-brand-900 underline dark:text-brand-300">
                                    <span>🔍</span> Lihat Bukti
                                </a>
                                @else
                                <span class="text-gray-400 italic">Belum ada</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="inline-flex items-center gap-2 justify-end">
                                    @if($order->status_pembayaran !== 'lunas')
                                    <form action="{{ route('admin.layanan-bisnis.orders.quick-verify', $order) }}" method="POST" class="inline" onsubmit="return confirm('Verifikasi pembayaran pesanan {{ $order->kode_pesanan }} ({{ $order->total_bayar_format }}) sebagai LUNAS?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-2 text-xs font-bold transition shadow-sm">
                                            ✓ Set Lunas
                                        </button>
                                    </form>
                                    @endif
                                    <a href="{{ route('admin.layanan-bisnis.orders.show', $order) }}"
                                       class="inline-flex rounded-xl bg-gray-100 px-3 py-2 text-xs font-bold text-gray-700 transition hover:bg-brand-50 hover:text-brand-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-brand-900/30 dark:hover:text-brand-300">
                                        Detail
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-400">Belum ada pesanan yang masuk.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section id="finance">
        <div class="mb-4">
            <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">Laporan Keuangan</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Rekap bulanan nilai pesanan, pembayaran lunas, dan piutang yang masih outstanding.</p>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wide text-gray-500 dark:bg-gray-800/60">
                        <tr>
                            <th class="px-4 py-3 text-left">Periode</th>
                            <th class="px-4 py-3 text-left">Jumlah Pesanan</th>
                            <th class="px-4 py-3 text-left">Total Tagihan</th>
                            <th class="px-4 py-3 text-left">Pembayaran Lunas</th>
                            <th class="px-4 py-3 text-left">Belum Lunas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($monthlyReport as $row)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ \Carbon\Carbon::createFromFormat('Y-m', $row->periode)->translatedFormat('F Y') }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $row->total_pesanan }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">Rp {{ number_format((float) $row->total_tagihan, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-green-700 dark:text-green-300">Rp {{ number_format((float) $row->total_lunas, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-red-700 dark:text-red-300">Rp {{ number_format((float) $row->total_piutang, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-gray-400">Belum ada data transaksi untuk dilaporkan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
@endsection
