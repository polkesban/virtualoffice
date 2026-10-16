@extends('pbm.layouts.app')

@section('fullwidth', true)

@section('content')
<div class="max-w-7xl mx-auto pb-16 px-4 sm:px-6">
    <!-- Top Nav & Breadcrumbs -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <a href="{{ route('pbm.quizzes.show', $quiz->id) }}" class="inline-flex items-center text-sm font-medium text-sky-600 hover:text-sky-700 transition">
            <i data-feather="arrow-left" class="w-4 h-4 mr-1"></i> Kembali ke Detail Kuis
        </a>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">
                <span class="w-2 h-2 rounded-full bg-purple-600 animate-pulse"></span>
                Monitoring CBT & Proctoring Real-time
            </span>
            <a href="{{ route('pbm.quizzes.edit', $quiz->id) }}" class="p-2 text-gray-500 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition" title="Pengaturan Kuis">
                <i data-feather="settings" class="w-4 h-4"></i>
            </a>
        </div>
    </div>

    <!-- Header Banner -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-purple-600 text-white">
                        Dashboard Pengawas
                    </span>
                    @if($quiz->enable_proctoring)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                        Proctoring Aktif
                    </span>
                    @endif
                    @if($quiz->require_webcam)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-700 flex items-center gap-1">
                        <i data-feather="video" class="w-3 h-3"></i> Webcam Wajib
                    </span>
                    @endif
                    @if($quiz->require_microphone)
                    @php
                        $sensLabel = $quiz->mic_sensitivity === 'high' ? 'Peka Bisikan' : ($quiz->mic_sensitivity === 'low' ? 'Toleran' : 'Standar');
                    @endphp
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-pink-100 text-pink-800 flex items-center gap-1">
                        <i data-feather="mic" class="w-3 h-3"></i> Mic Wajib ({{ $sensLabel }})
                    </span>
                    @endif
                    @if($quiz->enable_browser_lockdown)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 flex items-center gap-1">
                        <i data-feather="lock" class="w-3 h-3"></i> Lockdown Aktif
                    </span>
                    @endif
                    @if($quiz->require_seb)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 flex items-center gap-1">
                        <i data-feather="shield" class="w-3 h-3"></i> Safe Exam Browser
                    </span>
                    @endif
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900">{{ $quiz->title }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $quiz->clinicalSession ? $quiz->clinicalSession->title : 'Sesi Ujian' }} &bull; 
                    {{ $quiz->clinicalSession && $quiz->clinicalSession->clinicalClass ? $quiz->clinicalSession->clinicalClass->name : '' }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button onclick="window.location.reload()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                    <i data-feather="refresh-cw" class="w-3.5 h-3.5"></i> Segarkan Data
                </button>
                @if($quiz->require_seb)
                <a href="{{ route('pbm.quizzes.seb-config', $quiz->id) }}" class="px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                    <i data-feather="download" class="w-3.5 h-3.5"></i> File SEB
                </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-xl mb-6 text-emerald-800 text-sm font-medium flex items-center gap-2">
        <i data-feather="check-circle" class="w-5 h-5 text-emerald-600 shrink-0"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl mb-6 text-red-800 text-sm font-medium flex items-center gap-2">
        <i data-feather="alert-circle" class="w-5 h-5 text-red-600 shrink-0"></i>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <!-- Metrics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between text-gray-500 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Total Peserta</span>
                <i data-feather="users" class="w-4 h-4 text-purple-600"></i>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-gray-900">{{ $totalAttempts }}</div>
            <span class="text-[11px] text-gray-400">Total sesi pengerjaan</span>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between text-gray-500 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Sedang Ujian</span>
                <i data-feather="activity" class="w-4 h-4 text-sky-600"></i>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-sky-600">{{ $activeAttempts }}</div>
            <span class="text-[11px] text-gray-400">Aktif mengerjakan</span>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between text-gray-500 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Selesai</span>
                <i data-feather="check-circle" class="w-4 h-4 text-emerald-600"></i>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-emerald-600">{{ $finishedAttempts }}</div>
            <span class="text-[11px] text-gray-400">Telah mengumpulkan</span>
        </div>

        <div class="bg-white rounded-2xl p-5 border {{ $lockedAttempts > 0 ? 'border-red-300 bg-red-50/30' : 'border-gray-200' }} shadow-sm">
            <div class="flex items-center justify-between text-gray-500 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Terkunci</span>
                <i data-feather="lock" class="w-4 h-4 text-red-600"></i>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-red-600">{{ $lockedAttempts }}</div>
            <span class="text-[11px] text-red-500 font-medium">Perlu tindakan dosen</span>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm col-span-2 lg:col-span-1">
            <div class="flex items-center justify-between text-gray-500 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Pelanggaran</span>
                <i data-feather="alert-triangle" class="w-4 h-4 text-amber-500"></i>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-amber-600">{{ $totalViolations }}</div>
            <span class="text-[11px] text-gray-400">Total pelanggaran terdeteksi</span>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div x-data="{ activeTab: 'participants' }">
        <div class="border-b border-gray-200 mb-6 flex items-center gap-4 overflow-x-auto">
            <button @click="activeTab = 'participants'" 
                    :class="activeTab === 'participants' ? 'border-purple-600 text-purple-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-4 border-b-2 text-sm flex items-center gap-2 whitespace-nowrap transition">
                <i data-feather="users" class="w-4 h-4"></i> Daftar Peserta ({{ $totalAttempts }})
            </button>
            <button @click="activeTab = 'gallery'" 
                    :class="activeTab === 'gallery' ? 'border-purple-600 text-purple-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-4 border-b-2 text-sm flex items-center gap-2 whitespace-nowrap transition">
                <i data-feather="image" class="w-4 h-4"></i> Galeri Snapshot Kamera
            </button>
            <button @click="activeTab = 'violations'" 
                    :class="activeTab === 'violations' ? 'border-purple-600 text-purple-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-4 border-b-2 text-sm flex items-center gap-2 whitespace-nowrap transition">
                <i data-feather="shield-alert" class="w-4 h-4"></i> Log Pelanggaran Real-time
            </button>
        </div>

        <!-- Tab 1: Participants Table -->
        <div x-show="activeTab === 'participants'" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-gray-500 font-semibold text-xs uppercase tracking-wider">
                            <th class="py-3.5 px-4">Peserta</th>
                            <th class="py-3.5 px-4">Waktu Mulai / Selesai</th>
                            <th class="py-3.5 px-4 text-center">Status Pengerjaan</th>
                            <th class="py-3.5 px-4 text-center">Nilai</th>
                            <th class="py-3.5 px-4 text-center">Pelanggaran</th>
                            <th class="py-3.5 px-4 text-center">Snapshot</th>
                            <th class="py-3.5 px-4 text-right">Aksi Pengawas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($attempts as $attempt)
                        @php
                            $studentName = $attempt->mahasiswa ? $attempt->mahasiswa->nama : ($attempt->user ? $attempt->user->name : 'Anonim');
                            $studentNim = $attempt->mahasiswa ? $attempt->mahasiswa->nim : ($attempt->user ? $attempt->user->username : '-');
                            $snapshotCount = $attempt->proctoringLogs->whereNotNull('snapshot_path')->count();
                        @endphp
                        <tr class="hover:bg-gray-50/80 transition {{ $attempt->is_locked ? 'bg-red-50/40' : '' }}">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-gray-900">{{ $studentName }}</div>
                                <div class="text-xs text-gray-500 font-mono">{{ $studentNim }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-gray-600">
                                <div><span class="text-gray-400">Mulai:</span> {{ $attempt->started_at ? $attempt->started_at->format('H:i:s') : '-' }}</div>
                                @if($attempt->finished_at)
                                <div><span class="text-gray-400">Selesai:</span> {{ $attempt->finished_at->format('H:i:s') }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($attempt->is_locked)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">
                                        <i data-feather="lock" class="w-3 h-3"></i> Terkunci
                                    </span>
                                @elseif($attempt->finished_at)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                        <i data-feather="check" class="w-3 h-3"></i> Selesai
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-100 text-sky-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-600 animate-ping"></span> Mengerjakan
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-gray-900">
                                {{ $attempt->score !== null ? number_format($attempt->score, 1) : '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($attempt->violation_count > 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-extrabold {{ $attempt->violation_count >= ($quiz->max_violations ?? 3) ? 'bg-red-600 text-white' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $attempt->violation_count }}x
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">0</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($snapshotCount > 0)
                                    <button type="button" 
                                            onclick="openStudentSnapshots({{ $attempt->id }}, '{{ addslashes($studentName) }}')"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-purple-600 hover:text-purple-800 bg-purple-50 hover:bg-purple-100 px-2.5 py-1 rounded-lg transition">
                                        <i data-feather="camera" class="w-3 h-3"></i> {{ $snapshotCount }} Foto
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if($attempt->is_locked)
                                    <form action="{{ route('pbm.quizzes.attempts.unlock', [$quiz->id, $attempt->id]) }}" method="POST" onsubmit="return confirm('Buka kembali kuncian ujian untuk {{ $studentName }}?')">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-sm">
                                            <i data-feather="unlock" class="w-3 h-3"></i> Buka Kunci
                                        </button>
                                    </form>
                                    @else
                                    <button type="button" 
                                            onclick="openDisqualifyModal({{ $attempt->id }}, '{{ addslashes($studentName) }}')"
                                            class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Diskualifikasi">
                                        <i data-feather="user-x" class="w-4 h-4"></i>
                                    </button>
                                    @endif

                                    @if($attempt->finished_at)
                                    <a href="{{ route('pbm.quizzes.attempt.result', [$quiz->id, $attempt->id]) }}" class="p-1.5 text-gray-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition" title="Lihat Pembahasan">
                                        <i data-feather="eye" class="w-4 h-4"></i>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-12 text-gray-400 text-sm">
                                <i data-feather="inbox" class="w-10 h-10 mx-auto mb-2 text-gray-300"></i>
                                Belum ada mahasiswa yang memulai ujian ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 2: Snapshot Gallery -->
        <div x-show="activeTab === 'gallery'" class="space-y-6">
            @php
                $allSnapshots = $attempts->flatMap(function($attempt) {
                    return $attempt->proctoringLogs->whereNotNull('snapshot_path')->map(function($log) use ($attempt) {
                        $log->student_name = $attempt->mahasiswa ? $attempt->mahasiswa->nama : ($attempt->user ? $attempt->user->name : 'Mahasiswa');
                        $log->student_nim = $attempt->mahasiswa ? $attempt->mahasiswa->nim : ($attempt->user ? $attempt->user->username : '-');
                        return $log;
                    });
                })->sortByDesc('created_at');
            @endphp

            @if($allSnapshots->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($allSnapshots as $snap)
                @php
                    $isViolation = !in_array($snap->event_type, ['periodic_snapshot', 'exam_started', 'camera_ready'], true);
                @endphp
                <div class="group bg-white rounded-xl border {{ $isViolation ? 'border-red-300 ring-2 ring-red-200' : 'border-gray-200' }} overflow-hidden shadow-xs hover:shadow-md transition">
                    <div class="relative aspect-4/3 bg-gray-900 cursor-pointer overflow-hidden" onclick="previewImage('{{ $snap->snapshot_url }}', '{{ addslashes($snap->student_name) }}', '{{ $snap->event_type }}', '{{ $snap->created_at->format('d M Y, H:i:s') }}')">
                        <img src="{{ $snap->snapshot_url }}" alt="Snapshot" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        <div class="absolute top-2 left-2">
                            @if($isViolation)
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-600 text-white shadow-xs">
                                Pelanggaran
                            </span>
                            @else
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-gray-900/70 text-white backdrop-blur-xs">
                                Berkala
                            </span>
                            @endif
                        </div>
                        <div class="absolute bottom-1 right-1 text-[9px] text-white/90 bg-black/60 px-1 py-0.5 rounded font-mono">
                            {{ $snap->created_at->format('H:i:s') }}
                        </div>
                    </div>
                    <div class="p-2 text-xs">
                        <div class="font-bold text-gray-900 truncate" title="{{ $snap->student_name }}">{{ $snap->student_name }}</div>
                        <div class="text-[11px] text-gray-500 truncate capitalize">{{ str_replace('_', ' ', $snap->event_type) }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center text-gray-400">
                <i data-feather="camera-off" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                <h3 class="text-base font-medium text-gray-800">Belum ada foto snapshot tersimpan</h3>
                <p class="text-xs text-gray-500 mt-1">Snapshot akan otomatis muncul di sini saat peserta mengaktifkan webcam.</p>
            </div>
            @endif
        </div>

        <!-- Tab 3: Complete Violations Log -->
        <div x-show="activeTab === 'violations'" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            @php
                $allLogs = $attempts->flatMap(function($attempt) {
                    return $attempt->proctoringLogs->map(function($log) use ($attempt) {
                        $log->student_name = $attempt->mahasiswa ? $attempt->mahasiswa->nama : ($attempt->user ? $attempt->user->name : 'Mahasiswa');
                        $log->student_nim = $attempt->mahasiswa ? $attempt->mahasiswa->nim : ($attempt->user ? $attempt->user->username : '-');
                        return $log;
                    });
                })->sortByDesc('created_at');
            @endphp

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-gray-500 font-semibold text-xs uppercase tracking-wider">
                            <th class="py-3 px-4">Waktu</th>
                            <th class="py-3 px-4">Peserta</th>
                            <th class="py-3 px-4">Jenis Peristiwa</th>
                            <th class="py-3 px-4">Keterangan / Detail</th>
                            <th class="py-3 px-4 text-center">Bukti Foto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($allLogs as $log)
                        @php
                            $isViolation = !in_array($log->event_type, ['periodic_snapshot', 'exam_started', 'camera_ready', 'unlocked_by_lecturer'], true);
                        @endphp
                        <tr class="hover:bg-gray-50 transition {{ $isViolation ? 'bg-amber-50/30' : '' }}">
                            <td class="py-3 px-4 text-xs font-mono text-gray-500 whitespace-nowrap">
                                {{ $log->created_at->format('d/m H:i:s') }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-bold text-gray-900 block">{{ $log->student_name }}</span>
                                <span class="text-xs text-gray-400 font-mono">{{ $log->student_nim }}</span>
                            </td>
                            <td class="py-3 px-4">
                                @if($log->event_type === 'voice_detected')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-purple-100 text-purple-800">
                                        <i data-feather="mic" class="w-3 h-3 text-purple-600"></i> Suara Percakapan
                                    </span>
                                @elseif($isViolation)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800">
                                        <i data-feather="alert-circle" class="w-3 h-3"></i> {{ ucfirst(str_replace('_', ' ', $log->event_type)) }}
                                    </span>
                                @elseif($log->event_type === 'unlocked_by_lecturer')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800">
                                        <i data-feather="unlock" class="w-3 h-3"></i> Dibuka Dosen
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                                        {{ ucfirst(str_replace('_', ' ', $log->event_type)) }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-xs text-gray-600">
                                @if($log->details)
                                    <span class="font-mono bg-gray-50 px-2 py-1 rounded border border-gray-100 inline-block">
                                        {{ is_array($log->details) ? json_encode($log->details, JSON_UNESCAPED_UNICODE) : $log->details }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($log->snapshot_path)
                                <button type="button" 
                                        onclick="previewImage('{{ $log->snapshot_url }}', '{{ addslashes($log->student_name) }}', '{{ $log->event_type }}', '{{ $log->created_at->format('d M Y, H:i:s') }}')"
                                        class="p-1 rounded text-purple-600 hover:bg-purple-100 transition inline-flex items-center gap-1 text-xs font-semibold">
                                    <i data-feather="image" class="w-4 h-4"></i> Lihat Foto
                                </button>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-12 text-gray-400 text-sm">
                                Belum ada log aktivitas yang tercatat.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Foto Preview Detail -->
<div id="imageModal" class="fixed inset-0 z-50 bg-gray-900/80 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="fixed inset-0" onclick="closeImageModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl max-w-xl w-full overflow-hidden z-10">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
            <div>
                <h3 id="modalImageStudent" class="font-bold text-gray-900 text-sm"></h3>
                <span id="modalImageMeta" class="text-xs text-gray-500"></span>
            </div>
            <button type="button" onclick="closeImageModal()" class="p-1 text-gray-400 hover:text-gray-700 rounded-lg transition">
                <i data-feather="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="p-4 bg-black flex items-center justify-center min-h-[300px]">
            <img id="modalImageSrc" src="" alt="Bukti Foto" class="max-h-[65vh] w-auto object-contain rounded-lg">
        </div>
        <div class="p-3 bg-gray-50 border-t border-gray-100 flex justify-end">
            <button type="button" onclick="closeImageModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-300 transition">
                Tutup
            </button>
        </div>
    </div>
</div>

<!-- Modal: Diskualifikasi Peserta -->
<div id="disqualifyModal" class="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="fixed inset-0" onclick="closeDisqualifyModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden z-10">
        <form id="disqualifyForm" method="POST">
            @csrf
            <div class="p-5 border-b border-gray-100">
                <h3 class="font-bold text-gray-900 text-lg flex items-center gap-2 text-red-600">
                    <i data-feather="alert-triangle" class="w-5 h-5"></i> Diskualifikasi Peserta Ujian
                </h3>
                <p class="text-xs text-gray-500 mt-1">Tindakan ini akan mengunci ujian peserta secara permanen dan memberikan nilai 0.</p>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nama Mahasiswa:</label>
                    <div id="disqualifyStudentName" class="font-bold text-gray-900 text-base"></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Diskualifikasi <span class="text-red-500">*</span></label>
                    <textarea name="reason" rows="3" required placeholder="Contoh: Terbukti berulang kali membuka tab pencarian dan meninggalkan ruang ujian" class="w-full rounded-xl border-gray-300 text-sm focus:border-red-500 focus:ring-red-200"></textarea>
                </div>
            </div>
            <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2">
                <button type="button" onclick="closeDisqualifyModal()" class="px-4 py-2 text-gray-600 text-xs font-semibold hover:bg-gray-200 rounded-xl transition">Batal</button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-md transition">Diskualifikasi Sekarang</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function previewImage(src, name, type, time) {
        document.getElementById('modalImageSrc').src = src;
        document.getElementById('modalImageStudent').textContent = name;
        document.getElementById('modalImageMeta').textContent = `${type.replace('_', ' ')} • ${time}`;
        document.getElementById('imageModal').classList.remove('hidden');
    }

    function closeImageModal() {
        document.getElementById('imageModal').classList.add('hidden');
    }

    function openDisqualifyModal(attemptId, studentName) {
        document.getElementById('disqualifyStudentName').textContent = studentName;
        document.getElementById('disqualifyForm').action = `{{ url('pbm/quizzes/' . $quiz->id . '/attempts') }}/${attemptId}/disqualify`;
        document.getElementById('disqualifyModal').classList.remove('hidden');
    }

    function closeDisqualifyModal() {
        document.getElementById('disqualifyModal').classList.add('hidden');
    }

    function openStudentSnapshots(attemptId, studentName) {
        // Switch to gallery tab and filter if needed
        alert('Fitur filter foto untuk ' + studentName + ' dapat dilihat pada tab Galeri.');
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>
@endpush
