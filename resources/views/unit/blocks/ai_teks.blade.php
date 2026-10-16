@php $cfg = $section->konfigurasi ?? []; @endphp
@if(!empty($cfg['gambar_url']) || !empty($cfg['konten']))
<section class="py-12 bg-gray-50 dark:bg-gray-950">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-md dark:border-gray-800 dark:bg-gray-900">
            @if(!empty($cfg['gambar_url']))
            <figure class="not-prose overflow-hidden bg-gray-100 dark:bg-gray-800">
                <img src="{{ Str::startsWith($cfg['gambar_url'], ['http://', 'https://']) ? $cfg['gambar_url'] : \App\Support\MediaStorage::url($cfg['gambar_url']) }}"
                     alt="{{ $cfg['gambar_alt'] ?? ($section->judul ?: ($cfg['_prompt_acara'] ?? 'Ilustrasi blok AI teks')) }}"
                     class="h-auto w-full max-h-[500px] object-cover">
            </figure>
            @endif

            <div class="p-6 sm:p-8 md:p-10">
                @if(!empty($section->judul))
                <h2 class="text-2xl font-extrabold mb-6" style="color: {{ $themeColor ?? 'inherit' }}">{{ $section->judul }}</h2>
                @endif
                
                @if(!empty($cfg['konten']))
                <div class="prose prose-lg dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                    {!! $cfg['konten'] !!}
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endif
