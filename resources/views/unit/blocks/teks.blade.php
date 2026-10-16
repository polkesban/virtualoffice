{{-- Block: Teks / HTML Content --}}
<section class="py-12 bg-white dark:bg-gray-950">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        @if($section->judul)
        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-6">{{ $section->judul }}</h2>
        @endif
        @if(!empty($cfg['konten']))
        <div class="prose prose-lg dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
            {!! nl2br(e($cfg['konten'])) !!}
        </div>
        @endif
    </div>
</section>
