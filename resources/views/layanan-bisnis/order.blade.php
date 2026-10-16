@extends('layouts.app')

@section('title', 'Pemesanan Layanan & Produk Bisnis')
@section('description', 'Form pemesanan layanan, produk, catering, pelatihan, dan sewa dari Unit Bisnis Poltekkes Bandung.')

@php
    $produkData = $layananList->map(fn ($item) => [
        'id' => $item->id,
        'nama' => $item->nama,
        'slug' => $item->slug,
        'kategori' => $item->kategori,
        'kategori_label' => \App\Models\LayananBisnis::$kategoriLabels[$item->kategori] ?? $item->kategori,
        'harga' => (float) ($item->harga ?? 0),
        'harga_format' => $item->harga_format,
        'satuan_harga' => $item->satuan_harga,
        'deskripsi' => $item->deskripsi,
        'gambar' => $item->gambar ? \App\Support\MediaStorage::url($item->gambar, 'bisnis') : null,
        'is_tersedia' => (bool) $item->is_tersedia,
        'stok' => $item->stok !== null ? (int) $item->stok : null,
        'is_available' => (bool) $item->isAvailable(),
        'status_stok_label' => $item->status_stok_label,
        'opsi_varian' => $item->aktif_varian_opsi,
        'qris_link' => $item->qris_link ? (
            str_contains($item->qris_link, 'drive.google.com')
                ? (\App\Support\GoogleDriveEmbed::directImageUrl($item->qris_link) ?: $item->qris_link)
                : (str_starts_with($item->qris_link, 'http') || str_starts_with($item->qris_link, '/') ? $item->qris_link : '/' . $item->qris_link)
        ) : null,
        'qris_label' => $item->qris_label,
    ])->values();
    $produkData = \App\Support\PublicLocale::translateData($produkData->toArray());
    $orderInteractiveLabels = \App\Support\PublicLocale::translateData([
        'openPayment' => 'Buka QRIS Pembayaran',
    ]);
    $orderLocaleTag = \App\Support\PublicLocale::localeTag();

    $orderFormConfig = [
        'products' => $produkData,
        'initialItems' => $defaultItems,
        'labels' => $orderInteractiveLabels,
        'localeTag' => $orderLocaleTag,
    ];
@endphp

@section('content')
<section class="bg-gradient-to-br from-brand-50 via-white to-emerald-50 py-12 dark:from-gray-950 dark:via-gray-950 dark:to-gray-900">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 max-w-3xl">
            <span class="inline-flex rounded-full bg-brand-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-brand-700 dark:bg-brand-900/40 dark:text-brand-300">
                Unit Bisnis
            </span>
            <h1 class="mt-4 text-3xl font-black tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                Form Pemesanan Layanan & Produk
            </h1>
            <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-300 sm:text-base">
                Isi data pemesan, pilih item yang dibutuhkan, lalu unggah bukti pembayaran. Status pesanan dan verifikasi pembayaran akan dikelola admin Unit Bisnis.
            </p>
        </div>

        @if(session('success') && $latestOrder)
        <div class="mb-8 rounded-3xl border border-green-200 bg-green-50 p-6 shadow-sm dark:border-green-900/40 dark:bg-green-950/30">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-green-700 dark:text-green-300">Pesanan Terkirim</p>
                    <h2 class="mt-2 text-xl font-extrabold text-gray-900 dark:text-white">Kode Pesanan: {{ $latestOrder->kode_pesanan }}</h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Simpan kode ini untuk referensi saat konfirmasi pembayaran atau follow-up melalui admin Unit Bisnis.</p>
                </div>
                <div class="grid min-w-[260px] grid-cols-2 gap-3">
                    <div class="rounded-2xl bg-white/80 p-4 dark:bg-black/20">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Total Bayar</p>
                        <p class="mt-1 text-lg font-black text-gray-900 dark:text-white">{{ $latestOrder->total_bayar_format }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/80 p-4 dark:bg-black/20">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Status Pembayaran</p>
                        <p class="mt-1 text-lg font-black {{ $latestOrder->status_pembayaran === 'lunas' ? 'text-green-600 dark:text-green-400' : (!empty($latestOrder->bukti_pembayaran) ? 'text-amber-600 dark:text-amber-400' : 'text-red-500') }}">{{ $latestOrder->status_pembayaran_label }}</p>
                        @if($latestOrder->status_pembayaran === 'lunas')
                            <p class="text-[11px] text-green-700 dark:text-green-300 mt-1 font-medium">✓ Pembayaran telah diverifikasi lunas.</p>
                        @elseif(!empty($latestOrder->bukti_pembayaran))
                            <p class="text-[11px] text-amber-700 dark:text-amber-300 mt-1 font-medium">⏳ Bukti terunggah, menunggu verifikasi admin.</p>
                        @else
                            <p class="text-[11px] text-gray-500 mt-1">Silakan lakukan transfer & unggah bukti.</p>
                        @endif
                    </div>
                </div>
            </div>

            @if($latestOrder->qris_link)
            <div class="mt-5 rounded-2xl border border-dashed border-green-300 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:text-green-200">
                Link QRIS: <a href="{{ $latestOrder->qris_link }}" target="_blank" rel="noopener noreferrer" class="font-bold underline">{{ $latestOrder->qris_label ?: 'Buka QRIS Pembayaran' }}</a>
            </div>
            @endif
        </div>
        @endif

        <div x-data="businessOrderForm(@js($orderFormConfig))"
            class="grid grid-cols-1 gap-8 lg:grid-cols-12">

            <div class="lg:col-span-8">
                <form action="{{ route('layanan-bisnis.order.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    @if($errors->any())
                    <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/20 dark:text-red-300">
                        <ul class="list-disc space-y-1 pl-5">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">Data Pemesan</h2>
                        <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Nama Lengkap</label>
                                <input type="text" name="nama_pemesan" value="{{ old('nama_pemesan') }}" required
                                       class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:bg-white dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Nomor WhatsApp</label>
                                <input type="text" name="nomor_wa" value="{{ old('nomor_wa') }}" required
                                       placeholder="08xxxxxxxxxx"
                                       class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:bg-white dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Email</label>
                                <input type="email" name="email" value="{{ old('email') }}"
                                       class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:bg-white dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Alamat / Lokasi Pengiriman / Catatan Lokasi</label>
                                <textarea name="alamat" rows="3"
                                          class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:bg-white dark:border-gray-700 dark:bg-gray-800 dark:text-white">{{ old('alamat') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-6">
                        <div>
                            <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">Item Pesanan</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pilih produk atau layanan utama, lalu tentukan varian/menu dan jumlah yang dipesan.</p>
                        </div>

                        {{-- 1. Pilih Produk / Layanan Utama dengan Thumbnail Kartu --}}
                        <div class="rounded-3xl bg-brand-50/40 dark:bg-brand-950/20 border border-brand-200/80 dark:border-brand-900/50 p-4 sm:p-6 space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div>
                                    <label class="text-xs font-bold uppercase tracking-wider text-brand-900 dark:text-brand-300 flex items-center gap-1.5">
                                        <span>🏪</span>
                                        <span>Pilih Produk / Layanan Utama</span>
                                    </label>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Klik salah satu produk di bawah untuk melihat pilihan menu & varian:</p>
                                </div>
                                <template x-if="selectedProduct">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-brand-600 text-white shadow-xs">
                                        <span>✓</span>
                                        <span x-text="`Dipilih: ${selectedProduct.nama}`"></span>
                                    </span>
                                </template>
                            </div>

                            {{-- Thumbnail Cards Grid --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                                <template x-for="product in products" :key="product.id">
                                    <div @click="product.is_available ? selectMainProduct(product.id) : null"
                                         :class="{
                                             'ring-2 ring-brand-500 border-brand-500 bg-white dark:bg-gray-800 shadow-md transform -translate-y-0.5': String(selectedProductId) === String(product.id),
                                             'border-gray-200 dark:border-gray-700/80 bg-white/90 dark:bg-gray-800/80 hover:border-brand-300 dark:hover:border-brand-700 hover:shadow-sm': String(selectedProductId) !== String(product.id) && product.is_available,
                                             'opacity-60 cursor-not-allowed bg-gray-100 dark:bg-gray-900 border-gray-200 dark:border-gray-800': !product.is_available,
                                             'cursor-pointer': product.is_available
                                         }"
                                         class="relative flex flex-col rounded-2xl border p-3 transition-all duration-200 text-left group">

                                        {{-- Header: Kategori & Status Badge --}}
                                        <div class="flex items-center justify-between gap-2 mb-2">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-gray-100 dark:bg-gray-700/70 text-gray-600 dark:text-gray-300 truncate"
                                                  x-text="product.kategori_label"></span>

                                            <template x-if="String(selectedProductId) === String(product.id)">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-brand-600 text-white shadow-xs shrink-0">
                                                    <span>✓</span>
                                                    <span>Terpilih</span>
                                                </span>
                                            </template>
                                            <template x-if="String(selectedProductId) !== String(product.id)">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0"
                                                      :class="product.is_available ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'"
                                                      x-text="product.is_available ? (product.stok !== null ? 'Sisa ' + product.stok : '● Ready') : '● Stok Habis'"></span>
                                            </template>
                                        </div>

                                        {{-- Product Thumbnail Image --}}
                                        <div class="relative w-full aspect-[16/10] rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700/50 mb-2.5 border border-gray-100 dark:border-gray-700/60 shadow-xs">
                                            <template x-if="product.gambar">
                                                <img :src="resolveImageUrl(product.gambar)"
                                                     :alt="product.nama"
                                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                            </template>
                                            <template x-if="!product.gambar">
                                                <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 bg-gradient-to-br from-brand-50 to-emerald-50 dark:from-gray-800 dark:to-gray-900">
                                                    <span class="text-3xl">🏪</span>
                                                    <span class="text-[10px] font-bold mt-1 text-gray-400">Unit Bisnis</span>
                                                </div>
                                            </template>

                                            {{-- Out of stock overlay --}}
                                            <template x-if="!product.is_available">
                                                <div class="absolute inset-0 bg-black/60 backdrop-blur-[1px] flex items-center justify-center">
                                                    <span class="px-2.5 py-1 rounded-lg bg-rose-600 text-white text-xs font-black shadow-sm">
                                                        Stok Habis
                                                    </span>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- Title & Info --}}
                                        <div class="flex-1 flex flex-col justify-between">
                                            <div>
                                                <h3 class="font-extrabold text-sm text-gray-900 dark:text-white line-clamp-1 group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors"
                                                    x-text="product.nama"></h3>
                                                <template x-if="product.deskripsi">
                                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 line-clamp-2 mt-0.5"
                                                       x-text="product.deskripsi"></p>
                                                </template>
                                            </div>

                                            <div class="mt-2.5 pt-2 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
                                                <div>
                                                    <span class="text-xs font-black text-brand-700 dark:text-brand-300"
                                                          x-text="formatCurrency(product.harga)"></span>
                                                    <span class="text-[10px] text-gray-400"
                                                          x-text="'/ ' + (product.satuan_harga || 'item')"></span>
                                                </div>
                                                <template x-if="product.opsi_varian && product.opsi_varian.length">
                                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 font-semibold bg-gray-100 dark:bg-gray-700/80 px-2 py-0.5 rounded-full">
                                                        <span x-text="`${product.opsi_varian.length} menu`"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Selected Product Details Bar --}}
                            <template x-if="selectedProduct">
                                <div class="pt-3 border-t border-brand-200/60 dark:border-brand-900/40">
                                    <template x-if="!selectedProduct.is_available">
                                        <div class="mb-3 p-3 rounded-xl bg-rose-50 border border-rose-200 dark:bg-rose-950/40 dark:border-rose-900/50 text-xs font-bold text-rose-700 dark:text-rose-300 flex items-center gap-2">
                                            <span>⚠️</span>
                                            <span>Stok produk ini sedang habis atau tidak tersedia untuk pemesanan saat ini.</span>
                                        </div>
                                    </template>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-gray-600 dark:text-gray-300">
                                        <span>Produk Terpilih: <strong class="text-gray-900 dark:text-white" x-text="selectedProduct.nama"></strong></span>
                                        <span>•</span>
                                        <span>Harga Dasar: <strong class="text-gray-900 dark:text-white" x-text="formatCurrency(selectedProduct.harga)"></strong> <span x-text="'/ ' + (selectedProduct.satuan_harga || 'item')"></span></span>
                                        <span>•</span>
                                        <span>Ketersediaan: 
                                            <strong :class="selectedProduct.is_available ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'" 
                                                    x-text="selectedProduct.status_stok_label || (selectedProduct.is_available ? 'Ready' : 'Stok Habis')"></strong>
                                        </span>
                                        <span>•</span>
                                        <span>Varian: <strong class="text-gray-900 dark:text-white" x-text="selectedProduct.opsi_varian && selectedProduct.opsi_varian.length ? selectedProduct.opsi_varian.length + ' pilihan' : 'Standar'"></strong></span>
                                        <template x-if="selectedProduct.qris_link">
                                            <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-semibold">
                                                <span>✓</span>
                                                <span>QRIS Siap</span>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- 2. Daftar Menu & Varian Produk (Hanya Tampil Jika Produk Utama Sudah Dipilih) --}}
                        <template x-if="selectedProduct">
                            <div class="space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div>
                                        <h3 class="text-sm sm:text-base font-extrabold text-gray-900 dark:text-white">Daftar Pilihan Menu & Varian</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Silakan isi kuantiti (jumlah) untuk menu/varian yang ingin dipesan.</p>
                                    </div>
                                    <template x-if="items.length">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-brand-50 text-brand-700 border border-brand-200 dark:bg-brand-950/40 dark:text-brand-300 dark:border-brand-800">
                                            <span x-text="`${items.length} pilihan menu`"></span>
                                        </span>
                                    </template>
                                </div>

                                <div class="space-y-3">
                                    <template x-for="(item, index) in items" :key="index">
                                        <div class="rounded-2xl border p-4 transition-all duration-200"
                                             :class="!item.is_available 
                                                 ? 'border-gray-200 bg-gray-100/70 dark:border-gray-800 dark:bg-gray-900/60 opacity-75' 
                                                 : (Number(item.jumlah) > 0 
                                                     ? 'border-brand-500 bg-brand-50/30 dark:border-brand-500/60 dark:bg-brand-950/30 shadow-sm ring-1 ring-brand-500/20' 
                                                     : 'border-gray-200 bg-gray-50/50 dark:border-gray-700/80 dark:bg-gray-800/40 hover:border-gray-300 dark:hover:border-gray-600')">
                                            
                                            {{-- Hidden inputs for backend controller --}}
                                            <input type="hidden" :name="`items[${index}][layanan_bisnis_id]`" :value="item.layanan_bisnis_id">
                                            <input type="hidden" :name="`items[${index}][jenis_tipe_ukuran]`" :value="item.jenis_tipe_ukuran">

                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                                {{-- Info Menu / Varian --}}
                                                <div class="flex items-center gap-3.5 min-w-0">
                                                    {{-- Thumbnail / Icon --}}
                                                    <div class="relative w-14 h-14 sm:w-16 sm:h-16 rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shrink-0 flex items-center justify-center shadow-xs">
                                                        <template x-if="item.gambar">
                                                            <img :src="resolveImageUrl(item.gambar)" class="w-full h-full object-cover" :alt="item.jenis_tipe_ukuran">
                                                        </template>
                                                        <template x-if="!item.gambar">
                                                            <div class="flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                                                                <span class="text-2xl select-none">🍱</span>
                                                            </div>
                                                        </template>
                                                    </div>

                                                    {{-- Nama & Harga --}}
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <h4 class="font-bold text-gray-900 dark:text-white text-sm sm:text-base leading-snug"
                                                                x-text="item.jenis_tipe_ukuran || selectedProduct?.nama"></h4>
                                                            <template x-if="!item.is_available">
                                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900/50">
                                                                    ● Stok Habis
                                                                </span>
                                                            </template>
                                                            <template x-if="item.is_available && item.stok !== null">
                                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-900/50"
                                                                      x-text="`Sisa ${item.stok}`">
                                                                </span>
                                                            </template>
                                                            <template x-if="item.is_available && Number(item.jumlah) > 0">
                                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-brand-100 text-brand-800 dark:bg-brand-900/60 dark:text-brand-300">
                                                                    <span>✓</span>
                                                                    <span x-text="`${item.jumlah} dipesan`"></span>
                                                                </span>
                                                            </template>
                                                        </div>
                                                        <div class="mt-1 flex items-baseline gap-1.5 text-xs sm:text-sm">
                                                            <span class="font-black text-brand-600 dark:text-brand-400" x-text="formatCurrency(item.harga)"></span>
                                                            <span class="text-xs text-gray-500 dark:text-gray-400" x-text="'/ ' + (item.satuan_harga || 'item')"></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Stepper Qty & Subtotal --}}
                                                <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center gap-2 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-gray-200/60 dark:border-gray-700/60">
                                                    <template x-if="!item.is_available">
                                                        <div class="px-3 py-1.5 rounded-xl bg-gray-200/80 dark:bg-gray-800 text-xs font-bold text-gray-500 select-none">
                                                            Stok Habis
                                                        </div>
                                                    </template>
                                                    <template x-if="item.is_available">
                                                        <div class="flex items-center">
                                                            <button type="button" @click="item.jumlah = Math.max(0, Number(item.jumlah || 0) - 1)"
                                                                    title="Kurangi kuantiti"
                                                                    class="w-9 h-9 sm:w-10 sm:h-10 flex items-center justify-center rounded-l-2xl border border-r-0 border-gray-200 bg-white hover:bg-gray-100 active:bg-gray-200 text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-200 font-black text-base select-none transition cursor-pointer">
                                                                −
                                                            </button>
                                                            <input type="number" min="0" :max="item.stok !== null ? item.stok : 999"
                                                                   :name="`items[${index}][jumlah]`"
                                                                   x-model.number="item.jumlah"
                                                                   @input="if(item.stok !== null && item.jumlah > item.stok) item.jumlah = item.stok"
                                                                   class="w-14 sm:w-16 h-9 sm:h-10 text-center border-y border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900 px-1 text-sm sm:text-base font-black text-gray-900 dark:text-white outline-none focus:ring-1 focus:ring-brand-500">
                                                            <button type="button" @click="item.jumlah = Math.min(item.stok !== null ? item.stok : 999, Number(item.jumlah || 0) + 1)"
                                                                    :disabled="item.stok !== null && Number(item.jumlah || 0) >= item.stok"
                                                                    title="Tambah kuantiti"
                                                                    class="w-9 h-9 sm:w-10 sm:h-10 flex items-center justify-center rounded-r-2xl border border-l-0 border-gray-200 bg-white hover:bg-gray-100 active:bg-gray-200 disabled:opacity-40 disabled:cursor-not-allowed text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-200 font-black text-base select-none transition cursor-pointer">
                                                                +
                                                            </button>
                                                        </div>
                                                    </template>
                                                    <div class="text-xs text-right">
                                                        <span class="text-gray-400">Subtotal: </span>
                                                        <span class="font-extrabold"
                                                              :class="Number(item.jumlah) > 0 ? 'text-brand-700 dark:text-brand-300' : 'text-gray-400 dark:text-gray-500'"
                                                              x-text="formatCurrency(itemSubtotal(item))"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                {{-- Bottom Summary of Items --}}
                                <div class="flex flex-wrap items-center justify-between gap-3 p-4 rounded-2xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200/80 dark:border-gray-700 text-xs sm:text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-600 dark:text-gray-300 font-medium">Total Item Dipilih:</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                                              :class="totalQuantity() > 0 ? 'bg-brand-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
                                              x-text="`${totalQuantity()} porsi / item`"></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-500 dark:text-gray-400 font-medium">Subtotal Produk:</span>
                                        <span class="text-base sm:text-lg font-black text-brand-700 dark:text-brand-300" x-text="formatCurrency(grandTotal())"></span>
                                    </div>
                                </div>

                                <template x-if="totalQuantity() === 0">
                                    <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-xs text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300 flex items-center gap-2.5 shadow-xs">
                                        <span class="text-lg">⚠️</span>
                                        <span>Belum ada kuantiti yang diisi. Silakan tentukan kuantiti (minimal 1) pada salah satu pilihan menu di atas.</span>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="!selectedProduct">
                            <div class="rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-800 p-8 text-center bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300 text-2xl mb-3">
                                    📋
                                </div>
                                <h4 class="font-bold text-gray-800 dark:text-gray-200 text-base">Pilih Produk atau Layanan Utama</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                                    Silakan tentukan produk atau layanan pada pilihan di atas terlebih dahulu untuk menampilkan daftar menu, varian, dan harganya.
                                </p>
                            </div>
                        </template>
                    </div>

                    <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">Pembayaran</h2>
                        <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-5">
                            <div class="lg:col-span-3 space-y-4">
                                <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-800/60">
                                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-gray-400">Jumlah Bayar</p>
                                    <p class="mt-2 text-3xl font-black text-gray-900 dark:text-white" x-text="formatCurrency(grandTotal())"></p>
                                </div>

                                <template x-if="!selectedProduct">
                                    <div class="rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 px-4 py-4 text-xs text-gray-500 dark:text-gray-400">
                                        Pilih produk / layanan terlebih dahulu untuk melihat informasi dan QRIS pembayaran.
                                    </div>
                                </template>

                                <div class="rounded-2xl border border-dashed border-emerald-300 bg-emerald-50/50 p-4 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/20 dark:text-emerald-200" x-show="selectedProduct && currentPaymentLink()">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="font-bold text-emerald-800 dark:text-emerald-300">QRIS Pembayaran Resmi</p>
                                        <a :href="currentPaymentLink()?.link" target="_blank" download class="text-xs font-semibold text-brand-600 hover:underline">Unduh QRIS ↗</a>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3" x-text="currentPaymentLink()?.label || 'KOPERASI PEGAWAI HUSADA SEJAHTERA'"></p>
                                    <template x-if="isImageLink(currentPaymentLink()?.link)">
                                        <div class="flex flex-col items-center justify-center p-3 bg-white dark:bg-gray-900 rounded-2xl border border-emerald-200 dark:border-emerald-800 shadow-sm max-w-[260px] mx-auto my-2">
                                            <a :href="currentPaymentLink()?.link" target="_blank" class="block cursor-zoom-in group" title="Klik untuk membuka ukuran penuh">
                                                <img :src="currentPaymentLink()?.link" alt="QRIS" class="max-h-72 w-auto object-contain rounded-lg shadow-sm group-hover:scale-[1.02] transition-transform">
                                            </a>
                                            <span class="mt-2 text-[10px] text-gray-400 text-center">Scan QRIS melalui GoPay, OVO, Dana, BCA, Mandiri, BRI, dll</span>
                                        </div>
                                    </template>
                                    <template x-if="!isImageLink(currentPaymentLink()?.link)">
                                        <a :href="currentPaymentLink()?.link" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex font-semibold underline" x-text="currentPaymentLink()?.label || labels.openPayment"></a>
                                    </template>
                                </div>

                                <div class="rounded-2xl border border-dashed border-amber-300 px-4 py-4 text-sm text-amber-800 dark:border-amber-800 dark:text-amber-200" x-show="selectedProduct && !currentPaymentLink()">
                                    QRIS belum tersedia untuk item yang dipilih. Admin akan menghubungi Anda untuk instruksi pembayaran.
                                </div>
                            </div>

                            <div class="lg:col-span-2 space-y-4">
                                <div>
                                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Upload Bukti Pembayaran</label>
                                    <input type="file" name="bukti_pembayaran" accept=".jpg,.jpeg,.png,.webp,.pdf"
                                           class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 outline-none transition focus:border-brand-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                    <p class="mt-1 text-xs text-gray-400">Format: JPG, PNG, WebP, atau PDF. Maksimal 5 MB.</p>
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Catatan Tambahan</label>
                                    <textarea name="catatan_pemesan" rows="5"
                                              class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-brand-400 focus:bg-white dark:border-gray-700 dark:bg-gray-800 dark:text-white">{{ old('catatan_pemesan') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Dengan mengirim pesanan, Anda menyetujui verifikasi manual oleh admin Unit Bisnis.</p>
                        <button type="submit"
                                :disabled="totalQuantity() === 0"
                                :class="totalQuantity() === 0 ? 'opacity-50 cursor-not-allowed bg-gray-400 dark:bg-gray-700' : 'bg-brand-600 hover:bg-brand-700 cursor-pointer shadow-md shadow-brand-500/20'"
                                class="inline-flex items-center gap-2 rounded-full px-6 py-3 text-sm font-black text-white transition">
                            Kirim Pesanan
                        </button>
                    </div>
                </form>
            </div>

            <aside class="lg:col-span-4 space-y-4">
                <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">Alur Pemesanan</h2>
                    <ol class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                        <li>1. Pilih layanan atau produk yang ingin dipesan.</li>
                        <li>2. Tentukan kuantiti menu/varian yang dibutuhkan.</li>
                        <li>3. Lakukan pembayaran sesuai total yang muncul.</li>
                        <li>4. Unggah bukti pembayaran dan kirim pesanan.</li>
                        <li>5. Admin akan memverifikasi dan memperbarui status pesanan Anda.</li>
                    </ol>
                </div>

                <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">Katalog Aktif</h2>
                    <div class="mt-4 space-y-3">
                        @foreach($layananList as $item)
                        <div class="flex items-center gap-3 rounded-2xl bg-gray-50 p-3 dark:bg-gray-800/60 cursor-pointer hover:bg-brand-50/50 dark:hover:bg-brand-950/20 transition border border-transparent hover:border-brand-200"
                             @click="selectMainProduct('{{ $item->id }}')">
                            @if($item->gambar)
                            <img src="{{ \App\Support\MediaStorage::url($item->gambar, 'bisnis') }}" alt="{{ $item->nama }}" class="w-12 h-12 rounded-xl object-cover shrink-0 border border-gray-200 dark:border-gray-700">
                            @else
                            <div class="w-12 h-12 rounded-xl bg-brand-100 dark:bg-brand-900/40 text-brand-700 dark:text-brand-300 flex items-center justify-center text-xl shrink-0">
                                🏪
                            </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-1">
                                    <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $item->nama }}</p>
                                    <span class="text-[10px] font-black text-brand-700 dark:text-brand-300 shrink-0">{{ $item->harga_format }}</span>
                                </div>
                                <p class="text-[10px] text-gray-400">{{ \App\Models\LayananBisnis::$kategoriLabels[$item->kategori] ?? $item->kategori }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
function businessOrderForm(config) {
    return {
        products: config.products || [],
        labels: config.labels || {},
        localeTag: config.localeTag || document.documentElement.lang || 'id-ID',
        selectedProductId: '',
        items: [],

        init() {
            const initialList = config.initialItems || [];
            let firstId = (initialList.length > 0 && initialList[0]?.layanan_bisnis_id)
                ? String(initialList[0].layanan_bisnis_id) 
                : '';

            if (!firstId && this.products.length > 0) {
                const avail = this.products.find(p => p.is_available);
                firstId = avail ? String(avail.id) : String(this.products[0].id);
            }
            
            this.selectedProductId = firstId;
            if (firstId) {
                this.buildVariantList(initialList);
            } else {
                this.items = [];
            }
        },

        selectMainProduct(productId) {
            if (String(this.selectedProductId) === String(productId)) return;
            this.selectedProductId = String(productId);
            this.onMainProductChange();
        },

        get selectedProduct() {
            return this.products.find((p) => String(p.id) === String(this.selectedProductId)) || null;
        },

        onMainProductChange() {
            if (this.selectedProductId) {
                this.buildVariantList([]);
            } else {
                this.items = [];
            }
        },

        buildVariantList(initialList = []) {
            const product = this.selectedProduct;
            if (!product) {
                this.items = [];
                return;
            }

            const variants = product.opsi_varian || [];
            if (variants.length > 0) {
                this.items = variants.map((v, idx) => {
                    const isAvail = Boolean(product.is_available && (v.is_available ?? true));
                    const maxStok = (v.stok !== null && v.stok !== undefined && v.stok !== '') ? Number(v.stok) : (product.stok !== null && product.stok !== undefined ? Number(product.stok) : 999);
                    const match = initialList.find(it => it.jenis_tipe_ukuran === v.label);
                    let qty = 0;
                    if (isAvail) {
                        if (match && match.jumlah !== undefined) {
                            qty = Math.min(maxStok, Number(match.jumlah));
                        } else if (initialList.length === 0 && idx === 0) {
                            qty = maxStok > 0 ? 1 : 0;
                        }
                    }

                    return {
                        layanan_bisnis_id: this.selectedProductId,
                        jenis_tipe_ukuran: v.label,
                        harga: (v.harga !== null && v.harga !== undefined) ? Number(v.harga) : Number(product.harga || 0),
                        satuan_harga: v.satuan_harga || product.satuan_harga || 'item',
                        gambar: v.gambar || null,
                        jumlah: qty,
                        is_available: isAvail,
                        stok: (v.stok !== null && v.stok !== undefined && v.stok !== '') ? Number(v.stok) : null,
                        status_stok_label: !isAvail ? 'Stok Habis' : (v.stok !== null && v.stok !== undefined && v.stok !== '' ? `Sisa ${v.stok}` : 'Ready'),
                    };
                });
            } else {
                const isAvail = Boolean(product.is_available ?? true);
                const maxStok = (product.stok !== null && product.stok !== undefined && product.stok !== '') ? Number(product.stok) : 999;
                const match = initialList[0];
                let qty = 0;
                if (isAvail) {
                    qty = match ? Math.min(maxStok, Number(match.jumlah || 1)) : (maxStok > 0 ? 1 : 0);
                }
                this.items = [{
                    layanan_bisnis_id: this.selectedProductId,
                    jenis_tipe_ukuran: '',
                    harga: Number(product.harga || 0),
                    satuan_harga: product.satuan_harga || 'item',
                    gambar: null,
                    jumlah: qty,
                    is_available: isAvail,
                    stok: (product.stok !== null && product.stok !== undefined && product.stok !== '') ? Number(product.stok) : null,
                    status_stok_label: !isAvail ? 'Stok Habis' : (product.stok !== null && product.stok !== undefined && product.stok !== '' ? `Sisa ${product.stok}` : 'Ready'),
                }];
            }
        },

        resolveImageUrl(url) {
            if (!url) return null;
            url = String(url).trim();
            if (url.startsWith('data:image/')) {
                return url;
            }
            if (url.includes('drive.google.com')) {
                const m = url.match(/(?:file\/d\/|open\?id=|uc\?id=)([a-zA-Z0-9_-]+)/);
                if (m && m[1]) return 'https://lh3.googleusercontent.com/d/' + m[1] + '=w600';
            }
            if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('/')) {
                return url;
            }
            return '/storage/' + url.replace(/^\/+/, '');
        },

        totalQuantity() {
            return this.items.reduce((sum, item) => sum + Math.max(0, Number(item.jumlah || 0)), 0);
        },

        itemSubtotal(item) {
            return Number(item.harga || 0) * Math.max(0, Number(item.jumlah || 0));
        },

        grandTotal() {
            return this.items.reduce((carry, item) => carry + this.itemSubtotal(item), 0);
        },

        currentPaymentLink() {
            const product = this.selectedProduct;
            if (product?.qris_link) {
                return {
                    link: product.qris_link,
                    label: product.qris_label || this.labels.openPayment,
                };
            }
            return null;
        },

        formatCurrency(value) {
            return new Intl.NumberFormat(this.localeTag, {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
            }).format(Number(value || 0));
        },

        isImageLink(url) {
            if (!url) return false;
            return /\.(png|jpe?g|webp|gif|svg)(\?.*)?$/i.test(url) 
                || url.startsWith('data:image/') 
                || url.includes('storage/')
                || url.includes('googleusercontent.com')
                || url.includes('drive.google.com');
        },
    };
}
</script>
@endpush