{{-- Block: Program Pelatihan --}}
@php
    use App\Models\Pelatihan;
    $limit = $cfg['limit'] ?? 6;
    $jenis = $cfg['jenis'] ?? '';
    $items = Pelatihan::where('aktif', 1)
        ->when($jenis, fn($q) => $q->where('jenis', $jenis))
        ->orderByDesc('tanggal_mulai')
        ->limit($limit)
        ->get();
@endphp
<section class="py-14 bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-8">
            {{ $section->judul ?: 'Program Pelatihan & Pengembangan' }}
        </h2>
        @if($items->isEmpty())
        <p class="text-gray-400 text-sm">Belum ada data program pelatihan.</p>
        @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($items as $item)
            <div class="bg-white dark:bg-gray-950 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden hover:shadow-md transition-shadow">
                @if($item->foto)
                <img src="{{ \App\Support\MediaStorage::url($item->foto) }}" class="w-full h-36 object-cover" alt="{{ $item->judul }}">
                @endif
                <div class="p-4">
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-teal-50 text-teal-700">{{ $item->jenis_label }}</span>
                    <h3 class="mt-2 font-bold text-gray-900 dark:text-white text-sm line-clamp-2">{{ $item->judul }}</h3>
                    <p class="text-xs text-gray-400 mt-1">{{ $item->durasi }}</p>
                    @if($item->lokasi)<p class="text-xs text-gray-400">📍 {{ $item->lokasi }}</p>@endif
                    @if($item->jumlah_peserta)<p class="text-xs text-brand-600 font-bold mt-1">{{ $item->jumlah_peserta }} peserta</p>@endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
