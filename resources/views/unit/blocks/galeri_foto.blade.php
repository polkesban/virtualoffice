@php
    $cfg   = $section->konfigurasi ?? [];
    $items = \App\Support\PublicLocale::translateData($cfg['items'] ?? []);
    $kolom = (int) ($cfg['kolom'] ?? 3);
    $gridClass = match($kolom) {
        2 => 'grid-cols-2',
        4 => 'grid-cols-2 sm:grid-cols-4',
        default => 'grid-cols-2 sm:grid-cols-3',
    };
@endphp
@if(!empty($items))
<div
    x-data="{
        open: false,
        current: { url: '', judul: '', caption: '' },
        show(item) { this.current = item; this.open = true; }
    }"
    @keydown.escape.window="open = false"
>
    <div class="grid {{ $gridClass }} gap-3">
        @foreach($items as $item)
        @if(!empty($item['url']))
        <div class="group relative overflow-hidden rounded-xl aspect-square cursor-pointer bg-gray-100 dark:bg-gray-800"
             @click="show({{ json_encode(['url' => $item['url'], 'judul' => $item['judul'] ?? '', 'caption' => $item['caption'] ?? '']) }})">
            <img src="{{ $item['url'] }}"
                 alt="{{ $item['judul'] ?? '' }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 ease-out"
                 loading="lazy">
            @if(!empty($item['judul']) || !empty($item['caption']))
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
                <div>
                    @if(!empty($item['judul']))<p class="text-white text-sm font-semibold leading-tight">{{ $item['judul'] }}</p>@endif
                    @if(!empty($item['caption']))<p class="text-white/80 text-xs mt-0.5">{{ $item['caption'] }}</p>@endif
                </div>
            </div>
            @endif
        </div>
        @endif
        @endforeach
    </div>

    {{-- Lightbox --}}
    <div x-show="open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
         @click.self="open = false">
        <div class="relative max-w-4xl w-full">
            <img :src="current.url" :alt="current.judul" class="max-h-[80vh] w-full object-contain rounded-xl shadow-2xl">
            <div x-show="current.judul || current.caption" class="mt-3 text-center text-white">
                <p class="font-semibold" x-text="current.judul"></p>
                <p class="text-sm text-white/70 mt-0.5" x-text="current.caption"></p>
            </div>
            <button @click="open = false" class="absolute top-2 right-2 bg-white/20 hover:bg-white/40 text-white rounded-full w-9 h-9 flex items-center justify-center text-lg">✕</button>
        </div>
    </div>
</div>
@endif
