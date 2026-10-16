@extends('admin.layouts.admin')
@section('title', 'Detail Laporan WFH')
@section('page-title', 'Detail Laporan WFH')
@section('page-subtitle', 'Tinjau rincian kegiatan harian yang dilaporkan.')

@section('content')
<div class="mx-auto max-w-7xl p-6">
    <div class="mb-6 flex flex-wrap justify-between items-center gap-3">
        <a href="{{ route('admin.wfh.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
            ⬅️ Kembali ke Rekap
        </a>
        <button onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl border border-gray-250 bg-white px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:bg-gray-900 dark:border-gray-750 dark:text-gray-200">
            🖨️ Cetak Halaman
        </button>
    </div>

    @php
        $emp = $wfhReport->user;
        $deptLabel = '';
        if ($emp->managedUnitPage) {
            $deptLabel = $emp->managedUnitPage->judul;
        } elseif ($emp->programStudi) {
            $deptLabel = 'Prodi ' . $emp->programStudi->nama;
        } elseif ($emp->jurusan) {
            $deptLabel = 'Jurusan ' . $emp->jurusan->nama;
        } else {
            $deptLabel = $emp->role_label;
        }
    @endphp

    <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-6 print:border-none print:shadow-none print:p-0">
        <!-- Header info -->
        <div class="border-b border-gray-100 dark:border-gray-800 pb-5">
            <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-wider">{{ strtoupper($deptLabel) }}</h3>
            <p class="text-sm font-bold text-brand-600 dark:text-brand-400">{{ $emp->name }} — NIP/ID: {{ $emp->identity_number ?: '-' }}</p>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-600 dark:text-gray-300">
                <div>
                    <span class="font-bold text-gray-400 uppercase tracking-wider text-xs block">Hari & Tanggal WFH</span>
                    <span class="text-base font-bold text-gray-800 dark:text-gray-100">{{ $wfhReport->tanggal->translatedFormat('l, d F Y') }}</span>
                </div>
                <div>
                    <span class="font-bold text-gray-400 uppercase tracking-wider text-xs block">Catatan Wadir I / Masukan</span>
                    <span class="text-base font-bold text-brand-700 dark:text-brand-350">{{ $wfhReport->catatan_wadir ?: 'Belum ada catatan' }}</span>
                </div>
            </div>
        </div>

        <!-- Activities table -->
        <div class="overflow-x-auto rounded-2xl border border-gray-150 dark:border-gray-800">
            <table class="min-w-full divide-y divide-gray-250 text-sm dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-950">
                    <tr class="text-left text-xs font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-3 border-r border-gray-200 dark:border-gray-850 w-12 text-center">No.</th>
                        <th class="px-4 py-3 border-r border-gray-200 dark:border-gray-850 w-32">Waktu</th>
                        <th class="px-4 py-3 border-r border-gray-200 dark:border-gray-850">Kegiatan / Agenda</th>
                        <th class="px-4 py-3 border-r border-gray-200 dark:border-gray-850">Hasil Kegiatan</th>
                        <th class="px-4 py-3 border-r border-gray-200 dark:border-gray-850">Kendala/ Masalah</th>
                        <th class="px-4 py-3 border-r border-gray-200 dark:border-gray-850">RTL/ Rencana Tindak Lanjut</th>
                        <th class="px-4 py-3 border-r border-gray-200 dark:border-gray-850">Penanggung Jawab</th>
                        <th class="px-4 py-3 border-r border-gray-200 dark:border-gray-850">Target Waktu</th>
                        <th class="px-4 py-3">KET</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-150 dark:divide-gray-800 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300">
                    @forelse($wfhReport->activities as $idx => $act)
                        <tr class="align-top">
                            <td class="px-4 py-3 border-r border-gray-200 dark:border-gray-800 text-center font-bold text-gray-500">{{ $idx + 1 }}</td>
                            <td class="px-4 py-3 border-r border-gray-200 dark:border-gray-800 font-medium">{{ $act->waktu ?: '-' }}</td>
                            <td class="px-4 py-3 border-r border-gray-200 dark:border-gray-800 leading-relaxed">{{ $act->kegiatan }}</td>
                            <td class="px-4 py-3 border-r border-gray-200 dark:border-gray-800 leading-relaxed">{{ $act->hasil ?: '-' }}</td>
                            <td class="px-4 py-3 border-r border-gray-200 dark:border-gray-800 leading-relaxed text-red-650 dark:text-red-400">{{ $act->kendala ?: '-' }}</td>
                            <td class="px-4 py-3 border-r border-gray-200 dark:border-gray-800 leading-relaxed">{{ $act->rtl ?: '-' }}</td>
                            <td class="px-4 py-3 border-r border-gray-200 dark:border-gray-800">{{ $act->penanggung_jawab ?: '-' }}</td>
                            <td class="px-4 py-3 border-r border-gray-200 dark:border-gray-800">{{ $act->target_waktu ?: '-' }}</td>
                            <td class="px-4 py-3">
                                @if($act->keterangan)
                                    <span class="inline-block rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700 dark:bg-blue-900/20 dark:text-blue-300">{{ $act->keterangan }}</span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-400">Belum ada item detail kegiatan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
