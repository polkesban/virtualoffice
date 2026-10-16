@extends('layouts.app')

@section('title', 'Struktur Organisasi')
@section('description', 'Struktur Organisasi Politeknik Kesehatan Kemenkes Bandung.')

@section('content')
<div class="bg-white dark:bg-gray-950 min-h-screen">

    {{-- Hero Banner --}}
    <div class="bg-gradient-to-br from-brand-800 to-brand-950 py-16">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-[11px] font-black uppercase tracking-widest text-brand-300 mb-2">Tentang Kami</p>
            <h1 class="text-3xl sm:text-4xl font-black text-white">Struktur Organisasi</h1>
            <nav class="mt-4 flex justify-center gap-1 text-sm text-brand-300" aria-label="Breadcrumb">
                <a href="{{ url('/') }}" class="hover:text-white transition-colors">Beranda</a>
                <span class="text-brand-600 mx-1">/</span>
                <span class="text-white">Struktur Organisasi</span>
            </nav>
        </div>
    </div>

    {{-- Sub-Navigation --}}
    <div class="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <nav class="flex gap-0 overflow-x-auto scrollbar-none" aria-label="Navigasi profil">
                @php $tabs = [
                    ['href' => '/profil/sejarah',    'label' => 'Sejarah'],
                    ['href' => '/profil/visi-misi',  'label' => 'Visi & Misi'],
                    ['href' => '/profil/struktur',   'label' => 'Struktur Organisasi'],
                    ['href' => '/profil/akreditasi', 'label' => 'Akreditasi'],
                ]; @endphp
                @foreach($tabs as $tab)
                <a href="{{ url($tab['href']) }}"
                   class="whitespace-nowrap px-4 py-3 text-sm font-semibold border-b-2 transition-colors
                          {{ request()->is(ltrim($tab['href'], '/'))
                             ? 'border-brand-600 text-brand-700 dark:text-brand-400'
                             : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-gray-200' }}">
                    {{ $tab['label'] }}
                </a>
                @endforeach
            </nav>
        </div>
    </div>

    {{-- Content --}}
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-12">

        <p class="text-gray-500 dark:text-gray-400 mb-8 text-center">
            Struktur organisasi Politeknik Kesehatan Kemenkes Bandung sesuai Peraturan Menteri Kesehatan yang berlaku.
        </p>

        {{-- Org Chart (visual cards) --}}
        <div class="space-y-6">

            {{-- Direktur --}}
            <div class="flex justify-center">
                <div class="rounded-2xl border-2 border-brand-600 bg-brand-600 text-white px-8 py-4 text-center shadow-lg shadow-brand-900/20 w-64">
                    <p class="text-xs font-black uppercase tracking-wider text-brand-200">Pimpinan</p>
                    <p class="text-base font-extrabold mt-1">Direktur</p>
                </div>
            </div>

            <div class="flex justify-center"><div class="w-0.5 h-6 bg-gray-300 dark:bg-gray-700"></div></div>

            {{-- Wakil Direktur --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @php $wakdir = [
                    ['title' => 'Wakil Direktur I', 'sub' => 'Bidang Akademik & Kemahasiswaan'],
                    ['title' => 'Wakil Direktur II', 'sub' => 'Bidang Administrasi Umum'],
                    ['title' => 'Wakil Direktur III', 'sub' => 'Bidang Kemahasiswaan'],
                ]; @endphp
                @foreach($wakdir as $w)
                <div class="rounded-2xl border border-brand-200 dark:border-brand-800 bg-brand-50 dark:bg-brand-950 px-5 py-4 text-center">
                    <p class="text-xs font-black uppercase tracking-wider text-brand-500 dark:text-brand-400">{{ $w['title'] }}</p>
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mt-1">{{ $w['sub'] }}</p>
                </div>
                @endforeach
            </div>

            <div class="flex justify-center"><div class="w-0.5 h-6 bg-gray-300 dark:bg-gray-700"></div></div>

            {{-- Pusat & Unit --}}
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-gray-400 text-center mb-4">Pusat & Unit Kerja</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    @php $units = [
                        'Pusat Penjaminan Mutu',
                        'Pusat Pengembangan Pendidikan',
                        'Pusat Penelitian & Pengabmas',
                        'Unit Teknologi & Informasi',
                        'Unit Laboratorium Terpadu',
                        'Unit Perpustakaan Terpadu',
                        'Satuan Pengawas Internal',
                        'Unit Bisnis',
                        'Unit Asrama',
                    ]; @endphp
                    @foreach($units as $u)
                    <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900 px-3 py-3 text-center">
                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 leading-snug">{{ $u }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-center"><div class="w-0.5 h-6 bg-gray-300 dark:bg-gray-700"></div></div>

            {{-- Jurusan --}}
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-gray-400 text-center mb-4">Jurusan / Departemen</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    @php $jurusan = [
                        ['nama' => 'Keperawatan Bandung', 'prodi' => ['D-III', 'D-IV', 'Profesi Ners']],
                        ['nama' => 'Keperawatan Bogor',   'prodi' => ['D-III']],
                        ['nama' => 'Kebidanan Bandung',   'prodi' => ['D-III', 'D-IV', 'Profesi Bidan']],
                        ['nama' => 'Kebidanan Karawang',  'prodi' => ['D-III']],
                        ['nama' => 'Kebidanan Bogor',     'prodi' => ['D-III']],
                        ['nama' => 'Gizi',                'prodi' => ['D-IV', 'Profesi Dietisien']],
                        ['nama' => 'Kesehatan Gigi',      'prodi' => ['D-III', 'D-IV']],
                        ['nama' => 'Kesehatan Lingkungan','prodi' => ['D-III', 'D-IV']],
                        ['nama' => 'TLM',                 'prodi' => ['D-III', 'D-IV']],
                        ['nama' => 'Farmasi',             'prodi' => ['D-III']],
                        ['nama' => 'Promosi Kesehatan',   'prodi' => ['D-IV']],
                        ['nama' => 'Rekam Medis & Info',  'prodi' => ['D-III']],
                    ]; @endphp
                    @foreach($jurusan as $j)
                    <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3">
                        <p class="text-xs font-bold text-gray-900 dark:text-white leading-snug">{{ $j['nama'] }}</p>
                        <div class="mt-1 flex flex-wrap gap-1">
                            @foreach($j['prodi'] as $p)
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-brand-50 dark:bg-brand-950 text-brand-600 dark:text-brand-400">{{ $p }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>

        <p class="mt-10 text-xs text-gray-400 text-center">
            Struktur sesuai Peraturan Menteri Kesehatan RI No. 38 Tahun 2018 tentang Organisasi dan Tata Kerja Politeknik Kesehatan di Lingkungan Badan PPSDM Kesehatan Kemenkes RI.
        </p>

    </div>
</div>
@endsection
