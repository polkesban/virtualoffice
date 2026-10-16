{{-- Block: Publikasi & Penelitian --}}
@php
    use App\Models\Publikasi;
    $limit = $cfg['limit'] ?? 5;
    $jenis = $cfg['jenis'] ?? '';
    $items = Publikasi::where('aktif', 1)
        ->when($jenis, fn($q) => $q->where('jenis', $jenis))
        ->orderByDesc('tahun')
        ->limit($limit)
        ->get();
@endphp
<section class="py-14 bg-white dark:bg-gray-950">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-8">
            {{ $section->judul ?: 'Publikasi & Penelitian' }}
        </h2>
        @if($items->isEmpty())
        <p class="text-gray-400 text-sm">Belum ada data publikasi.</p>
        @else
        <div class="space-y-4">
            @foreach($items as $item)
            <div class="p-5 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 hover:shadow-sm transition-shadow">
                <div class="flex items-start gap-3">
                    <span class="shrink-0 px-2 py-1 rounded-lg text-xs font-bold bg-purple-50 text-purple-700">{{ $item->jenis_label }}</span>
                    <div class="min-w-0">
                        <h3 class="font-bold text-gray-900 dark:text-white">{{ $item->judul }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $item->penulis }}</p>
                        @if($item->institusi)<p class="text-xs text-gray-400 italic">{{ $item->institusi }}</p>@endif
                        @if($item->tahun || $item->doi)
                        <p class="text-xs text-gray-400 mt-1">
                            @if($item->tahun){{ $item->tahun }}@endif
                            @if($item->doi) · DOI: {{ $item->doi }}@endif
                        </p>
                        @endif
                        @if($item->abstrak)
                        <p class="text-sm text-gray-500 mt-2 line-clamp-2">{{ $item->abstrak }}</p>
                        @endif
                    </div>
                    <div class="shrink-0 flex gap-2">
                        @if($item->file_url)
                        <a href="{{ $item->file_url }}" target="_blank" class="px-3 py-1 rounded-lg text-xs font-bold bg-green-50 text-green-700 hover:bg-green-100 transition-colors">⬇ File</a>
                        @endif
                        @if($item->link_url)
                        <a href="{{ $item->link_url }}" target="_blank" class="px-3 py-1 rounded-lg text-xs font-bold bg-brand-50 text-brand-700 hover:bg-brand-100 transition-colors">↗ Link</a>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
