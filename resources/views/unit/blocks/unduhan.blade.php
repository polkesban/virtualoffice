{{-- Block: File & Unduhan --}}
@php
    $items = $cfg['items'] ?? [];
    $tipeIcon = ['PDF'=>'📄','Word'=>'📝','Excel'=>'📊','PPT'=>'📊','ZIP'=>'🗜️'];
@endphp
<section class="py-14 bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-8">
            {{ $section->judul ?: 'Dokumen & Unduhan' }}
        </h2>
        @if(empty($items))
        <p class="text-gray-400 text-sm">Belum ada file yang tersedia untuk diunduh.</p>
        @else
        <div class="space-y-2">
            @foreach($items as $item)
            @if(!empty($item['nama']) && !empty($item['url']))
            <a href="{{ $item['url'] }}" target="_blank" rel="noopener"
               class="flex items-center gap-4 p-4 bg-white dark:bg-gray-950 rounded-xl border border-gray-100 dark:border-gray-800 hover:border-brand-300 hover:shadow-sm transition-all group">
                <span class="text-2xl shrink-0">{{ $tipeIcon[$item['tipe'] ?? ''] ?? '📎' }}</span>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 dark:text-white group-hover:text-brand-600 transition-colors">{{ $item['nama'] }}</p>
                    @if(!empty($item['deskripsi']))<p class="text-xs text-gray-400 line-clamp-1">{{ $item['deskripsi'] }}</p>@endif
                </div>
                <div class="shrink-0 flex items-center gap-2 text-xs text-gray-400">
                    @if(!empty($item['tipe']))<span class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 font-mono">{{ $item['tipe'] }}</span>@endif
                    @if(!empty($item['ukuran']))<span>{{ $item['ukuran'] }}</span>@endif
                    <span class="text-brand-600 group-hover:translate-x-0.5 transition-transform">⬇</span>
                </div>
            </a>
            @endif
            @endforeach
        </div>
        @endif
    </div>
</section>
