@extends('layouts.app')

@section('title', 'Portal Mahasiswa')
@section('description', 'Ruang publik Portal Mahasiswa Poltekkes Bandung untuk mengakses organisasi mahasiswa, BEM KM, HIMA jurusan, dan pintu masuk ke portal portofolio mahasiswa.')

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
</style>

<section class="bg-gradient-to-br from-brand-900 via-slate-900 to-slate-950 py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <nav class="mb-3 flex text-sm text-brand-200" aria-label="Breadcrumb">
            <ol class="flex items-center gap-1.5" role="list">
                <li><a href="{{ route('home') }}" class="transition-colors hover:text-white">Beranda</a></li>
                <li aria-hidden="true"><span>/</span></li>
                <li class="text-white" aria-current="page">Portal Mahasiswa</li>
            </ol>
        </nav>

        <div class="grid gap-8 lg:grid-cols-[minmax(0,1.35fr)_minmax(20rem,0.85fr)] lg:items-end">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-brand-200">Ekosistem Mahasiswa</p>
                <h1 class="mt-3 max-w-4xl text-3xl font-black tracking-tight text-white sm:text-5xl">Portal Mahasiswa untuk Akses Organisasi, Aktivitas Kemahasiswaan, dan Portofolio</h1>
                <p class="mt-4 max-w-3xl text-sm leading-7 text-brand-100 sm:text-base">Halaman ini menjadi gerbang publik untuk melihat halaman BEM KM Poltekkes Bandung, HIMA tiap jurusan, dan mengarahkan mahasiswa ke portal login untuk mengelola portofolio.</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('mahasiswa.login') }}" class="inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-brand-800 transition hover:bg-brand-50">Masuk Portal Mahasiswa</a>
                    <a href="#organisasi-mahasiswa" class="inline-flex items-center justify-center rounded-full border border-white/20 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">Lihat Organisasi Mahasiswa</a>
                </div>
            </div>
            <div class="rounded-3xl border border-white/10 bg-white/10 p-6 text-white backdrop-blur-sm">
                <h2 class="text-lg font-black">Akses cepat mahasiswa</h2>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-brand-100" role="list">
                    <li>Login untuk mengisi portofolio, capaian, dan profil mahasiswa</li>
                    <li>Lihat halaman BEM KM Poltekkes Bandung dan HIMA jurusan</li>
                    <li>Gunakan Pojok Alumni untuk tracer study setelah lulus</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section id="organisasi-mahasiswa" class="bg-teal-50/20 dark:bg-gray-950/50 py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-8 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
            <div class="glassmorphic-card rounded-3xl border border-slate-250/20 dark:border-gray-800/35 p-6 shadow-sm sm:p-8">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400">Portal Login</p>
                <h2 class="mt-3 text-2xl font-black text-slate-950 dark:text-white">Masuk untuk melengkapi portofolio mahasiswa</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-gray-300">Gunakan akun mahasiswa untuk memperbarui profil, mengisi portofolio, and menyiapkan rekam jejak yang nantinya berguna untuk tracer study alumni.</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('mahasiswa.login') }}" class="inline-flex items-center justify-center rounded-full bg-brand-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-800">Login Portal Mahasiswa</a>
                    <a href="{{ route('mahasiswa.register') }}" class="inline-flex items-center justify-center rounded-full border border-brand-200 px-5 py-3 text-sm font-semibold text-brand-700 dark:text-brand-400 transition hover:bg-brand-50 dark:hover:bg-brand-900/30">Registrasi Akun</a>
                </div>
            </div>

            <div class="space-y-6">
                <div class="glassmorphic-card rounded-3xl border border-slate-250/20 dark:border-gray-800/35 p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400">Organisasi Utama</p>
                            <h2 class="mt-3 text-xl font-black text-slate-950 dark:text-white">BEM KM Poltekkes Bandung</h2>
                        </div>
                        @if ($bemPage)
                        <a href="{{ url($bemPage->publicPath()) }}" class="inline-flex items-center justify-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-brand-700 dark:text-brand-400 transition hover:bg-brand-50 dark:hover:bg-brand-900/30">Buka Halaman</a>
                        @endif
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-gray-300">Informasi kepengurusan, program kerja, dan aktivitas mahasiswa tingkat institusi dapat dipublikasikan dari halaman resmi BEM KM.</p>
                </div>

                <div x-data="{ viewMode: localStorage.getItem('hima_view_mode') || 'grid', switchViewMode(mode) { this.viewMode = mode; localStorage.setItem('hima_view_mode', mode); } }" class="glassmorphic-card rounded-3xl border border-slate-250/20 dark:border-gray-800/35 p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400">Himpunan Mahasiswa</p>
                            <h2 class="mt-1 text-xl font-black text-slate-950 dark:text-white">HIMA masing-masing jurusan</h2>
                        </div>
                        <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-gray-800 p-1 rounded-2xl border border-slate-200 dark:border-gray-700 shrink-0">
                            <button type="button" 
                                    @click="switchViewMode('grid')"
                                    :class="viewMode === 'grid' ? 'bg-brand-600 text-white font-extrabold shadow-sm' : 'text-slate-600 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-700 font-semibold'"
                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs transition-all cursor-pointer">
                                <span>🧱</span> Grid
                            </button>
                            <button type="button" 
                                    @click="switchViewMode('list')"
                                    :class="viewMode === 'list' ? 'bg-brand-600 text-white font-extrabold shadow-sm' : 'text-slate-600 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-700 font-semibold'"
                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs transition-all cursor-pointer">
                                <span>📋</span> List
                            </button>
                        </div>
                    </div>

                    <!-- GRID VIEW MODE -->
                    <div x-show="viewMode === 'grid'" class="mt-5 grid gap-4 md:grid-cols-2">
                        @forelse ($himaPages as $item)
                        <article class="rounded-2xl border border-slate-250/15 dark:border-gray-800/30 bg-white/40 dark:bg-slate-900/40 p-4">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $item['label'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-gray-300">Halaman publik resmi untuk informasi kegiatan, kepengurusan, dan komunikasi mahasiswa jurusan.</p>
                            <a href="{{ url($item['page']->publicPath()) }}" class="mt-4 inline-flex items-center justify-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-brand-700 dark:text-brand-400 transition hover:bg-brand-50 dark:hover:bg-brand-900/30">Buka Halaman</a>
                        </article>
                        @empty
                        <p class="text-sm text-slate-500 dark:text-gray-400">Halaman HIMA belum tersedia.</p>
                        @endforelse
                    </div>

                    <!-- LIST VIEW MODE -->
                    <div x-show="viewMode === 'list'" class="mt-5 divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($himaPages as $item)
                        <div class="py-3 flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $item['label'] }}</h3>
                                <p class="text-xs text-slate-500 dark:text-gray-400">HIMA Jurusan Poltekkes Bandung</p>
                            </div>
                            <a href="{{ url($item['page']->publicPath()) }}" class="inline-flex h-8 items-center justify-center rounded-xl bg-brand-600 px-3.5 text-xs font-bold text-white shadow-sm hover:bg-brand-500 transition-colors shrink-0">
                                Buka Halaman
                            </a>
                        </div>
                        @empty
                        <p class="text-sm text-slate-500 dark:text-gray-400">Halaman HIMA belum tersedia.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection