@php
    $forewordParagraphs = [
        'Versi HTML LAKIP 2025 ini menuliskan ulang isi utama dokumen resmi agar pembaca dapat mengikuti arah kebijakan, indikator, dan tindak lanjut institusi tanpa harus membuka reproduksi PDF per halaman.',
        'Struktur yang dipertahankan meliputi pengantar singkat, ringkasan eksekutif, pokok isi Bab I sampai Bab IV, indikator-indikator utama yang menjadi sorotan, serta lampiran ringkas yang merangkum fokus bukti dukung dan tindak lanjut.',
        'Pendekatan ini membuat halaman utama lebih ringan, lebih mudah dipindai di perangkat mobile, dan lebih nyaman untuk stakeholder yang ingin membaca substansi kinerja tanpa terganggu tata letak PDF asli.',
    ];

    $executiveIndicatorItems = [
        'Kategori akuntabilitas kinerja tetap berada pada level A dengan catatan nilai 2022-2024 sebesar 92,00; 92,90; dan 90,00.',
        'Realisasi anggaran 2025 mencapai 98,31% dari target 96%, menunjukkan pelaksanaan anggaran yang lebih kuat dibanding tahun sebelumnya.',
        'Pendapatan BLU mencapai Rp62,09 miliar dari target Rp58,8 miliar dengan capaian 105,60%.',
        'Pendapatan dari optimalisasi aset dan kerja sama mencapai Rp6,24 miliar dengan capaian 113,87%.',
        'Modernisasi pengelolaan BLU mencapai 95,17% dari target 90%, ditopang integrasi data dan operasionalisasi sistem BIOS.',
        'Kelulusan uji kompetensi first taker mencapai 98,44% dan menjaga mutu lulusan pada jalur vokasi kesehatan.',
        'Serapan lulusan di sektor kesehatan mencapai 43,36% dari target 34%, sedangkan serapan ke luar negeri mencapai 15,29% dari target 12%.',
        'Sebanyak 19 dari 20 program studi telah berakreditasi unggul atau setara akreditasi internasional.',
        'Prestasi mahasiswa melonjak menjadi 232 capaian dari target 98 capaian.',
        'Publikasi ilmiah, pengabdian masyarakat, dan hilirisasi inovasi juga semuanya berada di atas target tahunan.',
    ];

    $documentOutline = [
        'Bab I Pendahuluan: dasar penyusunan, maksud dan tujuan, kedudukan, serta lingkungan strategis institusi.',
        'Bab II Perencanaan dan Penetapan Kinerja: rancangan aksi, sasaran 2025-2029, dan fokus penguatan layanan.',
        'Bab III Akuntabilitas Kinerja: analisis indikator utama, capaian BLU, mutu lulusan, SDM, penelitian, pengabdian, dan prestasi.',
        'Bab IV Penutup: kesimpulan umum dan agenda tindak lanjut berkelanjutan.',
        'Lampiran Ringkas: rangkuman bukti dukung, angka kunci, dan area yang memerlukan kesinambungan penguatan.',
    ];

    $chapterOneSections = [
        [
            'title' => 'A. Latar Belakang dan Dasar Penyusunan',
            'paragraphs' => [
                'LAKIP 2025 disusun sebagai bentuk pertanggungjawaban Direktur dan seluruh jajaran Poltekkes Kemenkes Bandung kepada Menteri Kesehatan dan stakeholder atas pelaksanaan tugas, program, dan indikator kinerja utama selama satu tahun anggaran.',
                'Dokumen ini menempatkan akuntabilitas, evaluasi capaian, dan transparansi kinerja sebagai landasan utama, sekaligus menjadi bahan pembelajaran untuk penyusunan langkah perbaikan tahun berikutnya.',
            ],
        ],
        [
            'title' => 'B. Maksud, Tujuan, dan Nilai Guna',
            'paragraphs' => [
                'Penyusunan LAKIP diarahkan untuk memperlihatkan keterkaitan antara target perjanjian kinerja, realisasi yang dicapai, dan agenda tindak lanjut yang harus dijaga secara konsisten pada periode berikutnya.',
            ],
            'bullets' => [
                'Menjelaskan kinerja institusi secara ringkas dan terukur kepada pimpinan, regulator, auditor, dan publik.',
                'Menjadi dasar evaluasi atas program kerja, penganggaran, dan efektivitas implementasi strategi.',
                'Memperkuat budaya kerja berbasis indikator dan hasil, bukan sekadar pelaksanaan kegiatan.',
            ],
        ],
        [
            'title' => 'C. Kedudukan dan Lingkungan Strategis',
            'paragraphs' => [
                'Poltekkes Kemenkes Bandung menjalankan pendidikan, penelitian, dan pengabdian kepada masyarakat dengan dukungan tata kelola BLU, sistem informasi, jejaring kerja sama, sarana prasarana, dan sumber daya manusia yang terus diperkuat.',
                'Faktor strategis yang paling memengaruhi pencapaian 2025 mencakup promosi mahasiswa baru, mutu pembelajaran, efektivitas layanan digital, riset terapan, penyerapan lulusan, serta perluasan kerja sama nasional dan internasional.',
            ],
        ],
    ];

    $chapterTwoSections = [
        [
            'title' => 'Fokus Rancangan Aksi 2025',
            'items' => $planningFocus,
        ],
        [
            'title' => 'Arah Sasaran Strategis',
            'items' => [
                'Masyarakat sehat dan komunitas dengan gaya hidup sehat.',
                'Layanan kesehatan yang baik, adil, dan terjangkau.',
                'Sistem kesehatan yang kuat dan pembiayaan kesehatan yang efektif.',
                'Pemanfaatan teknologi kesehatan yang semakin maju dan terintegrasi.',
                'Penguatan mutu pendidikan, riset, pengabdian, dan tata kelola BLU yang akuntabel.',
            ],
        ],
    ];

    $explorerBase = [
        [
            'key' => 'modernisasi-blu',
            'group' => 'Keuangan & BLU',
            'label' => 'Modernisasi Pengelolaan BLU',
            'target' => 90,
            'target_label' => '90,00%',
            'actual' => 95.17,
            'actual_label' => '95,17%',
            'achievement' => 105.74,
            'achievement_label' => '105,74%',
            'summary' => 'Capaian ini ditopang integrasi data 15,17%, operasionalisasi BIOS 80%, dan penguatan branding layanan BLU melalui media digital.',
        ],
        [
            'key' => 'realisasi-anggaran',
            'group' => 'Keuangan & BLU',
            'label' => 'Realisasi Anggaran',
            'target' => 96,
            'target_label' => '96,00%',
            'actual' => 98.31,
            'actual_label' => '98,31%',
            'achievement' => 102.41,
            'achievement_label' => '102,41%',
            'summary' => 'Pelaksanaan anggaran berada di atas target dan memperlihatkan pengendalian realisasi yang lebih baik terhadap pagu efektif tahun berjalan.',
        ],
        [
            'key' => 'ukom',
            'group' => 'Lulusan & Akademik',
            'label' => 'Kelulusan Uji Kompetensi',
            'target' => 98,
            'target_label' => '98,00%',
            'actual' => 98.44,
            'actual_label' => '98,44%',
            'achievement' => 100.45,
            'achievement_label' => '100,45%',
            'summary' => 'Sebanyak 1.454 dari 1.477 peserta first taker dinyatakan kompeten sehingga mutu lulusan tetap terjaga sangat tinggi.',
        ],
        [
            'key' => 'serapan-sektor-kesehatan',
            'group' => 'Lulusan & Akademik',
            'label' => 'Serapan Lulusan di Sektor Kesehatan',
            'target' => 34,
            'target_label' => '34,00%',
            'actual' => 43.36,
            'actual_label' => '43,36%',
            'achievement' => 127.53,
            'achievement_label' => '127,53%',
            'summary' => 'Serapan lulusan di sektor kesehatan melampaui target dengan dukungan mutu lulusan, jaringan mitra, dan kebutuhan pasar kerja yang tetap tinggi.',
        ],
        [
            'key' => 'serapan-luar-negeri',
            'group' => 'Lulusan & Akademik',
            'label' => 'Serapan Lulusan di Luar Negeri',
            'target' => 12,
            'target_label' => '12,00%',
            'actual' => 15.29,
            'actual_label' => '15,29%',
            'achievement' => 127.42,
            'achievement_label' => '127,42%',
            'summary' => 'Penempatan kerja internasional terus menguat seiring perluasan jejaring rekrutmen dan kesiapan lulusan yang lebih kompetitif.',
        ],
        [
            'key' => 'serdos',
            'group' => 'SDM',
            'label' => 'Dosen Bersertifikat (Serdos)',
            'target' => 89,
            'target_label' => '89,00%',
            'actual' => 94.27,
            'actual_label' => '94,27%',
            'achievement' => 105.92,
            'achievement_label' => '105,92%',
            'summary' => 'Penguatan sertifikasi dosen menunjukkan kapasitas akademik tenaga pendidik semakin baik untuk menopang mutu pembelajaran.',
        ],
        [
            'key' => 'bahasa-inggris',
            'group' => 'SDM',
            'label' => 'Kemampuan Bahasa Inggris Dosen',
            'target' => 30,
            'target_label' => '30,00%',
            'actual' => 33.68,
            'actual_label' => '33,68%',
            'achievement' => 112.27,
            'achievement_label' => '112,27%',
            'summary' => 'Kemampuan bahasa Inggris dosen terus diperkuat untuk menopang kelas internasional, kolaborasi global, dan daya saing institusi.',
        ],
        [
            'key' => 'akreditasi-prodi',
            'group' => 'Kelembagaan',
            'label' => 'Prodi Akreditasi Unggul / Internasional',
            'target' => 95,
            'target_label' => '95,00%',
            'actual' => 95,
            'actual_label' => '95,00%',
            'achievement' => 100,
            'achievement_label' => '100,00%',
            'summary' => 'Sebanyak 19 dari 20 program studi telah berada pada kategori unggul atau setara akreditasi internasional, menjaga reputasi mutu akademik institusi.',
        ],
    ];

    $explorerScale = 140;
    $metricStyles = [
        'target' => [
            'label' => 'Target',
            'bar_style' => 'background: linear-gradient(180deg, #dbeafe 0%, #60a5fa 48%, #1d4ed8 100%);',
            'focus_shadow' => '0 0 0 4px rgba(147, 197, 253, 0.75), 0 24px 44px -22px rgba(29, 78, 216, 0.6)',
            'badge_style' => 'background-color: #dbeafe; color: #1e3a8a;',
            'dot_style' => 'background-color: #93c5fd;',
        ],
        'actual' => [
            'label' => 'Realisasi',
            'bar_style' => 'background: linear-gradient(180deg, #99f6e4 0%, #2dd4bf 48%, #0f766e 100%);',
            'focus_shadow' => '0 0 0 4px rgba(94, 234, 212, 0.76), 0 24px 44px -22px rgba(15, 118, 110, 0.66)',
            'badge_style' => 'background-color: #99f6e4; color: #134e4a;',
            'dot_style' => 'background-color: #5eead4;',
        ],
        'achievement' => [
            'label' => 'Capaian',
            'bar_style' => 'background: linear-gradient(180deg, #fde68a 0%, #f59e0b 48%, #b45309 100%);',
            'focus_shadow' => '0 0 0 4px rgba(252, 211, 77, 0.72), 0 24px 44px -22px rgba(180, 83, 9, 0.7)',
            'badge_style' => 'background-color: #fde68a; color: #78350f;',
            'dot_style' => 'background-color: #fcd34d;',
        ],
    ];

    $performanceExplorer = array_map(function (array $item) use ($metricStyles, $explorerScale) {
        return [
            'key' => $item['key'],
            'group' => $item['group'],
            'label' => $item['label'],
            'summary' => $item['summary'],
            'metrics' => [
                [
                    'key' => 'target',
                    'label' => $metricStyles['target']['label'],
                    'display' => $item['target_label'],
                    'value' => $item['target'],
                    'height' => max(18, ($item['target'] / $explorerScale) * 100),
                    'description' => 'Target indikator yang ditetapkan untuk tahun 2025.',
                    'bar_style' => $metricStyles['target']['bar_style'],
                    'focus_shadow' => $metricStyles['target']['focus_shadow'],
                    'badge_style' => $metricStyles['target']['badge_style'],
                    'dot_style' => $metricStyles['target']['dot_style'],
                ],
                [
                    'key' => 'actual',
                    'label' => $metricStyles['actual']['label'],
                    'display' => $item['actual_label'],
                    'value' => $item['actual'],
                    'height' => max(18, ($item['actual'] / $explorerScale) * 100),
                    'description' => 'Realisasi aktual yang dicapai oleh Poltekkes pada indikator ini selama tahun 2025.',
                    'bar_style' => $metricStyles['actual']['bar_style'],
                    'focus_shadow' => $metricStyles['actual']['focus_shadow'],
                    'badge_style' => $metricStyles['actual']['badge_style'],
                    'dot_style' => $metricStyles['actual']['dot_style'],
                ],
                [
                    'key' => 'achievement',
                    'label' => $metricStyles['achievement']['label'],
                    'display' => $item['achievement_label'],
                    'value' => $item['achievement'],
                    'height' => max(18, ($item['achievement'] / $explorerScale) * 100),
                    'description' => 'Persentase capaian yang membandingkan realisasi terhadap target indikator.',
                    'bar_style' => $metricStyles['achievement']['bar_style'],
                    'focus_shadow' => $metricStyles['achievement']['focus_shadow'],
                    'badge_style' => $metricStyles['achievement']['badge_style'],
                    'dot_style' => $metricStyles['achievement']['dot_style'],
                ],
            ],
        ];
    }, $explorerBase);

    $appendixHighlights = [
        'Lampiran resmi dokumen mencakup reproduksi tabel, grafik, dan rincian data dukung yang menjelaskan per indikator bagaimana target, realisasi, dan tren kinerja dicapai.',
        'Area yang paling kuat dalam bukti dukung 2025 berada pada kenaikan pendapatan BLU, efektivitas penganggaran, publikasi ilmiah, pengabdian masyarakat, prestasi mahasiswa, dan akreditasi program studi.',
        'Area yang tetap perlu kesinambungan penguatan adalah riset terapan, hilirisasi inovasi, perluasan jejaring lulusan, dan transformasi layanan digital yang terintegrasi di seluruh unit.',
    ];
@endphp

<section id="dokumen-lengkap" class="scroll-mt-44 bg-slate-100 py-14 sm:py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 sm:p-8">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] lg:items-start">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Dokumen HTML Ringan</p>
                    <h2 class="mt-2 text-3xl font-black text-slate-900">Isi utama LAKIP 2025 yang ditulis ulang ke HTML</h2>
                    <p class="mt-5 max-w-3xl text-sm leading-7 text-slate-600">Bagian ini menggantikan pola lama yang membuka reproduksi PDF per bab. Fokusnya adalah membuat isi pokok dokumen 2025 lebih cepat dimuat, tetap nyaman dibaca di mobile, dan lebih mudah dipindai oleh pimpinan, auditor, mitra, maupun publik umum.</p>
                    <div class="mt-6 flex flex-wrap gap-3 text-sm font-semibold text-slate-600">
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-4 py-2">HTML semantik</span>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-4 py-2">Tanpa facsimile per bab</span>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-4 py-2">Grafik dan angka utama dipertahankan</span>
                    </div>
                </div>

                <aside class="rounded-[1.75rem] border border-teal-100 bg-teal-50 p-5 sm:p-6">
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-700">Cakupan HTML</p>
                    <ul class="mt-4 space-y-3 text-sm leading-7 text-slate-700">
                        <li>Ringkasan eksekutif dan pengantar pembacaan dokumen.</li>
                        <li>Pokok isi Bab I sampai Bab IV yang ditulis ulang ke format web.</li>
                        <li>Explorer interaktif untuk indikator 2025 yang paling representatif.</li>
                        <li>Ringkasan lampiran dan tindak lanjut tanpa harus membuka tampilan PDF lama.</li>
                    </ul>
                </aside>
            </div>

            <div class="mt-8 overflow-x-auto pb-1">
                <div class="flex min-w-max gap-3 text-sm font-semibold text-slate-600">
                    <a href="#dokumen-pengantar" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-teal-50 hover:text-teal-800">Pengantar</a>
                    <a href="#dokumen-bab-1" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-teal-50 hover:text-teal-800">Bab I</a>
                    <a href="#dokumen-bab-2" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-teal-50 hover:text-teal-800">Bab II</a>
                    <a href="#dokumen-bab-3" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-teal-50 hover:text-teal-800">Bab III</a>
                    <a href="#dokumen-bab-4" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-teal-50 hover:text-teal-800">Bab IV</a>
                    <a href="#dokumen-lampiran" class="rounded-full bg-slate-100 px-4 py-2 transition hover:bg-teal-50 hover:text-teal-800">Lampiran</a>
                </div>
            </div>
        </div>

        <div class="mt-8 space-y-10">
            <section id="dokumen-pengantar" class="scroll-mt-52 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 sm:p-8">
                <div class="grid gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Pengantar</p>
                        <h3 class="mt-2 text-2xl font-black text-slate-900">Kerangka baca cepat untuk LAKIP 2025</h3>
                        <div class="mt-5 space-y-4 text-sm leading-7 text-slate-700">
                            @foreach ($forewordParagraphs as $paragraph)
                            <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                    </div>

                    <aside class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5 sm:p-6">
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-700">Daftar Isi Singkat</p>
                        <ul class="mt-5 space-y-3 text-sm leading-7 text-slate-700">
                            @foreach ($documentOutline as $item)
                            <li class="rounded-2xl border border-white bg-white px-4 py-3">{{ $item }}</li>
                            @endforeach
                        </ul>
                    </aside>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-teal-100 bg-teal-50 p-6 sm:p-8">
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-700">Ringkasan Eksekutif Lengkap</p>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        @foreach ($executiveIndicatorItems as $item)
                        <article class="rounded-2xl border border-white/80 bg-white/90 px-4 py-4 text-sm leading-7 text-slate-700">{{ $item }}</article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="dokumen-bab-1" class="scroll-mt-52 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-700">Bab I</p>
                <h3 class="mt-2 text-2xl font-black text-slate-900">Pendahuluan</h3>
                <div class="mt-6 grid gap-5 lg:grid-cols-3">
                    @foreach ($chapterOneSections as $section)
                    <article class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-6">
                        <h4 class="text-lg font-black text-slate-900">{{ $section['title'] }}</h4>
                        <div class="mt-4 space-y-3 text-sm leading-7 text-slate-700">
                            @foreach ($section['paragraphs'] as $paragraph)
                            <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                        @if (! empty($section['bullets']))
                        <ul class="mt-4 space-y-2 text-sm leading-7 text-slate-700">
                            @foreach ($section['bullets'] as $bullet)
                            <li class="rounded-2xl bg-white px-4 py-3">{{ $bullet }}</li>
                            @endforeach
                        </ul>
                        @endif
                    </article>
                    @endforeach
                </div>
            </section>

            <section id="dokumen-bab-2" class="scroll-mt-52 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-700">Bab II</p>
                <h3 class="mt-2 text-2xl font-black text-slate-900">Perencanaan dan Penetapan Kinerja</h3>

                <div class="mt-6 grid gap-5 lg:grid-cols-2">
                    @foreach ($chapterTwoSections as $section)
                    <article class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-6">
                        <h4 class="text-lg font-black text-slate-900">{{ $section['title'] }}</h4>
                        <ul class="mt-4 space-y-2 text-sm leading-7 text-slate-700">
                            @foreach ($section['items'] as $item)
                            <li class="rounded-2xl bg-white px-4 py-3">{{ $item }}</li>
                            @endforeach
                        </ul>
                    </article>
                    @endforeach
                </div>

                <div class="mt-8 overflow-hidden rounded-[1.75rem] border border-slate-200 shadow-sm">
                    <div class="overflow-x-auto bg-white">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-950 text-left text-white">
                                <tr>
                                    <th class="px-5 py-4">Kelompok</th>
                                    <th class="px-5 py-4">Indikator Pilihan</th>
                                    <th class="px-5 py-4">Target 2025</th>
                                    <th class="px-5 py-4">Realisasi 2025</th>
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
                                    <td class="px-5 py-4 font-semibold text-teal-700">{{ $row['achievement'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section id="dokumen-bab-3" class="scroll-mt-52 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-700">Bab III</p>
                <h3 class="mt-2 text-2xl font-black text-slate-900">Akuntabilitas Kinerja</h3>
                <div class="mt-5 space-y-4 text-sm leading-7 text-slate-700">
                    <p>Bab III pada versi HTML ini merangkum indikator 2025 yang paling representatif bagi pembaca umum: tata kelola BLU, pengendalian anggaran, mutu lulusan, serapan kerja, penguatan SDM, dan konsistensi mutu program studi.</p>
                    <p>Setiap metrik di bawah dapat dihover, difokuskan, atau ditap untuk menampilkan detail aktif seperti yang sudah diterapkan pada LAKIP 2024.</p>
                </div>

                <div
                    x-data="{
                        series: @js(\App\Support\PublicLocale::translateData($performanceExplorer)),
                        activeSeriesKey: 'realisasi-anggaran',
                        activeMetric: 'actual',
                        activate(seriesKey, metric) {
                            this.activeSeriesKey = seriesKey;
                            this.activeMetric = metric;
                        },
                        activeSeries() {
                            return this.series.find((item) => item.key === this.activeSeriesKey) ?? this.series[0];
                        },
                        activePoint() {
                            const series = this.activeSeries();
                            return series.metrics.find((metric) => metric.key === this.activeMetric) ?? series.metrics[0];
                        },
                        isActive(seriesKey, metric) {
                            return this.activeSeriesKey === seriesKey && this.activeMetric === metric;
                        },
                    }"
                    class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_22rem]"
                >
                    <div class="rounded-[1.75rem] border border-slate-200 bg-[radial-gradient(circle_at_top,_rgba(20,184,166,0.18),_transparent_42%),linear-gradient(180deg,_rgba(255,255,255,1),_rgba(248,250,252,1))] p-6 shadow-lg shadow-teal-950/5">
                        <div class="overflow-x-auto pb-1">
                            <div class="flex min-w-max gap-3">
                                <template x-for="item in series" :key="item.key">
                                    <button
                                        type="button"
                                        class="rounded-full border px-4 py-2 text-sm font-semibold transition"
                                        @mouseenter="activate(item.key, activeMetric)"
                                        @focus="activate(item.key, activeMetric)"
                                        @click="activate(item.key, activeMetric)"
                                        :class="activeSeriesKey === item.key ? 'border-teal-200 bg-teal-50 text-teal-900 shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-teal-200 hover:text-teal-800'"
                                        x-text="item.label"
                                    ></button>
                                </template>
                            </div>
                        </div>

                        <div class="mt-6 rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-lg shadow-teal-950/5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-teal-700" x-text="activeSeries().group"></p>
                                    <h4 class="mt-2 text-2xl font-black text-slate-900" x-text="activeSeries().label"></h4>
                                </div>
                                <p class="max-w-sm text-sm leading-7 text-slate-600" x-text="activeSeries().summary"></p>
                            </div>

                            <div class="mt-8 grid gap-4 md:grid-cols-3">
                                <template x-for="metric in activeSeries().metrics" :key="activeSeries().key + '-' + metric.key">
                                    <button
                                        type="button"
                                        class="group relative flex min-h-[18rem] flex-col rounded-[1.4rem] border border-slate-100 bg-slate-50/90 p-4 text-center shadow-sm transition hover:border-slate-200 focus:outline-none"
                                        @mouseenter="activate(activeSeries().key, metric.key)"
                                        @focus="activate(activeSeries().key, metric.key)"
                                        @click="activate(activeSeries().key, metric.key)"
                                        :aria-pressed="activeMetric === metric.key ? 'true' : 'false'"
                                    >
                                        <span class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500" x-text="metric.label"></span>
                                        <span
                                            x-show="activeMetric === metric.key"
                                            x-cloak
                                            x-transition.opacity.scale.origin.top
                                            class="pointer-events-none absolute left-1/2 top-10 -translate-x-1/2 whitespace-nowrap rounded-full bg-slate-950 px-3 py-1 text-[11px] font-semibold text-white shadow-xl"
                                            x-text="metric.display"
                                        ></span>
                                        <div class="relative mt-10 flex min-h-[14rem] flex-1 items-end overflow-visible rounded-[1rem] bg-white px-2 pb-2 pt-3 shadow-inner shadow-slate-200/70 ring-1 ring-slate-100">
                                            <div
                                                class="w-full rounded-t-[1rem] transition duration-300"
                                                :style="activeMetric === metric.key ? 'min-height: 3rem; height: ' + metric.height + '%; ' + metric.bar_style + ' opacity: 1; transform: translateY(-6px); box-shadow: ' + metric.focus_shadow + ';' : 'min-height: 3rem; height: ' + metric.height + '%; ' + metric.bar_style + ' opacity: 0.94;'"
                                            ></div>
                                            <div class="pointer-events-none absolute inset-x-3 bottom-1/2 border-t border-dashed border-slate-200"></div>
                                            <div class="pointer-events-none absolute inset-x-3 top-3 border-t border-dashed border-slate-200"></div>
                                        </div>
                                        <p class="mt-4 text-lg font-black text-slate-900" x-text="metric.display"></p>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <aside class="rounded-[1.75rem] border border-slate-900 bg-slate-950 p-6 text-white shadow-2xl shadow-teal-950/20">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-teal-200">Detail Aktif</p>
                        <div class="mt-4 flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-400" x-text="activePoint().label"></p>
                                <h5 class="mt-2 text-2xl font-black text-white" x-text="activeSeries().label"></h5>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold" :style="activePoint().badge_style" x-text="activePoint().display"></span>
                        </div>

                        <p class="mt-5 text-sm leading-7 text-slate-300" x-text="activePoint().description"></p>
                        <p class="mt-4 text-sm leading-7 text-teal-100/80" x-text="activeSeries().summary"></p>

                        <div class="mt-6 grid grid-cols-3 gap-3">
                            <template x-for="metric in activeSeries().metrics" :key="metric.key">
                                <button
                                    type="button"
                                    class="rounded-2xl border border-white/10 bg-white/5 p-3 text-left transition"
                                    @mouseenter="activate(activeSeries().key, metric.key)"
                                    @focus="activate(activeSeries().key, metric.key)"
                                    @click="activate(activeSeries().key, metric.key)"
                                    :class="activeMetric === metric.key ? 'border-white/30 bg-white/15 shadow-lg shadow-teal-950/20' : 'hover:border-white/20 hover:bg-white/10'"
                                >
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full" :style="metric.dot_style"></span>
                                        <span class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400" x-text="metric.label"></span>
                                    </div>
                                    <p class="mt-3 text-lg font-black text-white" x-text="metric.display"></p>
                                </button>
                            </template>
                        </div>
                    </aside>
                </div>

                <div class="mt-8 grid gap-5 lg:grid-cols-2 xl:grid-cols-3">
                    @foreach ($performanceHighlights as $item)
                    <article class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-6">
                        <div class="flex items-end justify-between gap-4">
                            <h4 class="text-lg font-black text-slate-900">{{ $item['title'] }}</h4>
                            <span class="text-lg font-black text-teal-700">{{ $item['value'] }}</span>
                        </div>
                        <p class="mt-4 text-sm leading-7 text-slate-600">{{ $item['body'] }}</p>
                    </article>
                    @endforeach
                </div>
            </section>

            <section id="dokumen-bab-4" class="scroll-mt-52 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-700">Bab IV</p>
                <h3 class="mt-2 text-2xl font-black text-slate-900">Penutup dan Arah Perbaikan</h3>
                <div class="mt-5 space-y-4 text-sm leading-7 text-slate-700">
                    <p>Bab penutup menegaskan bahwa kinerja 2025 secara umum melampaui target pada sebagian besar indikator utama, khususnya pada area keuangan BLU, serapan lulusan, prestasi sivitas akademika, dan akreditasi program studi.</p>
                    <p>Walaupun demikian, dokumen tetap menempatkan keberlanjutan penguatan riset terapan, hilirisasi inovasi, kerja sama, dan pengembangan layanan digital sebagai agenda penting yang harus terus dikawal.</p>
                </div>

                <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($followUpSteps as $step)
                    <article class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5 text-sm leading-7 text-slate-700">{{ $step }}</article>
                    @endforeach
                </div>

                <div class="mt-8 grid gap-5 lg:grid-cols-3">
                    @foreach ($efficiencyNotes as $note)
                    <article class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-6">
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-teal-700">Efisiensi</p>
                        <h4 class="mt-2 text-lg font-black text-slate-900">{{ $note['title'] }}</h4>
                        <p class="mt-4 text-sm leading-7 text-slate-600">{{ $note['body'] }}</p>
                    </article>
                    @endforeach
                </div>
            </section>

            <section id="dokumen-lampiran" class="scroll-mt-52 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-700">Lampiran Ringkas</p>
                <h3 class="mt-2 text-2xl font-black text-slate-900">Bukti dukung dan konteks lanjutan yang paling relevan</h3>
                <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($appendixHighlights as $item)
                    <article class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5 text-sm leading-7 text-slate-700">{{ $item }}</article>
                    @endforeach
                </div>

                <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($quickFacts as $fact)
                    <article class="rounded-[1.75rem] border border-slate-200 bg-white px-5 py-5 shadow-sm shadow-slate-200/60">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $fact['label'] }}</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ $fact['value'] }}</p>
                        <p class="mt-3 text-sm leading-7 text-slate-600">{{ $fact['note'] }}</p>
                    </article>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</section>