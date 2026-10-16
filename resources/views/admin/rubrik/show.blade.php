@extends('admin.layouts.admin')
@section('title', 'Detail Rubrik — ' . $rubrik->judul)

@section('content')
<div class="p-6 max-w-6xl mx-auto space-y-6">

    {{-- Top bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-gray-900 p-6 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
        <div>
            <div class="flex flex-wrap items-center gap-2 mb-1">
                <a href="{{ route('admin.rubrik.index') }}" class="text-xs font-bold text-brand-600 hover:underline">← Kembali ke Daftar Rubrik</a>
                <span class="text-gray-300">·</span>
                <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-brand-50 text-brand-700">{{ $rubrik->kategori_label }}</span>
                <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-purple-50 text-purple-700">{{ $rubrik->jenis_rubrik_label }}</span>
            </div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $rubrik->judul }}</h1>
            @if($rubrik->deskripsi)
            <p class="text-sm text-gray-500 mt-1">{{ $rubrik->deskripsi }}</p>
            @endif
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('admin.rubrik.print', $rubrik) }}" target="_blank"
               class="px-3.5 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 text-gray-800 dark:text-gray-200 font-bold text-xs transition-colors">
                🖨️ Cetak Rubrik
            </a>
            <a href="{{ route('admin.rubrik.edit', $rubrik) }}"
               class="px-3.5 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs transition-colors">
                ✏️ Edit Rubrik
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-green-50 border border-green-200 text-sm text-green-700 font-medium">
        {{ session('success') }}
    </div>
    @endif

    {{-- Summary Metadata Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-gray-100 dark:border-gray-800">
            <span class="text-xs text-gray-400 font-semibold uppercase">Total Kriteria Utama</span>
            <p class="text-xl font-black text-gray-900 dark:text-white mt-1">{{ $rubrik->rootItems->count() }} Kriteria</p>
        </div>

        <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-gray-100 dark:border-gray-800">
            <span class="text-xs text-gray-400 font-semibold uppercase">Total Bobot / Poin</span>
            <p class="text-xl font-black text-brand-600 mt-1">{{ $rubrik->total_weight }}</p>
        </div>

        <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-gray-100 dark:border-gray-800">
            <span class="text-xs text-gray-400 font-semibold uppercase">Skala Maksimal</span>
            <p class="text-xl font-black text-indigo-600 mt-1">1 – {{ $rubrik->skala_max }}</p>
        </div>

        <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-gray-100 dark:border-gray-800">
            <span class="text-xs text-gray-400 font-semibold uppercase">Rumus Skoring</span>
            <p class="text-xs font-bold text-gray-900 dark:text-white mt-1 leading-tight">{{ $rubrik->rumus_penilaian_label }}</p>
        </div>
    </div>

    {{-- Rubric Matrix Table --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden space-y-4 p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <span>🧩</span> Matriks Rubrik Penilaian & Sub-Kriteria
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-bold uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4 w-1/4">Kriteria Penilaian / Sub-Kriteria</th>
                        <th class="py-3 px-4 w-20 text-center">Bobot</th>
                        <th class="py-3 px-4">Deskriptor & Level Capaian Skala (1–{{ $rubrik->skala_max }})</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-xs text-gray-800 dark:text-gray-200">
                    @foreach($rubrik->rootItems as $idx => $item)
                    <tr class="bg-gray-50/70 dark:bg-gray-800/70 font-bold border-t border-gray-200 dark:border-gray-700">
                        <td class="py-3.5 px-4 text-center text-gray-500 font-bold">{{ $idx + 1 }}</td>
                        <td class="py-3.5 px-4 align-top">
                            <strong class="font-black text-sm text-gray-900 dark:text-white block">{{ $item->nama_kriteria }}</strong>
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
                                <span class="text-xs text-purple-600 font-bold">Memiliki {{ $item->subItems->count() }} Sub-Kriteria</span>
                            @endif
                        </td>
                    </tr>

                    {{-- Render Sub-Kriteria jika ada --}}
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
                                    $sMax = (float) $rubrik->skala_max;
                                    for ($s = (int) $sMax; $s >= 1; $s--) {
                                        $label = match ($s) {
                                            5 => 'Sangat Baik (5)',
                                            4 => 'Sangat Baik (4)',
                                            3 => 'Baik (3)',
                                            2 => 'Cukup (2)',
                                            1 => 'Kurang (1)',
                                            default => "Skor $s",
                                        };
                                        $subLevels[] = ['skor' => (float)$s, 'label' => $label, 'deskripsi' => 'Standard kriteria penilaian'];
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
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400">{{ $lvl['deskripsi'] }}</p>
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

    {{-- Interactive Simulator --}}
    @php
        $allItems = $rubrik->items;
        $parentIds = $allItems->pluck('parent_id')->filter()->unique()->toArray();
        $evalItems = $allItems->reject(fn ($i) => in_array($i->id, $parentIds, true));
        if ($evalItems->isEmpty()) {
            $evalItems = $allItems;
        }
    @endphp

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6 space-y-5">
        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span>🧮</span> Kalkulator Simulasi Penilaian
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Uji coba simulasi pengisian skor untuk melihat perhitungan Nilai Akhir Terbobot dan Grade.</p>
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
                        <span class="text-xs font-mono font-bold text-brand-600">Skor: <span id="scoreDisplay_{{ $item->id }}">0</span> / {{ $rubrik->skala_max }}</span>
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
                    <input type="number" step="0.1" min="0" max="{{ $rubrik->skala_max }}" value="0"
                           oninput="setScore({{ $item->id }}, parseFloat(this.value)||0, {{ $item->bobot }})"
                           class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 font-bold">
                    @endif
                </div>
                @endforeach
            </div>

            {{-- Result Card --}}
            <div class="bg-gray-50 dark:bg-gray-800/80 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 flex flex-col justify-between space-y-4">
                <div>
                    <h3 class="font-bold text-xs uppercase tracking-wider text-gray-400 mb-3">Hasil Kalkulasi Simulasi</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500">Skor Murni Total:</span>
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
                    Kalkulasi menggunakan rumus: <strong>{{ $rubrik->rumus_penilaian_label }}</strong>.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const scores = {};
    const weights = {};
    const skalaMax = {{ $rubrik->skala_max }};
    const rumus = '{{ $rubrik->rumus_penilaian ?? 'weighted_percentage' }}';
    const totalWeight = {{ $rubrik->total_weight }};

    function setScore(itemId, score, weight) {
        scores[itemId] = score;
        weights[itemId] = weight;

        const display = document.getElementById('scoreDisplay_' + itemId);
        if (display) display.textContent = score;

        document.querySelectorAll('.sim-btn-' + itemId).forEach(btn => {
            btn.classList.remove('bg-brand-600', 'text-white', 'border-brand-600');
            btn.classList.add('bg-white', 'dark:bg-gray-800', 'text-gray-800', 'dark:text-gray-200');
        });

        const safeScoreId = String(score).replace('.', '_');
        const activeBtn = document.getElementById('btn_' + itemId + '_' + safeScoreId);
        if (activeBtn) {
            activeBtn.classList.remove('bg-white', 'dark:bg-gray-800', 'text-gray-800', 'dark:text-gray-200');
            activeBtn.classList.add('bg-brand-600', 'text-white', 'border-brand-600');
        }

        recalculate();
    }

    function recalculate() {
        let rawTotal = 0;
        let weightedTotal = 0;
        let maxPossibleRaw = 0;

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

        let finalPercentage = 0;
        if (rumus === 'score_times_weight') {
            finalPercentage = maxPossibleRaw > 0 ? (weightedTotal / maxPossibleRaw) * 100 : 0;
        } else if (rumus === 'raw_sum') {
            finalPercentage = maxPossibleRaw > 0 ? (rawTotal / maxPossibleRaw) * 100 : 0;
        } else {
            finalPercentage = totalWeight > 0 ? weightedTotal : (maxPossibleRaw > 0 ? (rawTotal / maxPossibleRaw) * 100 : 0);
        }

        document.getElementById('simRawTotal').textContent = rawTotal.toFixed(2);
        document.getElementById('simWeightedTotal').textContent = finalPercentage.toFixed(2) + '%';

        const gradeBadge = document.getElementById('simGradeBadge');
        let letter = 'E';
        let bgClass = 'bg-red-100 text-red-700';

        if (finalPercentage >= 85) { letter = 'A'; bgClass = 'bg-green-100 text-green-800'; }
        else if (finalPercentage >= 75) { letter = 'B'; bgClass = 'bg-blue-100 text-blue-800'; }
        else if (finalPercentage >= 65) { letter = 'C'; bgClass = 'bg-yellow-100 text-yellow-800'; }
        else if (finalPercentage >= 50) { letter = 'D'; bgClass = 'bg-orange-100 text-orange-800'; }

        gradeBadge.textContent = letter;
        gradeBadge.className = 'inline-block text-3xl font-black px-5 py-1.5 rounded-xl ' + bgClass;
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
@endsection
