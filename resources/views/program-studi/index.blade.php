@extends('layouts.app')

@section('title', 'Program Studi')
@section('description', 'Pilihan program studi D3, D4, dan Profesi di Politeknik Kesehatan Kemenkes Bandung — terakreditasi Unggul.')

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
    viewMode: localStorage.getItem('prodi_view_mode') || 'grid',
    switchViewMode(mode) {
        this.viewMode = mode;
        localStorage.setItem('prodi_view_mode', mode);
    }
}">

<section class="glassmorphic-header relative z-10 py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <nav class="mb-3 flex text-sm text-teal-800 dark:text-brand-200" aria-label="Breadcrumb">
            <ol class="flex items-center gap-1.5" role="list">
                <li><a href="{{ route('home') }}" class="hover:text-teal-950 dark:hover:text-white transition-colors">Beranda</a></li>
                <li aria-hidden="true"><span>/</span></li>
                <li class="text-teal-950 dark:text-white" aria-current="page">Program Studi</li>
            </ol>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-teal-950 dark:text-white">Program Studi</h1>
                <p class="mt-2 text-teal-850 dark:text-brand-200">{{ $prodis->flatten()->count() }} program studi terakreditasi</p>
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
                    <span>📋</span> Tampilan List (Per Jurusan)
                </button>
            </div>
        </div>
    </div>
</section>

<section class="bg-teal-50/20 dark:bg-gray-950/50 py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <!-- ===================== GRID VIEW MODE (PER JENJANG) ===================== -->
        <div x-show="viewMode === 'grid'" class="space-y-14">
            @foreach (['D4' => 'Sarjana Terapan (D4)', 'D3' => 'Diploma Tiga (D3)', 'Profesi' => 'Program Profesi'] as $jenjang => $label)
            @if (isset($prodis[$jenjang]))
            <div>
                <div class="flex items-center gap-3 mb-6">
                    <span class="rounded-full bg-brand-600 px-4 py-1 text-sm font-bold text-white">{{ $jenjang }}</span>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $label }}</h2>
                </div>
                <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" role="list">
                    @foreach ($prodis[$jenjang] as $prodi)
                    <li>
                        <a href="{{ route('program-studi.show', $prodi->slug) }}"
                           class="group glassmorphic-card flex h-full flex-col rounded-2xl border border-gray-250/20 dark:border-gray-800/35 p-6 shadow-sm transition hover:border-brand-300 dark:hover:border-brand-800 hover:shadow-md">
                            <div class="flex items-start justify-between">
                                <span class="text-4xl" aria-hidden="true">{{ $prodi->icon_emoji ?? '🎓' }}</span>
                                <span class="rounded-full bg-green-50 dark:bg-green-950/30 px-2.5 py-0.5 text-[10px] font-semibold text-green-700 dark:text-green-400">
                                    {{ $prodi->akreditasi }}
                                </span>
                            </div>
                            <h3 class="mt-3 font-semibold text-gray-900 dark:text-white group-hover:text-brand-700 dark:group-hover:text-brand-400 transition-colors">
                                {{ $prodi->nama }}
                            </h3>
                            @if ($prodi->gelar)
                            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ $prodi->gelar }}</p>
                            @endif
                            @if ($prodi->deskripsi_singkat)
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 line-clamp-3">{{ $prodi->deskripsi_singkat }}</p>
                            @endif
                            <div class="mt-auto flex items-center justify-between pt-4 text-xs text-gray-450 dark:text-gray-500">
                                <span>⏱ {{ $prodi->durasi_semester }} semester</span>
                                @if($prodi->kuota)
                                <span>{{ $prodi->kuota }} Kursi/Tahun</span>
                                @endif
                            </div>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
            @endforeach
        </div>

        <!-- ===================== LIST VIEW MODE (PER JURUSAN) ===================== -->
        <div x-show="viewMode === 'list'" class="space-y-10">
            @foreach ($jurusans as $jurusan)
            @if ($jurusan->programStudis->count() > 0)
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-6 sm:p-8 shadow-sm">
                <!-- Jurusan Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-100 dark:border-gray-800 pb-5 mb-6">
                    <div class="flex items-center gap-3">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 text-2xl shrink-0">🎓</span>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex rounded-full bg-purple-50 dark:bg-purple-950/40 px-2.5 py-0.5 text-[10px] font-bold text-purple-700 dark:text-purple-300 uppercase tracking-wide">JURUSAN</span>
                                <span class="text-xs font-semibold text-gray-400 dark:text-gray-500">{{ $jurusan->programStudis->count() }} Program Studi</span>
                            </div>
                            <h2 class="text-xl font-black text-gray-900 dark:text-white mt-1">{{ $jurusan->nama }}</h2>
                        </div>
                    </div>
                    @if ($jurusan->programStudi)
                    <a href="{{ route('unit.show', $jurusan->programStudi->slug ?? $jurusan->slug) }}" 
                       class="inline-flex h-9 items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 px-4 text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors self-start sm:self-auto shrink-0">
                        Buka Halaman Jurusan
                    </a>
                    @endif
                </div>

                <!-- Child Program Studi List under Jurusan -->
                <div class="divide-y divide-gray-100 dark:divide-gray-800/60">
                    @foreach ($jurusan->programStudis as $prodi)
                    <div class="py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 hover:bg-gray-50/50 dark:hover:bg-gray-800/40 px-3 rounded-2xl transition-colors">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-lg" aria-hidden="true">{{ $prodi->icon_emoji ?? '🎓' }}</span>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                    <a href="{{ route('program-studi.show', $prodi->slug) }}" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">
                                        {{ $prodi->nama }}
                                    </a>
                                </h3>
                                <span class="rounded-full bg-brand-50 dark:bg-brand-950/40 px-2.5 py-0.5 text-[10px] font-bold text-brand-700 dark:text-brand-300">
                                    {{ $prodi->jenjang }}
                                </span>
                                <span class="rounded-full bg-green-50 dark:bg-green-950/30 px-2.5 py-0.5 text-[10px] font-semibold text-green-700 dark:text-green-400">
                                    {{ $prodi->akreditasi }}
                                </span>
                            </div>
                            @if ($prodi->deskripsi_singkat)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ $prodi->deskripsi_singkat }}</p>
                            @endif
                            <div class="mt-2 flex flex-wrap items-center gap-4 text-xs text-gray-400 dark:text-gray-500">
                                <span>⏱ Durasi: {{ $prodi->durasi_semester }} semester</span>
                                @if($prodi->kuota)
                                <span>👥 Kuota: {{ $prodi->kuota }} Kursi/Tahun</span>
                                @endif
                                @if($prodi->gelar)
                                <span>🎓 Gelar: {{ $prodi->gelar }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="shrink-0">
                            <a href="{{ route('program-studi.show', $prodi->slug) }}" 
                               class="inline-flex h-9 items-center justify-center rounded-xl bg-brand-600 hover:bg-brand-500 px-4 text-xs font-bold text-white shadow-sm transition-colors">
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            @endforeach

            <!-- Unassigned Prodis if any -->
            @if ($unassignedProdis->count() > 0)
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 border-b border-gray-100 dark:border-gray-800 pb-4 mb-6">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-600 text-lg">📚</span>
                    <h2 class="text-xl font-black text-gray-900 dark:text-white">Program Studi Lainnya</h2>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800/60">
                    @foreach ($unassignedProdis as $prodi)
                    <div class="py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 hover:bg-gray-50/50 dark:hover:bg-gray-800/40 px-3 rounded-2xl transition-colors">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-lg" aria-hidden="true">{{ $prodi->icon_emoji ?? '🎓' }}</span>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                    <a href="{{ route('program-studi.show', $prodi->slug) }}" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">
                                        {{ $prodi->nama }}
                                    </a>
                                </h3>
                                <span class="rounded-full bg-brand-50 dark:bg-brand-950/40 px-2.5 py-0.5 text-[10px] font-bold text-brand-700 dark:text-brand-300">
                                    {{ $prodi->jenjang }}
                                </span>
                            </div>
                        </div>
                        <div class="shrink-0">
                            <a href="{{ route('program-studi.show', $prodi->slug) }}" 
                               class="inline-flex h-9 items-center justify-center rounded-xl bg-brand-600 hover:bg-brand-500 px-4 text-xs font-bold text-white shadow-sm transition-colors">
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

    </div>
</section>

</div>
@endsection
