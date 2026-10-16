@extends('pbm.layouts.app')

@section('content')
<div x-data="ukomItemAnalysis()" class="pb-16">
    <!-- Non-Print Header Bar -->
    <div class="print:hidden mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <a href="{{ route('pbm.quizzes.show', $quiz->id) }}" class="inline-flex items-center text-sm font-semibold text-sky-600 hover:text-sky-700 mb-2 transition">
                <i data-feather="arrow-left" class="w-4 h-4 mr-1"></i> Kembali ke Kuis
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 tracking-tight">
                    Analisis Butir Soal: {{ $quiz->title }}
                </h1>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-100 text-sky-800 border border-sky-200">
                    Standar UKOM
                </span>
            </div>
            <p class="text-sm text-gray-600 mt-1 max-w-3xl">
                Evaluasi komprehensif parameter psikometrik instrumen: Korelasi Poin Biserial ($r_{pbis}$), Reliabilitas $KR\text{-}20$ & $KR\text{-}21$, Daya Beda ($D$), Tingkat Kesukaran ($P$), dan Efektivitas Pengecoh Uji Kompetensi Nasional.
            </p>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="window.print()" class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-xl shadow-sm hover:bg-gray-50 hover:text-gray-900 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500">
                <i data-feather="printer" class="w-4 h-4 mr-2 text-gray-500"></i> Cetak Laporan / PDF
            </button>
            <a href="{{ route('pbm.quizzes.analysis.export', $quiz->id) }}" class="inline-flex items-center px-4 py-2.5 bg-sky-600 border border-transparent text-white text-sm font-medium rounded-xl shadow-sm hover:bg-sky-700 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500">
                <i data-feather="download" class="w-4 h-4 mr-2"></i> Ekspor Data (.CSV)
            </a>
        </div>
    </div>

    <!-- PRINT ONLY OFFICIAL HEADER -->
    <div class="hidden print:block mb-6 border-b-2 border-gray-800 pb-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold uppercase tracking-wider text-gray-900">KEMENTERIAN KESEHATAN REPUBLIK INDONESIA</h2>
                <h3 class="text-base font-semibold text-gray-800">POLITEKNIK KESEHATAN KEMENKES BANDUNG</h3>
                <p class="text-xs text-gray-600">Unit Pengembangan Pendidikan & Uji Kompetensi Tenaga Kesehatan (CBT Center)</p>
            </div>
            <div class="text-right text-xs text-gray-500">
                <p>Dokumen: Lembar Analisis Item Properties UKOM</p>
                <p>Tanggal Cetak: {{ date('d F Y H:i') }} WIB</p>
            </div>
        </div>
        <div class="mt-4 text-center">
            <h1 class="text-xl font-bold uppercase underline">LAPORAN ANALISIS BUTIR SOAL & ITEM PROPERTIES</h1>
            <p class="text-sm font-medium text-gray-700 mt-0.5">Instrumen Evaluasi: {{ $quiz->title }}</p>
        </div>
    </div>

    <!-- Test Properties / Parameter Karakteristik Tes -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Mean & Distribusi Skor -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm hover:border-sky-300 transition relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-sky-50 rounded-full opacity-60 pointer-events-none"></div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-sky-700">Rata-rata Skor (Mean)</span>
                <span class="p-1.5 bg-sky-100 text-sky-700 rounded-lg">
                    <i data-feather="bar-chart-2" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="text-3xl font-extrabold text-gray-900">{{ number_format($meanScore, 2) }}</div>
            <div class="mt-2 text-xs text-gray-500 flex items-center justify-between border-t border-gray-100 pt-2">
                <span>Median: <strong class="text-gray-700">{{ number_format($medianScore, 2) }}</strong></span>
                <span>Rentang: <strong class="text-gray-700">{{ number_format($minScore, 0) }} - {{ number_format($maxScore, 0) }}</strong></span>
            </div>
        </div>

        <!-- Card 2: Varians & Deviasi Standar -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm hover:border-purple-300 transition relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-purple-50 rounded-full opacity-60 pointer-events-none"></div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-purple-700">Varians & Deviasi Standar</span>
                <span class="p-1.5 bg-purple-100 text-purple-700 rounded-lg">
                    <i data-feather="activity" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="text-3xl font-extrabold text-gray-900">{{ number_format($varianceScore, 2) }}</div>
            <div class="mt-2 text-xs text-gray-500 flex items-center justify-between border-t border-gray-100 pt-2">
                <span>SD Skor ($S_t$): <strong class="text-gray-700">{{ number_format($sdScore, 2) }}</strong></span>
                <span>SD Mentah: <strong class="text-gray-700">{{ number_format($sdRaw, 2) }}</strong></span>
            </div>
        </div>

        <!-- Card 3: Reliabilitas KR-20 & KR-21 -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm hover:border-emerald-300 transition relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-emerald-50 rounded-full opacity-60 pointer-events-none"></div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Reliabilitas: KR-20 & KR-21</span>
                <span class="p-1.5 bg-emerald-100 text-emerald-700 rounded-lg">
                    <i data-feather="shield-check" class="w-4 h-4"></i>
                </span>
            </div>
            
            <div class="grid grid-cols-2 gap-2 mt-1">
                <!-- KR-20 -->
                <div class="bg-emerald-50/70 rounded-xl p-2.5 border border-emerald-100">
                    <div class="text-[11px] font-bold text-emerald-900 uppercase flex items-center justify-between">
                        <span>KR-20</span>
                        <span class="text-[9px] font-medium text-emerald-600">&Sigma;pq</span>
                    </div>
                    <div class="text-2xl font-extrabold text-emerald-950 mt-0.5">{{ number_format($kr20, 2) }}</div>
                    <div class="text-[10px] font-semibold text-emerald-700 truncate mt-0.5" title="{{ $kr20Status }}">
                        {{ $kr20 >= 0.70 ? 'Reliabel UKOM' : 'Cukup' }}
                    </div>
                </div>

                <!-- KR-21 -->
                <div class="bg-teal-50/70 rounded-xl p-2.5 border border-teal-100">
                    <div class="text-[11px] font-bold text-teal-900 uppercase flex items-center justify-between">
                        <span>KR-21</span>
                        <span class="text-[9px] font-medium text-teal-600">Mean X̄</span>
                    </div>
                    <div class="text-2xl font-extrabold text-teal-950 mt-0.5">{{ number_format($kr21, 2) }}</div>
                    <div class="text-[10px] font-semibold text-teal-700 truncate mt-0.5" title="{{ $kr21Status }}">
                        {{ $kr21 >= 0.70 ? 'Reliabel UKOM' : 'Cukup' }}
                    </div>
                </div>
            </div>

            <div class="mt-2.5 text-[10px] text-gray-500 border-t border-gray-100 pt-2 flex items-center justify-between">
                <span>Target UKOM: $\ge 0.70$</span>
                <span class="text-gray-400">Konsistensi Internal</span>
            </div>
        </div>

        <!-- Card 4: Point Biserial & SEM -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm hover:border-amber-300 transition relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-amber-50 rounded-full opacity-60 pointer-events-none"></div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-700">Point Biserial & SEM</span>
                <span class="p-1.5 bg-amber-100 text-amber-700 rounded-lg">
                    <i data-feather="target" class="w-4 h-4"></i>
                </span>
            </div>

            <div class="grid grid-cols-2 gap-2 mt-1">
                <!-- Rata-rata Point Biserial -->
                <div class="bg-amber-50/70 rounded-xl p-2.5 border border-amber-100">
                    <div class="text-[11px] font-bold text-amber-900 uppercase flex items-center justify-between">
                        <span>Mean $r_{pbis}$</span>
                        <span class="text-[9px] font-medium text-amber-600">Diskriminasi</span>
                    </div>
                    <div class="text-2xl font-extrabold text-amber-950 mt-0.5">{{ number_format($meanRpbis, 2) }}</div>
                    <div class="text-[10px] font-semibold text-amber-700 truncate mt-0.5">
                        {{ $meanRpbisCategory }}
                    </div>
                </div>

                <!-- SEM -->
                <div class="bg-indigo-50/70 rounded-xl p-2.5 border border-indigo-100">
                    <div class="text-[11px] font-bold text-indigo-900 uppercase flex items-center justify-between">
                        <span>SEM</span>
                        <span class="text-[9px] font-medium text-indigo-600">Presisi</span>
                    </div>
                    <div class="text-2xl font-extrabold text-indigo-950 mt-0.5">&plusmn;{{ number_format($sem, 2) }}</div>
                    <div class="text-[10px] font-semibold text-indigo-700 truncate mt-0.5">
                        skor tes
                    </div>
                </div>
            </div>

            <div class="mt-2.5 text-[10px] text-gray-500 flex items-center justify-between border-t border-gray-100 pt-2">
                <span>Rentang $r_{pbis}$: <strong class="text-gray-700">{{ number_format($minRpbis, 2) }} s.d {{ number_format($maxRpbis, 2) }}</strong></span>
                <span>Sampel: <strong class="text-gray-700">{{ $totalAttempts }} mhs</strong></span>
            </div>
        </div>
    </div>

    <!-- Proporsi Mutu Butir Soal UKOM (Quality Distribution Banner) -->
    <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm mb-6 print:border-gray-400">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div>
                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <i data-feather="pie-chart" class="w-4 h-4 text-sky-600"></i>
                    Distribusi Mutu Butir Soal Berdasarkan Rekomendasi UKOM
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">Klasifikasi kelayakan butir soal untuk masuk ke dalam Bank Soal Uji Kompetensi Nasional.</p>
            </div>
            <div class="text-xs text-gray-500">
                Total Soal: <strong class="text-gray-800">{{ $totalQuestions }} butir</strong>
            </div>
        </div>

        <!-- Stacked Progress Bar -->
        <div class="w-full bg-gray-100 rounded-full h-3 flex overflow-hidden mb-4">
            @if($summaryPercent['diterima'] > 0)
                <div class="bg-emerald-500 h-full transition-all" style="width: {{ $summaryPercent['diterima'] }}%" title="Diterima (Bank Soal): {{ $summaryPercent['diterima'] }}%"></div>
            @endif
            @if($summaryPercent['revisi_pengecoh'] > 0)
                <div class="bg-amber-400 h-full transition-all" style="width: {{ $summaryPercent['revisi_pengecoh'] }}%" title="Revisi Pengecoh: {{ $summaryPercent['revisi_pengecoh'] }}%"></div>
            @endif
            @if($summaryPercent['revisi_stem'] > 0)
                <div class="bg-sky-400 h-full transition-all" style="width: {{ $summaryPercent['revisi_stem'] }}%" title="Revisi Kasus/Stem: {{ $summaryPercent['revisi_stem'] }}%"></div>
            @endif
            @if($summaryPercent['ditolak'] > 0)
                <div class="bg-rose-500 h-full transition-all" style="width: {{ $summaryPercent['ditolak'] }}%" title="Ditolak: {{ $summaryPercent['ditolak'] }}%"></div>
            @endif
        </div>

        <!-- Quick Filter Grid Buttons -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <button type="button" @click="filterStatus = filterStatus === 'Diterima' ? 'ALL' : 'Diterima'" :class="filterStatus === 'Diterima' ? 'ring-2 ring-emerald-500 bg-emerald-50 border-emerald-300' : 'bg-white hover:bg-emerald-50/50 border-gray-200'" class="p-3 rounded-xl border text-left transition flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 text-xs font-semibold text-emerald-800">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Diterima (Bank Soal)
                    </div>
                    <div class="text-xl font-bold text-emerald-900 mt-1">{{ $summaryCounts['diterima'] }} <span class="text-xs font-normal text-emerald-700">({{ $summaryPercent['diterima'] }}%)</span></div>
                </div>
                <i data-feather="check-circle" class="w-5 h-5 text-emerald-500 opacity-60"></i>
            </button>

            <button type="button" @click="filterStatus = filterStatus === 'Revisi Pengecoh' ? 'ALL' : 'Revisi Pengecoh'" :class="filterStatus === 'Revisi Pengecoh' ? 'ring-2 ring-amber-500 bg-amber-50 border-amber-300' : 'bg-white hover:bg-amber-50/50 border-gray-200'" class="p-3 rounded-xl border text-left transition flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 text-xs font-semibold text-amber-800">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> Revisi Pengecoh
                    </div>
                    <div class="text-xl font-bold text-amber-900 mt-1">{{ $summaryCounts['revisi_pengecoh'] }} <span class="text-xs font-normal text-amber-700">({{ $summaryPercent['revisi_pengecoh'] }}%)</span></div>
                </div>
                <i data-feather="alert-triangle" class="w-5 h-5 text-amber-500 opacity-60"></i>
            </button>

            <button type="button" @click="filterStatus = filterStatus === 'Revisi Kasus/Stem' ? 'ALL' : 'Revisi Kasus/Stem'" :class="filterStatus === 'Revisi Kasus/Stem' ? 'ring-2 ring-sky-500 bg-sky-50 border-sky-300' : 'bg-white hover:bg-sky-50/50 border-gray-200'" class="p-3 rounded-xl border text-left transition flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 text-xs font-semibold text-sky-800">
                        <span class="w-2.5 h-2.5 rounded-full bg-sky-400"></span> Revisi Kasus / Stem
                    </div>
                    <div class="text-xl font-bold text-sky-900 mt-1">{{ $summaryCounts['revisi_stem'] }} <span class="text-xs font-normal text-sky-700">({{ $summaryPercent['revisi_stem'] }}%)</span></div>
                </div>
                <i data-feather="edit-3" class="w-5 h-5 text-sky-500 opacity-60"></i>
            </button>

            <button type="button" @click="filterStatus = filterStatus === 'Ditolak' ? 'ALL' : 'Ditolak'" :class="filterStatus === 'Ditolak' ? 'ring-2 ring-rose-500 bg-rose-50 border-rose-300' : 'bg-white hover:bg-rose-50/50 border-gray-200'" class="p-3 rounded-xl border text-left transition flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 text-xs font-semibold text-rose-800">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Ditolak / Dibuang
                    </div>
                    <div class="text-xl font-bold text-rose-900 mt-1">{{ $summaryCounts['ditolak'] }} <span class="text-xs font-normal text-rose-700">({{ $summaryPercent['ditolak'] }}%)</span></div>
                </div>
                <i data-feather="x-circle" class="w-5 h-5 text-rose-500 opacity-60"></i>
            </button>
        </div>
    </div>

    <!-- Main Card & Data Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden print:border-none print:shadow-none">
        <!-- Filter & Search Toolbar (Screen Only) -->
        <div class="print:hidden p-5 border-b border-gray-200 bg-gray-50/70 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Search input -->
            <div class="relative flex-1 max-w-md">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                    <i data-feather="search" class="w-4 h-4"></i>
                </span>
                <input type="text" x-model="searchQuery" placeholder="Cari kasus klinis, pertanyaan, atau nomor soal..." class="w-full pl-9 pr-4 py-2 bg-white border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition">
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Status Filter -->
                <select x-model="filterStatus" class="text-xs font-medium bg-white border border-gray-300 rounded-xl px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <option value="ALL">Semua Rekomendasi</option>
                    <option value="Diterima">Diterima (Bank Soal)</option>
                    <option value="Revisi Pengecoh">Revisi Pengecoh</option>
                    <option value="Revisi Kasus/Stem">Revisi Kasus/Stem</option>
                    <option value="Ditolak">Ditolak</option>
                </select>

                <!-- Difficulty Filter -->
                <select x-model="filterDifficulty" class="text-xs font-medium bg-white border border-gray-300 rounded-xl px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <option value="ALL">Semua Kesukaran (P)</option>
                    <option value="Sukar">Sukar (P &lt; 0.25)</option>
                    <option value="Sedang (Ideal UKOM)">Sedang / Ideal (0.25 - 0.75)</option>
                    <option value="Mudah">Mudah (P &gt; 0.75)</option>
                </select>

                <!-- Discrimination Filter (D) -->
                <select x-model="filterDiscrimination" class="text-xs font-medium bg-white border border-gray-300 rounded-xl px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <option value="ALL">Semua Daya Beda (D)</option>
                    <option value="Sangat Baik">Sangat Baik (D &ge; 0.40)</option>
                    <option value="Baik">Baik (0.30 - 0.39)</option>
                    <option value="Cukup">Cukup (0.20 - 0.29)</option>
                    <option value="Buruk">Buruk (0.00 - 0.19)</option>
                    <option value="Negatif">Negatif (D &lt; 0.00)</option>
                </select>

                <!-- Point Biserial Filter (r_pbis) -->
                <select x-model="filterRpbis" class="text-xs font-medium bg-white border border-gray-300 rounded-xl px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <option value="ALL">Semua Poin Biserial (r_pbis)</option>
                    <option value="Sangat Baik">Sangat Baik (r &ge; 0.40)</option>
                    <option value="Baik">Baik (0.30 - 0.39)</option>
                    <option value="Cukup">Cukup (0.20 - 0.29)</option>
                    <option value="Rendah">Rendah (0.00 - 0.19)</option>
                    <option value="Negatif">Negatif (r &lt; 0.00)</option>
                </select>

                <button type="button" @click="resetFilters()" x-show="isFiltered" class="text-xs font-semibold text-sky-600 hover:text-sky-800 px-2 py-1 transition">
                    Reset
                </button>
            </div>
        </div>

        <!-- Filter info bar -->
        <div class="print:hidden px-5 py-2.5 bg-gray-50 border-b border-gray-100 flex items-center justify-between text-xs text-gray-500">
            <div>
                Menampilkan <span class="font-bold text-gray-800" x-text="filteredQuestions.length"></span> dari <span class="font-bold text-gray-800">{{ $totalQuestions }}</span> butir soal
            </div>
            <div class="flex items-center gap-2">
                <span class="text-gray-400">Kelompok Atas ($U$): {{ $upperGroupCount }} mhs</span>
                <span class="text-gray-300">&bull;</span>
                <span class="text-gray-400">Kelompok Bawah ($L$): {{ $lowerGroupCount }} mhs</span>
            </div>
        </div>

        <!-- Table Container -->
        <div class="overflow-x-auto swipe-scroll">
            <table class="min-w-full divide-y divide-gray-200 border-collapse">
                <thead class="bg-gray-100/80 text-gray-700 text-xs uppercase font-bold tracking-wider">
                    <tr>
                        <th class="px-3 py-3.5 text-center w-12 border-b border-gray-200">No</th>
                        <th class="px-4 py-3.5 text-left min-w-[260px] border-b border-gray-200">Kasus Klinis & Pertanyaan</th>
                        <th class="px-2 py-3.5 text-center w-14 border-b border-gray-200">Kunci</th>
                        <th class="px-3 py-3.5 text-center min-w-[130px] border-b border-gray-200">
                            Tingkat Kesukaran ($P$)
                            <div class="text-[10px] font-normal text-gray-500 normal-case">Proporsi Benar</div>
                        </th>
                        <th class="px-3 py-3.5 text-center min-w-[130px] border-b border-gray-200">
                            Daya Beda ($D$)
                            <div class="text-[10px] font-normal text-gray-500 normal-case">$P_U - P_L$</div>
                        </th>
                        <th class="px-3 py-3.5 text-center min-w-[140px] border-b border-gray-200">
                            Poin Biserial ($r_{pbis}$)
                            <div class="text-[10px] font-normal text-gray-500 normal-case">Korelasi Butir-Total</div>
                        </th>
                        <th class="px-4 py-3.5 text-left min-w-[180px] border-b border-gray-200">
                            Analisis Pengecoh
                            <div class="text-[10px] font-normal text-gray-500 normal-case">Efektivitas Opsi A - E</div>
                        </th>
                        <th class="px-3 py-3.5 text-center min-w-[130px] border-b border-gray-200">
                            Rekomendasi UKOM
                        </th>
                        <th class="print:hidden px-3 py-3.5 text-center w-24 border-b border-gray-200">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100 text-sm">
                    <template x-for="(q, idx) in filteredQuestions" :key="q.id">
                        <tr class="hover:bg-sky-50/40 transition">
                            <!-- No -->
                            <td class="px-3 py-4 text-center font-bold text-gray-800 align-top" x-text="q.number"></td>

                            <!-- Kasus & Pertanyaan -->
                            <td class="px-4 py-4 align-top">
                                <div class="space-y-1.5">
                                    <template x-if="q.vignette">
                                        <div class="text-xs text-gray-600 bg-gray-50 p-2 rounded-lg border border-gray-100 font-serif leading-relaxed line-clamp-2 hover:line-clamp-none transition" :title="q.vignette" x-text="q.vignette"></div>
                                    </template>
                                    <div class="font-medium text-gray-900 leading-snug" x-text="q.question_text"></div>
                                    <div class="flex items-center gap-2 text-[11px] text-gray-400">
                                        <span>Soal ID: #<span x-text="q.id"></span></span>
                                        <span>&bull;</span>
                                        <span>5 Opsi Pilihan (A - E)</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Kunci Jawaban -->
                            <td class="px-2 py-4 text-center align-top">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-emerald-100 text-emerald-800 font-extrabold text-sm border border-emerald-200 shadow-sm" x-text="q.key_letter"></span>
                            </td>

                            <!-- Tingkat Kesukaran (P) -->
                            <td class="px-3 py-4 text-center align-top whitespace-nowrap">
                                <div class="font-extrabold text-base text-gray-900" x-text="Number(q.p_value).toFixed(2)"></div>
                                <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                      :class="{
                                          'bg-red-100 text-red-800 border border-red-200': q.p_category === 'Sukar',
                                          'bg-emerald-100 text-emerald-800 border border-emerald-200': q.p_category.includes('Sedang'),
                                          'bg-blue-100 text-blue-800 border border-blue-200': q.p_category === 'Mudah'
                                      }"
                                      x-text="q.p_category">
                                </span>
                                <div class="text-[10px] text-gray-400 mt-1">
                                    $P_U$: <span x-text="q.p_upper"></span> | $P_L$: <span x-text="q.p_lower"></span>
                                </div>
                            </td>

                            <!-- Daya Beda (D) -->
                            <td class="px-3 py-4 text-center align-top whitespace-nowrap">
                                <div class="font-extrabold text-base text-gray-900" x-text="Number(q.d_value).toFixed(2)"></div>
                                <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                      :class="{
                                          'bg-emerald-100 text-emerald-800 border border-emerald-200': q.d_category === 'Sangat Baik',
                                          'bg-teal-100 text-teal-800 border border-teal-200': q.d_category === 'Baik',
                                          'bg-amber-100 text-amber-800 border border-amber-200': q.d_category === 'Cukup',
                                          'bg-rose-100 text-rose-800 border border-rose-200': q.d_category === 'Buruk',
                                          'bg-red-100 text-red-900 border border-red-300 font-bold': q.d_category.includes('Negatif')
                                      }"
                                      x-text="q.d_category">
                                </span>
                                <div class="text-[10px] text-gray-400 mt-1">
                                    Kelley 27%
                                </div>
                            </td>

                            <!-- Poin Biserial (r_pbis) Dedicated Column -->
                            <td class="px-3 py-4 text-center align-top whitespace-nowrap">
                                <div class="font-extrabold text-base text-gray-900" x-text="Number(q.rpbis_value).toFixed(2)"></div>
                                <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                      :class="{
                                          'bg-emerald-100 text-emerald-800 border border-emerald-200': q.rpbis_category === 'Sangat Baik',
                                          'bg-teal-100 text-teal-800 border border-teal-200': q.rpbis_category === 'Baik',
                                          'bg-amber-100 text-amber-800 border border-amber-200': q.rpbis_category === 'Cukup',
                                          'bg-rose-100 text-rose-800 border border-rose-200': q.rpbis_category === 'Rendah',
                                          'bg-red-100 text-red-900 border border-red-300 font-bold': q.rpbis_category === 'Negatif'
                                      }"
                                      x-text="q.rpbis_category">
                                </span>
                                <div class="text-[10px] text-gray-400 mt-1">
                                    Standar: &ge; 0.20
                                </div>
                            </td>

                            <!-- Analisis Pengecoh -->
                            <td class="px-4 py-4 align-top text-xs space-y-1.5">
                                <div class="flex items-center gap-1.5 font-medium"
                                     :class="q.functioning_distractors === q.total_distractors ? 'text-emerald-700' : (q.functioning_distractors > 0 ? 'text-amber-700' : 'text-red-700')">
                                    <span class="w-2 h-2 rounded-full" :class="q.functioning_distractors === q.total_distractors ? 'bg-emerald-500' : (q.functioning_distractors > 0 ? 'bg-amber-500' : 'bg-red-500')"></span>
                                    <span x-text="q.functioning_distractors + ' dari ' + q.total_distractors + ' pengecoh berfungsi'"></span>
                                </div>
                                
                                <template x-if="q.non_functioning_distractors > 0">
                                    <div class="text-red-600 bg-red-50 px-2 py-0.5 rounded text-[11px] inline-block">
                                        <span x-text="q.non_functioning_distractors"></span> pengecoh &lt;5% (tidak berfungsi)
                                    </div>
                                </template>

                                <template x-if="q.misleading_distractors > 0">
                                    <div class="text-purple-700 bg-purple-50 px-2 py-0.5 rounded text-[11px] inline-block font-medium">
                                        <span x-text="q.misleading_distractors"></span> pengecoh menyesatkan ($U > L$)
                                    </div>
                                </template>
                            </td>

                            <!-- Rekomendasi UKOM -->
                            <td class="px-3 py-4 text-center align-top whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold"
                                      :class="{
                                          'bg-emerald-100 text-emerald-800 border border-emerald-200': q.ukom_status === 'Diterima',
                                          'bg-amber-100 text-amber-800 border border-amber-200': q.ukom_status === 'Revisi Pengecoh',
                                          'bg-sky-100 text-sky-800 border border-sky-200': q.ukom_status === 'Revisi Kasus/Stem',
                                          'bg-rose-100 text-rose-800 border border-rose-200': q.ukom_status === 'Ditolak'
                                      }">
                                    <span x-text="q.ukom_status"></span>
                                </span>
                            </td>

                            <!-- Aksi (Screen Only) -->
                            <td class="print:hidden px-3 py-4 text-center align-top whitespace-nowrap">
                                <button type="button" @click="openModal(q)" class="inline-flex items-center px-3 py-1.5 bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 rounded-lg text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-sky-500">
                                    <i data-feather="eye" class="w-3.5 h-3.5 mr-1"></i> Detail
                                </button>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty state if search has no match -->
                    <tr x-show="filteredQuestions.length === 0">
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                            <i data-feather="inbox" class="w-10 h-10 mx-auto text-gray-300 mb-2"></i>
                            <p class="font-medium">Tidak ada butir soal yang sesuai dengan kriteria filter.</p>
                            <button type="button" @click="resetFilters()" class="mt-2 text-xs font-semibold text-sky-600 hover:underline">
                                Reset filter pencarian
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PRINT ONLY EXPANDED ITEM PROPERTIES SHEETS -->
    <div class="hidden print:block mt-8 space-y-6 page-break">
        <h3 class="text-base font-bold uppercase border-b pb-2 text-gray-900">RINCIAN LEMBAR KERJA ITEM PROPERTIES PER BUTIR SOAL</h3>
        @foreach($questionsData as $q)
        <div class="border border-gray-400 p-4 rounded mb-4 break-inside-avoid">
            <div class="flex items-center justify-between border-b pb-2 mb-3">
                <span class="font-bold text-sm">SOAL NOMOR {{ $q['number'] }} (Kunci: {{ $q['key_letter'] }})</span>
                <span class="text-xs font-bold uppercase border px-2 py-0.5 rounded">Rekomendasi UKOM: {{ $q['ukom_status'] }}</span>
            </div>

            @if(!empty($q['vignette']))
            <div class="mb-2">
                <span class="text-xs font-bold text-gray-700">Vignette (Kasus Klinis):</span>
                <p class="text-xs italic bg-gray-50 p-2 rounded border border-gray-200 text-gray-800">{{ $q['vignette'] }}</p>
            </div>
            @endif

            <div class="mb-3">
                <span class="text-xs font-bold text-gray-700">Pertanyaan (Lead-in):</span>
                <p class="text-xs font-semibold text-gray-900">{{ $q['question_text'] }}</p>
            </div>

            <div class="grid grid-cols-4 gap-2 text-xs bg-gray-50 p-2 border rounded mb-3">
                <div>Tingkat Kesukaran ($P$): <strong>{{ $q['p_value'] }} ({{ $q['p_category'] }})</strong></div>
                <div>Daya Beda ($D$): <strong>{{ $q['d_value'] }} ({{ $q['d_category'] }})</strong></div>
                <div>Poin Biserial ($r_{pbis}$): <strong>{{ $q['rpbis_value'] }} ({{ $q['rpbis_category'] }})</strong></div>
                <div>Kelompok Atas ($P_U$): <strong>{{ $q['p_upper'] }}</strong> | Bawah ($P_L$): <strong>{{ $q['p_lower'] }}</strong></div>
            </div>

            <table class="w-full text-xs border border-gray-300">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-2 py-1 text-center w-8">Opsi</th>
                        <th class="border px-2 py-1 text-left">Pilihan Jawaban</th>
                        <th class="border px-2 py-1 text-center w-16">Pemilih ($N$)</th>
                        <th class="border px-2 py-1 text-center w-12">%</th>
                        <th class="border px-2 py-1 text-center w-12">Atas ($U$)</th>
                        <th class="border px-2 py-1 text-center w-12">Bawah ($L$)</th>
                        <th class="border px-2 py-1 text-left w-32">Efektivitas Opsi</th>
                        <th class="border px-2 py-1 text-left">Pembahasan / Rationale</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($q['options'] as $opt)
                    <tr class="{{ $opt['is_correct'] ? 'bg-emerald-50 font-semibold' : '' }}">
                        <td class="border px-2 py-1 text-center">{{ $opt['letter'] }}</td>
                        <td class="border px-2 py-1">{{ $opt['text'] }}</td>
                        <td class="border px-2 py-1 text-center">{{ $opt['count'] }}</td>
                        <td class="border px-2 py-1 text-center">{{ $opt['percentage'] }}%</td>
                        <td class="border px-2 py-1 text-center">{{ $opt['count_upper'] }}</td>
                        <td class="border px-2 py-1 text-center">{{ $opt['count_lower'] }}</td>
                        <td class="border px-2 py-1">{{ $opt['status'] }}</td>
                        <td class="border px-2 py-1 text-gray-600">{{ $opt['rationale'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-2 text-xs text-gray-700 bg-gray-50 p-2 rounded border">
                <strong>Catatan Telaah Mutu:</strong> {{ $q['diagnostic_notes'] }}
            </div>
        </div>
        @endforeach

        <!-- Pengesahan Tanda Tangan -->
        <div class="mt-8 pt-6 border-t border-gray-400 grid grid-cols-2 text-xs break-inside-avoid">
            <div>
                <p>Mengetahui,</p>
                <p class="font-bold">Koordinator CBT / Penjaminan Mutu</p>
                <div class="h-16"></div>
                <p class="font-bold underline">( .................................................... )</p>
                <p>NIP. ....................................................</p>
            </div>
            <div class="text-right">
                <p>Bandung, {{ date('d F Y') }}</p>
                <p class="font-bold">Dosen Penanggung Jawab / Reviewer</p>
                <div class="h-16"></div>
                <p class="font-bold underline">( {{ Auth::user()->name ?? 'Dosen Pengampu' }} )</p>
                <p>NIP. ....................................................</p>
            </div>
        </div>
    </div>

    <!-- DETAIL ITEM PROPERTIES MODAL (SCREEN ONLY) -->
    <div x-show="modalOpen" 
         x-cloak
         @keydown.escape.window="closeModal()"
         class="print:hidden fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true">
        
        <!-- Backdrop -->
        <div x-show="modalOpen" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" 
             @click="closeModal()"></div>

        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-6">
            <div x-show="modalOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all w-full max-w-4xl border border-gray-200">
                
                <template x-if="selectedQuestion">
                    <div>
                        <!-- Modal Header -->
                        <div class="bg-gradient-to-r from-sky-600 to-indigo-700 px-6 py-4 text-white flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white/20 font-bold text-white text-sm">
                                    #<span x-text="selectedQuestion.number"></span>
                                </span>
                                <div>
                                    <h3 class="text-lg font-bold">Lembar Item Properties Soal</h3>
                                    <p class="text-xs text-sky-100">Kunci Jawaban: Opsi <strong class="underline" x-text="selectedQuestion.key_letter"></strong> &bull; Standar Uji Kompetensi Nasional</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1 rounded-full text-xs font-extrabold shadow-sm"
                                      :class="{
                                          'bg-emerald-100 text-emerald-900': selectedQuestion.ukom_status === 'Diterima',
                                          'bg-amber-100 text-amber-900': selectedQuestion.ukom_status === 'Revisi Pengecoh',
                                          'bg-sky-100 text-sky-900': selectedQuestion.ukom_status === 'Revisi Kasus/Stem',
                                          'bg-rose-100 text-rose-900': selectedQuestion.ukom_status === 'Ditolak'
                                      }"
                                      x-text="selectedQuestion.ukom_status">
                                </span>
                                <button type="button" @click="closeModal()" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                                    <i data-feather="x" class="w-5 h-5"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-6 max-h-[75vh] overflow-y-auto space-y-6">
                            
                            <!-- Vignette (Kasus Klinis) -->
                            <template x-if="selectedQuestion.vignette">
                                <div class="bg-amber-50/50 rounded-xl p-4 border border-amber-200/80">
                                    <div class="flex items-center gap-1.5 text-xs font-bold text-amber-800 uppercase tracking-wide mb-1.5">
                                        <i data-feather="file-text" class="w-4 h-4 text-amber-600"></i> Kasus Klinis (Vignette)
                                    </div>
                                    <p class="text-sm font-serif leading-relaxed text-gray-800" x-text="selectedQuestion.vignette"></p>
                                </div>
                            </template>

                            <!-- Pertanyaan (Lead-in) -->
                            <div class="bg-sky-50/40 rounded-xl p-4 border border-sky-100">
                                <div class="text-xs font-bold text-sky-800 uppercase tracking-wide mb-1 flex items-center gap-1.5">
                                    <i data-feather="help-circle" class="w-4 h-4 text-sky-600"></i> Pertanyaan (Lead-in)
                                </div>
                                <div class="text-base font-bold text-gray-900" x-text="selectedQuestion.question_text"></div>
                            </div>

                            <!-- Parameter Psikometrik Utama Butir -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div class="bg-gray-50 rounded-xl p-3 border border-gray-200">
                                    <div class="text-[11px] font-semibold text-gray-500 uppercase">Tingkat Kesukaran ($P$)</div>
                                    <div class="text-xl font-extrabold text-gray-900 mt-0.5" x-text="selectedQuestion.p_value"></div>
                                    <div class="text-xs font-semibold mt-1"
                                         :class="{
                                             'text-red-600': selectedQuestion.p_category === 'Sukar',
                                             'text-emerald-600': selectedQuestion.p_category.includes('Sedang'),
                                             'text-blue-600': selectedQuestion.p_category === 'Mudah'
                                         }"
                                         x-text="selectedQuestion.p_category">
                                    </div>
                                </div>

                                <div class="bg-gray-50 rounded-xl p-3 border border-gray-200">
                                    <div class="text-[11px] font-semibold text-gray-500 uppercase">Daya Beda ($D$)</div>
                                    <div class="text-xl font-extrabold text-gray-900 mt-0.5" x-text="selectedQuestion.d_value"></div>
                                    <div class="text-xs font-semibold mt-1"
                                         :class="{
                                             'text-emerald-600': selectedQuestion.d_category === 'Sangat Baik' || selectedQuestion.d_category === 'Baik',
                                             'text-amber-600': selectedQuestion.d_category === 'Cukup',
                                             'text-red-600': selectedQuestion.d_category.includes('Buruk') || selectedQuestion.d_category.includes('Negatif')
                                         }"
                                         x-text="selectedQuestion.d_category">
                                    </div>
                                </div>

                                <div class="bg-gray-50 rounded-xl p-3 border border-gray-200">
                                    <div class="text-[11px] font-semibold text-gray-500 uppercase">Poin Biserial ($r_{pbis}$)</div>
                                    <div class="text-xl font-extrabold text-gray-900 mt-0.5" x-text="selectedQuestion.rpbis_value"></div>
                                    <div class="text-xs font-semibold mt-1"
                                         :class="{
                                             'text-emerald-600': selectedQuestion.rpbis_category === 'Sangat Baik' || selectedQuestion.rpbis_category === 'Baik',
                                             'text-amber-600': selectedQuestion.rpbis_category === 'Cukup',
                                             'text-red-600': selectedQuestion.rpbis_category === 'Rendah' || selectedQuestion.rpbis_category === 'Negatif'
                                         }"
                                         x-text="selectedQuestion.rpbis_category">
                                    </div>
                                </div>

                                <div class="bg-gray-50 rounded-xl p-3 border border-gray-200">
                                    <div class="text-[11px] font-semibold text-gray-500 uppercase">Proporsi Atas / Bawah</div>
                                    <div class="text-sm font-bold text-gray-800 mt-1">
                                        $P_U$: <span class="text-emerald-700" x-text="selectedQuestion.p_upper"></span>
                                    </div>
                                    <div class="text-sm font-bold text-gray-800">
                                        $P_L$: <span class="text-rose-700" x-text="selectedQuestion.p_lower"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabel Analisis Distribusi Opsi & Pengecoh -->
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 mb-2 flex items-center justify-between">
                                    <span>Distribusi Pemilih & Efektivitas Opsi (A - E)</span>
                                    <span class="text-xs font-normal text-gray-500">
                                        Kriteria Pengecoh Baik: Dipilih &ge; 5% dan Pemilih Kelompok Bawah ($L$) &ge; Kelompok Atas ($U$)
                                    </span>
                                </h4>
                                
                                <div class="overflow-x-auto border border-gray-200 rounded-xl">
                                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                                        <thead class="bg-gray-50 font-semibold text-gray-600 uppercase">
                                            <tr>
                                                <th class="px-3 py-2.5 text-center w-10">Opsi</th>
                                                <th class="px-3 py-2.5 text-left">Teks Pilihan Jawaban</th>
                                                <th class="px-3 py-2.5 text-center w-24">Pemilih ($N$ & %)</th>
                                                <th class="px-2 py-2.5 text-center w-14">Atas ($U$)</th>
                                                <th class="px-2 py-2.5 text-center w-14">Bawah ($L$)</th>
                                                <th class="px-3 py-2.5 text-center w-36">Efektivitas Opsi</th>
                                                <th class="px-4 py-2.5 text-left min-w-[200px]">Rasional / Pembahasan Klinis</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 bg-white">
                                            <template x-for="opt in selectedQuestion.options" :key="opt.id">
                                                <tr :class="opt.is_correct ? 'bg-emerald-50/60 font-medium' : ''">
                                                    <!-- Letter -->
                                                    <td class="px-3 py-3 text-center align-top font-bold">
                                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full"
                                                              :class="opt.is_correct ? 'bg-emerald-600 text-white font-extrabold' : 'bg-gray-100 text-gray-700'"
                                                              x-text="opt.letter">
                                                        </span>
                                                    </td>

                                                    <!-- Option text -->
                                                    <td class="px-3 py-3 align-top">
                                                        <div class="text-gray-900" x-text="opt.text"></div>
                                                    </td>

                                                    <!-- Pemilih count and % -->
                                                    <td class="px-3 py-3 text-center align-top whitespace-nowrap">
                                                        <span class="font-bold text-gray-900" x-text="opt.count"></span> mhs
                                                        <div class="text-[11px] text-gray-500 font-medium" x-text="opt.percentage + '%'"></div>
                                                    </td>

                                                    <!-- Upper count -->
                                                    <td class="px-2 py-3 text-center align-top font-semibold text-gray-700" x-text="opt.count_upper"></td>

                                                    <!-- Lower count -->
                                                    <td class="px-2 py-3 text-center align-top font-semibold text-gray-700" x-text="opt.count_lower"></td>

                                                    <!-- Status badge -->
                                                    <td class="px-3 py-3 text-center align-top whitespace-nowrap">
                                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold"
                                                              :class="{
                                                                  'bg-emerald-100 text-emerald-800 border border-emerald-200': opt.status_type === 'key',
                                                                  'bg-emerald-50 text-emerald-700 border border-emerald-200': opt.status_type === 'functioning',
                                                                  'bg-rose-100 text-rose-800 border border-rose-200': opt.status_type === 'non_functioning',
                                                                  'bg-purple-100 text-purple-800 border border-purple-200 font-bold': opt.status_type === 'misleading'
                                                              }"
                                                              x-text="opt.status">
                                                        </span>
                                                    </td>

                                                    <!-- Rationale -->
                                                    <td class="px-4 py-3 align-top text-gray-600 text-[11px] leading-relaxed" x-text="opt.rationale"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Catatan Rekomendasi Tim Telaah UKOM -->
                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                                <div class="text-xs font-bold uppercase tracking-wider text-gray-700 mb-1 flex items-center gap-1.5">
                                    <i data-feather="check-square" class="w-4 h-4 text-sky-600"></i> Catatan Telaah Psikometrik & Saran Perbaikan
                                </div>
                                <p class="text-xs text-gray-800 leading-relaxed font-medium" x-text="selectedQuestion.diagnostic_notes"></p>
                            </div>

                        </div>

                        <!-- Modal Footer -->
                        <div class="bg-gray-50 px-6 py-3.5 border-t border-gray-200 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <button type="button" 
                                        @click="prevQuestion()" 
                                        :disabled="selectedQuestionIndex <= 0"
                                        class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-medium text-gray-700 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center gap-1">
                                    <i data-feather="chevron-left" class="w-3.5 h-3.5"></i> Soal Sebelumnya
                                </button>
                                <button type="button" 
                                        @click="nextQuestion()" 
                                        :disabled="selectedQuestionIndex >= allQuestions.length - 1"
                                        class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-medium text-gray-700 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center gap-1">
                                    Soal Selanjutnya <i data-feather="chevron-right" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                            <button type="button" @click="closeModal()" class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white rounded-xl text-xs font-semibold transition">
                                Tutup
                            </button>
                        </div>
                    </div>
                </template>

            </div>
        </div>
    </div>
</div>

@push('head')
<style>
@media print {
    /* Hide navigation, sidebar, and interactive UI */
    header, nav, aside, .print\\:hidden, #sidebar, footer {
        display: none !important;
    }
    body {
        background-color: #ffffff !important;
        color: #000000 !important;
        font-size: 11pt;
        padding: 0 !important;
        margin: 0 !important;
    }
    .page-break {
        page-break-before: always;
    }
    .break-inside-avoid {
        page-break-inside: avoid;
    }
    table {
        border-collapse: collapse !important;
        width: 100% !important;
    }
    th, td {
        border: 1px solid #999999 !important;
        padding: 4px 6px !important;
    }
}
</style>
@endpush

<script>
function ukomItemAnalysis() {
    return {
        allQuestions: @json($questionsData),
        searchQuery: '',
        filterStatus: 'ALL',
        filterDifficulty: 'ALL',
        filterDiscrimination: 'ALL',
        filterRpbis: 'ALL',
        modalOpen: false,
        selectedQuestion: null,
        selectedQuestionIndex: 0,

        init() {
            this.$nextTick(() => {
                if (window.feather) feather.replace();
            });
        },

        get isFiltered() {
            return this.searchQuery !== '' || 
                   this.filterStatus !== 'ALL' || 
                   this.filterDifficulty !== 'ALL' || 
                   this.filterDiscrimination !== 'ALL' ||
                   this.filterRpbis !== 'ALL';
        },

        resetFilters() {
            this.searchQuery = '';
            this.filterStatus = 'ALL';
            this.filterDifficulty = 'ALL';
            this.filterDiscrimination = 'ALL';
            this.filterRpbis = 'ALL';
        },

        get filteredQuestions() {
            return this.allQuestions.filter(q => {
                // Search
                if (this.searchQuery.trim() !== '') {
                    const query = this.searchQuery.toLowerCase();
                    const matchText = (q.question_text || '').toLowerCase().includes(query);
                    const matchVignette = (q.vignette || '').toLowerCase().includes(query);
                    const matchNumber = String(q.number) === query.trim();
                    if (!matchText && !matchVignette && !matchNumber) {
                        return false;
                    }
                }

                // Status
                if (this.filterStatus !== 'ALL' && q.ukom_status !== this.filterStatus) {
                    return false;
                }

                // Difficulty
                if (this.filterDifficulty !== 'ALL' && q.p_category !== this.filterDifficulty) {
                    return false;
                }

                // Discrimination (D)
                if (this.filterDiscrimination !== 'ALL') {
                    if (this.filterDiscrimination === 'Negatif' && !q.d_category.includes('Negatif')) return false;
                    if (this.filterDiscrimination !== 'Negatif' && q.d_category !== this.filterDiscrimination) return false;
                }

                // Point Biserial (r_pbis)
                if (this.filterRpbis !== 'ALL') {
                    if (this.filterRpbis === 'Negatif' && !q.rpbis_category.includes('Negatif')) return false;
                    if (this.filterRpbis !== 'Negatif' && q.rpbis_category !== this.filterRpbis) return false;
                }

                return true;
            });
        },

        openModal(q) {
            this.selectedQuestion = q;
            this.selectedQuestionIndex = this.allQuestions.findIndex(item => item.id === q.id);
            this.modalOpen = true;
            document.body.classList.add('modal-open');
            this.$nextTick(() => {
                if (window.feather) feather.replace();
            });
        },

        closeModal() {
            this.modalOpen = false;
            document.body.classList.remove('modal-open');
        },

        nextQuestion() {
            if (this.selectedQuestionIndex < this.allQuestions.length - 1) {
                this.selectedQuestionIndex++;
                this.selectedQuestion = this.allQuestions[this.selectedQuestionIndex];
                this.$nextTick(() => {
                    if (window.feather) feather.replace();
                });
            }
        },

        prevQuestion() {
            if (this.selectedQuestionIndex > 0) {
                this.selectedQuestionIndex--;
                this.selectedQuestion = this.allQuestions[this.selectedQuestionIndex];
                this.$nextTick(() => {
                    if (window.feather) feather.replace();
                });
            }
        }
    };
}
</script>
@endsection
