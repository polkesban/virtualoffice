<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rubrik Penilaian — {{ $event->judul }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100 min-h-screen flex flex-col antialiased">

    <div class="p-4 sm:p-6 max-w-6xl mx-auto w-full space-y-6 flex-1">

        {{-- Navigation Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-gray-900 p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm">
            <div>
                <a href="{{ route('unit.events.access', [$page->slug, $event->slug, $participant->access_token]) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 dark:text-brand-400 hover:underline mb-2">
                    ← Kembali ke Dashboard Akses
                </a>
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white">📊 Rubrik Penilaian Kegiatan</h1>
                <p class="text-xs text-gray-500 mt-1">{{ $event->judul }} ({{ $page->judul }})</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-brand-50 text-brand-700 dark:bg-brand-900/40 dark:text-brand-300">
                    Akses: {{ $participant->name ?? 'Preseptor / Peserta' }}
                </span>
            </div>
        </div>

        @if($rubriks->isEmpty())
        <div class="bg-white dark:bg-gray-900 p-8 rounded-3xl border border-gray-100 dark:border-gray-800 text-center space-y-3">
            <p class="text-4xl">📋</p>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Belum Ada Rubrik Penilaian Dihubungkan</h2>
            <p class="text-xs text-gray-500 max-w-md mx-auto">Admin atau Dosen pengampu dapat membuat dan mengaitkan instrumen rubrik penilaian untuk kegiatan ini di menu Admin > Rubrik Penilaian.</p>
        </div>
        @else

        {{-- Rubric Selector Tabs if Multiple --}}
        @if($rubriks->count() > 1)
        <div class="flex items-center gap-2 overflow-x-auto pb-1">
            @foreach($rubriks as $r)
            <a href="{{ route('unit.events.rubrik.show', [$page->slug, $event->slug, $participant->access_token, 'rubrik_id' => $r->id]) }}"
               class="px-4 py-2 rounded-2xl text-xs font-bold whitespace-nowrap transition-colors border {{ ($selectedRubrik && $selectedRubrik->id === $r->id) ? 'bg-brand-600 text-white border-brand-600 shadow-sm' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-800 hover:bg-gray-50' }}">
                {{ $r->judul }}
            </a>
            @endforeach
        </div>
        @endif

        @if($selectedRubrik)
        {{-- Selected Rubric Header & Summary --}}
        <div class="bg-white dark:bg-gray-900 p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 dark:border-gray-800 pb-4">
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-brand-50 text-brand-700 dark:bg-brand-900/40 dark:text-brand-300">{{ $selectedRubrik->kategori_label }}</span>
                        <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-purple-50 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">{{ $selectedRubrik->jenis_rubrik_label }}</span>
                    </div>
                    <h2 class="text-xl font-extrabold text-gray-900 dark:text-white">{{ $selectedRubrik->judul }}</h2>
                    @if($selectedRubrik->deskripsi)
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $selectedRubrik->deskripsi }}</p>
                    @endif
                </div>

                <a href="{{ route('admin.rubrik.print', $selectedRubrik) }}" target="_blank"
                   class="px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-900 dark:text-white font-bold text-xs transition-colors shrink-0 flex items-center justify-center gap-2">
                    🖨️ Cetak Formulir Rubrik
                </a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                <div class="bg-gray-50 dark:bg-gray-800/60 p-3 rounded-2xl">
                    <span class="text-gray-400 font-semibold block text-[10px] uppercase">Jumlah Kriteria</span>
                    <strong class="text-sm font-bold text-gray-900 dark:text-white">{{ $selectedRubrik->rootItems->count() }} Kriteria Utama</strong>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/60 p-3 rounded-2xl">
                    <span class="text-gray-400 font-semibold block text-[10px] uppercase">Total Bobot / Poin</span>
                    <strong class="text-sm font-bold text-brand-600">{{ $selectedRubrik->total_weight }}</strong>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/60 p-3 rounded-2xl">
                    <span class="text-gray-400 font-semibold block text-[10px] uppercase">Skala Capaian</span>
                    <strong class="text-sm font-bold text-indigo-600">1 – {{ $selectedRubrik->skala_max }}</strong>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/60 p-3 rounded-2xl">
                    <span class="text-gray-400 font-semibold block text-[10px] uppercase">Rumus Perhitungan</span>
                    <strong class="text-xs font-bold text-gray-800 dark:text-gray-200 leading-tight block truncate">{{ $selectedRubrik->rumus_penilaian_label }}</strong>
                </div>
            </div>
        </div>

        {{-- Rubric Matrix Table --}}
        <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden p-6 space-y-4">
            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span>🧩</span> Matriks Indikator & Rating Scale (RS 1 – {{ $selectedRubrik->skala_max }})
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-bold uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                            <th class="py-3 px-4 w-12 text-center">No</th>
                            <th class="py-3 px-4 w-1/3">Komponen / Sub-Kriteria Penilaian</th>
                            <th class="py-3 px-4 w-20 text-center">Bobot</th>
                            <th class="py-3 px-4">Deskriptor Level Capaian Skala (Rating Scale)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-xs text-gray-800 dark:text-gray-200">
                        @foreach($selectedRubrik->rootItems as $idx => $item)
                        <tr class="bg-gray-50/70 dark:bg-gray-800/70 font-bold border-t border-gray-200 dark:border-gray-700">
                            <td class="py-3.5 px-4 text-center text-gray-500 font-bold">{{ $idx + 1 }}</td>
                            <td class="py-3.5 px-4 align-top">
                                <strong class="font-extrabold text-sm text-gray-900 dark:text-white block">{{ $item->nama_kriteria }}</strong>
                                @if($item->deskripsi)
                                <p class="text-gray-500 font-normal mt-0.5 leading-relaxed">{{ $item->deskripsi }}</p>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center align-top font-black text-brand-600 text-sm">
                                {{ $item->bobot }}
                            </td>
                            <td class="py-3.5 px-4 align-top">
                                @if($item->subItems->isEmpty())
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        @foreach(($item->levels ?? []) as $lvl)
                                        <div class="p-2.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-700">
                                            <div class="flex items-center justify-between gap-2 mb-1">
                                                <span class="font-bold text-xs text-brand-700 dark:text-brand-400">{{ $lvl['label'] ?? '' }}</span>
                                                <span class="px-1.5 py-0.5 rounded bg-gray-200 dark:bg-gray-700 text-[10px] font-black text-gray-700 dark:text-gray-300">Skor {{ $lvl['skor'] ?? '' }}</span>
                                            </div>
                                            @if(!empty($lvl['deskripsi']))
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400 font-normal">{{ $lvl['deskripsi'] }}</p>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-purple-600 font-bold">Terbagi atas {{ $item->subItems->count() }} sub-kriteria</span>
                                @endif
                            </td>
                        </tr>

                        @foreach($item->subItems as $sIdx => $sub)
                        <tr class="hover:bg-purple-50/30 dark:hover:bg-purple-900/10 transition-colors">
                            <td class="py-3 px-4 text-center text-xs font-semibold text-purple-400 pl-8">└ {{ $idx + 1 }}.{{ $sIdx + 1 }}</td>
                            <td class="py-3 px-4 align-top pl-8">
                                <span class="font-bold text-xs text-purple-950 dark:text-purple-300 block">{{ $sub->nama_kriteria }}</span>
                                @if($sub->deskripsi)
                                <p class="text-[11px] text-gray-500 mt-0.5">{{ $sub->deskripsi }}</p>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center align-top font-bold text-purple-600 text-xs">
                                {{ $sub->bobot }}
                            </td>
                            <td class="py-3 px-4 align-top">
                                @php
                                    $subLevels = !empty($sub->levels) ? $sub->levels : (!empty($item->levels) ? $item->levels : []);
                                    if (empty($subLevels)) {
                                        $sMax = (float) $selectedRubrik->skala_max;
                                        for ($s = (int) $sMax; $s >= 1; $s--) {
                                            $subLevels[] = ['skor' => (float)$s, 'label' => "Rating $s", 'deskripsi' => 'Standard kriteria penilaian'];
                                        }
                                    }
                                @endphp
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    @foreach($subLevels as $lvl)
                                    <div class="p-2 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                                        <div class="flex items-center justify-between gap-1 mb-0.5">
                                            <span class="font-semibold text-[11px] text-gray-800 dark:text-gray-200">{{ $lvl['label'] ?? '' }}</span>
                                            <span class="px-1 py-0.5 rounded bg-gray-200 dark:bg-gray-700 text-[9px] font-bold">Skor {{ $lvl['skor'] ?? '' }}</span>
                                        </div>
                                        @if(!empty($lvl['deskripsi']))
                                        <p class="text-[10px] text-gray-500 dark:text-gray-400 font-normal">{{ $lvl['deskripsi'] }}</p>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Score Calculator Simulator --}}
        @php
            $allItems = $selectedRubrik->items;
            $parentIds = $allItems->pluck('parent_id')->filter()->unique()->toArray();
            $evalItems = $allItems->reject(fn ($i) => in_array($i->id, $parentIds, true));
            if ($evalItems->isEmpty()) {
                $evalItems = $allItems;
            }
        @endphp

        <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm p-6 space-y-5">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>🧮</span> Kalkulator Simulasi / Pengisian Skor
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Uji coba kalkulasi skor per komponen untuk melihat perkiraan Nilai Akhir & Grade.</p>
                </div>
                <button type="button" onclick="resetSimulator()" class="text-xs font-semibold text-brand-600 hover:underline">Reset Simulasi</button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-4">
                    @foreach($evalItems as $item)
                    <div class="p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs text-gray-900 dark:text-white">
                                @if($item->parent_id) └ @endif {{ $item->nama_kriteria }} (Bobot {{ $item->bobot }})
                            </span>
                            <span class="text-xs font-mono font-bold text-brand-600">Skor: <span id="scoreDisplay_{{ $item->id }}">0</span> / {{ $selectedRubrik->skala_max }}</span>
                        </div>

                        @php
                            $effectiveLevels = !empty($item->levels) ? $item->levels : ($item->parent?->levels ?? []);
                        @endphp

                        @if(!empty($effectiveLevels))
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach($effectiveLevels as $lvl)
                            <button type="button" onclick="setScore({{ $item->id }}, {{ $lvl['skor'] }}, {{ $item->bobot }})"
                                    id="btn_{{ $item->id }}_{{ str_replace('.', '_', $lvl['skor']) }}"
                                    class="sim-btn-{{ $item->id }} flex-1 py-1.5 px-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs font-bold hover:bg-brand-50 transition-colors text-center">
                                {{ $lvl['skor'] }} <span class="text-[10px] text-gray-400 font-normal block truncate">{{ $lvl['label'] }}</span>
                            </button>
                            @endforeach
                        </div>
                        @else
                        <input type="number" step="0.1" min="0" max="{{ $selectedRubrik->skala_max }}" value="0"
                               oninput="setScore({{ $item->id }}, parseFloat(this.value)||0, {{ $item->bobot }})"
                               class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 font-bold">
                        @endif
                    </div>
                    @endforeach
                </div>

                {{-- Result Card --}}
                <div class="bg-gray-50 dark:bg-gray-800/80 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 flex flex-col justify-between space-y-4">
                    <div>
                        <h4 class="font-bold text-xs uppercase tracking-wider text-gray-400 mb-3">Hasil Perhitungan Simulasi</h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500">Total Skor Murni:</span>
                                <span class="font-bold font-mono text-gray-900 dark:text-white" id="simRawTotal">0.00</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500">Nilai Akhir (%):</span>
                                <span class="font-bold font-mono text-gray-900 dark:text-white text-base" id="simWeightedTotal">0.00%</span>
                            </div>
                            <div class="pt-3 border-t border-gray-200 dark:border-gray-700 text-center">
                                <span class="text-xs text-gray-400 block mb-1 font-semibold">Grade Akhir</span>
                                <span id="simGradeBadge" class="inline-block text-3xl font-black px-4 py-1 rounded-xl bg-gray-200 text-gray-700">E</span>
                            </div>
                        </div>
                    </div>

                    <div class="text-[11px] text-gray-400 text-center italic">
                        Perhitungan berbasis rumus: <strong>{{ $selectedRubrik->rumus_penilaian_label }}</strong>.
                    </div>
                </div>
            </div>
        </div>
        @endif
        @endif

    </div>

    <script>
        const scores = {};
        const weights = {};
        const skalaMax = {{ $selectedRubrik->skala_max ?? 4 }};
        const rumus = '{{ $selectedRubrik->rumus_penilaian ?? 'weighted_percentage' }}';
        const totalWeight = {{ $selectedRubrik->total_weight ?? 0 }};

        function setScore(itemId, score, weight) {
            scores[itemId] = score;
            weights[itemId] = weight;

            const display = document.getElementById('scoreDisplay_' + itemId);
            if (display) display.textContent = score;

            document.querySelectorAll('.sim-btn-' + itemId).forEach(btn => {
                btn.classList.remove('bg-brand-600', 'text-white', 'border-brand-600');
                btn.classList.add('bg-white', 'dark:bg-gray-800');
            });

            const safeScoreId = String(score).replace('.', '_');
            const activeBtn = document.getElementById('btn_' + itemId + '_' + safeScoreId);
            if (activeBtn) {
                activeBtn.classList.remove('bg-white', 'dark:bg-gray-800');
                activeBtn.classList.add('bg-brand-600', 'text-white', 'border-brand-600');
            }

            recalculate();
        }

        function recalculate() {
            let rawTotal = 0;
            let weightedTotal = 0;
            let maxPossibleRaw = 0;

            @if(isset($evalItems))
            @foreach($evalItems as $item)
                const s_{{ $item->id }} = scores[{{ $item->id }}] || 0;
                const w_{{ $item->id }} = weights[{{ $item->id }}] || {{ $item->bobot }};
                rawTotal += s_{{ $item->id }};

                if (rumus === 'score_times_weight') {
                    maxPossibleRaw += skalaMax * w_{{ $item->id }};
                    weightedTotal += s_{{ $item->id }} * w_{{ $item->id }};
                } else {
                    maxPossibleRaw += skalaMax;
                    if (totalWeight > 0) {
                        weightedTotal += (s_{{ $item->id }} / skalaMax) * w_{{ $item->id }};
                    }
                }
            @endforeach
            @endif

            let finalPercentage = 0;
            if (rumus === 'score_times_weight') {
                finalPercentage = maxPossibleRaw > 0 ? (weightedTotal / maxPossibleRaw) * 100 : 0;
            } else if (rumus === 'raw_sum') {
                finalPercentage = maxPossibleRaw > 0 ? (rawTotal / maxPossibleRaw) * 100 : 0;
            } else {
                finalPercentage = totalWeight > 0 ? weightedTotal : (maxPossibleRaw > 0 ? (rawTotal / maxPossibleRaw) * 100 : 0);
            }

            const simRaw = document.getElementById('simRawTotal');
            const simWeighted = document.getElementById('simWeightedTotal');
            if (simRaw) simRaw.textContent = rawTotal.toFixed(2);
            if (simWeighted) simWeighted.textContent = finalPercentage.toFixed(2) + '%';

            const gradeBadge = document.getElementById('simGradeBadge');
            if (gradeBadge) {
                let letter = 'E';
                let bgClass = 'bg-red-100 text-red-700';

                if (finalPercentage >= 85) { letter = 'A'; bgClass = 'bg-green-100 text-green-800'; }
                else if (finalPercentage >= 75) { letter = 'B'; bgClass = 'bg-blue-100 text-blue-800'; }
                else if (finalPercentage >= 65) { letter = 'C'; bgClass = 'bg-yellow-100 text-yellow-800'; }
                else if (finalPercentage >= 50) { letter = 'D'; bgClass = 'bg-orange-100 text-orange-800'; }

                gradeBadge.textContent = letter;
                gradeBadge.className = 'inline-block text-3xl font-black px-5 py-1.5 rounded-xl ' + bgClass;
            }
        }

        function resetSimulator() {
            for (const key in scores) delete scores[key];
            document.querySelectorAll('[id^="scoreDisplay_"]').forEach(el => el.textContent = '0');
            document.querySelectorAll('[class*="sim-btn-"]').forEach(btn => {
                btn.classList.remove('bg-brand-600', 'text-white', 'border-brand-600');
                btn.classList.add('bg-white', 'dark:bg-gray-800');
            });
            recalculate();
        }
    </script>
</body>
</html>
