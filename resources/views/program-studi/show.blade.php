@extends('layouts.app')

@section('title', $prodi->nama)
@section('description', $prodi->deskripsi_singkat ?? 'Program studi ' . $prodi->nama . ' di Poltekkes Bandung — terakreditasi ' . $prodi->akreditasi)
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

<section class="glassmorphic-header relative z-10 py-14">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <nav class="mb-4 flex text-sm text-teal-850 dark:text-brand-200" aria-label="Breadcrumb">
            <ol class="flex items-center gap-1.5" role="list">
                <li><a href="{{ route('home') }}" class="hover:text-teal-950 dark:hover:text-white transition-colors">Beranda</a></li>
                <li aria-hidden="true"><span>/</span></li>
                <li><a href="{{ route('program-studi') }}" class="hover:text-teal-950 dark:hover:text-white transition-colors">Program Studi</a></li>
                <li aria-hidden="true"><span>/</span></li>
                <li class="text-teal-950 dark:text-white" aria-current="page">{{ $prodi->nama }}</li>
            </ol>
        </nav>

        <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center">
            <span class="text-5xl" aria-hidden="true">{{ $prodi->icon_emoji ?? '🎓' }}</span>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-teal-600/20 dark:bg-white/20 px-3 py-1 text-xs font-semibold text-teal-900 dark:text-white">{{ $prodi->jenjang }}</span>
                    <span class="rounded-full bg-green-400/20 px-3 py-1 text-xs font-semibold text-green-800 dark:text-green-200">Akreditasi {{ $prodi->akreditasi }}</span>
                </div>
                <h1 class="mt-2 text-3xl font-extrabold text-teal-950 dark:text-white">{{ $prodi->nama }}</h1>
                @if ($prodi->gelar)
                <p class="mt-1 text-teal-850 dark:text-brand-200">Gelar: {{ $prodi->gelar }}</p>
                @endif
            </div>
        </div>
    </div>
</section>

<div class="bg-teal-50/20 dark:bg-gray-950/50 py-14">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="lg:grid lg:grid-cols-3 lg:gap-12">

            {{-- Konten Utama --}}
            <div class="lg:col-span-2">
                {{-- Quick Stats --}}
                <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3 mb-10">
                    @php $prodiStats = [
                        ['label' => 'Durasi', 'nilai' => $prodi->durasi_semester . ' Semester'],
                        ['label' => 'Kuota/Tahun', 'nilai' => ($prodi->kuota ? $prodi->kuota . ' Mhs' : '—')],
                        ['label' => 'Akreditasi', 'nilai' => $prodi->akreditasi],
                    ]; @endphp
                    @foreach ($prodiStats as $stat)
                    <div class="rounded-xl border border-brand-200/20 dark:border-brand-800/20 bg-brand-50/50 dark:bg-brand-950/20 p-4 text-center">
                        <dt class="text-xs text-brand-600 dark:text-brand-400 font-medium">{{ $stat['label'] }}</dt>
                        <dd class="mt-1 text-xl font-extrabold text-brand-800 dark:text-brand-300">{{ $stat['nilai'] }}</dd>
                    </div>
                    @endforeach
                </dl>

                {{-- Deskripsi --}}
                @if ($prodi->deskripsi)
                <div class="prose prose-lg max-w-none dark:prose-invert prose-headings:text-gray-900 dark:prose-headings:text-white prose-a:text-brand-600 dark:prose-a:text-brand-400">
                    {!! $prodi->deskripsi !!}
                </div>
                @else
                <p class="text-gray-500 dark:text-gray-400">Informasi detail program studi sedang diperbarui.</p>
                @endif

                {{-- CTA Daftar --}}
                <div class="mt-10 rounded-2xl bg-brand-50/50 dark:bg-brand-950/20 border border-brand-200/20 dark:border-brand-800/20 p-6">
                    <h2 class="text-lg font-bold text-brand-800 dark:text-brand-300">Tertarik bergabung?</h2>
                    <p class="mt-1 text-sm text-brand-700 dark:text-brand-400">Daftarkan dirimu sebelum kuota habis!</p>
                           <a href="{{ route('penerimaan') }}"
                               target="_blank" rel="noopener noreferrer"
                        class="mt-4 inline-flex items-center gap-2 rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 transition-colors">
                        Daftar Sekarang
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Sidebar: Program Lainnya --}}
            <aside class="mt-12 lg:mt-0" aria-label="Program studi lainnya">
                <h2 class="font-semibold text-gray-900 dark:text-white">Program Studi Lainnya</h2>
                <ul class="mt-4 space-y-3" role="list">
                    @foreach ($lainnya as $item)
                    <li>
                        <a href="{{ route('program-studi.show', $item->slug) }}"
                           class="group flex items-center gap-3 rounded-xl border border-gray-250/20 dark:border-gray-800/35 bg-white/40 dark:bg-gray-900/40 p-3 hover:border-brand-200 dark:hover:border-brand-800 hover:bg-brand-50/50 dark:hover:bg-brand-950/20 transition-colors">
                            <span class="text-2xl" aria-hidden="true">{{ $item->icon_emoji ?? '🎓' }}</span>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-brand-700 dark:group-hover:text-brand-400 transition-colors">{{ $item->nama }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $item->jenjang }} · {{ $item->akreditasi }}</p>
                            </div>
                        </a>
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('program-studi') }}" class="mt-4 block text-center text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-305 transition-colors">
                    Lihat Semua Program →
                </a>
            </aside>
        </div>
    </div>
</div>

{{-- Page builder sections from kaprodi --}}
@if($prodi->unitPage && $prodi->unitPage->sections->isNotEmpty())
<div class="py-2">
    @foreach($prodi->unitPage->sections as $section)
        @includeIf('unit.blocks.' . $section->tipe, ['section' => $section, 'cfg' => $section->konfigurasi ?? []])
    @endforeach
</div>
@endif

@endsection
