@extends('pbm.layouts.app')

@section('content')
<!-- Flash Messages -->
@if(session('success'))
<div class="bg-green-50 border-l-4 border-green-500 p-3 rounded-r-xl mb-4 text-green-800 text-sm font-medium">
    <i data-feather="check-circle" class="w-4 h-4 inline mr-1"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="bg-red-50 border-l-4 border-red-500 p-3 rounded-r-xl mb-4 text-red-800 text-sm font-medium">
    <i data-feather="alert-circle" class="w-4 h-4 inline mr-1"></i> {{ session('error') }}
</div>
@endif

<a href="{{ url()->previous() }}" class="inline-flex items-center text-sm font-medium text-sky-600 hover:text-sky-700 mb-6 transition">
    <i data-feather="arrow-left" class="w-4 h-4 mr-1"></i> Kembali ke Sesi
</a>

<!-- Quiz Banner -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-8">
    <div class="bg-gradient-to-r from-purple-600 to-indigo-700 p-8 text-white">
        <div class="flex items-center gap-2 mb-3 flex-wrap">
            <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider backdrop-blur-sm">
                Kuis CBT
            </span>
            @if($quiz->enable_proctoring)
                <span class="bg-emerald-500/80 px-3 py-1 rounded-full text-xs font-semibold backdrop-blur-sm flex items-center gap-1">
                    <i data-feather="shield" class="w-3 h-3"></i> Proctoring Aktif
                </span>
            @endif
            @if($quiz->require_webcam)
                <span class="bg-sky-500/80 px-3 py-1 rounded-full text-xs font-semibold backdrop-blur-sm flex items-center gap-1">
                    <i data-feather="video" class="w-3 h-3"></i> Webcam Wajib
                </span>
            @endif
            @if($quiz->require_microphone)
                @php
                    $sensLabel = $quiz->mic_sensitivity === 'high' ? 'Peka Bisikan' : ($quiz->mic_sensitivity === 'low' ? 'Toleran' : 'Standar');
                @endphp
                <span class="bg-pink-500/80 px-3 py-1 rounded-full text-xs font-semibold backdrop-blur-sm flex items-center gap-1">
                    <i data-feather="mic" class="w-3 h-3"></i> Mic Wajib ({{ $sensLabel }})
                </span>
            @endif
            @if($quiz->enable_browser_lockdown)
                <span class="bg-amber-500/80 px-3 py-1 rounded-full text-xs font-semibold backdrop-blur-sm flex items-center gap-1">
                    <i data-feather="lock" class="w-3 h-3"></i> Lockdown Aktif
                </span>
            @endif
            @if($quiz->require_seb)
                <span class="bg-rose-500/80 px-3 py-1 rounded-full text-xs font-semibold backdrop-blur-sm flex items-center gap-1">
                    <i data-feather="monitor" class="w-3 h-3"></i> Safe Exam Browser
                </span>
            @endif
        </div>
        <h1 class="text-3xl font-extrabold mb-2">{{ $quiz->title }}</h1>
        <p class="text-purple-100 mb-6 flex items-center gap-2">
            <i data-feather="book-open" class="w-4 h-4"></i> {{ $quiz->description ?? 'Tidak ada deskripsi' }}
        </p>
    </div>
    
    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6 bg-gray-50/50">
        <div class="flex flex-col">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Waktu Pelaksanaan</span>
            <div class="font-medium text-gray-900 flex items-center gap-2">
                <i data-feather="calendar" class="w-4 h-4 text-gray-400"></i>
                @if($quiz->start_time && $quiz->end_time)
                    {{ $quiz->start_time->format('d M Y, H:i') }} - {{ $quiz->end_time->format('H:i') }}
                @else
                    Bebas Akses
                @endif
            </div>
        </div>
        <div class="flex flex-col">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Durasi Kuis</span>
            <div class="font-medium text-gray-900 flex items-center gap-2">
                <i data-feather="clock" class="w-4 h-4 text-gray-400"></i>
                {{ $quiz->duration_minutes ? $quiz->duration_minutes . ' Menit' : 'Tidak Dibatasi' }}
            </div>
        </div>
        <div class="flex flex-col">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Status Anda</span>
            <div class="font-medium text-gray-900 flex items-center gap-2">
                <i data-feather="user" class="w-4 h-4 text-gray-400"></i>
                @if($myAttempt && $myAttempt->finished_at)
                    <span class="inline-flex items-center gap-1.5 text-emerald-600 font-semibold text-sm">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Selesai (Nilai: {{ number_format($myAttempt->score, 1) }})
                    </span>
                @elseif($myAttempt && !$myAttempt->finished_at)
                    <span class="inline-flex items-center gap-1.5 text-amber-600 font-semibold text-sm">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span> Sedang Dikerjakan
                    </span>
                @else
                    <span class="text-gray-600 text-sm">Belum Mengerjakan</span>
                @endif
            </div>
        </div>
    </div>
</div>

@if($quiz->require_seb)
<div class="bg-rose-50 border-2 border-rose-200 rounded-2xl p-5 mb-8 shadow-xs">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-start gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                <i data-feather="shield" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-900 text-sm sm:text-base">Kuis ini mewajibkan Safe Exam Browser (SEB)</h3>
                <p class="text-xs sm:text-sm text-gray-600 mt-0.5">
                    Demi integritas ujian, Anda harus membuka dan mengerjakan kuis ini melalui aplikasi Safe Exam Browser di laptop/komputer.
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0 w-full sm:w-auto">
            <a href="{{ str_replace(['https://', 'http://'], ['sebs://', 'seb://'], route('pbm.quizzes.seb-config', $quiz->id)) }}" 
               class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-md shadow-rose-600/20 transition flex items-center gap-1.5 justify-center flex-1 sm:flex-none">
                <i data-feather="external-link" class="w-4 h-4"></i> Buka di SEB
            </a>
            <a href="{{ route('pbm.quizzes.seb-config', $quiz->id) }}" 
               class="px-4 py-2 bg-white border border-rose-200 hover:bg-rose-50 text-rose-700 rounded-xl text-xs font-bold shadow-xs transition flex items-center gap-1.5 justify-center flex-1 sm:flex-none">
                <i data-feather="download" class="w-4 h-4"></i> Unduh File .seb
            </a>
        </div>
    </div>
</div>
@endif

<div class="flex flex-wrap items-center justify-center gap-4 my-8">
    @if($myAttempt && $myAttempt->is_locked)
        <div class="w-full max-w-md bg-red-50 border-2 border-red-200 rounded-2xl p-5 text-center shadow-xs">
            <div class="w-12 h-12 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-3">
                <i data-feather="lock" class="w-6 h-6"></i>
            </div>
            <h3 class="font-bold text-red-900 text-base mb-1">Sesi Ujian Anda Terkunci</h3>
            <p class="text-xs text-red-700 mb-4">{{ $myAttempt->lock_reason ?? 'Terdeteksi pelanggaran melebihi batas toleransi pengawasan.' }}</p>
            <a href="{{ route('pbm.quizzes.attempt.take', [$quiz->id, $myAttempt->id]) }}" 
               class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold text-xs shadow-md transition inline-flex items-center gap-1.5">
                <i data-feather="alert-circle" class="w-4 h-4"></i> Lihat Detail Status Kuncian
            </a>
        </div>
    @elseif($myAttempt && !$myAttempt->finished_at)
        <a href="{{ route('pbm.quizzes.attempt.take', [$quiz->id, $myAttempt->id]) }}" 
           class="px-8 py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-xl font-bold shadow-lg shadow-amber-500/30 hover:from-amber-600 hover:to-amber-700 hover:-translate-y-0.5 transition-all flex items-center gap-2">
            <i data-feather="play" class="w-5 h-5"></i> Lanjutkan Mengerjakan Kuis
        </a>
    @elseif($myAttempt && $myAttempt->finished_at)
        <a href="{{ route('pbm.quizzes.attempt.result', [$quiz->id, $myAttempt->id]) }}" 
           class="px-8 py-3.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-bold shadow-lg shadow-emerald-600/30 hover:from-emerald-700 hover:to-teal-700 hover:-translate-y-0.5 transition-all flex items-center gap-2">
            <i data-feather="check-circle" class="w-5 h-5"></i> Lihat Hasil & Pembahasan
        </a>
        @if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum'])))
            <form action="{{ route('pbm.quizzes.attempt.start', $quiz->id) }}" method="POST">
                @csrf
                <button type="submit" class="px-5 py-3.5 bg-white border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition-all flex items-center gap-2 shadow-sm">
                    <i data-feather="refresh-cw" class="w-4 h-4"></i> Uji Coba Lagi (Mode Dosen)
                </button>
            </form>
        @endif
    @else
        @if($quiz->questions->count() === 0)
            <button disabled class="px-8 py-3.5 bg-gray-200 text-gray-400 rounded-xl font-bold cursor-not-allowed flex items-center gap-2">
                <i data-feather="alert-circle" class="w-5 h-5"></i> Soal Belum Tersedia
            </button>
        @else
            <form action="{{ route('pbm.quizzes.attempt.start', $quiz->id) }}" method="POST">
                @csrf
                <button type="submit" class="px-8 py-3.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-purple-600/30 hover:from-purple-700 hover:to-indigo-700 hover:-translate-y-0.5 transition-all flex items-center gap-2 cursor-pointer">
                    <i data-feather="play-circle" class="w-5 h-5"></i> Mulai Kerjakan Kuis
                </button>
            </form>
        @endif
    @endif
</div>

@if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum'])))
<!-- Area Dosen -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-12">
    <div class="p-4 sm:p-5 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
            <i data-feather="list" class="w-5 h-5 text-gray-500"></i> Daftar Soal ({{ $quiz->questions->count() }})
        </h2>
        <div class="flex flex-wrap items-center gap-2 swipe-scroll">
            <a href="{{ route('pbm.quizzes.proctoring', $quiz->id) }}" 
               class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-semibold transition flex items-center gap-1.5 shadow-sm shrink-0">
                <i data-feather="shield" class="w-4 h-4"></i>
                <span>Pengawasan (Proctoring)</span>
                @php
                    $violationTotal = $quiz->attempts()->sum('violation_count');
                @endphp
                @if($violationTotal > 0)
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-red-500 text-white font-bold ml-0.5">
                    {{ $violationTotal }}
                </span>
                @endif
            </a>
            <a href="{{ route('pbm.quizzes.edit', $quiz->id) }}" 
               class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-sm font-medium transition flex items-center gap-1.5 shrink-0">
                <i data-feather="settings" class="w-4 h-4 text-gray-500"></i> Pengaturan Kuis
            </a>
            <a href="{{ route('pbm.quizzes.analysis', $quiz->id) }}" 
               class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-sm font-medium transition flex items-center gap-1.5 shrink-0">
                <i data-feather="bar-chart-2" class="w-4 h-4 text-gray-500"></i> Analisis Butir Soal
            </a>
            <a href="{{ route('pbm.quizzes.questions.ai.create', $quiz->id) }}" 
               class="px-3 py-1.5 text-white rounded-lg text-sm font-semibold transition flex items-center gap-1.5 shadow-sm hover:opacity-95 shrink-0"
               style="background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%) !important; color: #ffffff !important;">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="stroke: #ffffff !important;">
                    <rect x="4" y="4" width="16" height="16" rx="2" stroke-width="2"></rect>
                    <rect x="9" y="9" width="6" height="6" stroke-width="2"></rect>
                </svg>
                <span style="color: #ffffff !important;">Generate Soal AI</span>
            </a>
            <button type="button" onclick="document.getElementById('importModal').classList.remove('hidden')" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition flex items-center gap-2 shrink-0">
                <i data-feather="upload" class="w-4 h-4"></i> Import Soal
            </button>
            <a href="{{ route('pbm.quizzes.questions.create', $quiz->id) }}" class="px-3 py-1.5 pbm-primary text-white rounded-lg text-sm font-medium hover:opacity-90 transition flex items-center gap-2 shadow-sm shrink-0">
                <i data-feather="plus" class="w-4 h-4"></i> Tambah Soal
            </a>
        </div>
    </div>
    <div class="p-4 sm:p-6">
        @if($quiz->questions->count() > 0)
            <div class="space-y-4">
                @foreach($quiz->questions as $index => $question)
                <div class="border border-gray-200 rounded-xl p-4 bg-white relative group">
                    <div class="sm:absolute sm:top-4 sm:right-4 flex justify-end gap-2 mb-2 sm:mb-0">
                        <a href="{{ route('pbm.questions.edit', $question->id) }}" class="p-1.5 text-gray-500 hover:text-sky-600 bg-gray-100 hover:bg-sky-50 rounded transition" title="Edit Soal">
                            <i data-feather="edit-2" class="w-4 h-4"></i>
                        </a>
                        <form action="{{ route('pbm.questions.destroy', $question->id) }}" method="POST" onsubmit="return confirm('Hapus soal ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1.5 text-gray-500 hover:text-red-600 bg-gray-100 hover:bg-red-50 rounded transition" title="Hapus Soal">
                                <i data-feather="trash-2" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                    <div class="flex gap-3">
                        <div class="font-bold text-gray-500 w-6 shrink-0">{{ $index + 1 }}.</div>
                        <div class="flex-1 min-w-0">
                            @if($question->vignette)
                            <div class="bg-blue-50/50 p-4 rounded-lg border border-blue-100 mb-3 text-gray-800 text-sm prose max-w-none">
                                <div class="font-bold text-blue-800 mb-1 flex items-center gap-1"><i data-feather="file-text" class="w-4 h-4"></i> Kasus / Vignette:</div>
                                {!! $question->vignette !!}
                            </div>
                            @endif
                            <div class="text-gray-900 font-medium mb-3 prose max-w-none break-words">{!! $question->question_text !!}</div>
                            <div class="space-y-2">
                                @foreach($question->options as $optIndex => $option)
                                <div class="p-2.5 rounded-lg border {{ $option->is_correct ? 'bg-green-50/70 border-green-200' : 'bg-gray-50/50 border-gray-100' }}">
                                    <div class="flex gap-2.5 items-start">
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 text-xs font-bold {{ $option->is_correct ? 'bg-green-600 text-white shadow-sm' : 'bg-gray-200 text-gray-600' }}">
                                            {{ chr(65 + $optIndex) }}
                                        </div>
                                        <div class="flex-1 text-sm {{ $option->is_correct ? 'text-green-900 font-medium' : 'text-gray-700' }} prose prose-sm max-w-none break-words">
                                            {!! $option->option_text !!}
                                        </div>
                                        @if($option->is_correct)
                                        <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded font-semibold shrink-0">Kunci Jawaban</span>
                                        @endif
                                    </div>
                                    @if($option->rationale)
                                    <div class="mt-2 ml-8 text-xs text-gray-600 bg-white p-2 rounded border border-gray-200/80">
                                        <span class="font-bold text-gray-700">Rasional:</span> {!! nl2br(e($option->rationale)) !!}
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                            <div class="mt-3 text-xs text-gray-400">Poin: {{ $question->points }}</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <i data-feather="inbox" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-1">Belum ada soal</h3>
                <p class="text-gray-500 text-sm">Kuis ini belum memiliki soal. Silakan tambahkan soal terlebih dahulu.</p>
            </div>
        @endif
    </div>
</div>

<!-- Modal Import Soal -->
<div id="importModal" class="fixed inset-0 z-50 overflow-y-auto p-3 sm:p-4 md:p-6 bg-gray-900/60 backdrop-blur-xs hidden flex items-center justify-center min-h-screen">
    <div class="fixed inset-0" onclick="document.getElementById('importModal').classList.add('hidden')"></div>
    <div class="relative w-full max-w-md my-auto bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh] z-10">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center shrink-0">
            <h3 class="font-bold text-lg text-gray-900">Import Soal (Aiken Format)</h3>
            <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')" class="p-1 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition">
                <i data-feather="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="{{ route('pbm.quizzes.questions.import', $quiz->id) }}" method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            <div class="p-5 space-y-4 overflow-y-auto flex-1">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pilih File (.txt) <span class="text-red-500">*</span></label>
                    <input type="file" name="aiken_file" accept=".txt" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm py-2">
                </div>
                <div class="bg-blue-50 border border-blue-100 p-3.5 rounded-xl text-xs text-blue-800 space-y-1">
                    <strong class="font-semibold block mb-1">Format Aiken:</strong>
                    Tulis soal di baris pertama.<br>
                    Lalu diikuti opsi jawaban (A. B. C. D.).<br>
                    Lalu diakhiri dengan baris ANSWER: X.<br>
                    <div class="mt-2 pt-2 border-t border-blue-200/60 font-mono text-[11px] bg-blue-100/50 p-2 rounded">
                        Siapa penemu lampu pijar?<br>
                        A. Thomas Alva Edison<br>
                        B. Nikola Tesla<br>
                        C. Albert Einstein<br>
                        ANSWER: A
                    </div>
                </div>
            </div>
            <div class="p-4 bg-gray-50 border-t border-gray-100 shrink-0 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 font-medium hover:bg-gray-200 rounded-lg transition text-sm">Batal</button>
                <button type="submit" class="pbm-primary px-4 py-2 rounded-lg font-medium hover:opacity-90 shadow-sm transition text-sm">Upload & Import</button>
            </div>
        </form>
    </div>
</div>

@endif

@endsection
