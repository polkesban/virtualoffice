@extends('layouts.app')

@section('title', 'Beranda')
@section('description', \App\Support\SiteAppearance::home()['description'])

@push('head')
@verbatim
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "EducationalOrganization",
  "name": "Politeknik Kesehatan Kemenkes Bandung",
  "alternateName": "Poltekkes Bandung",
  "url": "https://polkesban.ac.id",
  "logo": "https://polkesban.ac.id/images/logo.png",
  "address": { "@type": "PostalAddress", "streetAddress": "Jl. Pajajaran No. 56", "addressLocality": "Bandung", "postalCode": "40171", "addressCountry": "ID" },
  "telephone": "+62224231627", "email": "info@polkesban.ac.id"
}
</script>
@endverbatim
<style>
    /* Animasi bento cards masuk */
    @keyframes bentoIn {
        from { opacity: 0; transform: translateY(20px) scale(0.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .bento-card { animation: bentoIn 0.5s ease both; }
    .bento-card:nth-child(1) { animation-delay: 0.05s; }
    .bento-card:nth-child(2) { animation-delay: 0.12s; }
    .bento-card:nth-child(3) { animation-delay: 0.19s; }
    .bento-card:nth-child(4) { animation-delay: 0.26s; }
    .bento-card:nth-child(5) { animation-delay: 0.33s; }
    .bento-card:nth-child(6) { animation-delay: 0.40s; }

    /* Shimmer untuk area video saat loading */
    @keyframes shimmer {
        0%   { background-position: -200% center; }
        100% { background-position:  200% center; }
    }
    .video-shimmer {
        background: linear-gradient(90deg, #14532d 25%, #166534 50%, #14532d 75%);
        background-size: 200% auto;
        animation: shimmer 2.5s linear infinite;
    }

    /* Floating dots */
    @keyframes float {
        0%, 100% { transform: translateY(0);    }
        50%       { transform: translateY(-10px); }
    }
    .dot-a { animation: float 3.2s ease-in-out infinite; }
    .dot-b { animation: float 4.0s ease-in-out infinite 1.1s; }
    .dot-c { animation: float 3.6s ease-in-out infinite 0.5s; }

    /* Pulse ring pada CTA button */
    @keyframes pulseRing {
        0%   { transform: scale(1);   opacity: 0.5; }
        100% { transform: scale(1.7); opacity: 0;   }
    }
    .pulse-cta::before, .pulse-cta::after {
        content: '';
        position: absolute;
        inset: -6px;
        border-radius: 9999px;
        border: 2px solid #22c55e;
        animation: pulseRing 2s ease-out infinite;
        pointer-events: none;
    }
    .pulse-cta::after { animation-delay: 1s; }

    /* Gradient text */
    .grad-text {
        background: linear-gradient(135deg, #86efac, #22c55e, #38bdf8);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Dark mode smooth transition */
    html { transition: background-color 0.3s ease, color 0.3s ease; }

    /* Hide scrollbar on prodi scroll */
    .scrollbar-none { scrollbar-width: none; }
    .scrollbar-none::-webkit-scrollbar { display: none; }
</style>
@endpush

@section('content')
@php
    $homeAppearance = \App\Support\SiteAppearance::home();
    $hero = $homeAppearance['hero'];
    $statistics = $homeAppearance['statistics'];
    $quizAppearance = $homeAppearance['quiz'];
    $quizQuestions = $quizAppearance['questions'];
    $quizMap = $quizAppearance['map'];
    $quizMap2 = $quizAppearance['map2'];
    $quizDefaultResultKey = $quizAppearance['default_result_key'];
    $interactiveLocaleTag = \App\Support\PublicLocale::localeTag();
    $statisticsItems = \App\Support\PublicLocale::translateData($statistics['items']);
    $quizQuestions = \App\Support\PublicLocale::translateData($quizQuestions);
    $quizMap = \App\Support\PublicLocale::translateData($quizMap);
    $quizMap2 = \App\Support\PublicLocale::translateData($quizMap2);
    $homeInteractiveLabels = \App\Support\PublicLocale::translateData([
        'darkMode' => 'Mode Gelap',
        'lightMode' => 'Mode Terang',
        'darkModeAria' => 'Mode gelap aktif - klik untuk mode terang',
        'lightModeAria' => 'Mode terang aktif - klik untuk mode gelap',
        'questionLabel' => 'Pertanyaan',
    ]);
    $newsAppearance = $homeAppearance['news'];
    $announcementAppearance = $homeAppearance['announcements'];
    $achievementAppearance = $homeAppearance['achievements'];
    $ctaAppearance = $homeAppearance['cta'];
    $programAppearance = $homeAppearance['programs'];
    $galleryAppearance = $homeAppearance['gallery'];
    $newsletterAppearance = $homeAppearance['newsletter'];
    $formatHomeText = function (?string $text): string {
        return str_replace(
            ['{year}', '{next_year}'],
            [(string) date('Y'), (string) (date('Y') + 1)],
            (string) $text
        );
    };
    $resolveHomeHref = function (?string $href): string {
        if (! $href) {
            return '#';
        }

        return str_starts_with($href, 'http://') || str_starts_with($href, 'https://')
            ? $href
            : url($href);
    };
    $programStudiTotal = \Illuminate\Support\Facades\Schema::hasTable('program_studi')
        ? \App\Models\ProgramStudi::where('aktif', true)->count()
        : 0;
    $homeGalleryItems = \Illuminate\Support\Facades\Schema::hasTable('galeri')
        ? \App\Models\Galeri::where('tampil', true)
            ->orderBy('urutan')
            ->orderByDesc('id')
            ->take(8)
            ->get()
            ->map(fn ($item) => [
                'src' => $item->url,
                'alt' => $item->alt ?: $item->judul,
            ])
            ->all()
        : [];

    if ($homeGalleryItems === []) {
        $homeGalleryItems = [
            ['src' => asset('images/galeri/LkaTNgJRIJ7405o4U6FC.jpeg'), 'alt' => 'Aktivitas kampus Poltekkes Kemenkes Bandung'],
            ['src' => asset('images/galeri/PMq8PVZ3lwMKhzjXreKu.jpeg'), 'alt' => 'Kegiatan mahasiswa Poltekkes Kemenkes Bandung'],
            ['src' => asset('images/galeri/xtinfnwQkMVfJxWzGeUz.jpg'), 'alt' => 'Kampus Poltekkes Kemenkes Bandung'],
            ['src' => asset('images/galeri/15jjAxlMWCrinOTUmmbA.jpg'), 'alt' => 'Kegiatan akademik Poltekkes Kemenkes Bandung'],
            ['src' => asset('images/galeri/MQej6CCWrryxBJotqwch.jpg'), 'alt' => 'Laboratorium Poltekkes Kemenkes Bandung'],
            ['src' => asset('images/galeri/W2accLpITDPY9O43uvln.jpg'), 'alt' => 'Mahasiswa Poltekkes Kemenkes Bandung'],
            ['src' => asset('images/galeri/bGGIfpcGvj5kGsZKBuMV.jpg'), 'alt' => 'Kegiatan Poltekkes Kemenkes Bandung'],
            ['src' => asset('images/galeri/XpyVtCqYXlQFmAUYOm95.jpg'), 'alt' => 'Fasilitas Poltekkes Kemenkes Bandung'],
        ];
    }
@endphp

{{-- ══════════════════════════════════════════ --}}
{{-- BENTO GRID                                --}}
{{-- ══════════════════════════════════════════ --}}
<section class="mx-auto max-w-[1440px] px-3 sm:px-6 lg:px-8 pt-5 pb-8 lg:pt-7 lg:pb-12"
         aria-label="Halaman utama Poltekkes Bandung">

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-3.5">

        {{-- ───────────────────────────────── --}}
        {{-- CARD 1 · VIDEO HERO              --}}
        {{-- lg: col 1–12                     --}}
        {{-- ───────────────────────────────── --}}
        <div class="bento-card relative lg:col-span-12 rounded-3xl overflow-hidden
                    min-h-[360px] lg:min-h-[540px] video-shimmer group"
             aria-labelledby="hero-heading">

            {{--
              VIDEO KAMPUS
              Letakkan file di: public/videos/kampus.mp4
              Spesifikasi ideal: 1280×720, H.264, max 12 MB, 30fps
            --}}
            <video class="absolute inset-0 h-full w-full object-cover opacity-0 transition-opacity duration-700"
                   autoplay loop muted playsinline
                   oncanplay="this.style.opacity=1"
                   poster="{{ \App\Support\SiteAppearance::storageUrl($hero['poster_path'] ?? null, 'images/og-default.jpg') }}"
                   aria-hidden="true">
                <source src="{{ \App\Support\SiteAppearance::storageUrl($hero['video_path'] ?? null, 'videos/kampus.mp4') }}" type="video/mp4">
            </video>

            {{-- Gradient overlay --}}
            <div class="absolute inset-0 bg-gradient-to-br from-brand-950/92 via-brand-900/65 to-accent-800/35"></div>

            {{-- Decorative dots --}}
            <span class="absolute top-10 right-12 w-3 h-3 rounded-full bg-brand-300/40 dot-a" aria-hidden="true"></span>
            <span class="absolute top-24 right-6  w-2 h-2 rounded-full bg-accent-400/40 dot-b" aria-hidden="true"></span>
            <span class="absolute bottom-20 left-10 w-4 h-4 rounded-full bg-brand-400/30 dot-c" aria-hidden="true"></span>

            {{-- Content --}}
            <div class="relative z-10 flex h-full flex-col justify-between p-7 lg:p-9 min-h-[inherit]">
                {{-- Pill badge --}}
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur-sm
                                 border border-white/20 px-4 py-1.5 text-xs font-bold text-brand-200 uppercase tracking-widest">
                        <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                        {{ $formatHomeText($hero['badge_text']) }}
                    </span>
                </div>

                {{-- Headline (Text Removed per request) --}}
                <div>

                    <div class="mt-7 flex flex-wrap gap-3">
                                <a href="{{ $resolveHomeHref($hero['primary_href']) }}"
                                    @if($hero['primary_new_tab']) target="_blank" rel="noopener noreferrer" @endif
                           class="relative inline-flex items-center gap-2 rounded-full bg-brand-500 hover:bg-brand-400
                                  px-6 py-3 text-sm font-bold text-white shadow-2xl shadow-brand-900/50
                                  transition-all duration-200 hover:scale-105 pulse-cta">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                            {{ $hero['primary_label'] }}
                        </a>
                        <a href="{{ $resolveHomeHref($hero['secondary_href']) }}"
                           @if($hero['secondary_new_tab']) target="_blank" rel="noopener noreferrer" @endif
                           class="inline-flex items-center gap-2 rounded-full border-2 border-white/30
                                  px-6 py-3 text-sm font-semibold text-white
                                  transition-all duration-200 hover:border-white/60 hover:bg-white/10">
                            {{ $hero['secondary_label'] }}
                        </a>
                    </div>

                    {{-- Scroll hint --}}
                    <div class="mt-8 flex items-center gap-2.5 text-white/35" aria-hidden="true">
                        <span class="flex gap-1.5">
                            <span class="w-7 h-1.5 rounded-full bg-white/60"></span>
                            <span class="w-1.5 h-1.5 rounded-full bg-white/25"></span>
                            <span class="w-1.5 h-1.5 rounded-full bg-white/25"></span>
                        </span>
                        <span class="text-xs">{{ $hero['scroll_hint'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ───────────────────────────────── --}}
        {{-- CARD 2 · STATISTIK INTERAKTIF   --}}
        {{-- lg: col 1-5, row 2              --}}
        {{-- ───────────────────────────────── --}}
        <div class="bento-card md:col-span-2 lg:col-span-5 rounded-3xl overflow-hidden relative
                    bg-brand-700 dark:bg-brand-950 p-7"
             x-data="{
                 started: false,
                 localeTag: @js($interactiveLocaleTag),
                 counters: (@js($statisticsItems)).map(item => ({ ...item, current: 0 })),
                 run() {
                     if (this.started) return;
                     this.started = true;
                     this.counters.forEach(c => {
                         const steps = 120;
                         let n = 0;
                         const iv = setInterval(() => {
                             n++;
                             c.current = Math.round(c.target * (n / steps));
                             if (n >= steps) { c.current = c.target; clearInterval(iv); }
                         }, 2000 / steps);
                     });
                 }
             }"
             x-init="new IntersectionObserver(([e]) => { if (e.isIntersecting) run() }, { threshold: 0.3 }).observe($el)">

            <div class="absolute -top-10 -right-10 w-44 h-44 rounded-full bg-brand-600/25 blur-3xl pointer-events-none" aria-hidden="true"></div>
            <div class="absolute -bottom-8 -left-6 w-32 h-32 rounded-full bg-accent-500/15 blur-2xl pointer-events-none" aria-hidden="true"></div>

            <div class="relative z-10">
                <p class="text-[11px] font-black uppercase tracking-widest text-brand-300">{{ $statistics['eyebrow'] }}</p>
                <h2 class="mt-0.5 text-xl font-extrabold text-white">{{ $statistics['title'] }}</h2>

                <dl class="mt-5 grid grid-cols-2 gap-3">
                    <template x-for="c in counters" :key="c.label">
                        <div class="rounded-2xl bg-white/8 hover:bg-white/12 transition-colors duration-200 p-4">
                            <span class="text-2xl" x-text="c.icon" aria-hidden="true"></span>
                            <dt class="mt-1.5 text-2xl font-black text-white tabular-nums leading-none"
                                x-text="c.current.toLocaleString(localeTag) + c.suffix"></dt>
                            <dd class="mt-1 text-xs font-medium text-brand-200" x-text="c.label"></dd>
                        </div>
                    </template>
                </dl>
            </div>
        </div>



        {{-- ───────────────────────────────── --}}
        {{-- CARD 4 · CEK JURUSAN (Alpine.js) --}}
        {{-- lg: col 6–12                     --}}
        {{-- ───────────────────────────────── --}}
        <div class="bento-card md:col-span-2 lg:col-span-7 rounded-3xl overflow-hidden
                    bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800
                    p-7 flex flex-col"
             x-data="homeQuizData({
                 questions: @js($quizQuestions),
                 map: @js($quizMap),
                 map2: @js($quizMap2),
                 defaultResultKey: @js($quizDefaultResultKey),
                 labels: @js($homeInteractiveLabels),
             })">

            {{-- Kolom quiz --}}
            <div class="flex flex-col flex-1 w-full">
            {{-- Header --}}
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-2xl bg-brand-100 dark:bg-brand-900/50
                            flex items-center justify-center text-xl" aria-hidden="true">🎯</div>
                <div>
                    <h2 class="text-sm font-extrabold text-gray-900 dark:text-white leading-tight">{{ $quizAppearance['title'] }}</h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $quizAppearance['subtitle'] }}</p>
                </div>
            </div>

            {{-- Quiz --}}
            <div class="flex-1 flex flex-col" x-show="!result">
                <div class="flex justify-between text-xs text-gray-400 dark:text-gray-500 mb-1.5">
                    <span x-text="labels.questionLabel + ' ' + (step+1) + '/' + questions.length"></span>
                    <span x-text="Math.round((step / questions.length) * 100) + '%'"></span>
                </div>
                <div class="h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden mb-5">
                    <div class="h-full bg-brand-500 rounded-full transition-all duration-500 ease-out"
                         :style="{ width: (step / questions.length * 100) + '%' }"></div>
                </div>

                <div class="flex-1">
                    <template x-for="(q, i) in questions" :key="i">
                        <div x-show="step === i"
                             x-transition:enter="transition duration-300"
                             x-transition:enter-start="opacity-0 translate-x-3"
                             x-transition:enter-end="opacity-100 translate-x-0">
                            <p class="text-xl mb-1.5" x-text="q.icon" aria-hidden="true"></p>
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-4" x-text="q.q"></p>
                            <div class="grid grid-cols-2 gap-2">
                                <template x-for="opt in q.opts" :key="opt.val">
                                    <button @click="pick(opt.val)"
                                            class="text-left text-xs font-semibold rounded-xl p-3
                                                   bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300
                                                   border border-transparent
                                                   hover:bg-brand-50 dark:hover:bg-brand-950
                                                   hover:border-brand-200 dark:hover:border-brand-800
                                                   hover:text-brand-700 dark:hover:text-brand-300
                                                   transition-all duration-150 active:scale-95"
                                            x-text="opt.label">
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Result --}}
            <div x-show="result"
                 x-transition:enter="transition duration-400"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="flex-1 flex flex-col py-2">
                <p class="text-[11px] font-black uppercase tracking-widest text-brand-600 dark:text-brand-400 mb-3 text-center">
                    {{ $quizAppearance['result_title'] }}
                </p>
                <div class="flex gap-2 mb-4">
                    {{-- Pilihan 1 --}}
                    <div class="flex-1 rounded-2xl bg-brand-50 dark:bg-brand-950/60
                                border border-brand-200 dark:border-brand-800 p-3 flex flex-col items-center text-center">
                        <span class="text-[9px] font-black uppercase tracking-widest text-brand-500 mb-1">Pilihan 1</span>
                        <div class="text-3xl mb-1" x-text="result?.emoji" aria-hidden="true"></div>
                        <p class="text-[11px] font-extrabold text-gray-900 dark:text-white leading-snug"
                           x-text="result?.nama"></p>
                        <a :href="'/program-studi/' + result?.slug"
                           class="mt-2 text-[10px] font-bold text-brand-600 dark:text-brand-400 hover:underline">
                            Lihat detail →
                        </a>
                    </div>
                    {{-- Pilihan 2 --}}
                    <div class="flex-1 rounded-2xl bg-gray-50 dark:bg-gray-800/60
                                border border-gray-200 dark:border-gray-700 p-3 flex flex-col items-center text-center"
                         x-show="result2">
                        <span class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-1">Pilihan 2</span>
                        <div class="text-3xl mb-1" x-text="result2?.emoji" aria-hidden="true"></div>
                        <p class="text-[11px] font-extrabold text-gray-900 dark:text-white leading-snug"
                           x-text="result2?.nama"></p>
                        <a :href="'/program-studi/' + result2?.slug"
                           class="mt-2 text-[10px] font-bold text-gray-500 dark:text-gray-400 hover:underline">
                            Lihat detail →
                        </a>
                    </div>
                </div>
                <p class="text-[10px] text-center text-gray-400 dark:text-gray-500 mb-3">
                    {{ $quizAppearance['result_note'] }}
                </p>
                <div class="flex gap-2">
                    <a href="{{ route('program-studi') }}"
                       class="flex-1 rounded-xl bg-brand-600 hover:bg-brand-500
                              px-3 py-2 text-xs font-bold text-white text-center
                              transition-colors duration-200">
                        {{ $quizAppearance['all_programs_label'] }}
                    </a>
                    <button @click="reset()"
                            class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2
                                   text-xs font-semibold text-gray-500 dark:text-gray-400
                                   hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-200">
                        {{ $quizAppearance['reset_label'] }}
                    </button>
                </div>
            </div>
            </div>{{-- /kolom quiz --}}
        </div>

        {{-- ───────────────────────────────── --}}
        {{-- CARD 5 · BERITA TERBARU          --}}
        {{-- lg: col 1–8                      --}}
        {{-- ───────────────────────────────── --}}
        @php
            $beritaTerbaru = \Illuminate\Support\Facades\Schema::hasTable('berita')
                ? \App\Models\Berita::published()->latest('published_at')->take(3)->get()
                : collect();
        @endphp
        <div class="bento-card md:col-span-2 lg:col-span-8 rounded-3xl overflow-hidden
                    bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 p-7">

            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-sm font-extrabold text-gray-900 dark:text-white">{{ $newsAppearance['title'] }}</h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $newsAppearance['subtitle'] }}</p>
                </div>
                <a href="{{ route('berita') }}"
                   class="text-xs font-bold text-brand-600 dark:text-brand-400 hover:underline flex items-center gap-1">
                    {{ $newsAppearance['link_label'] }}
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            @if($beritaTerbaru->isEmpty())
                <div class="flex flex-col items-center justify-center py-10 text-center">
                    <div class="text-4xl mb-3" aria-hidden="true">📰</div>
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Belum ada berita.</p>
                    <p class="text-xs text-gray-400 dark:text-gray-600 mt-1">
                        Jalankan <code class="bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded-md">php artisan migrate:import</code>
                    </p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($beritaTerbaru as $item)
                        <a href="{{ route('berita.show', $item->slug) }}"
                           class="group flex gap-4 rounded-2xl p-3 -mx-1
                                  hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-200">
                            <div class="flex-shrink-0 w-16 h-16 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-800">
                                @if($item->gambar_sampul)
                                    <img src="{{ \App\Support\MediaStorage::url($item->gambar_sampul) }}"
                                         alt="{{ $item->gambar_sampul_alt ?? $item->judul }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                         loading="lazy" width="64" height="64">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-2xl bg-brand-50 dark:bg-brand-950"
                                         aria-hidden="true">🏥</div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="text-[10px] font-bold uppercase tracking-wide text-brand-600 dark:text-brand-400">
                                    {{ $item->kategori }}
                                </span>
                                <h3 class="mt-0.5 text-sm font-bold text-gray-900 dark:text-white leading-snug line-clamp-2
                                           group-hover:text-brand-700 dark:group-hover:text-brand-300 transition-colors">
                                    {{ $item->judul }}
                                </h3>
                                <time datetime="{{ $item->published_at?->toDateString() }}"
                                      class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 block">
                                    {{ $item->published_at?->translatedFormat('d M Y') ?? '—' }}
                                </time>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ───────────────────────────────── --}}
        {{-- CARD 5b · PENGUMUMAN             --}}
        {{-- lg: col 9–12                     --}}
        {{-- ───────────────────────────────── --}}
        @php
            $pengumumanTerbaru = \Illuminate\Support\Facades\Schema::hasTable('pengumuman')
                ? \App\Models\Pengumuman::where('aktif', true)
                    ->where('tanggal_mulai', '<=', now()->toDateString())
                    ->where(function($q){ $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai','>=',now()->toDateString()); })
                    ->orderByRaw("FIELD(prioritas,'urgent','penting','normal')")
                    ->orderBy('tanggal_mulai','desc')
                    ->take(4)->get()
                : collect();
        @endphp
        <div class="bento-card md:col-span-1 lg:col-span-4 rounded-3xl overflow-hidden
                    bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-extrabold text-gray-900 dark:text-white">{{ $announcementAppearance['title'] }}</h2>
                <a href="{{ route('pengumuman') }}" class="text-xs font-bold text-brand-600 dark:text-brand-400 hover:underline">{{ $announcementAppearance['link_label'] }}</a>
            </div>
            @if($pengumumanTerbaru->isEmpty())
                <p class="text-sm text-gray-400 py-6 text-center">{{ $announcementAppearance['empty_text'] }}</p>
            @else
                <div class="space-y-2.5">
                    @foreach($pengumumanTerbaru as $p)
                    @php $dot = match($p->prioritas){ 'urgent'=>'bg-red-500','penting'=>'bg-amber-400', default=>'bg-green-500' }; @endphp
                    <a href="{{ route('pengumuman') }}"
                       class="group flex gap-2.5 rounded-xl p-2 -mx-1 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <span class="mt-1.5 flex-shrink-0 w-2 h-2 rounded-full {{ $dot }}"></span>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-gray-900 dark:text-white line-clamp-2 leading-snug group-hover:text-brand-700 dark:group-hover:text-brand-300 transition-colors">
                                {{ $p->judul }}
                            </p>
                            <time class="text-[10px] text-gray-400 dark:text-gray-500">
                                {{ \Carbon\Carbon::parse($p->tanggal_mulai)->translatedFormat('d M Y') }}
                            </time>
                        </div>
                    </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ───────────────────────────────────────── --}}
        {{-- CARD 5c · MAHASISWA BERPRESTASI         --}}
        {{-- ───────────────────────────────────────── --}}
        @php
            $berprestasi = \Illuminate\Support\Facades\Schema::hasTable('mahasiswa_berprestasi')
                ? \App\Models\MahasiswaBerprestasi::aktif()
                    ->with([
                        'mahasiswa.programStudi',
                        'mahasiswa.portofolio' => fn($q) => $q->where('tampilkan', true)->orderByDesc('tanggal')->limit(3)
                    ])
                    ->orderBy('urutan')
                    ->orderByDesc('created_at')
                    ->take(3)->get()
                : collect();
        @endphp
        <div class="bento-card md:col-span-2 lg:col-span-9 rounded-3xl overflow-hidden
                    bg-gradient-to-br from-brand-900 via-brand-800 to-emerald-900
                    border border-brand-700/70 p-6 shadow-xl shadow-brand-950/20">

            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2">
                    <span class="text-lg" aria-hidden="true">🏆</span>
                    <div>
                        <h2 class="text-sm font-extrabold text-white">{{ $achievementAppearance['title'] }}</h2>
                        <p class="mt-0.5 text-[11px] font-medium text-brand-100/85">{{ $achievementAppearance['subtitle'] }}</p>
                    </div>
                </div>
                <a href="{{ route('mahasiswa.login') }}"
                   class="text-xs font-bold text-brand-100 hover:text-white hover:underline">
                    {{ $achievementAppearance['link_label'] }}
                </a>
            </div>

            @if($berprestasi->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @foreach($berprestasi as $bp)
                @php $mhs = $bp->mahasiswa; @endphp
                <div class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/10 p-4 shadow-lg shadow-brand-950/10 backdrop-blur-sm">
                    <img src="{{ $mhs->foto_url }}" alt="{{ $mhs->name }}"
                         class="w-12 h-12 rounded-xl object-cover flex-shrink-0 ring-2 ring-brand-200/60">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-semibold text-sm text-white truncate">{{ $mhs->name }}</p>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-200 text-amber-900 flex-shrink-0 shadow-sm">
                                {{ \App\Models\MahasiswaBerprestasi::$periodeLabels[$bp->periode] ?? 'Berprestasi' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-brand-100 mt-0.5 font-medium">
                            {{ $mhs->programStudi?->singkatan ?? '—' }} · {{ $bp->periode_label }}
                        </p>
                        @if($mhs->portofolio->isNotEmpty())
                            <div class="flex flex-wrap gap-1 mt-1.5">
                                @foreach($mhs->portofolio->take(2) as $ptf)
                                    <span class="px-1.5 py-0.5 rounded-md text-[10px] font-medium
                                                 bg-white/15 text-white border border-white/10 truncate max-w-[160px]">
                                        {{ $ptf->judul }}
                                    </span>
                                @endforeach
                            </div>
                        @elseif($bp->catatan_admin)
                            <p class="text-[11px] text-brand-100/90 mt-1 line-clamp-2">{{ $bp->catatan_admin }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            {{-- Placeholder --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @foreach([
                    ['badge' => 'Student of the Month',    'short' => 'SOTM', 'periode' => 'April 2026'],
                    ['badge' => 'Student of the Semester', 'short' => 'SOTS', 'periode' => 'Semester Genap 2025/2026'],
                    ['badge' => 'Student of the Year',     'short' => 'SOTY', 'periode' => 'Tahun 2026'],
                ] as $ph)
                <div class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/10 p-4 shadow-lg shadow-brand-950/10 backdrop-blur-sm">
                    <div class="w-12 h-12 rounded-xl flex-shrink-0 bg-amber-100 text-amber-900 flex items-center justify-center text-2xl">🎓</div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <div class="h-3 w-24 rounded-full bg-white/25"></div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-200 text-amber-900 flex-shrink-0 shadow-sm">
                                {{ $ph['short'] }}
                            </span>
                        </div>
                        <p class="text-[11px] font-semibold text-amber-100">{{ $ph['badge'] }}</p>
                        <p class="text-[11px] text-brand-100 mt-0.5">{{ $ph['periode'] }}</p>
                        <p class="text-[11px] text-white/75 italic mt-1">Belum dipilih</p>
                    </div>
                </div>
                @endforeach
            </div>
            <a href="{{ route('mahasiswa.register') }}"
               class="mt-4 flex items-center justify-center gap-1.5 w-full py-2.5 rounded-xl
                      bg-amber-200 hover:bg-amber-100 text-xs font-bold text-amber-950 transition-colors shadow-sm">
                <span aria-hidden="true">✨</span> {{ $achievementAppearance['empty_cta'] }}
            </a>
            @endif
        </div>

        {{-- ───────────────────────────────── --}}
        {{-- CARD 6 · CTA DAFTAR             --}}
        {{-- lg: col 10–12                   --}}
        {{-- ───────────────────────────────── --}}
        <div class="bento-card md:col-span-1 lg:col-span-3 rounded-3xl overflow-hidden relative
                    bg-gradient-to-br from-brand-600 via-brand-700 to-accent-700
                    p-7 flex flex-col justify-between min-h-[220px]">

            <div class="absolute -top-8 -right-8 w-36 h-36 rounded-full bg-white/5 pointer-events-none" aria-hidden="true"></div>
            <div class="absolute -bottom-6 -left-4 w-28 h-28 rounded-full bg-accent-300/10 pointer-events-none" aria-hidden="true"></div>

            <div class="relative z-10">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1
                             text-[11px] font-bold text-white/90 uppercase tracking-wide">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                    {{ $formatHomeText($ctaAppearance['badge_text']) }}
                </span>
                <h2 class="mt-4 text-2xl font-black text-white leading-tight">
                    {{ $ctaAppearance['title_line_1'] }}<br>{{ $ctaAppearance['title_line_2'] }}
                </h2>
                <p class="mt-2 text-sm text-white/65 leading-relaxed">
                    {{ $ctaAppearance['body'] }}
                </p>
            </div>

            <div class="relative z-10 mt-6 space-y-2">
                     <a href="{{ $resolveHomeHref($ctaAppearance['primary_href']) }}"
                         @if($ctaAppearance['primary_new_tab']) target="_blank" rel="noopener noreferrer" @endif
                   class="flex items-center justify-center gap-2 w-full rounded-xl
                          bg-white hover:bg-brand-50
                          px-5 py-3 text-sm font-bold text-brand-700
                          transition-all duration-200 hover:scale-[1.03] shadow-xl shadow-brand-900/20">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                    {{ $ctaAppearance['primary_label'] }}
                </a>
                <a href="{{ $resolveHomeHref($ctaAppearance['secondary_href']) }}"
                   @if($ctaAppearance['secondary_new_tab']) target="_blank" rel="noopener noreferrer" @endif
                   class="flex items-center justify-center w-full rounded-xl
                          border border-white/30 hover:border-white/60 hover:bg-white/10
                          px-5 py-2.5 text-sm font-semibold text-white transition-all duration-200">
                    {{ $ctaAppearance['secondary_label'] }}
                </a>
            </div>
        </div>

    </div>{{-- /bento grid --}}
</section>

{{-- ══════════════════════════════════════════ --}}
{{-- PROGRAM STUDI — Horizontal scroll cards    --}}
{{-- ══════════════════════════════════════════ --}}
@php
    // Tampilkan semua 20 prodi aktif di beranda (D4, D3, dan Profesi)
    $prodisBeranda = \Illuminate\Support\Facades\Schema::hasTable('program_studi')
        ? \App\Models\ProgramStudi::where('aktif', true)
            ->orderBy('jenjang','desc')->orderBy('urutan')
            ->get()
        : collect();
    // Gradien map berdasarkan slug aktual di DB
    $gradMap = [
        'keperawatan'                   => 'from-rose-500 to-pink-600',
        'kebidanan'                     => 'from-purple-500 to-violet-600',
        'gizi'                          => 'from-amber-500 to-orange-500',
        'kesehatan-lingkungan'          => 'from-emerald-500 to-teal-600',
        'promosi-kesehatan'             => 'from-lime-500 to-green-600',
        'sarjana-terapan-tlm'           => 'from-cyan-500 to-sky-600',
        'terapi-gigi'                   => 'from-teal-400 to-cyan-600',
        'keperawatan-bandung'           => 'from-rose-400 to-red-500',
        'keperawatan-bogor'             => 'from-rose-300 to-pink-500',
        'kebidanan-bandung'             => 'from-violet-500 to-purple-700',
        'kebidanan-karawang'            => 'from-violet-400 to-purple-600',
        'kebidanan-bogor'               => 'from-violet-300 to-purple-500',
        'analis-kesehatan'              => 'from-cyan-600 to-sky-700',
        'farmasi'                       => 'from-brand-500 to-brand-700',
        'gizi-d3'                       => 'from-yellow-500 to-amber-600',
        'kesehatan-gigi'                => 'from-teal-500 to-emerald-600',
        'sanitasi-kesehatan-lingkungan' => 'from-green-500 to-teal-700',
        'profesi-ners'                  => 'from-indigo-500 to-blue-600',
        'profesi-bidan'                 => 'from-fuchsia-500 to-pink-600',
        'profesi-dietisien'             => 'from-orange-500 to-amber-600',
    ];
@endphp

<section class="pb-10 lg:pb-14" aria-labelledby="prodi-scroll-heading">
    <div class="mx-auto max-w-[1440px] px-3 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between mb-5">
            <div>
                <p class="text-[11px] font-black uppercase tracking-widest text-brand-600 dark:text-brand-400">{{ $programAppearance['eyebrow'] }}</p>
                <h2 id="prodi-scroll-heading" class="mt-0.5 text-2xl font-extrabold text-gray-900 dark:text-white">{{ $programAppearance['title'] }}</h2>
            </div>
            <a href="{{ route('program-studi') }}"
               class="hidden sm:flex items-center gap-1 text-sm font-bold text-brand-600 dark:text-brand-400 hover:underline">
                {{ $programAppearance['desktop_link_label'] }}
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <div class="flex gap-3.5 overflow-x-auto pb-3 scrollbar-none snap-x snap-mandatory
                    lg:grid lg:grid-cols-4 xl:grid-cols-6 lg:overflow-visible"
             role="list">
            @foreach($prodisBeranda as $prodi)
            @php $g = $gradMap[$prodi->slug] ?? 'from-brand-500 to-brand-700'; @endphp
            <div class="flex-shrink-0 snap-start w-40 lg:w-auto" role="listitem">
                <a href="{{ route('program-studi.show', $prodi->slug) }}"
                   class="group flex flex-col rounded-2xl overflow-hidden
                          border border-gray-100 dark:border-gray-800
                          hover:shadow-xl hover:-translate-y-1.5 transition-all duration-200">
                    <div class="h-20 bg-gradient-to-br {{ $g }} flex items-center justify-center text-4xl">
                        {{ $prodi->icon_emoji ?? '🎓' }}
                    </div>
                    <div class="p-3 bg-white dark:bg-gray-900 flex-1">
                        <span class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wide">
                            {{ $prodi->jenjang }}
                        </span>
                        <p class="mt-0.5 text-xs font-bold text-gray-900 dark:text-white leading-snug">
                            {{ $prodi->nama }}
                        </p>
                        <span class="mt-2 inline-block text-[10px] font-bold px-1.5 py-0.5 rounded-full
                                     {{ $prodi->akreditasi === 'Unggul'
                                          ? 'bg-brand-100 text-brand-700 dark:bg-brand-950 dark:text-brand-300'
                                          : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">
                            {{ $prodi->akreditasi }}
                        </span>
                    </div>
                </a>
            </div>
            @endforeach
        </div>

        {{-- Link mobile --}}
        <div class="mt-4">
            <a href="{{ route('program-studi') }}"
               class="sm:hidden text-sm font-bold text-brand-600 dark:text-brand-400 hover:underline">
                {{ $programAppearance['mobile_link_label'] }}
            </a>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════ --}}
{{-- GALERI KAMPUS                              --}}
{{-- ══════════════════════════════════════════ --}}
<section class="mx-auto max-w-[1440px] px-3 sm:px-6 lg:px-8 pb-12"
         aria-labelledby="galeri-heading"
         x-data="{ lightboxOpen: false, lightboxImage: '', lightboxAlt: '', openLightbox(src, alt) { this.lightboxImage = src; this.lightboxAlt = alt; this.lightboxOpen = true; }, closeLightbox() { this.lightboxOpen = false; } }"
         @keydown.escape.window="closeLightbox()"
         x-effect="document.documentElement.classList.toggle('overflow-hidden', lightboxOpen)">
    <div class="flex items-end justify-between mb-5 gap-4">
        <div>
            <p class="text-[11px] font-black uppercase tracking-widest text-brand-600 dark:text-brand-400">{{ $galleryAppearance['eyebrow'] }}</p>
            <h2 id="galeri-heading" class="mt-0.5 text-2xl font-extrabold text-gray-900 dark:text-white">{{ $galleryAppearance['title'] }}</h2>
        </div>
        <a href="{{ route('galeri') }}"
           class="hidden sm:inline-flex items-center gap-1 text-sm font-bold text-brand-600 dark:text-brand-400 hover:underline">
            {{ $galleryAppearance['link_label'] }}
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
        @foreach($homeGalleryItems as $img)
        <button type="button"
                data-image="{{ $img['src'] }}"
                data-alt="{{ $img['alt'] }}"
                @click="openLightbox($el.dataset.image, $el.dataset.alt)"
                class="group relative aspect-[4/3] rounded-2xl overflow-hidden bg-gray-100 dark:bg-gray-800 text-left focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-950">
            <img src="{{ $img['src'] }}"
                 alt="{{ $img['alt'] }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                 loading="lazy">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/45 via-slate-950/0 to-transparent opacity-80 group-hover:opacity-100 transition-opacity duration-200"></div>
            <div class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-2 p-3">
                <span class="text-xs font-semibold text-white line-clamp-2">{{ $img['alt'] }}</span>
                <span class="inline-flex items-center rounded-full bg-white/20 px-2 py-1 text-[10px] font-bold text-white backdrop-blur-sm">{{ $galleryAppearance['lightbox_label'] }}</span>
            </div>
        </button>
        @endforeach
    </div>

    <div x-show="lightboxOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[120] flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-slate-950/85 backdrop-blur-sm" @click="closeLightbox()"></div>
        <div class="relative w-full max-w-6xl rounded-[2rem] border border-white/10 bg-slate-950/90 shadow-2xl shadow-black/40 overflow-hidden" @click.outside="closeLightbox()">
            <div class="flex items-center justify-between gap-4 border-b border-white/10 px-5 py-4">
                <p class="text-sm font-semibold text-white/85" x-text="lightboxAlt"></p>
                <button type="button" @click="closeLightbox()"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors"
                        aria-label="Tutup galeri">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="bg-black">
                <img :src="lightboxImage" :alt="lightboxAlt" class="max-h-[78vh] w-full object-contain">
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════ --}}{{-- NEWSLETTER STRIP                           --}}
{{-- ══════════════════════════════════════════ --}}
<section class="mx-auto max-w-[1440px] px-3 sm:px-6 lg:px-8 pb-12">
    <div class="rounded-3xl bg-gradient-to-r from-brand-700 via-brand-600 to-accent-600
                px-7 py-10 relative overflow-hidden">
        <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
            <div class="absolute -left-10 top-0 w-48 h-48 rounded-full bg-white/5 blur-3xl"></div>
            <div class="absolute right-0 bottom-0 w-40 h-40 rounded-full bg-accent-300/15 blur-2xl"></div>
        </div>
        <div class="relative flex flex-col sm:flex-row items-center gap-6 sm:gap-10">
            <div class="text-center sm:text-left flex-1">
                <p class="text-[11px] font-black uppercase tracking-widest text-brand-200">{{ $newsletterAppearance['eyebrow'] }}</p>
                <h2 class="mt-1 text-xl font-extrabold text-white">{{ $newsletterAppearance['title'] }}</h2>
                <p class="mt-1 text-sm text-white/65">{{ $newsletterAppearance['body'] }}</p>
            </div>
            <form class="relative w-full sm:w-auto"
                  x-data="{ email: '', sent: false }"
                  @submit.prevent="
                    if (!email) return;
                    fetch('{{ route('newsletter.subscribe') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ email })
                    }).then(r => r.ok && (sent = true, email = ''))">
                <template x-if="!sent">
                    <div class="flex gap-2">
                        <label for="nl-email" class="sr-only">Email kamu</label>
                        <input id="nl-email" type="email" x-model="email"
                               placeholder="{{ $newsletterAppearance['placeholder'] }}" required
                               class="w-64 rounded-xl bg-white/15 border border-white/20
                                      px-4 py-2.5 text-sm text-white placeholder-white/45
                                      focus:outline-none focus:ring-2 focus:ring-white/30 backdrop-blur-sm">
                        <button type="submit"
                                class="flex-shrink-0 rounded-xl bg-white hover:bg-brand-50
                                       px-5 py-2.5 text-sm font-bold text-brand-700
                                       transition-colors duration-200 whitespace-nowrap">
                            {{ $newsletterAppearance['button_label'] }}
                        </button>
                    </div>
                </template>
                <template x-if="sent">
                    <p class="flex items-center gap-2 text-white font-semibold text-sm py-2.5">
                        <svg class="w-5 h-5 text-green-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ $newsletterAppearance['success_message'] }}
                    </p>
                </template>
            </form>
        </div>
    </div>
</section>

@push('head')
<script>
    window.homeQuizData = function (config) {
        return {
            step: 0,
            answers: [],
            result: null,
            result2: null,
            questions: config.questions ?? [],
            map: config.map ?? {},
            map2: config.map2 ?? {},
            labels: config.labels ?? {},
            defaultResultKey: config.defaultResultKey ?? 'kep-klinis-d4',
            pick(val) {
                this.answers.push(val);

                if (this.step < this.questions.length - 1) {
                    this.step++;

                    return;
                }

                const [a1, a2, a3, a4] = this.answers;
                const key = `${a1}-${a2}-${a3}-${a4}`;
                const key3 = `${a1}-${a2}-${a3}`;
                const key2 = `${a1}-${a2}`;

                this.result = this.map[key]
                    ?? this.map[key3]
                    ?? this.map[key2]
                    ?? this.map[a1]
                    ?? this.map[this.defaultResultKey]
                    ?? Object.values(this.map)[0]
                    ?? null;

                this.result2 = this.map2[key]
                    ?? this.map2[key3]
                    ?? this.map2[key2]
                    ?? this.map2[a1]
                    ?? null;

                if (this.result2 && this.result && this.result2.slug === this.result.slug) {
                    this.result2 = null;
                }
            },
            reset() {
                this.step = 0;
                this.answers = [];
                this.result = null;
                this.result2 = null;
            },
        };
    };
</script>
@endpush

@endsection
