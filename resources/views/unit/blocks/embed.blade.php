{{-- Block: Embed (YouTube, Google Forms, Maps, dll.) --}}
@php
    $url    = $cfg['url'] ?? '';
    $tinggi = intval($cfg['tinggi'] ?? 450);
    $judul  = $cfg['judul'] ?? '';
@endphp
@if($url)
<section class="py-14 bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        @if($section->judul || $judul)
        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-6">{{ $section->judul ?: $judul }}</h2>
        @endif
        <div class="rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-800">
            <iframe src="{{ $url }}" width="100%" height="{{ $tinggi }}"
                    class="w-full" style="border:0;" allowfullscreen="" loading="lazy"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
            </iframe>
        </div>
        @if($judul && $section->judul)
        <p class="text-xs text-gray-400 text-center mt-2">{{ $judul }}</p>
        @endif
    </div>
</section>
@endif
