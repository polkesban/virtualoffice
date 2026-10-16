@extends('layouts.app')

@section('title', 'LAKIP 2025')
@section('description', 'Laporan Kinerja Instansi Pemerintah (LAKIP) Poltekkes Kemenkes Bandung Tahun 2025 dalam format HTML yang lengkap, terstruktur, dan mudah dibaca.')

@php
    $quickFacts = [
        ['label' => 'Indikator Utama', 'value' => '22 IKU', 'note' => 'Mencakup keuangan, akademik, SDM, penelitian, dan kelembagaan.'],
        ['label' => 'Kategori LAKIP', 'value' => 'A', 'note' => 'Nilai 2022-2024: 92,00 • 92,90 • 90,00.'],
        ['label' => 'Realisasi Anggaran', 'value' => '98,31%', 'note' => 'Terhadap pagu efektif tahun 2025.'],
        ['label' => 'Pendapatan BLU', 'value' => 'Rp62,09 M', 'note' => 'Melampaui target pendapatan tahun 2025.'],
        ['label' => 'Kelulusan UKOM', 'value' => '98,44%', 'note' => 'First taker kompeten tahun 2025.'],
        ['label' => 'Prodi Unggul', 'value' => '19 dari 20', 'note' => 'Setara 95% prodi unggul atau berakreditasi internasional.'],
    ];

    $missions = [
        'Menyelenggarakan pendidikan berkualitas, berorientasi global sesuai perkembangan ilmu dan teknologi terapan bidang kesehatan untuk menghasilkan lulusan berkarakter.',
        'Menyelenggarakan penelitian inovatif berbasis teknologi terapan dengan mengembangkan potensi bahan, kearifan lokal, dan pemberdayaan masyarakat.',
        'Menyelenggarakan pengabdian masyarakat yang inovatif dan berdampak luas melalui pemberdayaan masyarakat berbasis hasil penelitian.',
        'Menyelenggarakan kerjasama dengan pemangku kepentingan baik tingkat nasional maupun internasional untuk kemajuan bersama.',
        'Mengembangkan pendidikan dan program studi baru untuk menjawab perkembangan IPTEK bidang kesehatan dan tuntutan pelayanan kesehatan.',
        'Mengembangkan sumber daya yang berintegritas dan berkarakter dalam rangka terselenggaranya tata kelola yang baik, bersih, transparan, dan akuntabel sebagai BLU pendidikan.',
        'Mengimplementasikan transformasi kesehatan melalui sentra unggulan pendidikan diabetes mellitus pada kegiatan Tri Dharma Perguruan Tinggi.',
    ];

    $indicatorGroups = [
        [
            'title' => 'Keuangan dan BLU',
            'items' => [
                'Persentase EBITDA Margin',
                'Jumlah Pendapatan',
                'Jumlah Pendapatan dari Optimalisasi Aset dan Kerja Sama',
                'Modernisasi Pengelolaan BLU',
                'Indeks Akurasi Proyeksi Pendapatan BLU dan Belanja BLU',
                'Indeks Peningkatan Efisiensi Layanan BLU',
                'Nilai Kinerja Anggaran',
                'Persentase Realisasi Anggaran',
            ],
        ],
        [
            'title' => 'Akademik dan Lulusan',
            'items' => [
                'Persentase kelulusan uji kompetensi',
                'Persentase serapan lulusan bekerja maksimal 6 bulan',
                'Persentase serapan lulusan bekerja di sektor kesehatan',
                'Persentase serapan lulusan bekerja di luar negeri',
            ],
        ],
        [
            'title' => 'SDM',
            'items' => [
                'Rasio dosen tetap terhadap mahasiswa',
                'Persentase dosen fungsional dengan kualifikasi lektor kepala dan/atau guru besar',
                'Persentase dosen fungsional yang memiliki sertifikasi dosen',
                'Persentase dosen tetap yang memiliki kemampuan berbahasa Inggris',
            ],
        ],
        [
            'title' => 'Penelitian dan Pengabdian',
            'items' => [
                'Jumlah luaran penelitian yang dipublikasikan',
                'Jumlah produk inovasi yang dihilirisasi dan/atau dikomersialisasi',
                'Jumlah pengabdian yang dihasilkan',
            ],
        ],
        [
            'title' => 'Kelembagaan',
            'items' => [
                'Jumlah prestasi dosen',
                'Jumlah prestasi mahasiswa',
                'Persentase prodi yang memiliki akreditasi unggul dan/atau akreditasi internasional',
            ],
        ],
    ];

    $comparisonRows = [
        ['group' => 'Keuangan & BLU', 'indicator' => 'EBITDA Margin', 'target' => '4,47%', 'actual' => '8,90%', 'achievement' => '199,11%'],
        ['group' => 'Keuangan & BLU', 'indicator' => 'Pendapatan BLU', 'target' => 'Rp58,8 M', 'actual' => 'Rp62,09 M', 'achievement' => '105,60%'],
        ['group' => 'Keuangan & BLU', 'indicator' => 'Pendapatan Aset & Kerjasama', 'target' => 'Rp5,48 M', 'actual' => 'Rp6,24 M', 'achievement' => '113,87%'],
        ['group' => 'Keuangan & BLU', 'indicator' => 'Modernisasi Pengelolaan BLU', 'target' => '90%', 'actual' => '95,17%', 'achievement' => '105,74%'],
        ['group' => 'Keuangan & BLU', 'indicator' => 'Akurasi Proyeksi Pendapatan', 'target' => '3,7', 'actual' => '4,7', 'achievement' => '127,03%'],
        ['group' => 'Keuangan & BLU', 'indicator' => 'Nilai Kinerja Anggaran', 'target' => '92,35', 'actual' => '95,36', 'achievement' => '103,26%'],
        ['group' => 'Keuangan & BLU', 'indicator' => 'Realisasi Anggaran', 'target' => '96%', 'actual' => '98,31%', 'achievement' => '102,41%'],
        ['group' => 'Lulusan & Akademik', 'indicator' => 'Kelulusan Uji Kompetensi', 'target' => '98%', 'actual' => '98,44%', 'achievement' => '100,45%'],
        ['group' => 'Lulusan & Akademik', 'indicator' => 'Serapan Lulusan < 6 Bulan', 'target' => '50%', 'actual' => '51,54%', 'achievement' => '103,08%'],
        ['group' => 'Lulusan & Akademik', 'indicator' => 'Serapan Sektor Kesehatan', 'target' => '34%', 'actual' => '43,36%', 'achievement' => '127,53%'],
        ['group' => 'Lulusan & Akademik', 'indicator' => 'Serapan Luar Negeri', 'target' => '12%', 'actual' => '15,29%', 'achievement' => '127,42%'],
        ['group' => 'SDM', 'indicator' => 'Rasio Dosen Tetap : Mahasiswa', 'target' => '1:27', 'actual' => '1:28', 'achievement' => '101,15%'],
        ['group' => 'SDM', 'indicator' => 'Dosen Lektor Kepala / GB', 'target' => '32%', 'actual' => '33,33%', 'achievement' => '104,16%'],
        ['group' => 'SDM', 'indicator' => 'Dosen Bersertifikat (Serdos)', 'target' => '89%', 'actual' => '94,27%', 'achievement' => '105,92%'],
        ['group' => 'SDM', 'indicator' => 'Kemampuan Bahasa Inggris (TOEFL)', 'target' => '30%', 'actual' => '33,68%', 'achievement' => '112,27%'],
        ['group' => 'Penelitian & PKM', 'indicator' => 'HAKI / Inovasi Hilirisasi', 'target' => '66', 'actual' => '70', 'achievement' => '106,06%'],
        ['group' => 'Penelitian & PKM', 'indicator' => 'Pengabdian Masyarakat', 'target' => '45', 'actual' => '58', 'achievement' => '128,89%'],
        ['group' => 'Penelitian & PKM', 'indicator' => 'Publikasi Ilmiah', 'target' => '45', 'actual' => '62', 'achievement' => '137,78%'],
        ['group' => 'Kelembagaan', 'indicator' => 'Prestasi Dosen', 'target' => '21', 'actual' => '25', 'achievement' => '119,05%'],
        ['group' => 'Kelembagaan', 'indicator' => 'Prestasi Mahasiswa', 'target' => '98', 'actual' => '232', 'achievement' => '236,73%'],
        ['group' => 'Kelembagaan', 'indicator' => 'Prodi Akreditasi Unggul / Internasional', 'target' => '95%', 'actual' => '95%', 'achievement' => '100,00%'],
    ];

    $performanceHighlights = [
        [
            'title' => 'EBITDA Margin',
            'value' => '8,90%',
            'body' => 'EBITDA tahun 2025 mencapai Rp10.907.274.049 dari pendapatan operasional Rp122.570.609.341. Capaian ini ditopang peningkatan jumlah mahasiswa, pengembangan Unit Pengembangan Kompetensi, dan efisiensi belanja operasional.',
        ],
        [
            'title' => 'Pendapatan BLU',
            'value' => 'Rp62,09 miliar',
            'body' => 'Pendapatan BLU melampaui target Rp58,81 miliar. Kontributor utamanya adalah UKT mahasiswa, penyelenggaraan pelatihan melalui UPK, dan pendapatan jasa lembaga keuangan.',
        ],
        [
            'title' => 'Optimalisasi Aset',
            'value' => 'Rp6,25 miliar',
            'body' => 'Pendapatan optimalisasi aset dan kerja sama melampaui target dengan kenaikan dari pelatihan mandiri, pemanfaatan aset, penempatan deposito, serta penguatan layanan asrama.',
        ],
        [
            'title' => 'Modernisasi BLU',
            'value' => '95,17%',
            'body' => 'Modernisasi pengelolaan BLU ditopang integrasi data 15,17% dan operasionalisasi BIOS 80%, ditambah pemanfaatan website dan media sosial untuk branding BLU.',
        ],
        [
            'title' => 'Kelulusan UKOM',
            'value' => '98,44%',
            'body' => 'Sebanyak 1.454 dari 1.477 peserta first taker dinyatakan kompeten. Hasil ini menunjukkan standar pembekalan UKOM tetap terjaga, walaupun masih ada ruang perbaikan pada beberapa skema program.',
        ],
        [
            'title' => 'Akreditasi Prodi',
            'value' => '95%',
            'body' => 'Sebanyak 19 dari 20 program studi telah berakreditasi unggul. Faktor pendukung utamanya adalah komitmen mutu pendidikan, dukungan anggaran akreditasi, dan kualitas SDM.',
        ],
    ];

    $planningFocus = [
        'Peningkatan promosi dan seleksi mahasiswa baru, termasuk sosialisasi SNPMB dan pemanfaatan berbagai media sosial.',
        'Penguatan mutu pembelajaran melalui kurikulum OBE, workshop pembelajaran inovatif, serta penguatan karakter dan soft skill mahasiswa.',
        'Peningkatan sarana prasarana pendidikan, pengembangan fasilitas TI terintegrasi, dan penguatan koleksi digital perpustakaan.',
        'Penguatan penelitian terapan dan inovatif melalui proposal yang didanai, publikasi ilmiah bereputasi, dan peningkatan HAKI/paten.',
        'Penguatan pengabdian masyarakat melalui proposal PKM, publikasi hasil PKM, dan pengembangan desa binaan aktif.',
        'Penguatan jejaring kerja sama nasional dan internasional untuk implementasi Tri Dharma, riset, dan penyerapan lulusan.',
    ];

    $efficiencyNotes = [
        ['title' => 'Sarana dan prasarana', 'body' => 'Efisiensi dilakukan melalui aplikasi SIKAP, SIAKAD, dan pemanfaatan SRIKANDI untuk persuratan sehingga proses disposisi lebih cepat dan penggunaan kertas berkurang.'],
        ['title' => 'Sumber daya', 'body' => 'Penggunaan listrik dan air dioptimalkan, serta pola pembelajaran hybrid membantu menghemat penggunaan lampu dan AC.'],
        ['title' => 'Anggaran', 'body' => 'Sebagai BLU, Poltekkes menerapkan cross-subsidy, pengadaan terpusat, dan efisiensi belanja barang serta perjalanan dinas tanpa mengganggu capaian kinerja utama.'],
    ];

    $followUpSteps = [
        'Meningkatkan rasio pendapatan melalui kenaikan jumlah mahasiswa, optimalisasi aset, dan pelaksanaan kegiatan yang lebih efektif dan efisien.',
        'Menjaga standar kompetensi lulusan untuk meningkatkan penyerapan kerja di fasilitas kesehatan pemerintah, swasta, dan luar negeri.',
        'Memfasilitasi alumni memasuki dunia kerja melalui perluasan jejaring rekrutmen dengan institusi dalam dan luar negeri.',
        'Memberikan pembekalan dan pendampingan lebih intensif agar kelulusan uji kompetensi mendekati 100%.',
        'Meningkatkan kapasitas peneliti melalui pelatihan, workshop, roadmap penelitian, dan kebijakan riset yang sejalan dengan prioritas Kemenkes.',
        'Memfasilitasi publikasi ilmiah dosen di jurnal nasional terakreditasi dan jurnal internasional bereputasi, termasuk melalui insentif dan pendampingan artikel.',
        'Mengembangkan berbagai inovasi civitas akademika agar menghasilkan produk unggulan dan hilirisasi hasil penelitian yang dapat dikomersialisasi.',
        'Memperluas kerja sama dengan pemerintah daerah, institusi swasta, industri, dan mitra luar negeri untuk mendukung Tri Dharma dan penyerapan lulusan.',
        'Meningkatkan kemampuan bahasa Inggris dosen, prestasi dosen dan mahasiswa, serta menjaga akreditasi unggul program studi.',
        'Mengembangkan aplikasi layanan akademik dan non-akademik yang terintegrasi agar pelayanan publik semakin cepat, akurat, dan prima.',
    ];

    $documentYears = [
        ['label' => 'LAKIP 2025', 'href' => route('unit.administrasi-umum.lakip'), 'current' => true],
        ['label' => 'LAKIP 2024', 'href' => route('unit.administrasi-umum.lakip.2024'), 'current' => false],
        ['label' => 'LAKIP 2023', 'href' => route('unit.administrasi-umum.lakip.2023'), 'current' => false],
    ];
@endphp

@section('content')
<div class="relative overflow-hidden bg-slate-950">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.35),_transparent_38%),radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.25),_transparent_35%)]"></div>
    <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <nav class="mb-5 flex flex-wrap items-center gap-2 text-sm text-teal-100/80">
            <a href="{{ route('home') }}" class="transition hover:text-white">Beranda</a>
            <span>/</span>
            <a href="{{ route('unit.show', $page->slug) }}" class="transition hover:text-white">{{ $page->judul }}</a>
            <span>/</span>
            <span class="text-white">LAKIP 2025</span>
        </nav>

        <div class="grid gap-10 lg:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)] lg:items-end">
            <div>
                <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-teal-100">Bagian Keuangan</span>
                <h1 class="mt-5 max-w-4xl text-4xl font-black leading-tight text-white sm:text-5xl lg:text-6xl">LAKIP 2025 dalam format HTML yang lengkap, terbaca, dan ramah stakeholder.</h1>
                <p class="mt-5 max-w-3xl text-base leading-8 text-slate-200 sm:text-lg">Halaman ini menuliskan ulang isi utama dokumen LAKIP 2025 ke dalam HTML yang lebih ringan agar pembaca dapat menelusuri bab, indikator, dan tindak lanjut penting tanpa bergantung pada tampilan file PDF.</p>
                <div class="mt-6 flex flex-wrap gap-3 text-sm font-semibold">
                    @foreach ($documentYears as $documentYear)
                    <a href="{{ $documentYear['href'] }}" class="inline-flex items-center rounded-full border px-4 py-2 transition {{ $documentYear['current'] ? 'border-white/20 bg-white text-slate-900' : 'border-white/15 bg-white/10 text-white hover:bg-white/20' }}">{{ $documentYear['label'] }}</a>
                    @endforeach
                </div>
            </div>

            <aside class="rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl shadow-teal-950/30 backdrop-blur">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-teal-100">Ringkas</p>
                <dl class="mt-5 space-y-4 text-sm text-slate-100">
                    <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-3">
                        <dt class="text-slate-300">Dokumen</dt>
                        <dd class="text-right font-semibold">LAKIP Poltekkes Kemenkes Bandung 2025</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-3">
                        <dt class="text-slate-300">Tanggal dokumen</dt>
                        <dd class="text-right font-semibold">31 Desember 2025</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-3">
                        <dt class="text-slate-300">Cakupan</dt>
                        <dd class="text-right font-semibold">4 bab utama + ringkasan eksekutif</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-300">Tujuan</dt>
                        <dd class="text-right font-semibold">Akuntabilitas, evaluasi, dan transparansi kinerja</dd>
                    </div>
                </dl>
            </aside>
        </div>
    </div>
</div>

<nav aria-label="Navigasi isi halaman LAKIP 2025" class="sticky top-16 z-30 border-b border-slate-200/90 bg-white/95 shadow-sm backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
        <div class="overflow-x-auto pb-1">
            <div class="flex min-w-max gap-3 text-sm font-semibold text-slate-600">
            <a href="#ikhtisar" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-cyan-50 hover:text-cyan-700">Ikhtisar</a>
            <a href="#ringkasan-eksekutif" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-cyan-50 hover:text-cyan-700">Ringkasan Eksekutif</a>
            <a href="#indikator" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-cyan-50 hover:text-cyan-700">Indikator Utama</a>
            <a href="#capaian" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-cyan-50 hover:text-cyan-700">Capaian 2025</a>
            <a href="#bab-utama" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-cyan-50 hover:text-cyan-700">Bab Utama</a>
            <a href="#tindak-lanjut" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-cyan-50 hover:text-cyan-700">Tindak Lanjut</a>
            <a href="#dokumen-lengkap" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-cyan-50 hover:text-cyan-700">Dokumen Lengkap</a>
            </div>
        </div>
    </div>
</nav>

<section id="ikhtisar" class="scroll-mt-44 bg-slate-50 py-14 sm:py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-700">Ikhtisar</p>
                <h2 class="mt-2 text-3xl font-black text-slate-900">Sorotan cepat untuk pengunjung dan stakeholder</h2>
            </div>
            <p class="max-w-2xl text-sm leading-7 text-slate-600">LAKIP 2025 menunjukkan capaian kinerja Poltekkes Kemenkes Bandung yang secara umum berada di atas target, dengan penguatan pada area keuangan BLU, akademik, SDM, penelitian, pengabdian, dan kelembagaan.</p>
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
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-700">Ringkasan Eksekutif</p>
            <h2 class="mt-2 text-3xl font-black text-slate-900">Arah strategis, mandat, dan gambaran umum capaian</h2>
            <div class="mt-6 space-y-5 text-base leading-8 text-slate-700">
                <p>Laporan Kinerja Instansi Pemerintah (LAKIP) Tahun 2025 disusun sebagai bentuk pertanggungjawaban kinerja Direktur beserta seluruh jajaran Poltekkes Kemenkes Bandung kepada Menteri Kesehatan dan para pemangku kepentingan. Dokumen ini merangkum pelaksanaan tugas, program, dan capaian indikator kinerja utama dalam satu tahun anggaran.</p>
                <p>Visi institusi adalah menjadi perguruan tinggi kesehatan yang unggul dalam teknologi terapan, berkarakter, dan berdaya saing global. Untuk mewujudkannya, Poltekkes Kemenkes Bandung menjalankan Tri Dharma Perguruan Tinggi sekaligus memperkuat tata kelola BLU, transformasi digital, dan jejaring kerja sama.</p>
                <p>Secara umum, performa tahun 2025 menunjukkan tren positif. Beberapa indikator yang menonjol antara lain EBITDA margin, pendapatan BLU, optimalisasi aset, realisasi anggaran, serapan lulusan, pengabdian masyarakat, prestasi mahasiswa, dan akreditasi program studi.</p>
            </div>
        </div>

        <aside class="rounded-[2rem] border border-teal-100 bg-teal-50 p-6 sm:p-8">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-700">Tujuh Misi</p>
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
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-200">22 indikator utama</p>
                <h2 class="mt-2 text-3xl font-black">Peta indikator kinerja yang digunakan pada LAKIP 2025</h2>
            </div>
            <p class="max-w-2xl text-sm leading-7 text-slate-300">Daftar ini disusun ulang dari indikator utama dalam dokumen untuk memudahkan pembacaan cepat berdasarkan tema besar kinerja institusi.</p>
        </div>

        <div class="mt-8 grid gap-5 lg:grid-cols-2 xl:grid-cols-3">
            @foreach ($indicatorGroups as $group)
            <article class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm">
                <h3 class="text-lg font-black text-white">{{ $group['title'] }}</h3>
                <ul class="mt-4 space-y-3 text-sm leading-7 text-slate-200">
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
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-700">Capaian 2025</p>
                <h2 class="mt-2 text-3xl font-black text-slate-900">Perbandingan target, realisasi, dan capaian yang disorot dalam dokumen</h2>
            </div>
            <p class="max-w-2xl text-sm leading-7 text-slate-600">Tabel berikut menyajikan indikator strategis yang dibandingkan terhadap target PK 2025 dan target jangka menengah Renstra 2025-2029.</p>
        </div>

        <div class="mt-8 overflow-hidden rounded-[2rem] border border-slate-200 shadow-sm shadow-slate-200/60">
            <div class="overflow-x-auto bg-white">
                <table class="min-w-full divide-y divide-slate-200 text-sm text-slate-700">
                    <thead class="bg-slate-900 text-left text-xs uppercase tracking-[0.2em] text-slate-200">
                        <tr>
                            <th class="px-5 py-4">Kelompok</th>
                            <th class="px-5 py-4">Indikator</th>
                            <th class="px-5 py-4">Target 2025</th>
                            <th class="px-5 py-4">Realisasi 2025</th>
                            <th class="px-5 py-4">Capaian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($comparisonRows as $row)
                        <tr class="align-top hover:bg-slate-50">
                            <td class="px-5 py-4 font-semibold text-slate-500">{{ $row['group'] }}</td>
                            <td class="px-5 py-4 font-semibold text-slate-900">{{ $row['indicator'] }}</td>
                            <td class="px-5 py-4">{{ $row['target'] }}</td>
                            <td class="px-5 py-4">{{ $row['actual'] }}</td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700">{{ $row['achievement'] }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-10 grid gap-5 lg:grid-cols-2 xl:grid-cols-3">
            @foreach ($performanceHighlights as $item)
            <article class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                <div class="flex items-end justify-between gap-4">
                    <h3 class="text-lg font-black text-slate-900">{{ $item['title'] }}</h3>
                    <span class="text-xl font-black text-teal-700">{{ $item['value'] }}</span>
                </div>
                <p class="mt-4 text-sm leading-7 text-slate-600">{{ $item['body'] }}</p>
            </article>
            @endforeach
        </div>
    </div>
</section>

<section id="bab-utama" class="scroll-mt-44 bg-slate-50 py-14 sm:py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-700">Bab Utama</p>
            <h2 class="mt-2 text-3xl font-black text-slate-900">Isi dokumen yang ditata ulang untuk tampilan web</h2>
        </div>

        <div class="mt-8 space-y-5">
            <details open class="group rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-teal-700">Bab I</p>
                        <h3 class="mt-1 text-2xl font-black text-slate-900">Pendahuluan</h3>
                    </div>
                    <span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-600 transition group-open:bg-teal-50 group-open:text-teal-700">Buka / tutup</span>
                </summary>
                <div class="mt-6 grid gap-5 lg:grid-cols-2">
                    <article class="rounded-3xl bg-slate-50 p-5">
                        <h4 class="text-lg font-black text-slate-900">Latar Belakang</h4>
                        <p class="mt-3 text-sm leading-7 text-slate-600">LAKIP disusun sebagai dokumen akuntabilitas untuk menunjukkan bagaimana Poltekkes Kemenkes Bandung menjalankan tugas pokok dan fungsi sesuai mandat peraturan perundang-undangan, khususnya terkait keuangan negara, perencanaan pembangunan nasional, dan sistem akuntabilitas kinerja instansi pemerintah.</p>
                    </article>
                    <article class="rounded-3xl bg-slate-50 p-5">
                        <h4 class="text-lg font-black text-slate-900">Maksud dan Tujuan</h4>
                        <ul class="mt-3 space-y-2 text-sm leading-7 text-slate-600">
                            <li>Meningkatkan akuntabilitas, kredibilitas, dan kepercayaan masyarakat.</li>
                            <li>Menjelaskan ketercapaian kinerja atas perjanjian yang telah ditetapkan.</li>
                            <li>Menjadi dasar evaluasi dan perbaikan berkelanjutan pada tahun berikutnya.</li>
                        </ul>
                    </article>
                    <article class="rounded-3xl bg-slate-50 p-5">
                        <h4 class="text-lg font-black text-slate-900">Kedudukan dan Tugas Pokok</h4>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Poltekkes Kemenkes Bandung merupakan UPT Kementerian Kesehatan yang melaksanakan pendidikan vokasi bidang kesehatan pada jenjang Diploma III, Sarjana Terapan, dan Profesi. Fungsi utamanya meliputi pendidikan, penelitian, pengabdian masyarakat, penjaminan mutu, kerja sama, sistem informasi, humas, hingga administrasi umum.</p>
                    </article>
                    <article class="rounded-3xl bg-slate-50 p-5">
                        <h4 class="text-lg font-black text-slate-900">Lingkungan Strategis</h4>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Faktor yang memengaruhi kinerja antara lain mahasiswa, SDM pendidik dan tenaga kependidikan, sarana prasarana, jejaring kerja sama, sumber anggaran, dan program kegiatan yang dilaksanakan sepanjang tahun.</p>
                    </article>
                </div>
            </details>

            <details class="group rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-teal-700">Bab II</p>
                        <h3 class="mt-1 text-2xl font-black text-slate-900">Perencanaan dan Penetapan Kinerja</h3>
                    </div>
                    <span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-600 transition group-open:bg-teal-50 group-open:text-teal-700">Buka / tutup</span>
                </summary>
                <div class="mt-6 grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                    <article class="rounded-3xl bg-slate-50 p-5">
                        <h4 class="text-lg font-black text-slate-900">Fokus Rancangan Aksi</h4>
                        <ul class="mt-3 space-y-2 text-sm leading-7 text-slate-600">
                            @foreach ($planningFocus as $focus)
                            <li>{{ $focus }}</li>
                            @endforeach
                        </ul>
                    </article>
                    <article class="rounded-3xl bg-slate-50 p-5">
                        <h4 class="text-lg font-black text-slate-900">Sasaran Strategis 2025-2029</h4>
                        <ul class="mt-3 space-y-2 text-sm leading-7 text-slate-600">
                            <li>Masyarakat sehat.</li>
                            <li>Komunitas gaya hidup sehat.</li>
                            <li>Layanan kesehatan yang baik, adil, dan terjangkau.</li>
                            <li>Sistem kesehatan yang kuat.</li>
                            <li>Tata kelola pembiayaan kesehatan yang efektif.</li>
                            <li>Teknologi kesehatan yang maju.</li>
                        </ul>
                        <div class="mt-5 rounded-3xl border border-dashed border-teal-200 bg-white p-4 text-sm leading-7 text-slate-600">
                            Dokumen juga memuat target jangka menengah terukur, misalnya peningkatan promosi mahasiswa baru, penguatan kurikulum OBE, kenaikan koleksi digital perpustakaan, peningkatan HAKI/paten, serta penyiapan prodi untuk akreditasi unggul dan internasional.
                        </div>
                    </article>
                </div>
            </details>

            <details class="group rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-teal-700">Bab III</p>
                        <h3 class="mt-1 text-2xl font-black text-slate-900">Akuntabilitas Kinerja</h3>
                    </div>
                    <span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-600 transition group-open:bg-teal-50 group-open:text-teal-700">Buka / tutup</span>
                </summary>
                <div class="mt-6 grid gap-5 lg:grid-cols-3">
                    <article class="rounded-3xl bg-slate-50 p-5 lg:col-span-2">
                        <h4 class="text-lg font-black text-slate-900">Garis Besar Analisis</h4>
                        <div class="mt-3 space-y-4 text-sm leading-7 text-slate-600">
                            <p>Analisis capaian kinerja tahun 2025 dilakukan dengan membandingkan performa tahun berjalan dengan target yang ditetapkan, capaian tahun sebelumnya, serta target akhir Renstra 2025-2029. Dokumen menilai faktor pendukung, kendala, dan peluang perbaikan untuk setiap indikator utama.</p>
                            <p>Area yang paling kuat berada pada keuangan BLU, penguatan pendapatan, modernisasi pengelolaan, serapan lulusan, pengabdian masyarakat, prestasi mahasiswa, dan capaian akreditasi. Di sisi lain, dokumen tetap menekankan kebutuhan peningkatan berkelanjutan pada penelitian, pengabdian, dan penguatan kompetensi SDM.</p>
                        </div>
                    </article>
                    <article class="rounded-3xl border border-teal-100 bg-teal-50 p-5">
                        <h4 class="text-lg font-black text-slate-900">Nilai Penting</h4>
                        <dl class="mt-4 space-y-4 text-sm text-slate-700">
                            <div>
                                <dt class="font-semibold text-slate-500">Nilai LAKIP 2024</dt>
                                <dd class="mt-1 text-xl font-black text-teal-700">90,00</dd>
                            </div>
                            <div>
                                <dt class="font-semibold text-slate-500">Pendapatan Aset & Kerja Sama</dt>
                                <dd class="mt-1 text-xl font-black text-teal-700">Rp6,24 M</dd>
                            </div>
                            <div>
                                <dt class="font-semibold text-slate-500">Prestasi Mahasiswa</dt>
                                <dd class="mt-1 text-xl font-black text-teal-700">232 capaian</dd>
                            </div>
                        </dl>
                    </article>
                </div>
            </details>

            <details class="group rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-teal-700">Bab IV</p>
                        <h3 class="mt-1 text-2xl font-black text-slate-900">Penutup dan Arah Perbaikan</h3>
                    </div>
                    <span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-600 transition group-open:bg-teal-50 group-open:text-teal-700">Buka / tutup</span>
                </summary>
                <div class="mt-6 space-y-4 text-sm leading-7 text-slate-600">
                    <p>Dokumen penutup menyimpulkan bahwa hasil pengukuran atas 22 indikator utama pada tahun 2025 secara umum telah mencapai lebih dari 100% terhadap target. Hasil ini menjadi dasar untuk menyusun agenda peningkatan kinerja berikutnya agar capaian tidak hanya terjaga, tetapi juga lebih merata antar-area kinerja.</p>
                    <p>Penutup juga menekankan kesinambungan upaya peningkatan pendapatan BLU, kualitas lulusan, penyerapan kerja, penelitian, publikasi, inovasi, pengabdian masyarakat, kerja sama, kemampuan bahasa Inggris, prestasi sivitas akademika, serta transformasi layanan berbasis sistem informasi terintegrasi.</p>
                </div>
            </details>
        </div>
    </div>
</section>

<section class="bg-white py-14 sm:py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-5 lg:grid-cols-3">
            @foreach ($efficiencyNotes as $note)
            <article class="rounded-[2rem] border border-slate-200 bg-slate-50 p-6">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-teal-700">Efisiensi</p>
                <h3 class="mt-2 text-xl font-black text-slate-900">{{ $note['title'] }}</h3>
                <p class="mt-4 text-sm leading-7 text-slate-600">{{ $note['body'] }}</p>
            </article>
            @endforeach
        </div>
    </div>
</section>

<section id="tindak-lanjut" class="scroll-mt-44 bg-slate-950 py-14 text-white sm:py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-200">Tindak Lanjut</p>
                <h2 class="mt-2 text-3xl font-black">Agenda peningkatan kinerja yang ditegaskan dalam penutup LAKIP</h2>
            </div>
            <p class="max-w-2xl text-sm leading-7 text-slate-300">Daftar berikut disusun ulang dari butir-butir tindak lanjut pada Bab IV untuk memudahkan pembacaan dan monitoring lanjutan.</p>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2">
            @foreach ($followUpSteps as $step)
            <article class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <p class="text-sm leading-7 text-slate-200">{{ $step }}</p>
            </article>
            @endforeach
        </div>
    </div>
</section>

@include('unit.partials.lakip-2025-html-document')
@endsection