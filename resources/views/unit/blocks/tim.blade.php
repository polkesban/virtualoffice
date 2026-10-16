{{-- Block: Tim & Personil --}}
@php
    $items = $cfg['items'] ?? [];
@endphp
<section class="py-14 bg-white dark:bg-gray-950">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white text-center mb-10">
            {{ $section->judul ?: 'Tim Kami' }}
        </h2>
        @if(empty($items))
        <p class="text-gray-400 text-sm text-center">Data tim belum ditambahkan.</p>
        @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-6">
            @foreach($items as $item)
            @if(!empty($item['nama']))
            <div class="text-center group">
                @if(!empty($item['foto']))
                <img src="{{ $item['foto'] }}" alt="{{ $item['nama'] }}"
                     class="w-24 h-24 rounded-full object-cover mx-auto mb-3 ring-4 ring-gray-100 dark:ring-gray-800 group-hover:ring-brand-200 transition-all">
                @else
                <div class="w-24 h-24 rounded-full bg-gray-200 dark:bg-gray-700 mx-auto mb-3 flex items-center justify-center text-3xl ring-4 ring-gray-100 dark:ring-gray-800">
                    👤
                </div>
                @endif
                <p class="font-bold text-gray-900 dark:text-white text-sm">{{ $item['nama'] }}</p>
                @if(!empty($item['jabatan']))
                <p class="text-xs text-gray-500 mt-0.5">{{ $item['jabatan'] }}</p>
                @endif
                @if(!empty($item['bio']))
                <p class="text-xs text-gray-400 mt-1 line-clamp-2">{{ $item['bio'] }}</p>
                @endif
            </div>
            @endif
            @endforeach
        </div>
        @endif
    </div>
</section>
