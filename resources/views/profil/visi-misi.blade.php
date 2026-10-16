@extends('layouts.app')

@section('title', 'Visi & Misi')
@section('description', 'Visi dan Misi Politeknik Kesehatan Kemenkes Bandung — Menjadi Perguruan Tinggi Kesehatan yang Unggul dalam Teknologi Terapan.')

@section('content')
<div class="bg-white dark:bg-gray-950 min-h-screen">

    {{-- Hero Banner --}}
    <div class="bg-gradient-to-br from-brand-800 to-brand-950 py-16">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-[11px] font-black uppercase tracking-widest text-brand-300 mb-2">Tentang Kami</p>
            <h1 class="text-3xl sm:text-4xl font-black text-white">Visi & Misi</h1>
            <nav class="mt-4 flex justify-center gap-1 text-sm text-brand-300" aria-label="Breadcrumb">
                <a href="{{ url('/') }}" class="hover:text-white transition-colors">Beranda</a>
                <span class="text-brand-600 mx-1">/</span>
                <span class="text-white">Visi & Misi</span>
            </nav>
        </div>
    </div>

    {{-- Sub-Navigation --}}
    <div class="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
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
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12 space-y-12">

        {{-- VISI --}}
        <div class="rounded-3xl overflow-hidden border border-gray-100 dark:border-gray-800">
            <div class="bg-gradient-to-r from-brand-700 to-brand-900 px-8 py-5">
                <h2 class="text-lg font-extrabold text-white uppercase tracking-wider">VISI</h2>
            </div>
            <div class="p-8 bg-white dark:bg-gray-900">
                <blockquote class="text-xl font-bold text-gray-900 dark:text-white leading-relaxed text-center italic">
                    "Menjadi Perguruan Tinggi Kesehatan yang Unggul dalam Teknologi Terapan, Berkarakter dan Berdaya Saing Global"
                </blockquote>
            </div>
        </div>

        {{-- MISI --}}
        <div class="rounded-3xl overflow-hidden border border-gray-100 dark:border-gray-800">
            <div class="bg-gradient-to-r from-brand-700 to-brand-900 px-8 py-5">
                <h2 class="text-lg font-extrabold text-white uppercase tracking-wider">MISI</h2>
            </div>
            <div class="p-8 bg-white dark:bg-gray-900">
                <ol class="space-y-4">
                    @php $misi = [
                        'Menyelenggarakan pendidikan berkualitas, berorientasi global sesuai dengan perkembangan ilmu dan teknologi terapan bidang kesehatan untuk menghasilkan lulusan berkarakter.',
                        'Menyelenggarakan penelitian yang inovatif berbasis teknologi terapan dengan mengembangkan potensi bahan dan kearifan lokal serta pemberdayaan masyarakat.',
                        'Menyelenggarakan pengabdian masyarakat yang inovatif yang berdampak luas melalui pemberdayaan masyarakat dengan berbasis hasil penelitian dalam rangka penguatan pembelajaran yang berkualitas.',
                        'Menyelenggarakan kerjasama dengan pemangku kepentingan baik tingkat nasional maupun internasional untuk kemajuan bersama.',
                        'Mengembangkan pendidikan dan program studi baru untuk memenuhi perkembangan IPTEK bidang kesehatan dalam rangka tuntutan pelayanan Kesehatan.',
                        'Mengembangkan sumber daya yang berintegritas dan berkarakter dalam rangka terselenggaranya tata kelola yang baik dan bersih dengan menerapkan prinsip transparan dan akuntabel sebagai BLU pendidikan.',
                        'Mengimplementasikan transformasi kesehatan melalui sentra unggulan pendidikan diabetes mellitus pada kegiatan Tri Dharma Perguruan Tinggi.',
                    ]; @endphp
                    @foreach($misi as $i => $m)
                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-7 h-7 rounded-full bg-brand-100 dark:bg-brand-950 text-brand-700 dark:text-brand-300
                                     flex items-center justify-center text-sm font-black">{{ $i + 1 }}</span>
                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ $m }}</p>
                    </li>
                    @endforeach
                </ol>
            </div>
        </div>

        {{-- TUJUAN --}}
        <div class="rounded-3xl overflow-hidden border border-gray-100 dark:border-gray-800">
            <div class="bg-gradient-to-r from-brand-700 to-brand-900 px-8 py-5">
                <h2 class="text-lg font-extrabold text-white uppercase tracking-wider">TUJUAN</h2>
            </div>
            <div class="p-8 bg-white dark:bg-gray-900">
                <ol class="space-y-4">
                    @php $tujuan = [
                        'Menghasilkan lulusan tenaga kesehatan yang kompeten, berkarakter, dan berdaya saing global.',
                        'Menghasilkan penelitian inovatif berbasis teknologi terapan yang berkontribusi pada pembangunan kesehatan nasional.',
                        'Melaksanakan pengabdian kepada masyarakat yang berdampak nyata terhadap peningkatan derajat kesehatan.',
                        'Membangun kemitraan strategis nasional dan internasional untuk pengembangan institusi.',
                        'Mengembangkan tata kelola institusi yang transparan, akuntabel, dan berorientasi pelayanan prima.',
                    ]; @endphp
                    @foreach($tujuan as $i => $t)
                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-7 h-7 rounded-full bg-accent-100 dark:bg-accent-950 text-accent-700 dark:text-accent-300
                                     flex items-center justify-center text-sm font-black">{{ $i + 1 }}</span>
                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ $t }}</p>
                    </li>
                    @endforeach
                </ol>
            </div>
        </div>

        {{-- NILAI UTAMA --}}
        <div>
            <h2 class="text-xl font-extrabold text-gray-900 dark:text-white mb-6">Nilai Utama</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                @php $nilai = [
                    ['icon' => '🎯', 'label' => 'Integritas',   'desc' => 'Jujur, transparan & bertanggung jawab'],
                    ['icon' => '🌟', 'label' => 'Profesional',  'desc' => 'Kompeten & berorientasi mutu'],
                    ['icon' => '🤝', 'label' => 'Kolaboratif',  'desc' => 'Sinergi untuk kemajuan bersama'],
                    ['icon' => '💡', 'label' => 'Inovatif',     'desc' => 'Adaptif terhadap perkembangan IPTEK'],
                    ['icon' => '🌿', 'label' => 'Berwawasan',   'desc' => 'Peduli lingkungan & sosial'],
                    ['icon' => '🏆', 'label' => 'Berdaya Saing','desc' => 'Unggul di level nasional & global'],
                ]; @endphp
                @foreach($nilai as $n)
                <div class="rounded-2xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900 p-5 text-center">
                    <div class="text-3xl mb-2" aria-hidden="true">{{ $n['icon'] }}</div>
                    <p class="font-bold text-gray-900 dark:text-white text-sm">{{ $n['label'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $n['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</div>
@endsection
