<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rubrik Penilaian - {{ $rubrik->judul }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { background: white; font-size: 11pt; }
            .no-print { display: none !important; }
            .print-page { padding: 0; margin: 0; box-shadow: none; border: none; }
            @page { size: A4 portrait; margin: 15mm; }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans text-gray-900 min-h-screen p-6">

    {{-- Top Action Bar (No Print) --}}
    <div class="no-print max-w-4xl mx-auto mb-6 flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border border-gray-200">
        <a href="{{ route('admin.rubrik.show', $rubrik) }}" class="text-xs font-bold text-blue-600 hover:underline">← Kembali ke Detail Rubrik</a>
        <button onclick="window.print()" class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition-colors shadow">
            🖨️ Cetak Dokumen Rubrik
        </button>
    </div>

    {{-- Printable Page Container --}}
    <div class="print-page max-w-4xl mx-auto bg-white p-10 rounded-2xl shadow-md border border-gray-200 space-y-6">

        {{-- Header Kop --}}
        <div class="flex items-center justify-between border-b-2 border-gray-900 pb-4">
            <div class="flex items-center gap-4">
                <img src="/Kop surat Polkesban.png" alt="Logo Polkesban" class="h-16 w-auto object-contain" onerror="this.style.display='none'">
                <div>
                    <h2 class="text-base font-extrabold uppercase tracking-wide">KEMENTERIAN KESEHATAN REPUBLIK INDONESIA</h2>
                    <h1 class="text-lg font-black uppercase text-blue-900">POLTEKKES KEMENKES BANDUNG</h1>
                    <p class="text-xs text-gray-600">Jl. Pajajaran No. 56 Bandung | Web: poltekkesbandung.ac.id</p>
                </div>
            </div>
        </div>

        {{-- Title Block --}}
        <div class="text-center space-y-1 py-2">
            <h2 class="text-xl font-black uppercase tracking-wide text-gray-900">INSTRUMEN & RUBRIK PENILAIAN</h2>
            <p class="text-sm font-bold text-gray-700">{{ $rubrik->judul }}</p>
            <p class="text-xs text-gray-500">Kategori: {{ $rubrik->kategori_label }} | Skala Penilaian: 1 – {{ $rubrik->skala_max }}</p>
        </div>

        @if($rubrik->deskripsi)
        <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-700 leading-relaxed">
            <strong>Petunjuk Penilaian:</strong> {{ $rubrik->deskripsi }}
        </div>
        @endif

        {{-- Rubric Table --}}
        <table class="w-full text-left border-collapse border border-gray-900 text-xs">
            <thead>
                <tr class="bg-gray-200 text-gray-900 font-extrabold uppercase tracking-wider text-[11px]">
                    <th class="border border-gray-900 p-2 text-center w-8">No</th>
                    <th class="border border-gray-900 p-2 w-1/3">Kriteria Penilaian / Sub-Kriteria</th>
                    <th class="border border-gray-900 p-2 text-center w-16">Bobot</th>
                    <th class="border border-gray-900 p-2">Deskriptor & Level Skala Capaian (1–{{ $rubrik->skala_max }})</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rubrik->rootItems as $idx => $item)
                <tr class="border-b border-gray-900 bg-gray-50 font-bold">
                    <td class="border border-gray-900 p-2 text-center align-top">{{ $idx + 1 }}</td>
                    <td class="border border-gray-900 p-2 align-top">
                        <strong class="font-extrabold text-gray-900 text-xs block mb-0.5">{{ $item->nama_kriteria }}</strong>
                        @if($item->deskripsi)
                        <p class="text-[11px] font-normal text-gray-600 leading-snug">{{ $item->deskripsi }}</p>
                        @endif
                    </td>
                    <td class="border border-gray-900 p-2 text-center align-top font-bold text-xs">
                        {{ $item->bobot }}
                    </td>
                    <td class="border border-gray-900 p-2 align-top">
                        @if($item->subItems->isEmpty())
                            <div class="space-y-1">
                                @foreach(($item->levels ?? []) as $lvl)
                                <div class="border-b border-gray-200 last:border-none pb-1">
                                    <span class="font-bold text-gray-900 text-[11px]">Skor {{ $lvl['skor'] ?? '' }} ({{ $lvl['label'] ?? '' }}):</span>
                                    <span class="text-gray-700 text-[11px] font-normal">{{ $lvl['deskripsi'] ?: 'Sesuai standar kriteria.' }}</span>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <span class="text-[11px] font-bold text-gray-700">Terbagi atas {{ $item->subItems->count() }} sub-kriteria berikut:</span>
                        @endif
                    </td>
                </tr>

                @foreach($item->subItems as $sIdx => $sub)
                <tr class="border-b border-gray-900">
                    <td class="border border-gray-900 p-2 text-center text-gray-500 font-semibold text-[10px] pl-4">└ {{ $idx + 1 }}.{{ $sIdx + 1 }}</td>
                    <td class="border border-gray-900 p-2 align-top pl-6">
                        <strong class="font-bold text-gray-800 text-[11px] block">{{ $sub->nama_kriteria }}</strong>
                        @if($sub->deskripsi)
                        <p class="text-[10px] text-gray-500 leading-tight">{{ $sub->deskripsi }}</p>
                        @endif
                    </td>
                    <td class="border border-gray-900 p-2 text-center align-top font-semibold text-[11px]">
                        {{ $sub->bobot }}
                    </td>
                    <td class="border border-gray-900 p-2 align-top">
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
                                    $subLevels[] = ['skor' => (float)$s, 'label' => $label, 'deskripsi' => ''];
                                }
                            }
                        @endphp
                        <div class="space-y-1">
                            @foreach($subLevels as $lvl)
                            <div class="border-b border-gray-100 last:border-none pb-0.5">
                                <span class="font-semibold text-gray-900 text-[10px]">Skor {{ $lvl['skor'] ?? '' }} ({{ $lvl['label'] ?? '' }}):</span>
                                <span class="text-gray-600 text-[10px]">{{ $lvl['deskripsi'] ?: '-' }}</span>
                            </div>
                            @endforeach
                        </div>
                    </td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gray-100 font-bold text-xs">
                    <td colspan="2" class="border border-gray-900 p-2 text-right">TOTAL BOBOT/POIN:</td>
                    <td class="border border-gray-900 p-2 text-center text-blue-900 font-black">{{ $rubrik->total_weight }}</td>
                    <td class="border border-gray-900 p-2 text-gray-600 text-[11px]">Rumus Skoring: {{ $rubrik->rumus_penilaian_label }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- Signatures --}}
        <div class="pt-8 grid grid-cols-2 gap-8 text-xs">
            <div>
                <p class="text-gray-600">Mengetahui/Menyetujui,</p>
                <p class="font-bold mt-1">Penanggung Jawab / Pimpinan</p>
                <div class="h-16"></div>
                <p class="font-bold underline">_________________________</p>
                <p class="text-gray-500">NIP. ........................................</p>
            </div>
            <div class="text-right">
                <p class="text-gray-600">Bandung, {{ now()->translatedFormat('d F Y') }}</p>
                <p class="font-bold mt-1">Penilai / Penguji</p>
                <div class="h-16"></div>
                <p class="font-bold underline">{{ $rubrik->creator->name ?? '_________________________' }}</p>
                <p class="text-gray-500">NIP. {{ $rubrik->creator->identity_number ?? '........................................' }}</p>
            </div>
        </div>
    </div>
</body>
</html>
