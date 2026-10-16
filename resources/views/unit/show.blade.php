@extends('layouts.app')
@section('title', $page->judul)
@section('description', $page->sub_judul ?? $page->judul . ' — ' . config('app.name'))

@section('content')

@php
    $heroSections = $sections->filter(fn($s) => $s->tipe === 'hero');
    $otherSections = $sections->filter(fn($s) => $s->tipe !== 'hero');
    $sortedSections = $heroSections->concat($otherSections);
@endphp


{{-- Page header / nav --}}
@if($heroSections->isEmpty())
<div class="relative overflow-hidden"
     style="background-color: {{ $page->warna_tema }}{{ $page->cover_foto ? ';' : '' }}">
    @if($page->cover_foto)
    <img src="{{ \App\Support\MediaStorage::url($page->cover_foto) }}"
         class="absolute inset-0 w-full h-full object-cover" alt="">
    <div class="absolute inset-0" style="background-color: {{ $page->warna_tema }}; opacity: 0.80;"></div>
    @endif
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16">
        <nav class="mb-4 flex text-sm text-white/70">
            <a href="{{ route('home') }}" class="hover:text-white transition-colors">Beranda</a>
            <span class="mx-2">/</span>
            @if(isset($parentPage) && $parentPage)
            <a href="{{ $parentPage->publicUrl() }}" class="hover:text-white transition-colors">{{ $parentPage->judul }}</a>
            <span class="mx-2">/</span>
            @endif
            <span class="text-white">{{ $page->judul }}</span>
        </nav>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-white">{{ $page->judul }}</h1>
        @if($page->sub_judul)
        <p class="mt-3 text-lg text-white/80 max-w-2xl">{{ $page->sub_judul }}</p>
        @endif
    </div>
</div>
@endif

{{-- Render Hero Blocks (Always Visible at Top) --}}
@foreach($heroSections as $section)
    @php
        $cfg = $section->konfigurasi ?? [];
    @endphp
    @includeIf('unit.blocks.' . $section->tipe, ['section' => $section, 'cfg' => $cfg, 'themeColor' => $page->warna_tema, 'page' => $page])
@endforeach

{{-- Get Primary Color from Hero --}}
@php
    $primaryColor = $page->warna_tema;
    $heroSection = $heroSections->first();
    if ($heroSection) {
        $hCfg = $heroSection->konfigurasi ?? [];
        $hGradient = $hCfg['bg_gradient'] ?? 'none';
        if ($hGradient !== 'none') {
            $primaryColor = match($hGradient) {
                'blue-cyan' => '#1e40af', // from-blue-800
                'purple-pink' => '#6b21a8', // from-purple-800
                'indigo-blue' => '#312e81', // from-indigo-900
                'gray-dark' => '#111827', // from-gray-900
                'teal-emerald' => '#115e59', // from-teal-800
                'orange-red' => '#c2410c', // from-orange-700
                default => $primaryColor,
            };
        } elseif (!empty($hCfg['bg_color'])) {
            $primaryColor = $hCfg['bg_color'];
        }
    }
@endphp

{{-- Sub-Units Navigation --}}
@if(isset($subUnitPages) && $subUnitPages->isNotEmpty())
<div class="bg-white border-b border-gray-100 dark:bg-gray-900 dark:border-gray-800 shadow-sm relative z-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-5">
        <div class="flex flex-col md:flex-row items-center justify-center md:justify-between gap-4">
            <div class="flex items-center gap-3 shrink-0">
                <div class="flex h-8 w-8 items-center justify-center rounded-full" style="background-color: {{ $primaryColor }}1A; color: {{ $primaryColor }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                </div>
                <h3 class="text-sm font-bold text-gray-600 uppercase tracking-widest dark:text-gray-400">Jelajahi Sub Unit:</h3>
            </div>
            <div class="flex flex-wrap items-center justify-center md:justify-end gap-2 sm:gap-3">
                @foreach($subUnitPages as $subPage)
                <a href="{{ $subPage->publicUrl() }}" class="group inline-flex items-center gap-2 px-4 py-2 rounded-full border border-gray-200 bg-white text-sm font-bold text-gray-700 hover:shadow-md transition-all duration-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 hover:-translate-y-0.5" style="hover:border-color: {{ $primaryColor }}; hover:text-color: {{ $primaryColor }};">
                    <span class="group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors">{{ $subPage->judul }}</span>
                    <svg class="h-4 w-4 text-gray-400 group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

{{-- Render Other Blocks as Cards --}}
@if($otherSections->isNotEmpty() || (isset($events) && $events->isNotEmpty()))
<div id="unit-content-area">
    
    @if(!request('section'))
    {{-- Card Grid View --}}
    <div class="py-12 lg:py-16" style="background-color: {{ $primaryColor }}4D;">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center border-b border-slate-200/20 dark:border-gray-800/30 pb-6 mb-12">
                <h2 class="font-bold uppercase tracking-widest text-sm" style="color: {{ $primaryColor }}; letter-spacing: 0.1em;">Informasi & Layanan</h2>
                <p class="font-black drop-shadow-sm text-slate-950 dark:text-white text-3xl mt-2">Berita dan Kegiatan Kami</p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($otherSections as $section)
                @php
                    $cfg = $section->konfigurasi ?? [];
                                       // Ekstraksi Judul
                    $cardTitle = $section->judul ?: match($section->tipe) {
                        'google_drive' => $cfg['judul'] ?? 'Google Drive Viewer',
                        'ai_teks' => $cfg['_prompt_acara'] ?? 'Artikel Berita & Teks AI',
                        'teks' => 'Informasi & Teks',
                        'statistik' => 'Statistik & Data',
                        'event_unit' => 'Kegiatan & Event',
                        'faq' => 'Tanya Jawab (FAQ)',
                        'produk' => 'Produk & Layanan',
                        default => 'Informasi Tambahan'
                    };
                    
                    // Ekstraksi Deskripsi Singkat
                    $cardDesc = $cfg['sub'] ?? $cfg['deskripsi'] ?? '';
                    if (empty($cardDesc) && !empty($cfg['konten'])) {
                        $cardDesc = Str::limit(strip_tags($cfg['konten']), 100);
                    }
                    if (empty($cardDesc) && $section->tipe === 'produk' && !empty($cfg['items'])) {
                        $cardDesc = 'Daftar produk atau layanan yang bisa ditampilkan lengkap dengan kategori, harga, dan varian pilihan.';
                    }
                    if (empty($cardDesc)) {
                        $cardDesc = 'Klik di sini untuk membaca informasi dan layanan selengkapnya.';
                    }

                    // Ekstraksi Gambar Thumbnail
                    $cardImage = $cfg['gambar_url'] ?? $cfg['cover'] ?? ($cfg['items'][0]['gambar'] ?? '');
                    if (!empty($cardImage)) {
                        $cardImage = \App\Support\MediaStorage::url($cardImage);
                    }

                    // Icon Fallback
                    $icon = match($section->tipe) {
                        'teks', 'ai_teks' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
                        'statistik' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>',
                        'event_unit' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>',
                        'google_drive' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>',
                        'produk' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>',
                        default => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>'
                    };
                @endphp
                <a href="{{ request()->url() }}?section={{ $section->id }}"
                     class="group relative flex cursor-pointer flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl dark:border-gray-800 dark:bg-gray-900">
                    
                    @if($cardImage)
                    <img src="{{ $cardImage }}" class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" alt="Thumbnail">
                    <div class="relative flex flex-1 flex-col">
                        <div class="h-48 shrink-0"></div>
                        <div class="flex flex-1 flex-col border-t border-white/10 bg-gray-900/70 p-6">
                            <div class="mb-3 flex items-center text-xs font-semibold text-brand-300">
                                Lihat Detail <span class="ml-2 transition-transform group-hover:translate-x-1">→</span>
                            </div>
                            <h3 class="text-lg font-bold leading-tight text-white group-hover:text-brand-300">
                                {{ $cardTitle }}
                            </h3>
                            <p class="mt-1.5 text-xs text-gray-300 line-clamp-2">
                                {{ $cardDesc }}
                            </p>
                        </div>
                    </div>
                    @else
                    <div class="flex flex-1 flex-col p-6 relative z-10">
                        <div class="flex items-start justify-between mb-4">
                            <div class="inline-flex h-14 w-14 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/20 dark:text-brand-400 group-hover:scale-110 transition-transform">
                                {!! $icon !!}
                            </div>
                            <div class="flex items-center mt-2 text-xs font-semibold text-brand-600 dark:text-brand-400">
                                Lihat Detail <span class="ml-2 transition-transform group-hover:translate-x-1">→</span>
                            </div>
                        </div>
                        <h3 class="text-lg font-bold leading-tight text-gray-900 group-hover:text-brand-600 dark:text-white dark:group-hover:text-brand-400">
                            {{ $cardTitle }}
                        </h3>
                        <p class="mt-1.5 text-xs text-gray-500 line-clamp-2 dark:text-gray-400">
                            {{ $cardDesc }}
                        </p>
                    </div>
                    @endif
                </a>
            @endforeach

            {{-- Events Cards (including sub-units) --}}
            @if(isset($events) && $events->isNotEmpty())
                @foreach($events as $event)
                    @php
                        $eventImage = $event->flyer_path ? \App\Support\MediaStorage::url($event->flyer_path) : '';
                        $eventDate = $event->mulai_at ? $event->mulai_at->translatedFormat('d M Y') : '';
                        $eventCreator = $event->page && $event->page->id !== $page->id ? 'Oleh: ' . $event->page->judul : 'Event Kegiatan';
                    @endphp
                    <a href="{{ $event->publicUrl() }}" class="group relative flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl dark:border-gray-800 dark:bg-gray-900">
                        
                        @if($eventImage)
                        <img src="{{ $eventImage }}" class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" alt="Flyer Event">
                        <div class="relative flex flex-1 flex-col">
                            <div class="h-48 shrink-0"></div>
                            <div class="flex flex-1 flex-col border-t border-white/10 bg-gray-900/70 p-6">
                                <div class="mb-3 flex items-start justify-between gap-2">
                                    <span class="w-fit rounded-full bg-brand-500/80 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-white backdrop-blur-sm">{{ $eventCreator }}</span>
                                    <div class="flex items-center text-xs font-semibold text-brand-300">
                                        Lihat Event <span class="ml-2 transition-transform group-hover:translate-x-1">→</span>
                                    </div>
                                </div>
                                <h3 class="text-lg font-bold leading-tight text-white group-hover:text-brand-300">{{ $event->judul }}</h3>
                                <p class="mt-1.5 text-xs text-gray-300 line-clamp-2">{{ $eventDate }} • {{ $event->lokasi ?? 'Online/Offline' }}</p>
                            </div>
                        </div>
                        @else
                        <div class="relative flex flex-col flex-1">
                            <x-event-flyer :event="$event" compact="true" />
                        </div>
                        @endif
                    </a>
                @endforeach
            @endif
        </div>
    </div>
    @else

    {{-- Full View Mode --}}
    <div class="bg-white dark:bg-gray-950 pb-20">
        <div class="sticky top-[64px] z-40 border-b border-gray-200 bg-white/90 backdrop-blur-md dark:border-gray-800 dark:bg-gray-900/90 shadow-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-3">
                <a href="{{ request()->url() }}#unit-content-area" class="inline-flex items-center text-sm font-semibold text-gray-600 hover:text-brand-600 dark:text-gray-300 dark:hover:text-brand-400 transition-colors">
                    <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali ke Daftar Menu
                </a>
            </div>
        </div>
        
        @foreach($otherSections as $section)
            @if(request('section') == $section->id)
                @php $cfg = $section->konfigurasi ?? []; @endphp
                <div>
                    @includeIf('unit.blocks.' . $section->tipe, ['section' => $section, 'cfg' => $cfg, 'themeColor' => $page->warna_tema, 'page' => $page])
                </div>
            @endif
        @endforeach
    </div>
    @endif
</div>
@endif

@if($sections->isEmpty() && (!isset($events) || $events->isEmpty()))
<div class="py-24 text-center">
    <p class="text-gray-400">Halaman ini belum memiliki konten.</p>
</div>
@endif

@if($otherSections->isNotEmpty() || (isset($events) && $events->isNotEmpty()))
<button @click="document.getElementById('unit-content-area').scrollIntoView({behavior: 'smooth', block: 'start'})" 
        x-data="{ show: false }" 
        @scroll.window="show = window.pageYOffset > 300" 
        x-show="show" 
        x-transition.opacity.duration.300ms
        class="fixed bottom-6 left-6 z-50 flex h-14 w-14 items-center justify-center rounded-full shadow-2xl transition-transform hover:-translate-y-1 hover:scale-105"
        style="background-color: {{ $primaryColor ?? $page->warna_tema }}; color: white; display: none;">
    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
</button>
@endif

@endsection
