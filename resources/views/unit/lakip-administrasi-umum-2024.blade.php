@extends('layouts.app')

@section('title', 'LAKIP 2024')
@section('description', 'Laporan Kinerja Instansi Pemerintah (LAKIP/LKj) Poltekkes Kemenkes Bandung Tahun 2024 dalam format HTML yang lengkap, terstruktur, dan mudah dibaca.')

@php
    $quickFacts = [
        ['label' => 'Indikator Utama', 'value' => '17 IKU', 'note' => 'Meliputi tata kelola, pendidikan, dan penelitian/pengabdian.'],
        ['label' => 'Total Capaian', 'value' => '219,79%', 'note' => 'Akumulasi capaian kinerja institusi tahun 2024.'],
        ['label' => 'Pendapatan BLU', 'value' => 'Rp60,02 M', 'note' => 'Melampaui target Rp54,5 miliar pada tahun 2024.'],
        ['label' => 'Realisasi Anggaran', 'value' => '93,54%', 'note' => 'Satu indikator yang masih berada di bawah target 96%.'],
        ['label' => 'Kelulusan UKOM', 'value' => '99,13%', 'note' => 'Melampaui target kelulusan uji kompetensi sebesar 95%.'],
        ['label' => 'Prodi Unggul', 'value' => '16 prodi', 'note' => 'Penambahan prodi terakreditasi unggul jauh di atas target 1 prodi.'],
    ];

    $missions = [
        'Menyelenggarakan pendidikan berkualitas yang berkarakter nasional dan berwawasan global sesuai perkembangan ilmu dan teknologi terapan bidang kesehatan.',
        'Menyelenggarakan penelitian inovatif dengan mengembangkan potensi bahan lokal dan berbasis teknologi terapan.',
        'Menyelenggarakan inovasi pengabdian kepada masyarakat melalui pemberdayaan masyarakat berbasis hasil penelitian.',
        'Menyelenggarakan kerja sama dengan pemangku kepentingan nasional dan internasional dengan prinsip saling menguntungkan.',
        'Membentuk karakter bekerja dengan hati, kepemimpinan, dan entrepreneurship bagi pegawai maupun mahasiswa.',
        'Mengembangkan pendidikan dan program studi baru untuk menjawab tuntutan perkembangan pelayanan kesehatan.',
        'Meningkatkan sumber daya yang berintegritas untuk mewujudkan tata kelola BLU pendidikan yang baik, bersih, transparan, dan akuntabel.',
    ];

    $indicatorGroups = [
        [
            'title' => 'Tata Kelola dan Keuangan',
            'items' => [
                'Persentase realisasi pendapatan BLU terhadap biaya operasional',
                'Realisasi pendapatan Poltekkes BLU',
                'Realisasi pendapatan dari optimalisasi aset',
                'Persentase penyelesaian modernisasi BLU',
                'Indeks akurasi proyeksi pendapatan BLU',
                'Persentase tindak lanjut rekomendasi hasil pengawasan BPK RI',
                'Persentase realisasi anggaran',
            ],
        ],
        [
            'title' => 'Pendidikan dan Lulusan',
            'items' => [
                'Persentase dosen yang memiliki serdos dengan minimal 2 tahun jabatan fungsional',
                'Persentase dosen tetap dengan kualifikasi lektor kepala dan/atau guru besar',
                'Persentase kemampuan bahasa Inggris dosen KI level intermediet',
                'Persentase kelulusan uji kompetensi',
                'Penambahan prodi terakreditasi unggul',
                'Persentase serapan lulusan di fasyankes milik pemerintah',
                'Persentase lulusan perawat yang diterima bekerja di luar negeri',
                'Jumlah penghargaan yang didapat',
            ],
        ],
        [
            'title' => 'Penelitian dan Pengabdian',
            'items' => [
                'Jumlah penelitian yang dikomersialisasi',
                'Jumlah pengabdian kepada masyarakat yang sesuai program prioritas transformasi kesehatan',
            ],
        ],
    ];

    $comparisonRows = [
        ['group' => 'Tata Kelola', 'indicator' => 'Pendapatan BLU terhadap biaya operasional', 'target' => '48,11%', 'actual' => '49,52%', 'achievement' => '113,21%'],
        ['group' => 'Tata Kelola', 'indicator' => 'Pendapatan BLU', 'target' => 'Rp54,5 M', 'actual' => 'Rp60,02 M', 'achievement' => '110,14%'],
        ['group' => 'Tata Kelola', 'indicator' => 'Pendapatan optimalisasi aset', 'target' => 'Rp3,4 M', 'actual' => 'Rp5,08 M', 'achievement' => '149,31%'],
        ['group' => 'Tata Kelola', 'indicator' => 'Modernisasi BLU', 'target' => '167%', 'actual' => '179,10%', 'achievement' => '105,00%'],
        ['group' => 'Tata Kelola', 'indicator' => 'Akurasi proyeksi pendapatan BLU', 'target' => '3,5', 'actual' => '4,06', 'achievement' => '116,00%'],
        ['group' => 'Tata Kelola', 'indicator' => 'Tindak lanjut rekomendasi BPK', 'target' => '95%', 'actual' => '100%', 'achievement' => '105,26%'],
        ['group' => 'Tata Kelola', 'indicator' => 'Realisasi anggaran', 'target' => '96%', 'actual' => '93,54%', 'achievement' => '97,44%'],
        ['group' => 'Pendidikan', 'indicator' => 'Dosen bersertifikat (serdos)', 'target' => '80%', 'actual' => '88,54%', 'achievement' => '110,68%'],
        ['group' => 'Pendidikan', 'indicator' => 'Dosen lektor kepala / guru besar', 'target' => '30%', 'actual' => '34,15%', 'achievement' => '113,82%'],
        ['group' => 'Pendidikan', 'indicator' => 'Bahasa Inggris dosen KI', 'target' => '60%', 'actual' => '100%', 'achievement' => '166,67%'],
        ['group' => 'Pendidikan', 'indicator' => 'Kelulusan uji kompetensi', 'target' => '95%', 'actual' => '99,13%', 'achievement' => '104,35%'],
        ['group' => 'Pendidikan', 'indicator' => 'Penambahan prodi unggul', 'target' => '1 prodi', 'actual' => '16 prodi', 'achievement' => '1600,00%'],
        ['group' => 'Pendidikan', 'indicator' => 'Serapan lulusan di fasyankes pemerintah', 'target' => '30%', 'actual' => '30,21%', 'achievement' => '100,68%'],
        ['group' => 'Pendidikan', 'indicator' => 'Lulusan perawat bekerja di luar negeri', 'target' => '15%', 'actual' => '24,59%', 'achievement' => '163,93%'],
        ['group' => 'Pendidikan', 'indicator' => 'Jumlah penghargaan', 'target' => '10', 'actual' => '18', 'achievement' => '180,00%'],
        ['group' => 'Penelitian & PKM', 'indicator' => 'Penelitian yang dikomersialisasi', 'target' => '1 penelitian', 'actual' => '1 penelitian', 'achievement' => '100,00%'],
        ['group' => 'Penelitian & PKM', 'indicator' => 'PKM prioritas transformasi kesehatan', 'target' => '3 MoU', 'actual' => '9 MoU', 'achievement' => '300,00%'],
    ];

    $performanceHighlights = [
        [
            'title' => 'Pendapatan BLU',
            'value' => 'Rp60,02 miliar',
            'body' => 'Pendapatan BLU tahun 2024 melampaui target dan didorong oleh ketepatan pembayaran UKT, pendapatan bisnis BLU, dan peningkatan jumlah mahasiswa.',
        ],
        [
            'title' => 'Optimalisasi Aset',
            'value' => 'Rp5,08 miliar',
            'body' => 'Pendapatan dari optimalisasi aset mencapai 149,31% dari target, menunjukkan pemanfaatan aset tetap dan aset lancar yang lebih produktif.',
        ],
        [
            'title' => 'UKOM',
            'value' => '99,13%',
            'body' => 'Kelulusan uji kompetensi tetap tinggi berkat tryout, pengkayaan, dan pembimbingan lebih awal bagi mahasiswa yang akan mengikuti UKOM.',
        ],
        [
            'title' => 'Akreditasi Unggul',
            'value' => '16 prodi',
            'body' => 'Capaian reakreditasi unggul jauh melampaui target tahunan, memperkuat posisi mutu akademik Poltekkes Kemenkes Bandung.',
        ],
        [
            'title' => 'Lulusan Luar Negeri',
            'value' => '24,59%',
            'body' => 'Serapan lulusan perawat ke luar negeri berada di atas target, didukung promosi kompetensi lulusan dan penguatan jejaring kerja.',
        ],
        [
            'title' => 'PKM Prioritas',
            'value' => '9 MoU',
            'body' => 'Pengabdian kepada masyarakat yang selaras dengan prioritas transformasi kesehatan mencapai tiga kali lipat dari target.',
        ],
    ];

    $chapterSummaries = [
        [
            'title' => 'Bab I: Pendahuluan',
            'body' => 'Menjelaskan dasar hukum LKIP, maksud dan tujuan penyusunan, serta kedudukan Poltekkes Kemenkes Bandung sebagai UPT Kementerian Kesehatan yang menyelenggarakan pendidikan, penelitian, dan pengabdian kepada masyarakat.',
        ],
        [
            'title' => 'Bab II: Perencanaan dan Penetapan Kinerja',
            'body' => 'Merangkum visi, misi, sasaran strategis, target IKU, dan gambaran alokasi anggaran yang menjadi dasar pengukuran kinerja tahun 2024.',
        ],
        [
            'title' => 'Bab III: Akuntabilitas Kinerja',
            'body' => 'Menguraikan realisasi 17 indikator utama beserta analisis faktor pendukung, faktor penghambat, dan perbandingan target dengan realisasi.',
        ],
        [
            'title' => 'Bab IV: Penutup',
            'body' => 'Menegaskan area peningkatan yang harus diprioritaskan, terutama monitoring anggaran, tracer study, koordinasi lintas unit, penguatan SDM, dan inovasi layanan berbasis teknologi informasi.',
        ],
    ];

    $improvementFocus = [
        'Monitoring dan evaluasi realisasi anggaran secara berkala setiap triwulan agar deviasi dapat dikendalikan lebih cepat.',
        'Menambah dan mengoptimalkan berbagai saluran tracer study untuk memperkuat data serapan lulusan.',
        'Meningkatkan koordinasi antarunit dan pemangku kepentingan agar perencanaan lebih selaras dengan Renstra dan IKU direktur.',
        'Meningkatkan kapasitas dan kualitas SDM untuk mendukung target kinerja yang lebih tinggi pada tahun berikutnya.',
        'Mengembangkan inovasi layanan berbasis teknologi informasi agar proses akademik, keuangan, dan administrasi lebih efektif dan efisien.',
        'Menjaga kesinambungan pendampingan penelitian, publikasi, sertifikasi dosen, serta hilirisasi inovasi agar kinerja tidak hanya tinggi tetapi juga berkelanjutan.',
    ];

    $balancedNotes = [
        ['title' => 'Indikator yang melampaui target', 'body' => '16 dari 17 indikator telah memenuhi atau melampaui target. Capaian paling menonjol berasal dari akreditasi unggul, penghargaan, PKM prioritas, dan penyerapan lulusan luar negeri.'],
        ['title' => 'Indikator yang perlu perhatian', 'body' => 'Realisasi anggaran tercatat 93,54%, masih di bawah target 96%. Dokumen menekankan perlunya monitoring anggaran yang lebih rutin dan koordinasi pelaksanaan yang lebih rapat.'],
        ['title' => 'Pembelajaran utama', 'body' => 'Dokumen 2024 menunjukkan bahwa target keuangan, mutu akademik, dan penyerapan lulusan dapat dicapai bersamaan bila penguatan tata kelola, pendampingan SDM, dan pengelolaan data dilakukan sejak awal tahun.'],
    ];

    $documentYears = [
        ['label' => 'LAKIP 2025', 'href' => route('unit.administrasi-umum.lakip'), 'current' => false],
        ['label' => 'LAKIP 2024', 'href' => route('unit.administrasi-umum.lakip.2024'), 'current' => true],
        ['label' => 'LAKIP 2023', 'href' => route('unit.administrasi-umum.lakip.2023'), 'current' => false],
    ];
@endphp

@section('content')
<div class="relative overflow-hidden bg-slate-950">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.35),_transparent_38%),radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.22),_transparent_35%)]"></div>
    <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <nav class="mb-5 flex flex-wrap items-center gap-2 text-sm text-cyan-100/80">
            <a href="{{ route('home') }}" class="transition hover:text-white">Beranda</a>
            <span>/</span>
            <a href="{{ route('unit.show', $page->slug) }}" class="transition hover:text-white">{{ $page->judul }}</a>
            <span>/</span>
            <span class="text-white">LAKIP 2024</span>
        </nav>

        <div class="grid gap-10 lg:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)] lg:items-end">
            <div>
                <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-cyan-100">Bagian Keuangan</span>
                <h1 class="mt-5 max-w-4xl text-4xl font-black leading-tight text-white sm:text-5xl lg:text-6xl">LAKIP 2024 dalam format HTML yang lengkap, terbaca, dan mudah ditelusuri.</h1>
                <p class="mt-5 max-w-3xl text-base leading-8 text-slate-200 sm:text-lg">Halaman ini menuliskan ulang isi utama dokumen LKj/LKIP 2024 ke dalam HTML yang lebih ringan agar pembaca dapat menelusuri bab, tabel, dan capaian penting tanpa bergantung pada tampilan file PDF.</p>
                <div class="mt-6 flex flex-wrap gap-3 text-sm font-semibold">
                    @foreach ($documentYears as $documentYear)
                    <a href="{{ $documentYear['href'] }}" class="inline-flex items-center rounded-full border px-4 py-2 transition {{ $documentYear['current'] ? 'border-white/20 bg-white text-slate-900' : 'border-white/15 bg-white/10 text-white hover:bg-white/20' }}">{{ $documentYear['label'] }}</a>
                    @endforeach
                </div>
            </div>

            <aside class="rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl shadow-cyan-950/30 backdrop-blur">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-100">Ringkas</p>
                <dl class="mt-5 space-y-4 text-sm text-slate-100">
                    <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-3">
                        <dt class="text-slate-300">Dokumen</dt>
                        <dd class="text-right font-semibold">LKj / LAKIP Poltekkes Kemenkes Bandung 2024</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-3">
                        <dt class="text-slate-300">Tanggal dokumen</dt>
                        <dd class="text-right font-semibold">Januari 2025</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-3">
                        <dt class="text-slate-300">Cakupan</dt>
                        <dd class="text-right font-semibold">4 bab utama + ringkasan eksekutif</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-300">Fokus</dt>
                        <dd class="text-right font-semibold">Akuntabilitas, capaian IKU, dan tindak lanjut perbaikan</dd>
                    </div>
                </dl>
            </aside>
        </div>
    </div>
</div>

<nav aria-label="Navigasi isi halaman LAKIP 2024" class="sticky top-16 z-30 border-b border-slate-200/90 bg-white/95 shadow-sm backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
        <div class="overflow-x-auto pb-1">
            <div class="flex min-w-max gap-3 text-sm font-semibold text-slate-600">
            <a href="#ikhtisar" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-teal-50 hover:text-teal-700">Ikhtisar</a>
            <a href="#ringkasan-eksekutif" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-teal-50 hover:text-teal-700">Ringkasan Eksekutif</a>
            <a href="#indikator" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-teal-50 hover:text-teal-700">17 Indikator</a>
            <a href="#capaian" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-teal-50 hover:text-teal-700">Capaian 2024</a>
            <a href="#bab-utama" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-teal-50 hover:text-teal-700">Bab Utama</a>
            <a href="#tindak-lanjut" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-teal-50 hover:text-teal-700">Tindak Lanjut</a>
            <a href="#dokumen-lengkap" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-teal-50 hover:text-teal-700">Dokumen Lengkap</a>
            </div>
        </div>
    </div>
</nav>

<section id="ikhtisar" class="scroll-mt-44 bg-slate-50 py-14 sm:py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Ikhtisar</p>
                <h2 class="mt-2 text-3xl font-black text-slate-900">Sorotan cepat capaian institusi tahun 2024</h2>
            </div>
            <p class="max-w-2xl text-sm leading-7 text-slate-600">Dokumen 2024 menunjukkan performa institusi yang kuat pada pendapatan BLU, akreditasi unggul, kelulusan UKOM, serapan lulusan, dan pengabdian prioritas, dengan satu perhatian utama pada realisasi anggaran yang belum menyentuh target.</p>
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
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Ringkasan Eksekutif</p>
            <h2 class="mt-2 text-3xl font-black text-slate-900">Gambaran umum mandat, sasaran, dan hasil kinerja</h2>
            <div class="mt-6 space-y-5 text-base leading-8 text-slate-700">
                <p>Laporan Kinerja Instansi Pemerintah tahun 2024 disusun sebagai bentuk pertanggungjawaban Poltekkes Kemenkes Bandung atas pelaksanaan tugas dan fungsi selama satu tahun anggaran. Dokumen ini menampilkan pelaksanaan program, capaian indikator kinerja utama, dan evaluasi terhadap hasil yang diperoleh.</p>
                <p>Secara strategis, Poltekkes Kemenkes Bandung menempatkan diri sebagai perguruan tinggi kesehatan yang unggul dalam teknologi terapan, berkarakter, dan berdaya saing internasional. Kinerja 2024 menunjukkan bahwa tata kelola BLU, mutu pendidikan, dan reputasi kelembagaan bergerak positif secara bersamaan.</p>
                <p>Secara agregat, capaian institusi tercatat 219,79%. Sebanyak 16 indikator melampaui target, sementara 1 indikator yaitu realisasi anggaran masih berada di bawah target sehingga menjadi fokus pembenahan pada periode berikutnya.</p>
            </div>
        </div>

        <aside class="rounded-[2rem] border border-cyan-100 bg-cyan-50 p-6 sm:p-8">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Tujuh Misi</p>
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
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-200">17 indikator utama</p>
                <h2 class="mt-2 text-3xl font-black">Peta indikator kinerja yang digunakan pada LAKIP 2024</h2>
            </div>
            <p class="max-w-2xl text-sm leading-7 text-slate-300">Struktur indikator tahun 2024 menempatkan tata kelola keuangan sebagai fondasi, pendidikan sebagai mesin utama, dan penelitian/pengabdian sebagai penguat dampak institusi.</p>
        </div>

        <div class="mt-8 grid gap-5 lg:grid-cols-3">
            @foreach ($indicatorGroups as $group)
            <article class="rounded-[2rem] border border-white/10 bg-white/5 p-6">
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-200">{{ $group['title'] }}</p>
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
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Capaian 2024</p>
                <h2 class="mt-2 text-3xl font-black text-slate-900">Target, realisasi, dan persentase capaian indikator</h2>
            </div>
            <p class="max-w-2xl text-sm leading-7 text-slate-600">Tabel ini merangkum indikator yang paling relevan untuk pembaca umum: mana yang sudah melampaui target, mana yang tepat sasaran, dan mana yang masih perlu perhatian pada siklus kinerja berikutnya.</p>
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
                            <td class="px-5 py-4 font-bold {{ str_contains($row['achievement'], '97,44') ? 'text-amber-600' : 'text-emerald-600' }}">{{ $row['achievement'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($performanceHighlights as $highlight)
            <article class="rounded-[2rem] border border-slate-200 bg-slate-50 p-6">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-700">{{ $highlight['title'] }}</p>
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
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Bab utama</p>
                <h2 class="mt-2 text-3xl font-black text-slate-900">Empat bab yang membentuk struktur dokumen LKIP 2024</h2>
            </div>
            <p class="max-w-2xl text-sm leading-7 text-slate-600">Bagian ini memudahkan pengunjung memahami isi dokumen tanpa harus menelusuri seluruh naskah asli yang panjang dan formal.</p>
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
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-700">{{ $note['title'] }}</p>
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
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Tindak lanjut</p>
                <h2 class="mt-2 text-3xl font-black text-slate-900">Agenda perbaikan yang ditegaskan setelah evaluasi 2024</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach ($improvementFocus as $focus)
                    <article class="rounded-[2rem] border border-slate-200 bg-slate-50 px-5 py-5 text-sm leading-7 text-slate-600">{{ $focus }}</article>
                    @endforeach
                </div>
            </div>

            <aside class="rounded-[2rem] border border-cyan-100 bg-cyan-50 p-6 sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Catatan Pembaca</p>
                <div class="mt-5 space-y-4 text-sm leading-7 text-slate-700">
                    <p>Versi HTML ini sekarang memuat penulisan ulang isi utama dokumen agar lebih ringan daripada reproduksi gambar per halaman, tetapi tetap mempertahankan struktur bab, tabel inti, dan angka capaian penting.</p>
                    <p>Dokumen sumber menggunakan istilah LKj dan LKIP secara bergantian, tetapi substansinya sama: laporan akuntabilitas kinerja institusi dalam satu tahun berjalan.</p>
                    <p>Untuk perbandingan antartahun, pengunjung dapat berpindah langsung ke halaman LAKIP 2023 maupun LAKIP 2025 melalui tombol pilihan tahun di bagian atas halaman.</p>
                </div>
            </aside>
        </div>
    </div>
</section>

@include('unit.partials.lakip-2024-html-document')
@endsection