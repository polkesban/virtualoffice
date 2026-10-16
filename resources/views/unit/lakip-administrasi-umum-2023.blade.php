@extends('layouts.app')

@section('title', 'LAKIP 2023')
@section('description', 'Laporan Kinerja Instansi Pemerintah (LAKIP) Poltekkes Kemenkes Bandung Tahun 2023 dalam format HTML yang lengkap, terstruktur, dan mudah dibaca.')

@php
    $quickFacts = [
        ['label' => 'Indikator Utama', 'value' => '19 IKU', 'note' => 'Mencakup tata kelola BLU, SDM, lulusan, riset, pengabdian, dan prestasi.'],
        ['label' => 'Rata-rata Capaian', 'value' => '206,82%', 'note' => 'Sebanyak 18 dari 19 indikator mencapai atau melampaui target.'],
        ['label' => 'Pendapatan BLU', 'value' => 'Rp55,86 M', 'note' => 'Dari target Rp51,25 miliar yang ditetapkan pada tahun 2023.'],
        ['label' => 'Mahasiswa Aktif', 'value' => '4.625', 'note' => 'Mahasiswa Diploma III dan Sarjana Terapan yang tercatat pada tahun 2023.'],
        ['label' => 'Kelulusan UKOM', 'value' => '98,62%', 'note' => 'Melampaui target kelulusan uji kompetensi sebesar 97,15%.'],
        ['label' => 'Prestasi Mahasiswa', 'value' => '121', 'note' => 'Terdiri dari 3 tingkat kabupaten, 5 provinsi, 95 nasional, dan 18 internasional.'],
    ];

    $missions = [
        'Menyelenggarakan pendidikan berkualitas yang berkarakter nasional dan berwawasan global sesuai dengan perkembangan ilmu dan teknologi terapan bidang kesehatan.',
        'Menyelenggarakan penelitian yang inovatif dengan mengembangkan potensi bahan lokal dan berbasis teknologi terapan.',
        'Menyelenggarakan inovasi pengabdian kepada masyarakat melalui pemberdayaan masyarakat berbasis hasil penelitian dalam rangka penguatan pembelajaran yang berkualitas.',
        'Menyelenggarakan kerja sama dengan pemangku kepentingan nasional maupun internasional dengan prinsip saling menguntungkan dan kemajuan bersama.',
        'Membentuk karakter bekerja dengan hati, kepemimpinan, dan entrepreneurship bagi pegawai maupun mahasiswa.',
        'Mengembangkan pendidikan dan program studi baru untuk menjawab tuntutan perkembangan pelayanan kesehatan.',
        'Meningkatkan sumber daya yang berintegritas demi terselenggaranya tata kelola BLU pendidikan yang baik, bersih, transparan, dan akuntabel.',
    ];

    $indicatorGroups = [
        [
            'title' => 'Tata Kelola BLU',
            'items' => [
                'Persentase realisasi pendapatan BLU terhadap biaya operasional',
                'Realisasi pendapatan Poltekkes BLU',
                'Realisasi pendapatan dari optimalisasi aset',
                'Persentase penyelesaian modernisasi BLU',
                'Peta jalan pengembangan Poltekkes',
            ],
        ],
        [
            'title' => 'SDM dan Mutu Pendidikan',
            'items' => [
                'Jumlah dosen yang belum memiliki serdos meski telah 2 tahun jabfung',
                'Jumlah dosen tetap dengan kualifikasi lektor kepala dan/atau guru besar',
                'Persentase kemampuan bahasa Inggris dosen KI level intermediet',
                'Persentase kelulusan uji kompetensi',
                'Penambahan prodi terakreditasi unggul',
            ],
        ],
        [
            'title' => 'Lulusan dan Internasionalisasi',
            'items' => [
                'Respond rate tracer study alumni',
                'Persentase serapan lulusan di fasyankes milik pemerintah',
                'Jumlah lulusan perawat yang diterima bekerja di luar negeri',
                'Penambahan penguasaan bahasa asing selain bahasa Inggris bagi KI',
            ],
        ],
        [
            'title' => 'Riset, Pengabdian, dan Prestasi',
            'items' => [
                'Jumlah penelitian yang diimplementasikan untuk stunting, TB, PM, PTM, dan KIA',
                'Jumlah luaran penelitian yang dapat dimanfaatkan dalam ketahanan kesehatan',
                'Jumlah luaran pengabdian sesuai prioritas transformasi kesehatan',
                'Prestasi dosen',
                'Prestasi mahasiswa',
            ],
        ],
    ];

    $comparisonRows = [
        ['group' => 'Tata Kelola BLU', 'indicator' => 'Pendapatan BLU terhadap biaya operasional', 'target' => '49,70%', 'actual' => '48,88%', 'achievement' => '98,35%'],
        ['group' => 'Tata Kelola BLU', 'indicator' => 'Pendapatan Poltekkes BLU', 'target' => 'Rp51,25 M', 'actual' => 'Rp55,86 M', 'achievement' => '119,89%'],
        ['group' => 'Tata Kelola BLU', 'indicator' => 'Pendapatan optimalisasi aset', 'target' => 'Rp3,34 M', 'actual' => 'Rp4,03 M', 'achievement' => '120,86%'],
        ['group' => 'Tata Kelola BLU', 'indicator' => 'Penyelesaian modernisasi BLU', 'target' => '122%', 'actual' => '186,20%', 'achievement' => '137,36%'],
        ['group' => 'Tata Kelola BLU', 'indicator' => 'Roadmap pengembangan Poltekkes', 'target' => '1 dokumen', 'actual' => '1 dokumen', 'achievement' => '100,00%'],
        ['group' => 'SDM dan Mutu', 'indicator' => 'Dosen belum serdos yang telah 2 tahun jabfung', 'target' => 'turun menjadi 14 dosen', 'actual' => 'turun 12 dosen', 'achievement' => '147,10%'],
        ['group' => 'SDM dan Mutu', 'indicator' => 'Dosen lektor kepala / guru besar', 'target' => '32 dosen', 'actual' => '35 dosen', 'achievement' => '106,25%'],
        ['group' => 'SDM dan Mutu', 'indicator' => 'Kemampuan bahasa Inggris dosen KI', 'target' => '50%', 'actual' => '100%', 'achievement' => '200,00%'],
        ['group' => 'SDM dan Mutu', 'indicator' => 'Kelulusan uji kompetensi', 'target' => '97,15%', 'actual' => '98,62%', 'achievement' => '101,51%'],
        ['group' => 'SDM dan Mutu', 'indicator' => 'Prodi unggul yang memenuhi waktu reakreditasi', 'target' => '1 prodi', 'actual' => '1 prodi', 'achievement' => '100,00%'],
        ['group' => 'Lulusan', 'indicator' => 'Respond rate tracer study', 'target' => '80%', 'actual' => '94,50%', 'achievement' => '101,51%'],
        ['group' => 'Lulusan', 'indicator' => 'Serapan lulusan di fasyankes pemerintah', 'target' => '15%', 'actual' => '16,49%', 'achievement' => '109,93%'],
        ['group' => 'Lulusan', 'indicator' => 'Lulusan perawat bekerja di luar negeri', 'target' => '4 orang', 'actual' => '11 orang', 'achievement' => '275,00%'],
        ['group' => 'Lulusan', 'indicator' => 'Penguasaan bahasa asing selain Inggris bagi KI', 'target' => '1 bahasa', 'actual' => '1 bahasa', 'achievement' => '100,00%'],
        ['group' => 'Riset, PKM, dan Prestasi', 'indicator' => 'Penelitian untuk stunting, TB, PM, PTM, dan KIA', 'target' => '3 penelitian', 'actual' => '30 penelitian', 'achievement' => '1000,00%'],
        ['group' => 'Riset, PKM, dan Prestasi', 'indicator' => 'Luaran penelitian untuk ketahanan kesehatan', 'target' => '1 penelitian', 'actual' => '3 penelitian', 'achievement' => '300,00%'],
        ['group' => 'Riset, PKM, dan Prestasi', 'indicator' => 'Luaran pengabdian prioritas transformasi kesehatan', 'target' => '1 MoU', 'actual' => '5 MoU', 'achievement' => '500,00%'],
        ['group' => 'Riset, PKM, dan Prestasi', 'indicator' => 'Prestasi dosen', 'target' => '2 dosen', 'actual' => '2 dosen', 'achievement' => '100,00%'],
        ['group' => 'Riset, PKM, dan Prestasi', 'indicator' => 'Prestasi mahasiswa', 'target' => '63 prestasi', 'actual' => '121 prestasi', 'achievement' => '192,06%'],
    ];

    $performanceHighlights = [
        [
            'title' => 'Pendapatan BLU',
            'value' => 'Rp55,86 miliar',
            'body' => 'Pendapatan BLU melampaui target tahunan dan memperlihatkan kapasitas institusi menghasilkan pendapatan layanan yang lebih kuat dibanding rencana awal.',
        ],
        [
            'title' => 'Optimalisasi Aset',
            'value' => 'Rp4,03 miliar',
            'body' => 'Pendapatan optimalisasi aset berada di atas target dan menunjukkan pemanfaatan aset BLU semakin produktif.',
        ],
        [
            'title' => 'Modernisasi BLU',
            'value' => '186,20%',
            'body' => 'Penyelesaian modernisasi BLU menjadi salah satu sorotan karena jauh melampaui target dan menegaskan penguatan layanan berbasis sistem informasi.',
        ],
        [
            'title' => 'Kelulusan UKOM',
            'value' => '98,62%',
            'body' => 'Kelulusan uji kompetensi tetap sangat tinggi, menandakan kesiapan lulusan vokasi kesehatan tetap terjaga.',
        ],
        [
            'title' => 'Riset Prioritas',
            'value' => '30 penelitian',
            'body' => 'Penelitian yang mendukung prioritas transformasi kesehatan jauh melampaui target, terutama untuk stunting, TB, PM, PTM, dan KIA.',
        ],
        [
            'title' => 'Prestasi Mahasiswa',
            'value' => '121 prestasi',
            'body' => 'Prestasi mahasiswa melonjak tajam dengan distribusi capaian pada level kabupaten, provinsi, nasional, hingga internasional.',
        ],
    ];

    $chapterSummaries = [
        [
            'title' => 'Bab I: Pendahuluan',
            'body' => 'Menjelaskan latar belakang penyusunan LAKIP, kedudukan Poltekkes sebagai UPT Kementerian Kesehatan, tugas dan fungsi, organisasi, sumber daya, serta visi dan misi institusi.',
        ],
        [
            'title' => 'Bab II: Perencanaan dan Penetapan Kinerja',
            'body' => 'Merangkum arah kebijakan RPJMN dan transformasi kesehatan, sembilan strategi Poltekkes, perjanjian kinerja 19 indikator, serta informasi pagu awal dan revisi anggaran tahun 2023.',
        ],
        [
            'title' => 'Bab III: Akuntabilitas Kinerja',
            'body' => 'Menguraikan pengukuran atas 19 indikator, perbandingan target dan realisasi, analisis faktor pendukung, serta penjelasan area yang masih memerlukan perhatian.',
        ],
        [
            'title' => 'Bab IV: Penutup',
            'body' => 'Menegaskan bahwa 18 indikator telah mencapai target, lalu menutup dengan rencana tindak lanjut berupa penguatan tracer study, koordinasi, kapasitas SDM, dan inovasi layanan TI.',
        ],
    ];

    $balancedNotes = [
        ['title' => '18 dari 19 indikator tercapai', 'body' => 'Kinerja 2023 tergolong sangat kuat karena hanya satu indikator yang belum mencapai target tahunan, sementara yang lain memenuhi atau melampaui target.'],
        ['title' => 'Satu indikator yang perlu perhatian', 'body' => 'Persentase realisasi pendapatan BLU terhadap biaya operasional berada tipis di bawah target, sehingga pengendalian biaya dan penguatan pendapatan tetap penting.'],
        ['title' => 'Pembelajaran utama', 'body' => 'Penguatan tata kelola, kemitraan, dan dukungan institusi memungkinkan riset prioritas, prestasi mahasiswa, dan penempatan lulusan luar negeri tumbuh bersamaan.'],
    ];

    $improvementFocus = [
        'Menambah, mengembangkan, dan mengoptimalkan berbagai saluran untuk sosialisasi penelusuran lulusan atau tracer study.',
        'Meningkatkan koordinasi antarunit dan para pemangku kepentingan dengan sistem perencanaan yang lebih komprehensif dan selaras dengan Renstra serta IKU Direktur.',
        'Meningkatkan kapasitas dan kualitas SDM Poltekkes Kemenkes Bandung agar kinerja berikutnya lebih optimal.',
        'Mengembangkan inovasi layanan berbasis teknologi informasi agar proses kerja semakin efektif dan efisien.',
    ];

    $documentYears = [
        ['label' => 'LAKIP 2025', 'href' => route('unit.administrasi-umum.lakip'), 'current' => false],
        ['label' => 'LAKIP 2024', 'href' => route('unit.administrasi-umum.lakip.2024'), 'current' => false],
        ['label' => 'LAKIP 2023', 'href' => route('unit.administrasi-umum.lakip.2023'), 'current' => true],
    ];
@endphp

@section('content')
<div class="relative overflow-hidden bg-slate-950">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(251,191,36,0.28),_transparent_38%),radial-gradient(circle_at_bottom_right,_rgba(249,115,22,0.22),_transparent_35%)]"></div>
    <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <nav class="mb-5 flex flex-wrap items-center gap-2 text-sm text-amber-100/80">
            <a href="{{ route('home') }}" class="transition hover:text-white">Beranda</a>
            <span>/</span>
            <a href="{{ route('unit.show', $page->slug) }}" class="transition hover:text-white">{{ $page->judul }}</a>
            <span>/</span>
            <span class="text-white">LAKIP 2023</span>
        </nav>

        <div class="grid gap-10 lg:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)] lg:items-end">
            <div>
                <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-amber-100">Bagian Keuangan</span>
                <h1 class="mt-5 max-w-4xl text-4xl font-black leading-tight text-white sm:text-5xl lg:text-6xl">LAKIP 2023 dalam format HTML yang lengkap, ringan, dan mudah ditelusuri.</h1>
                <p class="mt-5 max-w-3xl text-base leading-8 text-slate-200 sm:text-lg">Halaman ini menuliskan ulang isi utama LAKIP 2023 ke dalam HTML yang lebih ringan agar pembaca dapat menelusuri bab, indikator, dan tindak lanjut penting tanpa bergantung pada tampilan file PDF.</p>
                <div class="mt-6 flex flex-wrap gap-3 text-sm font-semibold">
                    @foreach ($documentYears as $documentYear)
                    <a href="{{ $documentYear['href'] }}" class="inline-flex items-center rounded-full border px-4 py-2 transition {{ $documentYear['current'] ? 'border-white/20 bg-white text-slate-900' : 'border-white/15 bg-white/10 text-white hover:bg-white/20' }}">{{ $documentYear['label'] }}</a>
                    @endforeach
                </div>
            </div>

            <aside class="rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl shadow-orange-950/25 backdrop-blur">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-100">Ringkas</p>
                <dl class="mt-5 space-y-4 text-sm text-slate-100">
                    <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-3">
                        <dt class="text-slate-300">Dokumen</dt>
                        <dd class="text-right font-semibold">LAKIP Poltekkes Kemenkes Bandung 2023</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-3">
                        <dt class="text-slate-300">Periode kinerja</dt>
                        <dd class="text-right font-semibold">Januari sampai Desember 2023</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-3">
                        <dt class="text-slate-300">Cakupan</dt>
                        <dd class="text-right font-semibold">4 bab utama + ringkasan eksekutif</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-300">Fokus</dt>
                        <dd class="text-right font-semibold">Akuntabilitas BLU, mutu pendidikan, dan penguatan lulusan</dd>
                    </div>
                </dl>
            </aside>
        </div>
    </div>
</div>

<nav aria-label="Navigasi isi halaman LAKIP 2023" class="sticky top-16 z-30 border-b border-slate-200/90 bg-white/95 shadow-sm backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
        <div class="overflow-x-auto pb-1">
            <div class="flex min-w-max gap-3 text-sm font-semibold text-slate-600">
                <a href="#ikhtisar" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-amber-50 hover:text-amber-700">Ikhtisar</a>
                <a href="#ringkasan-eksekutif" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-amber-50 hover:text-amber-700">Ringkasan Eksekutif</a>
                <a href="#indikator" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-amber-50 hover:text-amber-700">19 Indikator</a>
                <a href="#capaian" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-amber-50 hover:text-amber-700">Capaian 2023</a>
                <a href="#bab-utama" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-amber-50 hover:text-amber-700">Bab Utama</a>
                <a href="#tindak-lanjut" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-amber-50 hover:text-amber-700">Tindak Lanjut</a>
                <a href="#dokumen-lengkap" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-amber-50 hover:text-amber-700">Dokumen Lengkap</a>
            </div>
        </div>
    </div>
</nav>

<section id="ikhtisar" class="scroll-mt-44 bg-slate-50 py-14 sm:py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-700">Ikhtisar</p>
                <h2 class="mt-2 text-3xl font-black text-slate-900">Sorotan cepat kinerja institusi tahun 2023</h2>
            </div>
            <p class="max-w-2xl text-sm leading-7 text-slate-600">LAKIP 2023 menunjukkan kinerja yang sangat kuat pada pendapatan BLU, modernisasi layanan, kelulusan UKOM, riset prioritas, dan prestasi mahasiswa, dengan satu perhatian utama pada rasio pendapatan BLU terhadap biaya operasional yang sedikit di bawah target.</p>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($quickFacts as $fact)
            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">{{ $fact['label'] }}</p>
                <p class="mt-3 text-3xl font-black text-slate-900">{{ $fact['value'] }}</p>
                <p class="mt-3 text-sm leading-7 text-slate-600">{{ $fact['note'] }}</p>
            </article>
            @endforeach
        </div>
    </div>
</section>

<section id="ringkasan-eksekutif" class="scroll-mt-44 bg-white py-14 sm:py-16">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(280px,0.9fr)] lg:px-8">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-700">Ringkasan Eksekutif</p>
            <h2 class="mt-2 text-3xl font-black text-slate-900">Mandat, hasil kinerja, dan alasan dokumen ini penting</h2>
            <div class="mt-6 space-y-5 text-base leading-8 text-slate-700">
                <p>LAKIP 2023 disusun sebagai laporan tahunan pertanggungjawaban Poltekkes Kemenkes Bandung atas pelaksanaan tugas dan fungsi selama satu tahun anggaran. Dokumen ini menjadi bentuk akuntabilitas kepada Kementerian Kesehatan, pemangku kepentingan, dan publik.</p>
                <p>Hasil pengukuran kinerja menunjukkan bahwa 18 dari 19 indikator mencapai atau melampaui target. Capaian agregat tersebut memperlihatkan bahwa penguatan tata kelola BLU, mutu pendidikan, dan ekosistem riset telah berjalan serempak sepanjang tahun 2023.</p>
                <p>Dokumen juga menegaskan bahwa hasil kinerja tidak berhenti pada angka capaian, tetapi harus diikuti perbaikan nyata pada tracer study, kualitas perencanaan, kapasitas SDM, dan layanan berbasis teknologi informasi.</p>
            </div>
        </div>

        <aside class="rounded-[2rem] border border-amber-100 bg-amber-50 p-6 sm:p-8">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-700">Tujuh Misi</p>
            <ol class="mt-5 space-y-3 text-sm leading-7 text-slate-700">
                @foreach ($missions as $mission)
                <li class="rounded-2xl border border-white/80 bg-white/80 px-4 py-3">{{ $mission }}</li>
                @endforeach
            </ol>
        </aside>
    </div>
</section>

<section id="indikator" class="scroll-mt-44 bg-slate-950 py-14 text-white sm:py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-200">19 indikator utama</p>
                <h2 class="mt-2 text-3xl font-black">Peta indikator kinerja yang digunakan pada LAKIP 2023</h2>
            </div>
            <p class="max-w-2xl text-sm leading-7 text-slate-300">Struktur indikator tahun 2023 memperlihatkan keseimbangan antara tata kelola BLU, mutu pendidikan, lulusan, internasionalisasi, riset, pengabdian masyarakat, dan prestasi sivitas akademika.</p>
        </div>

        <div class="mt-8 grid gap-5 lg:grid-cols-2 xl:grid-cols-4">
            @foreach ($indicatorGroups as $group)
            <article class="rounded-[2rem] border border-white/10 bg-white/5 p-6">
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-amber-200">{{ $group['title'] }}</p>
                <ul class="mt-5 space-y-3 text-sm leading-7 text-slate-200">
                    @foreach ($group['items'] as $item)
                    <li class="rounded-2xl border border-white/10 bg-black/10 px-4 py-3">{{ $item }}</li>
                    @endforeach
                </ul>
            </article>
            @endforeach
        </div>
    </div>
</section>

<section id="capaian" class="scroll-mt-44 bg-white py-14 sm:py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-700">Capaian 2023</p>
                <h2 class="mt-2 text-3xl font-black text-slate-900">Target, realisasi, dan persentase capaian indikator</h2>
            </div>
            <p class="max-w-2xl text-sm leading-7 text-slate-600">Tabel ini menuliskan ulang indikator yang paling relevan untuk pembaca umum. Persentase capaian di kolom terakhir ditampilkan mengikuti angka yang tertulis pada dokumen sumber 2023.</p>
        </div>

        <div class="mt-8 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-950 text-left text-white">
                        <tr>
                            <th class="px-5 py-4">Kelompok</th>
                            <th class="px-5 py-4">Indikator</th>
                            <th class="px-5 py-4">Target</th>
                            <th class="px-5 py-4">Realisasi</th>
                            <th class="px-5 py-4">Capaian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($comparisonRows as $row)
                        <tr class="align-top">
                            <td class="px-5 py-4 font-semibold text-slate-500">{{ $row['group'] }}</td>
                            <td class="px-5 py-4 font-semibold text-slate-900">{{ $row['indicator'] }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $row['target'] }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $row['actual'] }}</td>
                            <td class="px-5 py-4 font-semibold {{ str_contains($row['achievement'], '98,35') ? 'text-amber-600' : 'text-emerald-600' }}">{{ $row['achievement'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($performanceHighlights as $highlight)
            <article class="rounded-[2rem] border border-slate-200 bg-slate-50 p-6">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-700">{{ $highlight['title'] }}</p>
                <p class="mt-3 text-3xl font-black text-slate-900">{{ $highlight['value'] }}</p>
                <p class="mt-3 text-sm leading-7 text-slate-600">{{ $highlight['body'] }}</p>
            </article>
            @endforeach
        </div>
    </div>
</section>

<section id="bab-utama" class="scroll-mt-44 bg-slate-50 py-14 sm:py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-700">Bab Utama</p>
                <h2 class="mt-2 text-3xl font-black text-slate-900">Empat bab yang membentuk struktur dokumen LAKIP 2023</h2>
            </div>
            <p class="max-w-2xl text-sm leading-7 text-slate-600">Bagian ini memudahkan pengunjung memahami isi dokumen sebelum masuk ke versi HTML panjang yang mengikuti alur resmi LAKIP.</p>
        </div>

        <div class="mt-8 grid gap-5 lg:grid-cols-2">
            @foreach ($chapterSummaries as $chapter)
            <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
                <p class="text-lg font-black text-slate-900">{{ $chapter['title'] }}</p>
                <p class="mt-4 text-sm leading-7 text-slate-600">{{ $chapter['body'] }}</p>
            </article>
            @endforeach
        </div>

        <div class="mt-8 grid gap-5 lg:grid-cols-3">
            @foreach ($balancedNotes as $note)
            <article class="rounded-[2rem] border border-slate-200 bg-white p-6">
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-amber-700">{{ $note['title'] }}</p>
                <p class="mt-4 text-sm leading-7 text-slate-600">{{ $note['body'] }}</p>
            </article>
            @endforeach
        </div>
    </div>
</section>

<section id="tindak-lanjut" class="scroll-mt-44 bg-white py-14 sm:py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(320px,0.9fr)]">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-700">Tindak lanjut</p>
                <h2 class="mt-2 text-3xl font-black text-slate-900">Agenda perbaikan yang ditegaskan setelah evaluasi 2023</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach ($improvementFocus as $focus)
                    <article class="rounded-[2rem] border border-slate-200 bg-slate-50 px-5 py-5 text-sm leading-7 text-slate-600">{{ $focus }}</article>
                    @endforeach
                </div>
            </div>

            <aside class="rounded-[2rem] border border-amber-100 bg-amber-50 p-6 sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-700">Catatan Pembaca</p>
                <div class="mt-5 space-y-4 text-sm leading-7 text-slate-700">
                    <p>Versi HTML ini mempertahankan struktur resmi dokumen, tetapi menyederhanakan pengalaman membaca agar isi LAKIP 2023 bisa ditelusuri lebih cepat oleh pengunjung umum maupun stakeholder.</p>
                    <p>Beberapa angka capaian pada dokumen 2023 ditampilkan persis sebagaimana tertulis pada naskah sumber, termasuk saat angka persentasenya tidak sepenuhnya sejalan dengan pembagian aritmetika sederhana.</p>
                    <p>Untuk perbandingan antartahun, pengunjung dapat berpindah langsung ke halaman LAKIP 2024 dan LAKIP 2025 melalui tombol pilihan tahun di bagian atas halaman.</p>
                </div>
            </aside>
        </div>
    </div>
</section>

@include('unit.partials.lakip-2023-html-document')
@endsection