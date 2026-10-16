@extends('layouts.app')

@section('title', 'Berita & Kegiatan')
@section('description', 'Informasi dan kegiatan terkini Politeknik Kesehatan Kemenkes Bandung.')

@section('content')
<style>
    .glassmorphic-card {
        background-color: rgba(255, 255, 255, 0.72) !important;
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
    }
    .dark .glassmorphic-card {
        background-color: rgba(17, 24, 39, 0.8) !important;
    }
    .glassmorphic-header {
        background-color: rgba(15, 118, 110, 0.3) !important; /* Translucent Kemenkes Teal brand-700 fallback */
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
        border-bottom: 1px solid rgba(15, 118, 110, 0.15);
    }
</style>

<div x-data="{
    viewMode: localStorage.getItem('berita_view_mode') || 'grid',
    switchViewMode(mode) {
        this.viewMode = mode;
        localStorage.setItem('berita_view_mode', mode);
    }
}">

{{-- Page Header --}}
<section class="glassmorphic-header relative z-10 py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <nav class="mb-3 flex text-sm text-teal-800 dark:text-brand-200" aria-label="Breadcrumb">
            <ol class="flex items-center gap-1.5" role="list">
                <li><a href="{{ route('home') }}" class="hover:text-teal-950 dark:hover:text-white transition-colors">Beranda</a></li>
                <li aria-hidden="true"><span>/</span></li>
                <li class="text-teal-950 dark:text-white" aria-current="page">Berita</li>
            </ol>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-teal-950 dark:text-white">Berita & Kegiatan</h1>
                <p class="mt-2 text-teal-850 dark:text-brand-200">Informasi terkini dari Poltekkes Bandung</p>
            </div>
            <!-- View Mode Switcher -->
            <div class="flex items-center gap-1.5 bg-white/80 dark:bg-gray-800/80 p-1.5 rounded-2xl border border-teal-600/20 dark:border-gray-700 shadow-sm backdrop-blur-sm">
                <button type="button" 
                        @click="switchViewMode('grid')"
                        :class="viewMode === 'grid' ? 'bg-brand-600 text-white font-extrabold shadow-sm' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/60 font-semibold'"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs transition-all cursor-pointer">
                    <span>🧱</span> Tampilan Grid
                </button>
                <button type="button" 
                        @click="switchViewMode('list')"
                        :class="viewMode === 'list' ? 'bg-brand-600 text-white font-extrabold shadow-sm' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/60 font-semibold'"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs transition-all cursor-pointer">
                    <span>📋</span> Tampilan List
                </button>
            </div>
        </div>
    </div>
</section>

<section class="bg-teal-50/20 dark:bg-gray-950/50 py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        {{-- Filter Kategori --}}
        <div class="mb-8 flex flex-wrap gap-2" role="group" aria-label="Filter kategori">
            @foreach ([null => 'Semua', 'berita' => 'Berita', 'kegiatan' => 'Kegiatan', 'prestasi' => 'Prestasi'] as $key => $label)
            <a href="{{ $key ? route('berita', ['kategori' => $key]) : route('berita') }}"
               class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors
                      {{ $kategori === $key ? 'bg-brand-600 text-white' : 'bg-white/60 dark:bg-gray-900/60 border border-gray-250/20 dark:border-gray-800/35 text-gray-600 dark:text-gray-300 hover:border-brand-400 hover:text-brand-700 dark:hover:text-brand-400 shadow-sm backdrop-blur-md' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>

        @if ($beritas->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-800 py-20 text-center">
                <p class="text-gray-400 dark:text-gray-500">Belum ada berita tersedia.</p>
            </div>
        @else
            {{-- GRID VIEW MODE --}}
            <div x-show="viewMode === 'grid'" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($beritas as $item)
                <article class="group glassmorphic-card flex flex-col overflow-hidden rounded-2xl border border-gray-250/20 dark:border-gray-800/35 shadow-sm hover:shadow-md transition-shadow">
                    {{-- Gambar --}}
                    <a href="{{ route('berita.show', $item->slug) }}" class="block aspect-video overflow-hidden bg-brand-100 dark:bg-brand-950/20" tabindex="-1" aria-hidden="true">
                        @if ($item->gambar_sampul)
                            <img src="{{ \App\Support\MediaStorage::url($item->gambar_sampul) }}"
                                 alt="{{ $item->gambar_sampul_alt ?? $item->judul }}"
                                 loading="lazy"
                                 width="640" height="360"
                                 class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
                        @else
                            <div class="flex h-full items-center justify-center bg-gradient-to-br from-brand-100/50 to-brand-200/50 dark:from-brand-950/40 dark:to-brand-900/40">
                                <span class="text-4xl" aria-hidden="true">📰</span>
                            </div>
                        @endif
                    </a>

                    <div class="flex flex-1 flex-col p-5">
                        {{-- Kategori pill --}}
                        <span class="inline-flex w-fit rounded-full bg-brand-50 dark:bg-brand-950/30 px-2.5 py-0.5 text-xs font-medium text-brand-700 dark:text-brand-400 capitalize">
                            {{ $item->kategori }}
                        </span>

                        <h2 class="mt-2 font-semibold text-gray-900 dark:text-white group-hover:text-brand-700 dark:group-hover:text-brand-400 transition-colors line-clamp-2">
                            <a href="{{ route('berita.show', $item->slug) }}">{{ $item->judul }}</a>
                        </h2>

                        @if ($item->ringkasan)
                        <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400 line-clamp-3">{{ $item->ringkasan }}</p>
                        @endif

                        <div class="mt-auto flex items-center justify-between pt-4 text-xs text-gray-450 dark:text-gray-500">
                            <time datetime="{{ $item->published_at?->toDateString() }}">{{ $item->tanggal_format }}</time>
                            <span>{{ number_format($item->views) }} dibaca</span>
                        </div>
                    </div>
                </article>
                @endforeach
            </div>

            {{-- LIST VIEW MODE --}}
            <div x-show="viewMode === 'list'" class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-6 sm:p-8 shadow-sm divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($beritas as $item)
                <article class="py-5 first:pt-0 last:pb-0 flex flex-col md:flex-row md:items-center md:justify-between gap-6 hover:bg-gray-50/50 dark:hover:bg-gray-800/30 px-3 rounded-2xl transition-colors">
                    <div class="flex items-start gap-4 min-w-0 flex-1">
                        <a href="{{ route('berita.show', $item->slug) }}" class="h-20 w-32 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-800 shrink-0 border border-gray-200/60 dark:border-gray-700/60 block">
                            @if ($item->gambar_sampul)
                                <img src="{{ \App\Support\MediaStorage::url($item->gambar_sampul) }}" alt="{{ $item->judul }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full items-center justify-center bg-brand-50 dark:bg-brand-950/40 text-xl">📰</div>
                            @endif
                        </a>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex rounded-full bg-brand-50 dark:bg-brand-950/40 px-2.5 py-0.5 text-[10px] font-bold uppercase text-brand-700 dark:text-brand-300">
                                    {{ $item->kategori }}
                                </span>
                                <time datetime="{{ $item->published_at?->toDateString() }}" class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ $item->tanggal_format }}
                                </time>
                            </div>
                            <h2 class="text-base font-extrabold text-gray-900 dark:text-white mt-1 hover:text-brand-600 dark:hover:text-brand-400 transition-colors">
                                <a href="{{ route('berita.show', $item->slug) }}">{{ $item->judul }}</a>
                            </h2>
                            @if ($item->ringkasan)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ $item->ringkasan }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0 self-start md:self-center">
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ number_format($item->views) }} dibaca</span>
                        <a href="{{ route('berita.show', $item->slug) }}" class="inline-flex h-9 items-center justify-center rounded-xl bg-brand-600 hover:bg-brand-500 px-4 text-xs font-bold text-white shadow-sm transition-colors">
                            Baca Berita
                        </a>
                    </div>
                </article>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if ($beritas->hasPages())
            <div class="mt-10">
                {{ $beritas->links() }}
            </div>
            @endif
        @endif
    </div>
</section>

</div>

@endsection
