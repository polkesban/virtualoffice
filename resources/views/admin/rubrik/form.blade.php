@extends('admin.layouts.admin')
@section('title', $rubrik->exists ? 'Edit Rubrik Penilaian' : 'Buat Rubrik Penilaian')

@section('content')
<div class="p-6 max-w-5xl mx-auto space-y-6">

    {{-- Top bar --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.rubrik.index') }}" class="text-xs font-bold text-brand-600 hover:underline">← Kembali ke Daftar Rubrik</a>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                {{ $rubrik->exists ? 'Edit Rubrik Penilaian' : 'Buat Rubrik Penilaian Baru' }}
            </h1>
        </div>
    </div>

    @if($errors->any())
    <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700 space-y-1">
        <strong class="font-bold">Terjadi kesalahan input:</strong>
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Preset Template Selector --}}
    <div class="bg-gradient-to-r from-brand-600 to-indigo-700 p-6 rounded-2xl text-white shadow-lg space-y-3">
        <div class="flex items-center gap-2">
            <span class="text-xl">⚡</span>
            <h2 class="text-base font-bold">Gunakan Template Standard Kebidanan (Poltekkes Kemenkes Bandung)</h2>
        </div>
        <p class="text-xs text-brand-100">
            Muat kriteria, sub-kriteria, deskriptor level, bobot, dan skala standar secara otomatis untuk instrumen klinik (DOPS, Mini-CEX, CBD, Reflective Writing, Penilaian Sikap, RDK).
        </p>
        <div class="flex flex-col sm:flex-row items-center gap-3 pt-1">
            <select id="presetTemplateSelect" class="w-full sm:w-auto flex-1 px-4 py-2.5 text-sm rounded-xl border border-white/20 bg-white/10 text-white font-medium focus:ring-2 focus:ring-white focus:outline-none placeholder-white/50">
                <option value="" class="text-gray-900">-- Pilih Preset Instrumen Klinik --</option>
                <option value="reflective_writing" class="text-gray-900">📝 Reflective Writing (Stase Klinik - 6 Kriteria)</option>
                <option value="dops" class="text-gray-900">🩺 DOPS - Direct Observation of Procedural Skills (10 Kriteria, Skor×Bobot)</option>
                <option value="mini_cex" class="text-gray-900">🏥 Mini-CEX - Mini Clinical Evaluation Exercise (7 Komponen Rating Scale)</option>
                <option value="cbd" class="text-gray-900">📑 CBD - Case Based Discussion (7 Kriteria Penalaran Klinis, Skala 2.5 - 4.5)</option>
                <option value="penilaian_sikap" class="text-gray-900">👥 Penilaian Sikap Mahasiswa (Group Matrix - 5 Domain Sikap)</option>
                <option value="rdk" class="text-gray-900">💬 Penilaian Refleksi Diskusi Kasus / RDK (Checklist Observasi)</option>
            </select>
            <button type="button" onclick="loadSelectedPreset()" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-white text-brand-700 hover:bg-brand-50 font-extrabold text-sm transition-colors shadow-sm">
                📋 Terpilih: Terapkan Template
            </button>
        </div>
    </div>

    <form method="POST" action="{{ $rubrik->exists ? route('admin.rubrik.update', $rubrik) : route('admin.rubrik.store') }}" class="space-y-6" id="rubrikForm">
        @csrf
        @if($rubrik->exists)
            @method('PUT')
        @endif

        {{-- General Information --}}
        <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm space-y-5">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-800 pb-3">Informasi & Konfigurasi Rubrik</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">Judul Rubrik <span class="text-red-500">*</span></label>
                    <input type="text" name="judul" id="judulInput" value="{{ old('judul', $rubrik->judul) }}" required
                           placeholder="Contoh: Rubrik Penilaian Presentasi & Laporan Praktikum"
                           class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">Kategori <span class="text-red-500">*</span></label>
                    <select name="kategori" id="kategoriSelect" required class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        @foreach(\App\Models\EvaluationRubric::$kategoriLabels as $key => $label)
                        <option value="{{ $key }}" {{ old('kategori', $rubrik->kategori) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">Jenis Rubrik <span class="text-red-500">*</span></label>
                    <select name="jenis_rubrik" id="jenisRubrikSelect" required class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        @foreach(\App\Models\EvaluationRubric::$jenisRubrikLabels as $key => $label)
                        <option value="{{ $key }}" {{ old('jenis_rubrik', $rubrik->jenis_rubrik ?? 'analytic') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">Rumus Perhitungan Skor <span class="text-red-500">*</span></label>
                    <select name="rumus_penilaian" id="rumusPenilaianSelect" required class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        @foreach(\App\Models\EvaluationRubric::$rumusPenilaianLabels as $key => $label)
                        <option value="{{ $key }}" {{ old('rumus_penilaian', $rubrik->rumus_penilaian ?? 'weighted_percentage') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">Skala Maksimal <span class="text-red-500">*</span></label>
                    <input type="number" step="0.1" min="1" max="100" name="skala_max" id="skalaMaxInput" value="{{ old('skala_max', $rubrik->skala_max ?? 4) }}" required
                           class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <p class="text-[10px] text-gray-500 mt-1">Contoh: 4 (Skala 1-4), 3 (Skala 1-3), 4.5 (CBD desimal 2.5-4.5), atau 100.</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">Deskripsi / Petunjuk Penilaian</label>
                    <textarea name="deskripsi" id="deskripsiInput" rows="3" placeholder="Jelaskan petunjuk umum atau standar kelulusan rubrik penilaian ini..."
                              class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:outline-none">{{ old('deskripsi', $rubrik->deskripsi) }}</textarea>
                </div>

                @if($unitPage)
                <input type="hidden" name="unit_page_id" value="{{ $unitPage->id }}">
                <div class="p-3 rounded-xl bg-brand-50 text-xs text-brand-700 font-medium md:col-span-2">
                    📌 Terhubung ke Halaman Unit: <strong>{{ $unitPage->judul }}</strong>
                </div>
                @endif

                @if($unitEvent)
                <input type="hidden" name="unit_event_id" value="{{ $unitEvent->id }}">
                <div class="p-3 rounded-xl bg-purple-50 text-xs text-purple-700 font-medium md:col-span-2">
                    🎫 Terhubung ke Event Unit: <strong>{{ $unitEvent->judul }}</strong>
                </div>
                @endif
            </div>
        </div>

        {{-- Criteria & Levels Builder --}}
        <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm space-y-5">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Kriteria Utama & Sub-Kriteria</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Tentukan kriteria utama, sub-kriteria hirarkis, nilai bobot, dan deskriptor level capaian skala.</p>
                </div>
                <div class="text-right">
                    <span class="text-xs font-semibold text-gray-500">Total Bobot Utama:</span>
                    <span id="totalWeightBadge" class="text-sm font-extrabold px-2.5 py-1 rounded-lg bg-green-50 text-green-700">0</span>
                </div>
            </div>

            <div id="criteriaContainer" class="space-y-6">
                @php
                    $rawItems = [];
                    if ($rubrik->exists) {
                        $rawItems = $rubrik->rootItems->map(function ($item) {
                            $arr = $item->toArray();
                            $arr['sub_items'] = $item->subItems->map(fn($s) => $s->toArray())->toArray();
                            return $arr;
                        })->toArray();
                    }
                    $oldItems = old('items', $rawItems);
                    if (empty($oldItems)) {
                        $oldItems = [
                            ['nama_kriteria' => 'Penguasaan Materi & Kedalaman Isi', 'deskripsi' => 'Kemampuan menjelaskan materi secara sistematis dan komprehensif', 'bobot' => 40],
                            ['nama_kriteria' => 'Komunikasi & Retorika Penyampaian', 'deskripsi' => 'Penggunaan bahasa yang jelas, lugas, serta interaksi audiens', 'bobot' => 30],
                            ['nama_kriteria' => 'Media & Alat Bantu Visual', 'deskripsi' => 'Kerapihan slide/alat peraga dan penggunaan media pendukung', 'bobot' => 30],
                        ];
                    }
                @endphp

                @foreach($oldItems as $idx => $item)
                <div class="criterion-card p-5 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 space-y-4" data-index="{{ $idx }}">
                    <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $item['id'] ?? '' }}">

                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="criterion-number w-7 h-7 flex items-center justify-center rounded-xl bg-brand-600 text-white font-bold text-xs">{{ $idx + 1 }}</span>
                            <h3 class="font-bold text-sm text-gray-900 dark:text-white">Kriteria Utama {{ $idx + 1 }}</h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="addSubCriterion({{ $idx }})" class="text-xs font-bold text-purple-600 hover:text-purple-700 bg-purple-50 dark:bg-purple-900/30 px-3 py-1 rounded-lg transition-colors">
                                ＋ Tambah Sub Kriteria
                            </button>
                            <button type="button" onclick="removeCriterion(this)" class="text-xs font-bold text-red-500 hover:text-red-700 transition-colors">
                                ✕ Hapus
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-3">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama Kriteria <span class="text-red-500">*</span></label>
                            <input type="text" name="items[{{ $idx }}][nama_kriteria]" value="{{ $item['nama_kriteria'] ?? '' }}" required
                                   placeholder="Contoh: A. Sikap Jujur"
                                   class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Bobot / Poin <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="number" step="0.01" min="0" max="100" name="items[{{ $idx }}][bobot]" value="{{ $item['bobot'] ?? 0 }}" required
                                       oninput="calculateTotalWeight()"
                                       class="weight-input w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            </div>
                        </div>

                        <div class="md:col-span-4">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Deskripsi Kriteria</label>
                            <input type="text" name="items[{{ $idx }}][deskripsi]" value="{{ $item['deskripsi'] ?? '' }}"
                                   placeholder="Penjelasan rincian kriteria..."
                                   class="w-full px-3.5 py-2 text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    {{-- Level Descriptors Editor untuk Kriteria Utama (Server-Side Blade Rendered) --}}
                    @php
                        $itemLevels = $item['levels'] ?? [];
                        if (empty($itemLevels)) {
                            $sMax = (float) ($rubrik->skala_max ?? 4);
                            if ($sMax == 4.5) {
                                $cbdScales = [4.5, 4.0, 3.5, 3.0, 2.5];
                                $cbdLabels = ['Superior (4.5)', 'Di Atas Harapan (4.0)', 'Sesuai Harapan (3.5)', 'Borderline (3.0)', 'Di Bawah Harapan (2.5)'];
                                foreach ($cbdScales as $cIdx => $sk) {
                                    $itemLevels[] = ['skor' => $sk, 'label' => $cbdLabels[$cIdx], 'deskripsi' => ''];
                                }
                            } else {
                                for ($s = (int) $sMax; $s >= 1; $s--) {
                                    $lbl = match ($s) {
                                        5 => 'Sangat Baik (5)',
                                        4 => 'Sangat Baik (4)',
                                        3 => 'Baik (3)',
                                        2 => 'Cukup (2)',
                                        1 => 'Kurang (1)',
                                        default => "Skor $s",
                                    };
                                    $itemLevels[] = ['skor' => (float)$s, 'label' => $lbl, 'deskripsi' => ''];
                                }
                            }
                        }
                    @endphp
                    <div class="levels-editor-box pt-2" id="levelsEditor_items_{{ $idx }}">
                        <details class="mt-2 text-xs group" open>
                            <summary class="cursor-pointer font-bold text-purple-700 dark:text-purple-300 hover:underline flex items-center gap-1.5 py-1">
                                <span>📝 Edit Deskriptor Level Capaian Skala (Ubah Penjelasan Per Skor)</span>
                                <span class="text-[10px] text-gray-400 group-open:rotate-180 transition-transform">▼</span>
                            </summary>
                            <div class="mt-2 space-y-2 pl-2 border-l-2 border-purple-200 dark:border-purple-800/60">
                                @foreach($itemLevels as $lIdx => $lvl)
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-2 bg-gray-50 dark:bg-gray-800/80 p-2 rounded-lg border border-gray-200/60 dark:border-gray-700/60">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-gray-500">Skor & Label</label>
                                        <div class="flex items-center gap-1">
                                            <input type="number" step="0.1" name="items[{{ $idx }}][levels][{{ $lIdx }}][skor]" value="{{ $lvl['skor'] ?? '' }}" required class="w-14 px-2 py-1 text-xs rounded border border-gray-200 dark:border-gray-700 font-bold bg-white dark:bg-gray-900">
                                            <input type="text" name="items[{{ $idx }}][levels][{{ $lIdx }}][label]" value="{{ $lvl['label'] ?? '' }}" placeholder="Label" class="w-full px-2 py-1 text-xs rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 font-semibold">
                                        </div>
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="block text-[10px] font-semibold text-gray-500">Deskripsi Penjelasan Level Capaian</label>
                                        <input type="text" name="items[{{ $idx }}][levels][{{ $lIdx }}][deskripsi]" value="{{ $lvl['deskripsi'] ?? '' }}" placeholder="Ketik rincian indikator/deskripsi capaian untuk skor {{ $lvl['skor'] ?? '' }}..." class="w-full px-2.5 py-1 text-xs rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </details>
                    </div>

                    {{-- Container Sub Kriteria --}}
                    <div class="sub-criteria-container pl-6 border-l-2 border-purple-200 dark:border-purple-800 space-y-4 mt-3" id="subContainer_{{ $idx }}">
                        @php
                            $subItems = $item['sub_items'] ?? ($item['subItems'] ?? []);
                        @endphp
                        @foreach($subItems as $sIdx => $sub)
                        <div class="sub-criterion-row p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 space-y-3 relative">
                            <input type="hidden" name="items[{{ $idx }}][sub_items][{{ $sIdx }}][id]" value="{{ $sub['id'] ?? '' }}">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                <div class="md:col-span-2">
                                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-1">Nama Sub Kriteria</label>
                                    <input type="text" name="items[{{ $idx }}][sub_items][{{ $sIdx }}][nama_kriteria]" value="{{ $sub['nama_kriteria'] ?? '' }}" required
                                           placeholder="Contoh: 1. Tidak berkata/bertindak di luar norma"
                                           class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 font-medium">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-1">Bobot/Poin Sub</label>
                                    <input type="number" step="0.01" min="0" max="100" name="items[{{ $idx }}][sub_items][{{ $sIdx }}][bobot]" value="{{ $sub['bobot'] ?? 0 }}" required
                                           class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 font-medium">
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1">
                                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-1">Deskripsi Singkat</label>
                                        <input type="text" name="items[{{ $idx }}][sub_items][{{ $sIdx }}][deskripsi]" value="{{ $sub['deskripsi'] ?? '' }}"
                                               class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                    </div>
                                    <button type="button" onclick="this.closest('.sub-criterion-row').remove()" class="mt-4 text-xs font-bold text-red-500 hover:text-red-700 p-1">
                                        ✕
                                    </button>
                                </div>
                            </div>

                            {{-- Level Descriptors Editor untuk Sub-Kriteria (Server-Side Blade Rendered) --}}
                            @php
                                $subLevels = $sub['levels'] ?? [];
                                if (empty($subLevels)) {
                                    $subLevels = $itemLevels;
                                }
                            @endphp
                            <div class="sub-levels-editor-box pt-1" id="levelsEditor_items_{{ $idx }}_sub_{{ $sIdx }}">
                                <details class="mt-2 text-xs group" open>
                                    <summary class="cursor-pointer font-bold text-purple-700 dark:text-purple-300 hover:underline flex items-center gap-1.5 py-1">
                                        <span>📝 Edit Deskriptor Level Capaian Skala (Ubah Penjelasan Per Skor)</span>
                                        <span class="text-[10px] text-gray-400 group-open:rotate-180 transition-transform">▼</span>
                                    </summary>
                                    <div class="mt-2 space-y-2 pl-2 border-l-2 border-purple-200 dark:border-purple-800/60">
                                        @foreach($subLevels as $slIdx => $sLvl)
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 bg-gray-50 dark:bg-gray-800/80 p-2 rounded-lg border border-gray-200/60 dark:border-gray-700/60">
                                            <div>
                                                <label class="block text-[10px] font-semibold text-gray-500">Skor & Label</label>
                                                <div class="flex items-center gap-1">
                                                    <input type="number" step="0.1" name="items[{{ $idx }}][sub_items][{{ $sIdx }}][levels][{{ $slIdx }}][skor]" value="{{ $sLvl['skor'] ?? '' }}" required class="w-14 px-2 py-1 text-xs rounded border border-gray-200 dark:border-gray-700 font-bold bg-white dark:bg-gray-900">
                                                    <input type="text" name="items[{{ $idx }}][sub_items][{{ $sIdx }}][levels][{{ $slIdx }}][label]" value="{{ $sLvl['label'] ?? '' }}" placeholder="Label" class="w-full px-2 py-1 text-xs rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 font-semibold">
                                                </div>
                                            </div>
                                            <div class="md:col-span-3">
                                                <label class="block text-[10px] font-semibold text-gray-500">Deskripsi Penjelasan Level Capaian</label>
                                                <input type="text" name="items[{{ $idx }}][sub_items][{{ $sIdx }}][levels][{{ $slIdx }}][deskripsi]" value="{{ $sLvl['deskripsi'] ?? '' }}" placeholder="Ketik rincian indikator/deskripsi capaian untuk skor {{ $sLvl['skor'] ?? '' }}..." class="w-full px-2.5 py-1 text-xs rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </details>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>

            <button type="button" onclick="addCriterion()"
                    class="w-full py-3 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700 text-brand-600 hover:text-brand-500 hover:border-brand-300 font-bold text-sm transition-colors text-center">
                ＋ Tambah Kriteria Utama
            </button>
        </div>

        {{-- Submit Button --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.rubrik.index') }}" class="px-6 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold text-sm hover:bg-gray-200 transition-colors">
                Batal
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm transition-colors shadow-sm">
                💾 {{ $rubrik->exists ? 'Simpan Perubahan' : 'Simpan Rubrik' }}
            </button>
        </div>
    </form>
</div>

<script>
    function calculateTotalWeight() {
        let total = 0;
        document.querySelectorAll('#criteriaContainer > .criterion-card .weight-input').forEach(input => {
            total += parseFloat(input.value) || 0;
        });

        const badge = document.getElementById('totalWeightBadge');
        badge.textContent = total.toFixed(1);
    }

    function generateLevelsHtml(inputPrefix, levelsData, defaultMaxScale = 4) {
        let levels = Array.isArray(levelsData) && levelsData.length > 0 ? levelsData : [];

        if (levels.length === 0) {
            const sMax = parseFloat(defaultMaxScale) || 4;
            if (sMax === 4.5) {
                const cbdScales = [4.5, 4.0, 3.5, 3.0, 2.5];
                const cbdLabels = ['Superior (4.5)', 'Di Atas Harapan (4.0)', 'Sesuai Harapan (3.5)', 'Borderline (3.0)', 'Di Bawah Harapan (2.5)'];
                cbdScales.forEach((sk, i) => {
                    levels.push({ skor: sk, label: cbdLabels[i], deskripsi: '' });
                });
            } else {
                for (let s = Math.floor(sMax); s >= 1; s--) {
                    const label = s === 5 ? 'Sangat Baik' : (s === 4 ? 'Sangat Baik' : (s === 3 ? 'Baik' : (s === 2 ? 'Cukup' : 'Kurang')));
                    levels.push({ skor: s, label: `${label} (${s})`, deskripsi: '' });
                }
            }
        }

        let html = `
            <details class="mt-2 text-xs group" open>
                <summary class="cursor-pointer font-bold text-purple-700 dark:text-purple-300 hover:underline flex items-center gap-1.5 py-1">
                    <span>📝 Edit Deskriptor Level Capaian Skala (Ubah Penjelasan Per Skor)</span>
                    <span class="text-[10px] text-gray-400 group-open:rotate-180 transition-transform">▼</span>
                </summary>
                <div class="mt-2 space-y-2 pl-2 border-l-2 border-purple-200 dark:border-purple-800/60">
        `;

        levels.forEach((lvl, lIdx) => {
            html += `
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2 bg-gray-50 dark:bg-gray-800/80 p-2 rounded-lg border border-gray-200/60 dark:border-gray-700/60">
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-500">Skor & Label</label>
                        <div class="flex items-center gap-1">
                            <input type="number" step="0.1" name="${inputPrefix}[levels][${lIdx}][skor]" value="${lvl.skor}" required class="w-14 px-2 py-1 text-xs rounded border border-gray-200 dark:border-gray-700 font-bold bg-white dark:bg-gray-900">
                            <input type="text" name="${inputPrefix}[levels][${lIdx}][label]" value="${escapeHtml(lvl.label || '')}" placeholder="Label" class="w-full px-2 py-1 text-xs rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 font-semibold">
                        </div>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-[10px] font-semibold text-gray-500">Deskripsi Penjelasan Level Capaian</label>
                        <input type="text" name="${inputPrefix}[levels][${lIdx}][deskripsi]" value="${escapeHtml(lvl.deskripsi || '')}" placeholder="Ketik rincian indikator/deskripsi capaian untuk skor ${lvl.skor}..." class="w-full px-2.5 py-1 text-xs rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                    </div>
                </div>
            `;
        });

        html += `</div></details>`;
        return html;
    }

    function reindexCriteria() {
        const cards = document.querySelectorAll('#criteriaContainer > .criterion-card');
        cards.forEach((card, idx) => {
            card.setAttribute('data-index', idx);
            card.querySelector('.criterion-number').textContent = idx + 1;
            card.querySelector('h3').textContent = 'Kriteria Utama ' + (idx + 1);

            const addSubBtn = card.querySelector('button[onclick^="addSubCriterion"]');
            if (addSubBtn) addSubBtn.setAttribute('onclick', `addSubCriterion(${idx})`);

            const subContainer = card.querySelector('.sub-criteria-container');
            if (subContainer) subContainer.id = `subContainer_${idx}`;

            card.querySelectorAll('input, select, textarea').forEach(el => {
                if (el.name && !el.name.includes('[sub_items]')) {
                    el.name = el.name.replace(/items\[\d+\]/, 'items[' + idx + ']');
                } else if (el.name && el.name.includes('[sub_items]')) {
                    el.name = el.name.replace(/items\[\d+\]\[sub_items\]\[\d+\]/, function(match) {
                        const parts = match.match(/items\[\d+\]\[sub_items\]\[(\d+)\]/);
                        return `items[${idx}][sub_items][${parts[1]}]`;
                    });
                }
            });
        });
        calculateTotalWeight();
    }

    function addCriterion(data = null) {
        const idx = document.querySelectorAll('#criteriaContainer > .criterion-card').length;
        const container = document.getElementById('criteriaContainer');

        const div = document.createElement('div');
        div.className = 'criterion-card p-5 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 space-y-4';
        div.setAttribute('data-index', idx);

        const nama = data ? (data.nama_kriteria || '') : '';
        const bobot = data ? (data.bobot || 0) : 0;
        const desk = data ? (data.deskripsi || '') : '';
        const levels = data ? (data.levels || []) : [];

        const currentSkalaMax = document.getElementById('skalaMaxInput').value || 4;
        const levelsHtml = generateLevelsHtml(`items[${idx}]`, levels, currentSkalaMax);

        div.innerHTML = `
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="criterion-number w-7 h-7 flex items-center justify-center rounded-xl bg-brand-600 text-white font-bold text-xs">${idx + 1}</span>
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white">Kriteria Utama ${idx + 1}</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="addSubCriterion(${idx})" class="text-xs font-bold text-purple-600 hover:text-purple-700 bg-purple-50 dark:bg-purple-900/30 px-3 py-1 rounded-lg transition-colors">
                        ＋ Tambah Sub Kriteria
                    </button>
                    <button type="button" onclick="removeCriterion(this)" class="text-xs font-bold text-red-500 hover:text-red-700 transition-colors">
                        ✕ Hapus
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama Kriteria <span class="text-red-500">*</span></label>
                    <input type="text" name="items[${idx}][nama_kriteria]" value="${escapeHtml(nama)}" required placeholder="Contoh: Kualitas Analisis"
                           class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Bobot / Poin <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" max="100" name="items[${idx}][bobot]" value="${bobot}" required
                               oninput="calculateTotalWeight()"
                               class="weight-input w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>
                <div class="md:col-span-4">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Deskripsi Kriteria</label>
                    <input type="text" name="items[${idx}][deskripsi]" value="${escapeHtml(desk)}" placeholder="Penjelasan rincian kriteria..."
                           class="w-full px-3.5 py-2 text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>
            <div class="levels-editor-box pt-2">${levelsHtml}</div>
            <div class="sub-criteria-container pl-6 border-l-2 border-purple-200 dark:border-purple-800 space-y-4 mt-3" id="subContainer_${idx}"></div>
        `;

        container.appendChild(div);

        if (data && Array.isArray(data.sub_items)) {
            data.sub_items.forEach(sub => addSubCriterion(idx, sub));
        }

        reindexCriteria();
    }

    function addSubCriterion(parentIdx, subData = null) {
        const subContainer = document.getElementById(`subContainer_${parentIdx}`);
        if (!subContainer) return;

        const sIdx = subContainer.children.length;
        const div = document.createElement('div');
        div.className = 'sub-criterion-row p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 space-y-3 relative';

        const nama = subData ? (subData.nama_kriteria || '') : '';
        const bobot = subData ? (subData.bobot || 0) : 0;
        const desk = subData ? (subData.deskripsi || '') : '';
        const levels = subData ? (subData.levels || []) : [];

        const currentSkalaMax = document.getElementById('skalaMaxInput').value || 4;
        const levelsHtml = generateLevelsHtml(`items[${parentIdx}][sub_items][${sIdx}]`, levels, currentSkalaMax);

        div.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-1">Nama Sub Kriteria</label>
                    <input type="text" name="items[${parentIdx}][sub_items][${sIdx}][nama_kriteria]" value="${escapeHtml(nama)}" required
                           placeholder="Contoh: 1.1 Keterampilan Komunikasi"
                           class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 font-medium">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-1">Bobot/Poin Sub</label>
                    <input type="number" step="0.01" min="0" max="100" name="items[${parentIdx}][sub_items][${sIdx}][bobot]" value="${bobot}" required
                           class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 font-medium">
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex-1">
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-1">Deskripsi Singkat</label>
                        <input type="text" name="items[${parentIdx}][sub_items][${sIdx}][deskripsi]" value="${escapeHtml(desk)}"
                               class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    </div>
                    <button type="button" onclick="this.closest('.sub-criterion-row').remove()" class="mt-4 text-xs font-bold text-red-500 hover:text-red-700 p-1">
                        ✕
                    </button>
                </div>
            </div>
            <div class="sub-levels-editor-box pt-1">${levelsHtml}</div>
        `;

        subContainer.appendChild(div);
    }

    function removeCriterion(btn) {
        const card = btn.closest('.criterion-card');
        if (document.querySelectorAll('#criteriaContainer > .criterion-card').length <= 1) {
            alert('Rubrik penilaian harus memiliki minimal 1 kriteria.');
            return;
        }
        card.remove();
        reindexCriteria();
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    async function loadSelectedPreset() {
        const presetKey = document.getElementById('presetTemplateSelect').value;
        if (!presetKey) {
            alert('Silakan pilih preset template terlebih dahulu.');
            return;
        }

        try {
            const url = `{{ url('admin/rubrik/preset') }}/${presetKey}`;
            const resp = await fetch(url);
            if (!resp.ok) throw new Error('Gagal mengambil template preset.');

            const data = await resp.json();

            document.getElementById('judulInput').value = data.judul || '';
            document.getElementById('kategoriSelect').value = data.kategori || 'klinik';
            document.getElementById('jenisRubrikSelect').value = data.jenis_rubrik || 'analytic';
            document.getElementById('rumusPenilaianSelect').value = data.rumus_penilaian || 'weighted_percentage';
            document.getElementById('skalaMaxInput').value = data.skala_max || 4;
            document.getElementById('deskripsiInput').value = data.deskripsi || '';

            const container = document.getElementById('criteriaContainer');
            container.replaceChildren();

            if (Array.isArray(data.items)) {
                data.items.forEach(item => addCriterion(item));
            }

            calculateTotalWeight();
        } catch (err) {
            alert('Terjadi kesalahan: ' + err.message);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        calculateTotalWeight();
    });
</script>
@endsection
