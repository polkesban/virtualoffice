@extends('admin.layouts.admin')
@section('title', 'Laporan Kegiatan WFH')
@section('page-title', 'Laporan Kegiatan WFH')
@section('page-subtitle', 'Pengisian formulir harian dan rekapitulasi kegiatan WFH pegawai')

@section('content')
<div class="mx-auto max-w-7xl p-6">
    <!-- Success / Error Alerts -->
    @if(session('success'))
        <div class="mb-6 rounded-2xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 rounded-2xl bg-red-50 border border-red-200 p-4 text-sm text-red-700 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <!-- Tabs Header -->
    <div class="border-b border-gray-200 dark:border-gray-800 mb-6">
        <nav class="flex space-x-8" aria-label="Tabs">
            <button onclick="switchTab('recap-tab', 'my-reports-tab')" id="recap-tab-btn"
                class="border-brand-600 text-brand-600 dark:border-brand-400 dark:text-brand-400 border-b-2 py-4 px-1 text-sm font-bold tracking-wide">
                📊 Rekapitulasi Kegiatan WFH
            </button>
            <button onclick="switchTab('my-reports-tab', 'recap-tab')" id="my-reports-tab-btn"
                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 py-4 px-1 text-sm font-bold tracking-wide border-b-2">
                ✍️ Laporan Saya
            </button>
        </nav>
    </div>

    <!-- TAB 1: REKAPITULASI (GENERAL RECAP TABLE WITH FILTERS) -->
    <div id="recap-tab" class="space-y-6">
        <!-- Filters Form -->
        <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-4">Filter Rekapitulasi</h3>
            <form method="GET" action="{{ route('admin.wfh.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <!-- Tanggal -->
                <label class="space-y-1 text-xs font-bold text-gray-700 dark:text-gray-200">
                    <span>Tanggal</span>
                    <input type="date" name="tanggal" value="{{ $filters['tanggal'] ?? '' }}"
                        class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-medium text-gray-950 dark:text-white dark:bg-gray-950 dark:border-gray-750">
                </label>

                <!-- Bulan -->
                <label class="space-y-1 text-xs font-bold text-gray-700 dark:text-gray-200">
                    <span>Bulan</span>
                    <select name="bulan" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-medium text-gray-950 dark:text-white dark:bg-gray-950 dark:border-gray-750">
                        <option value="">Semua Bulan</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected(($filters['bulan'] ?? '') == $m)>
                                {{ Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </label>

                <!-- Tahun -->
                <label class="space-y-1 text-xs font-bold text-gray-700 dark:text-gray-200">
                    <span>Tahun</span>
                    <select name="tahun" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-medium text-gray-950 dark:text-white dark:bg-gray-950 dark:border-gray-750">
                        <option value="">Semua Tahun</option>
                        @for($y = now()->year + 1; $y >= 2024; $y--)
                            <option value="{{ $y }}" @selected(($filters['tahun'] ?? '') == $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </label>

                <!-- Unit -->
                <label class="space-y-1 text-xs font-bold text-gray-700 dark:text-gray-200">
                    <span>Unit/Bagian/Bidang</span>
                    <select name="unit_page_id" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-medium text-gray-950 dark:text-white dark:bg-gray-950 dark:border-gray-750">
                        <option value="">Semua Unit</option>
                        @foreach($unitPages as $unit)
                            <option value="{{ $unit->id }}" @selected(($filters['unit_page_id'] ?? '') == $unit->id)>{{ $unit->judul }}</option>
                        @endforeach
                    </select>
                </label>

                <!-- Prodi -->
                <label class="space-y-1 text-xs font-bold text-gray-700 dark:text-gray-200">
                    <span>Program Studi</span>
                    <select name="program_studi_id" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-medium text-gray-950 dark:text-white dark:bg-gray-950 dark:border-gray-750">
                        <option value="">Semua Prodi</option>
                        @foreach($programStudis as $prodi)
                            <option value="{{ $prodi->id }}" @selected(($filters['program_studi_id'] ?? '') == $prodi->id)>{{ $prodi->nama }}</option>
                        @endforeach
                    </select>
                </label>

                <!-- Jurusan -->
                <label class="space-y-1 text-xs font-bold text-gray-700 dark:text-gray-200">
                    <span>Jurusan</span>
                    <select name="jurusan_id" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-medium text-gray-950 dark:text-white dark:bg-gray-950 dark:border-gray-750">
                        <option value="">Semua Jurusan</option>
                        @foreach($jurusans as $jur)
                            <option value="{{ $jur->id }}" @selected(($filters['jurusan_id'] ?? '') == $jur->id)>{{ $jur->nama }}</option>
                        @endforeach
                    </select>
                </label>

                <!-- Buttons -->
                <div class="flex items-end gap-2 xl:col-span-6 mt-2">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                        🔍 Terapkan Filter
                    </button>
                    <a href="{{ route('admin.wfh.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-150 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Recap Tables List -->
        @if($reports->isEmpty())
            <div class="rounded-3xl border border-dashed border-gray-200 bg-white px-6 py-16 text-center dark:border-gray-700 dark:bg-gray-900">
                <p class="text-4xl">📭</p>
                <h2 class="mt-4 text-lg font-black text-gray-900 dark:text-white">Tidak ada laporan kegiatan WFH</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Silakan buat laporan baru atau sesuaikan filter Anda.</p>
            </div>
        @else
            @php
                $groupedReports = $reports->groupBy('user_id');
            @endphp
            @foreach($groupedReports as $userId => $userReports)
                @php
                    $firstReport = $userReports->first();
                    $emp = $firstReport->user;
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
                <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-6">
                    <!-- Creator Card Header -->
                    <div class="border-b border-gray-100 dark:border-gray-800 pb-3">
                        <h4 class="text-sm font-bold text-gray-400 dark:text-gray-500 tracking-wider uppercase">{{ $deptLabel }}</h4>
                        <h3 class="text-lg font-black text-gray-950 dark:text-white mt-1">{{ $emp->name }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">NIP/ID: <span class="font-bold text-gray-700 dark:text-gray-300">{{ $emp->identity_number ?: '-' }}</span></p>
                    </div>

                    <!-- Creator's Reports -->
                    <div class="space-y-6">
                        @php
                            $userReportsByDate = $userReports->groupBy(fn($r) => $r->tanggal->format('Y-m-d'));
                        @endphp
                        @foreach($userReportsByDate as $dateString => $dateReports)
                            @php
                                $firstDateReport = $dateReports->first();
                                $allActivities = $dateReports->flatMap->activities;
                                $allNotes = $dateReports->pluck('catatan_wadir')->filter()->unique()->implode('; ');
                            @endphp
                            <div class="rounded-2xl border border-gray-250/60 bg-gray-50/20 p-4 dark:border-gray-800 dark:bg-gray-950/20 space-y-3">
                                <div class="flex flex-wrap items-center justify-between border-b border-gray-150 dark:border-gray-805 pb-2 gap-3">
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Hari/Tanggal WFH: <span class="font-bold text-brand-600 dark:text-brand-400 text-sm">{{ $firstDateReport->tanggal->translatedFormat('l, d F Y') }}</span></p>
                                    </div>
                                    @if(Auth::user()->canAccessWfhRecaps())
                                        <div class="flex flex-wrap items-center gap-2">
                                            @foreach($dateReports as $idxReport => $report)
                                                <button onclick="toggleCatatanForm({{ $report->id }})" class="rounded-xl border border-gray-250/60 bg-white px-2.5 py-1 text-[10px] font-bold text-gray-700 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                                    📝 Catatan Laporan #{{ $idxReport + 1 }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <!-- Inline Notes Form for Wadir -->
                                @if(Auth::user()->canAccessWfhRecaps())
                                    @foreach($dateReports as $idxReport => $report)
                                        <div id="catatan-form-{{ $report->id }}" class="hidden p-4 rounded-2xl bg-white border border-gray-200 dark:bg-gray-900 dark:border-gray-800 my-2">
                                            <form method="POST" action="{{ route('admin.wfh.update-notes', $report) }}">
                                                @csrf
                                                <label class="block space-y-2">
                                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Catatan Wadir I / Admin (Laporan #{{ $idxReport + 1 }}):</span>
                                                    <textarea name="catatan_wadir" rows="2" placeholder="Masukkan catatan evaluasi atau instruksi tindak lanjut..."
                                                        class="w-full rounded-xl border border-gray-250 bg-white p-3 text-sm outline-none dark:border-gray-750 dark:bg-gray-900">{{ $report->catatan_wadir }}</textarea>
                                                </label>
                                                <div class="mt-2 flex justify-end gap-2">
                                                    <button type="button" onclick="toggleCatatanForm({{ $report->id }})" class="px-3 py-1.5 rounded-lg border text-xs font-bold text-gray-500">Batal</button>
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold">Simpan Catatan</button>
                                                </div>
                                            </form>
                                        </div>
                                    @endforeach
                                @endif

                                <!-- Detailed activities table -->
                                <div class="overflow-x-auto rounded-2xl border border-gray-150 dark:border-gray-800 bg-white dark:bg-gray-900">
                                    <table data-table-tools="off" class="min-w-full divide-y divide-gray-250 text-sm dark:divide-gray-850">
                                        <thead class="bg-gray-50 dark:bg-gray-950">
                                            <tr class="text-left text-xs font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                                <th class="px-4 py-3 border-r border-gray-200 dark:border-gray-800 w-12 text-center">No.</th>
                                                <th class="px-4 py-3 border-r border-gray-200 dark:border-gray-800 w-32">Waktu</th>
                                                <th class="px-4 py-3 border-r border-gray-200 dark:border-gray-800">Kegiatan / Agenda</th>
                                                <th class="px-4 py-3 border-r border-gray-200 dark:border-gray-800">Hasil Kegiatan</th>
                                                <th class="px-4 py-3 border-r border-gray-200 dark:border-gray-800">Kendala/ Masalah</th>
                                                <th class="px-4 py-3 border-r border-gray-200 dark:border-gray-800">RTL/ Rencana Tindak Lanjut</th>
                                                <th class="px-4 py-3 border-r border-gray-200 dark:border-gray-800">Penanggung Jawab</th>
                                                <th class="px-4 py-3 border-r border-gray-200 dark:border-gray-800">Target Waktu</th>
                                                <th class="px-4 py-3 border-r border-gray-200 dark:border-gray-800">KET</th>
                                                <th class="px-4 py-3">Catatan Wadir I</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-150 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                                            @forelse($allActivities as $idx => $act)
                                                <tr class="align-top">
                                                    <td class="px-4 py-3 border-r border-gray-200 dark:border-gray-800 text-center font-bold text-gray-500">{{ $idx + 1 }}</td>
                                                    <td class="px-4 py-3 border-r border-gray-200 dark:border-gray-800 font-medium">{{ $act->waktu ?: '-' }}</td>
                                                    <td class="px-4 py-3 border-r border-gray-200 dark:border-gray-800 leading-relaxed">{{ $act->kegiatan }}</td>
                                                    <td class="px-4 py-3 border-r border-gray-200 dark:border-gray-800 leading-relaxed">{{ $act->hasil ?: '-' }}</td>
                                                    <td class="px-4 py-3 border-r border-gray-200 dark:border-gray-800 leading-relaxed text-red-650 dark:text-red-400">{{ $act->kendala ?: '-' }}</td>
                                                    <td class="px-4 py-3 border-r border-gray-200 dark:border-gray-800 leading-relaxed">{{ $act->rtl ?: '-' }}</td>
                                                    <td class="px-4 py-3 border-r border-gray-200 dark:border-gray-800">{{ $act->penanggung_jawab ?: '-' }}</td>
                                                    <td class="px-4 py-3 border-r border-gray-200 dark:border-gray-800">{{ $act->target_waktu ?: '-' }}</td>
                                                    <td class="px-4 py-3 border-r border-gray-200 dark:border-gray-800">
                                                        @if($act->keterangan)
                                                            <span class="inline-block rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700 dark:bg-blue-900/20 dark:text-blue-300">{{ $act->keterangan }}</span>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    @if($idx === 0)
                                                        <td class="px-4 py-3 font-semibold text-brand-700 dark:text-brand-350" rowspan="{{ $allActivities->count() }}">
                                                            {!! nl2br(e($allNotes ?: 'Belum ada catatan')) !!}
                                                        </td>
                                                    @endif
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="10" class="px-4 py-8 text-center text-gray-400">Belum ada item detail kegiatan.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- TAB 2: LAPORAN SAYA (OWN REPORTS LIST) -->
    <div id="my-reports-tab" class="hidden space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-black text-gray-900 dark:text-white">Daftar Laporan Harian WFH Anda</h2>
            <a href="{{ route('admin.wfh.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                ➕ Isi Laporan Baru
            </a>
        </div>

        @if($myReports->isEmpty())
            <div class="rounded-3xl border border-dashed border-gray-200 bg-white px-6 py-16 text-center dark:border-gray-700 dark:bg-gray-900">
                <p class="text-4xl">📝</p>
                <h2 class="mt-4 text-lg font-black text-gray-900 dark:text-white">Anda belum pernah mengisi laporan WFH</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Mulai laporkan kegiatan WFH harian Anda dengan menekan tombol Isi Laporan Baru.</p>
            </div>
        @else
            <div class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-950">
                        <tr class="text-left text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <th class="px-6 py-4">Tanggal WFH</th>
                            <th class="px-6 py-4">Jumlah Kegiatan</th>
                            <th class="px-6 py-4">Catatan Wadir I / Masukan</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($myReports as $report)
                            <tr>
                                <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">
                                    {{ $report->tanggal->translatedFormat('l, d F Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $report->activities->count() }} Kegiatan
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-brand-600 dark:text-brand-400">
                                    {{ $report->catatan_wadir ?: '-' }}
                                </td>
                                <td class="px-6 py-4 text-right flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.wfh.show', $report) }}" class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-200 dark:bg-gray-850 dark:text-gray-300 dark:hover:bg-gray-700">Detail</a>
                                    <a href="{{ route('admin.wfh.edit', $report) }}" class="rounded-lg bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-700 hover:bg-brand-100 dark:bg-brand-950/30 dark:text-brand-300 dark:hover:bg-brand-900/50">Edit</a>
                                    <form action="{{ route('admin.wfh.destroy', $report) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus laporan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-bold text-red-650 hover:bg-red-100 dark:bg-red-950/20 dark:text-red-400 dark:hover:bg-red-900/30">Hapus</button>
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

<script>
    function switchTab(showId, hideId) {
        document.getElementById(showId).classList.remove('hidden');
        document.getElementById(hideId).classList.add('hidden');

        document.getElementById(showId + '-btn').classList.add('border-brand-600', 'text-brand-600', 'dark:border-brand-400', 'dark:text-brand-400');
        document.getElementById(showId + '-btn').classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');

        document.getElementById(hideId + '-btn').classList.remove('border-brand-600', 'text-brand-600', 'dark:border-brand-400', 'dark:text-brand-400');
        document.getElementById(hideId + '-btn').classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
    }

    function toggleCatatanForm(reportId) {
        const form = document.getElementById('catatan-form-' + reportId);
        if (form.classList.contains('hidden')) {
            form.classList.remove('hidden');
        } else {
            form.classList.add('hidden');
        }
    }
</script>
@endsection
