@extends('layouts.app')

@section('title', $berita->judul)
@section('description', $berita->meta_description ?? $berita->ringkasan ?? Str::limit(strip_tags($berita->isi), 160))
@section('og_image', \App\Support\MediaStorage::url($berita->gambar_sampul))

@push('head')
@php
$schema = [
    '@context' => 'https://schema.org',
    '@type'    => 'NewsArticle',
    'headline' => $berita->judul,
    'datePublished' => $berita->published_at?->toIso8601String(),
    'dateModified'  => $berita->updated_at->toIso8601String(),
    'author'    => ['@type' => 'Person', 'name' => $berita->penulis?->name ?? 'Poltekkes Bandung'],
    'publisher' => ['@type' => 'Organization', 'name' => 'Poltekkes Bandung', 'logo' => ['@type' => 'ImageObject', 'url' => asset('images/logo.png')]],
];
@endphp
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
<style>
    .glassmorphic-header {
        background-color: rgba(15, 118, 110, 0.3) !important; /* Translucent Kemenkes Teal brand-700 fallback */
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
        border-bottom: 1px solid rgba(15, 118, 110, 0.15);
    }
</style>

{{-- Page Header --}}
<section class="glassmorphic-header relative z-10 py-12">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <nav class="mb-3 flex text-sm text-teal-850 dark:text-brand-200" aria-label="Breadcrumb">
            <ol class="flex items-center gap-1.5" role="list">
                <li><a href="{{ route('home') }}" class="hover:text-teal-950 dark:hover:text-white transition-colors">Beranda</a></li>
                <li aria-hidden="true"><span>/</span></li>
                <li><a href="{{ route('berita') }}" class="hover:text-teal-950 dark:hover:text-white transition-colors">Berita</a></li>
                <li aria-hidden="true"><span>/</span></li>
                <li class="text-teal-950 dark:text-white truncate max-w-xs" aria-current="page">{{ Str::limit($berita->judul, 40) }}</li>
            </ol>
        </nav>

        <span class="inline-flex rounded-full bg-white/20 px-3 py-1 text-xs font-medium text-white capitalize">
            {{ $berita->kategori }}
        </span>
        <h1 class="mt-3 text-3xl font-bold leading-tight text-teal-950 dark:text-white lg:text-4xl">{{ $berita->judul }}</h1>

        <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-teal-850 dark:text-brand-200">
            <span>
                <time datetime="{{ $berita->published_at?->toDateString() }}">{{ $berita->tanggal_format }}</time>
            </span>
            @if ($berita->penulis)
            <span>Oleh <strong class="text-teal-950 dark:text-white">{{ $berita->penulis->name }}</strong></span>
            @endif
            <span>{{ number_format($berita->views) }} dibaca</span>
        </div>
    </div>
</section>

<div class="bg-teal-50/20 dark:bg-gray-950/50 py-12">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="lg:grid lg:grid-cols-3 lg:gap-10">

            {{-- Konten Artikel --}}
            <article class="lg:col-span-2">
                @if ($berita->gambar_sampul)
                <figure class="mb-8 overflow-hidden rounded-2xl border border-gray-250/20 dark:border-gray-800/35">
                    <img src="{{ \App\Support\MediaStorage::url($berita->gambar_sampul) }}"
                         alt="{{ $berita->gambar_sampul_alt ?? $berita->judul }}"
                         class="w-full object-cover"
                         width="800" height="450">
                </figure>
                @endif

                {{-- Isi Artikel --}}
                <div class="prose prose-lg max-w-none dark:prose-invert prose-headings:text-gray-900 dark:prose-headings:text-white prose-a:text-brand-600 dark:prose-a:text-brand-400 prose-img:rounded-xl">
                    {!! $berita->isi !!}
                </div>

                {{-- Tags --}}
                @if ($berita->tags)
                <div class="mt-8 flex flex-wrap gap-2" aria-label="Tag artikel">
                    @foreach ($berita->tags as $tag)
                    <span class="rounded-full bg-white/40 dark:bg-gray-900/40 border border-gray-250/15 dark:border-gray-800/30 px-3 py-1 text-xs text-gray-650 dark:text-gray-300"># {{ $tag }}</span>
                    @endforeach
                </div>
                @endif

                {{-- Share --}}
                <div class="mt-8 flex items-center gap-3 border-t border-gray-250/20 dark:border-gray-800/40 pt-6" x-data>
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Bagikan:</span>
                    <a href="https://wa.me/?text={{ urlencode($berita->judul . ' ' . url()->current()) }}"
                       target="_blank" rel="noopener noreferrer"
                       class="rounded-full bg-green-500 p-2 text-white hover:bg-green-600 transition-colors"
                       aria-label="Bagikan via WhatsApp">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    </a>
                    <button @click="navigator.share ? navigator.share({title: '{{ addslashes($berita->judul) }}', url: window.location.href}) : navigator.clipboard.writeText(window.location.href)"
                             class="rounded-full bg-white/40 dark:bg-gray-900/40 border border-gray-250/15 dark:border-gray-800/30 px-3 py-1.5 text-xs text-gray-650 dark:text-gray-300 hover:bg-gray-200/50 dark:hover:bg-gray-850/50 transition-colors">
                        Salin Link
                    </button>
                </div>
            </article>

            {{-- Sidebar: Berita Terkait --}}
            <aside class="mt-10 lg:mt-0" aria-label="Berita terkait">
                <h2 class="font-semibold text-gray-900 dark:text-white">Berita Terkait</h2>
                <ul class="mt-4 space-y-4" role="list">
                    @forelse ($terkait as $item)
                    <li class="group flex gap-3">
                        <a href="{{ route('berita.show', $item->slug) }}"
                           class="shrink-0 h-16 w-16 overflow-hidden rounded-lg bg-brand-100 dark:bg-brand-950/20 border border-gray-250/15 dark:border-gray-800/30 block">
                            @if ($item->gambar_sampul)
                                <img src="{{ \App\Support\MediaStorage::url($item->gambar_sampul) }}"
                                     alt="{{ $item->judul }}" loading="lazy"
                                     class="h-full w-full object-cover">
                            @endif
                        </a>
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-brand-700 dark:group-hover:text-brand-400 transition-colors line-clamp-2">
                                <a href="{{ route('berita.show', $item->slug) }}">{{ $item->judul }}</a>
                            </h3>
                            <time class="mt-1 text-xs text-gray-450 dark:text-gray-500" datetime="{{ $item->published_at?->toDateString() }}">
                                {{ $item->tanggal_format }}
                            </time>
                        </div>
                    </li>
                    @empty
                    <li class="text-sm text-gray-400">Tidak ada berita terkait.</li>
                    @endforelse
                </ul>
            </aside>
        </div>
    </div>
</div>

@endsection
