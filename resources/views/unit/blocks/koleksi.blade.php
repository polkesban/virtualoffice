{{-- Block: Koleksi Perpustakaan --}}
@php
    use App\Models\KoleksiPerpus;
    $limit    = $cfg['limit'] ?? 6;
    $kategori = $cfg['kategori'] ?? '';
    $items    = KoleksiPerpus::where('aktif', 1)
        ->when($kategori, fn($q) => $q->where('kategori', $kategori))
        ->orderBy('judul')
        ->limit($limit)
        ->get();
@endphp
<section class="py-14 bg-white dark:bg-gray-950">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-8">
            {{ $section->judul ?: 'Koleksi Perpustakaan' }}
        </h2>
        @if($items->isEmpty())
        <p class="text-gray-400 text-sm">Belum ada koleksi yang ditampilkan.</p>
        @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($items as $item)
            <div class="group rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 overflow-hidden hover:shadow-md transition-shadow text-center p-3">
                @if($item->cover)
                <img src="{{ \App\Support\MediaStorage::url($item->cover) }}" class="w-full h-28 object-cover rounded-xl mb-2" alt="{{ $item->judul }}">
                @else
                <div class="w-full h-28 bg-gray-200 dark:bg-gray-700 rounded-xl mb-2 flex items-center justify-center text-3xl">📖</div>
                @endif
                <p class="text-xs font-bold text-gray-800 dark:text-white line-clamp-2">{{ $item->judul }}</p>
                <p class="text-xs text-gray-400 line-clamp-1 mt-0.5">{{ $item->penulis }}</p>
                <p class="text-xs font-bold mt-1 {{ $item->stok_tersedia > 0 ? 'text-green-600' : 'text-red-500' }}">{{ $item->status_stok }}</p>
                @if($item->link_digital)
                <a href="{{ $item->link_digital }}" target="_blank" class="mt-1 inline-block text-xs text-brand-600 hover:underline">🔗 Digital</a>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
