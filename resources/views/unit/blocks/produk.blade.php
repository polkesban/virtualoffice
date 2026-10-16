@php
    $items = collect($cfg['items'] ?? [])
        ->map(function ($item) {
            $item = is_array($item) ? $item : [];
            $item['opsi_varian'] = collect($item['opsi_varian'] ?? [])
                ->map(fn ($option) => is_array($option) ? $option : [])
                ->filter(fn ($option) => trim((string) ($option['label'] ?? '')) !== '')
                ->values()
                ->all();

            return $item;
        })
        ->filter(fn ($item) => trim((string) ($item['nama'] ?? '')) !== '')
        ->values();

    $resolveImage = function (?string $path): ?string {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (str_contains($path, 'drive.google.com')) {
            $direct = \App\Support\GoogleDriveEmbed::directImageUrl($path);
            if ($direct) {
                return $direct;
            }
        }

        if (\App\Support\MediaStorage::isGoogleReference($path)) {
            return \App\Support\MediaStorage::url($path);
        }

        if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return \App\Support\MediaStorage::url($path) ?: asset($path);
    };

    $formatPrice = function ($price, ?string $unit = null): ?string {
        if ($price === null || $price === '') {
            return null;
        }

        return 'Rp ' . number_format((float) $price, 0, ',', '.') . ($unit ? ' / ' . trim($unit) : '');
    };
@endphp

<section class="py-14 bg-white dark:bg-gray-950" x-data="{ qrisModal: { open: false, title: '', image: '', label: '' } }">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 max-w-3xl">
            <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">
                {{ $section->judul ?: 'Produk & Layanan' }}
            </h2>
            <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                Daftar produk atau layanan yang bisa ditampilkan lengkap dengan kategori, harga, dan varian pilihan.
            </p>
        </div>

        @if($items->isEmpty())
        <div class="rounded-3xl border border-dashed border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50 px-6 py-10 text-center text-sm text-gray-400">
            Belum ada produk yang ditambahkan pada blok ini.
        </div>
        @else
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach($items as $item)
            @php
                $image = $resolveImage($item['gambar'] ?? null);
                $basePrice = $formatPrice($item['harga'] ?? null, $item['satuan_harga'] ?? null);
                $category = \App\Models\LayananBisnis::$kategoriLabels[$item['kategori'] ?? 'produk'] ?? ($item['kategori'] ?? 'Produk');
                $qrisPath = $item['qris_image'] ?? $item['qris_link'] ?? null;
                $qrisUrl = $qrisPath ? $resolveImage($qrisPath) : null;
                $linkPesan = $item['link_pesan'] ?? null;
                $isItemAvailable = (!isset($item['is_tersedia']) || (bool)$item['is_tersedia']) && (!isset($item['stok']) || $item['stok'] === null || $item['stok'] === '' || (int)$item['stok'] > 0);
                $itemStock = isset($item['stok']) && $item['stok'] !== null && $item['stok'] !== '' ? (int)$item['stok'] : null;
            @endphp
            <article class="flex h-full flex-col overflow-hidden rounded-3xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm transition-transform duration-200 hover:-translate-y-1 hover:shadow-lg {{ $isItemAvailable ? '' : 'opacity-85' }}">
                @if($image)
                <div class="aspect-[16/10] overflow-hidden bg-gray-100 dark:bg-gray-800 relative group/img">
                    <img src="{{ $image }}" 
                         alt="{{ $item['nama'] }}" 
                         class="h-full w-full object-cover transition-transform duration-300 group-hover/img:scale-105 {{ $isItemAvailable ? '' : 'grayscale-[40%]' }}"
                         loading="lazy"
                         onerror="this.onerror=null; this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                    <div class="hidden h-full w-full flex items-center justify-center bg-gray-100 dark:bg-gray-800 text-gray-400">
                        <span class="text-4xl">🛍️</span>
                    </div>
                </div>
                @endif

                <div class="flex h-full flex-col p-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-full bg-brand-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.15em] text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                            {{ $category }}
                        </span>
                        @if($basePrice)
                        <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.15em] text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                            {{ $basePrice }}
                        </span>
                        @endif
                        @if(!$isItemAvailable)
                        <span class="inline-flex rounded-full bg-rose-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.15em] text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">
                            ● Stok Habis
                        </span>
                        @elseif($itemStock !== null)
                        <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-bold text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                            Stok: {{ $itemStock }}
                        </span>
                        @endif
                    </div>

                    <h3 class="mt-4 text-xl font-black leading-tight text-gray-900 dark:text-white">{{ $item['nama'] }}</h3>

                    @if(!empty($item['deskripsi']))
                    <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $item['deskripsi'] }}</p>
                    @endif

                    @if(!empty($item['opsi_varian']))
                    <div class="mt-5 rounded-2xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/50 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Varian / Opsi</p>
                        <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                            @foreach($item['opsi_varian'] as $option)
                            @php
                                $isVarAvail = (!isset($option['tersedia']) || (bool)$option['tersedia']) && (!isset($option['stok']) || $option['stok'] === null || $option['stok'] === '' || (int)$option['stok'] > 0);
                            @endphp
                            <li class="flex items-center justify-between gap-3 border-b border-gray-200/80 dark:border-gray-800 pb-2 last:border-b-0 last:pb-0">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    @if(!empty($option['gambar']))
                                    @php
                                        $variantImg = \App\Support\MediaStorage::url($option['gambar']) ?? $option['gambar'];
                                    @endphp
                                    <img src="{{ $variantImg }}" class="w-8 h-8 rounded-lg object-cover bg-gray-100 dark:bg-gray-800 shrink-0 border border-gray-200 dark:border-gray-700" alt="{{ $option['label'] }}" loading="lazy">
                                    @endif
                                    <span class="font-medium text-xs sm:text-sm text-gray-800 dark:text-gray-200 truncate">{{ $option['label'] }}</span>
                                    @if(!$isVarAvail)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300">Habis</span>
                                    @endif
                                </div>
                                <span class="shrink-0 text-right text-xs font-semibold text-brand-600 dark:text-brand-400">
                                    {{ $formatPrice($option['harga'] ?? null, $option['satuan_harga'] ?? null) ?: 'Hubungi kami' }}
                                </span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="mt-auto pt-6 flex flex-wrap items-center gap-2.5">
                        @if(!$isItemAvailable)
                        <span class="inline-flex items-center justify-center gap-1.5 rounded-full bg-gray-200 dark:bg-gray-800 px-4 py-2 text-xs font-bold text-gray-500 cursor-not-allowed select-none">
                            <span>🚫</span>
                            <span>Stok Habis</span>
                        </span>
                        @elseif(!empty($linkPesan))
                        <a href="{{ $linkPesan }}"
                           target="{{ \Illuminate\Support\Str::startsWith($linkPesan, 'http') ? '_blank' : '_self' }}"
                           rel="noopener noreferrer"
                           class="inline-flex items-center justify-center gap-1.5 rounded-full bg-brand-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition-all hover:bg-brand-500 hover:shadow-md">
                            <span>🛍️</span>
                            <span>Pesan Sekarang</span>
                        </a>
                        @endif    @endif

                        @if($qrisUrl)
                        <button type="button"
                                @click="qrisModal = { open: true, title: '{{ addslashes($item['nama']) }}', image: '{{ $qrisUrl }}', label: '{{ addslashes($item['qris_label'] ?? 'QRIS Koperasi Pegawai Husada Sejahtera (NMID: ID1026497443527)') }}' }"
                                class="inline-flex items-center justify-center gap-1.5 rounded-full border border-emerald-300 bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-800 transition-all hover:bg-emerald-100 hover:border-emerald-400 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                            <span>Bayar via QRIS</span>
                        </button>
                        @endif
                    </div>
                </div>
            </article>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Modal Pop-up QRIS --}}
    <div x-show="qrisModal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm transition-opacity"
         @keydown.escape.window="qrisModal.open = false"
         @click.self="qrisModal.open = false">
        <div class="relative w-full max-w-sm rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800 text-center animate-in fade-in zoom-in-95 duration-200">
            <button type="button" 
                    @click="qrisModal.open = false" 
                    class="absolute top-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                ✕
            </button>
            
            <div class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 mb-2">
                <span>QRIS Pembayaran Resmi</span>
            </div>

            <h3 class="text-lg font-black text-gray-900 dark:text-white" x-text="qrisModal.title"></h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="qrisModal.label || 'KOPERASI PEGAWAI HUSADA SEJAHTERA'"></p>

            <div class="my-4 flex items-center justify-center p-2 rounded-2xl bg-white border border-gray-200 shadow-inner">
                <img :src="qrisModal.image" alt="QRIS" class="max-h-80 w-auto object-contain rounded-xl">
            </div>

            <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-4">
                Scan QR Code di atas menggunakan aplikasi m-Banking atau e-Wallet (BCA, Mandiri, BRI, BNI, BSI, Gopay, OVO, ShopeePay, Dana, dll).
            </p>

            <div class="flex gap-2">
                <a :href="qrisModal.image" download="qris-pembayaran.png" target="_blank" class="flex-1 rounded-xl bg-brand-600 hover:bg-brand-500 py-2.5 text-xs font-bold text-white transition-colors">
                    Unduh Gambar QRIS
                </a>
                <button type="button" @click="qrisModal.open = false" class="rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-2.5 text-xs font-medium text-gray-600 dark:text-gray-300">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</section>