@extends('mahasiswa.layouts.portal')

@section('title', 'Buku Portofolio Pembelajaran OBE')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span>📘</span> Buku Portofolio Pembelajaran (OBE)
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Portofolio Penilaian Pembelajaran berbasis Outcome Based Education (OBE) Poltekkes Kemenkes Bandung.
            </p>
        </div>
        <a href="{{ route('mahasiswa.obe.create') }}" 
           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-medium text-sm shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Buat Buku Portofolio Baru
        </a>
    </div>

    <!-- Alert Success -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center gap-3">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Content Card -->
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
        @if($list->isEmpty())
            <div class="p-12 text-center">
                <div class="w-16 h-16 rounded-full bg-brand-50 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 flex items-center justify-center text-3xl mx-auto mb-4">
                    📘
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Belum Ada Buku Portofolio OBE</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6">
                    Anda belum membuat dokumen Buku Portofolio Pembelajaran OBE. Klik tombol di bawah untuk membuat formulir baru.
                </p>
                <a href="{{ route('mahasiswa.obe.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 text-white text-sm font-medium hover:bg-brand-700 transition">
                    Buat Portofolio OBE
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-200 text-xs uppercase tracking-wider font-semibold border-b border-gray-100 dark:border-gray-800">
                        <tr>
                            <th class="px-6 py-4">Mata Kuliah & Kode</th>
                            <th class="px-6 py-4">Semester / TA</th>
                            <th class="px-6 py-4">Lahan Praktik</th>
                            <th class="px-6 py-4">Evidence</th>
                            <th class="px-6 py-4">Nilai</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($list as $item)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $item->nama_mk }}</div>
                                    <div class="text-xs text-gray-400 font-mono">{{ $item->kode_mk }} ({{ $item->sks }} SKS)</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                                        Semester {{ $item->semester }}
                                    </span>
                                    <div class="text-xs text-gray-400 mt-0.5">TA {{ $item->tahun_akademik }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($item->lahan_praktik)
                                        <div class="text-gray-900 dark:text-white font-medium">{{ $item->lahan_praktik }}</div>
                                        <div class="text-xs text-gray-400">Pembimbing: {{ $item->pembimbing_lahan ?? '-' }}</div>
                                    @else
                                        <span class="text-xs text-gray-400 italic">Tidak ada lahan praktik</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300">
                                        📄 {{ $item->evidences_count }} Dokumen
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($item->nilai_lambang)
                                        <span class="font-bold text-brand-600 dark:text-brand-400 text-base">
                                            {{ $item->nilai_lambang }}
                                        </span>
                                        <span class="text-xs text-gray-400">({{ $item->nilai_angka }})</span>
                                    @else
                                        <span class="text-xs text-gray-400 italic">Belum ada</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <a href="{{ route('mahasiswa.obe.print', $item->id) }}" target="_blank" 
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium transition" title="Preview & Cetak Dokumen PDF">
                                        🖨️ Cetak
                                    </a>
                                    <a href="{{ route('mahasiswa.obe.edit', $item->id) }}" 
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-brand-50 hover:bg-brand-100 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300 text-xs font-medium transition">
                                        ✏️ Edit
                                    </a>
                                    <form action="{{ route('mahasiswa.obe.destroy', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus portofolio OBE ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 dark:bg-red-950/30 text-red-600 dark:text-red-400 text-xs font-medium transition">
                                            🗑️
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
