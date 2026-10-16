@extends('pbm.layouts.app')

@section('fullwidth', true)

@push('head')
<style>
    /* CBT Option Card */
    .cbt-option-item {
        border: 2px solid #e5e7eb !important;
        background-color: #ffffff !important;
        border-radius: 0.875rem !important;
        padding: 1rem !important;
        display: flex !important;
        align-items: flex-start !important;
        gap: 1rem !important;
        cursor: pointer !important;
        user-select: none !important;
        transition: all 0.15s ease-in-out !important;
    }
    .cbt-option-item:hover {
        border-color: #93c5fd !important;
        background-color: #f8fafc !important;
    }
    .cbt-option-item.is-selected {
        border-color: #0284c7 !important; /* Sky 600 */
        background-color: #f0f9ff !important; /* Sky 50 */
        box-shadow: 0 2px 8px rgba(2, 132, 199, 0.18) !important;
    }

    /* Option Circle Badge (A, B, C, D, E) */
    .cbt-option-badge {
        width: 2.25rem !important;
        height: 2.25rem !important;
        border-radius: 9999px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-weight: 800 !important;
        font-size: 0.95rem !important;
        flex-shrink: 0 !important;
        background-color: #f3f4f6 !important; /* Gray 100 */
        color: #374151 !important; /* Gray 700 */
        border: 2px solid #d1d5db !important;
        transition: all 0.15s ease-in-out !important;
    }
    .cbt-option-item:hover .cbt-option-badge {
        background-color: #e5e7eb !important;
        color: #111827 !important;
    }
    .cbt-option-item.is-selected .cbt-option-badge {
        background-color: #0284c7 !important; /* Sky 600 solid */
        color: #ffffff !important; /* Pure White text */
        border-color: #0284c7 !important;
        box-shadow: 0 2px 4px rgba(2, 132, 199, 0.35) !important;
    }

    /* Option Text */
    .cbt-option-text {
        flex: 1 1 0% !important;
        font-size: 0.95rem !important;
        line-height: 1.6 !important;
        padding-top: 0.15rem !important;
        color: #1f2937 !important;
    }
    .cbt-option-item.is-selected .cbt-option-text {
        color: #0c4a6e !important; /* Sky 900 */
        font-weight: 500 !important;
    }

    /* Checkmark Circle on Right */
    .cbt-option-check {
        width: 1.5rem !important;
        height: 1.5rem !important;
        border-radius: 9999px !important;
        background-color: #0284c7 !important;
        color: #ffffff !important;
        display: none !important;
        align-items: center !important;
        justify-content: center !important;
        flex-shrink: 0 !important;
        margin-top: 0.25rem !important;
    }
    .cbt-option-item.is-selected .cbt-option-check {
        display: flex !important;
    }

    /* Palette Numbers Grid */
    .cbt-palette-btn {
        height: 2.75rem !important;
        border-radius: 0.75rem !important;
        font-weight: 700 !important;
        font-size: 0.875rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        position: relative !important;
        border: 2px solid #e5e7eb !important;
        background-color: #f9fafb !important;
        color: #374151 !important;
        transition: all 0.15s ease !important;
    }
    .cbt-palette-btn:hover {
        border-color: #cbd5e1 !important;
        background-color: #f3f4f6 !important;
    }
    .cbt-palette-btn.is-active {
        border-color: #0284c7 !important;
        box-shadow: 0 0 0 2px #38bdf8 !important;
        z-index: 10 !important;
    }
    .cbt-palette-btn.is-answered {
        background-color: #10b981 !important; /* Emerald 500 */
        border-color: #059669 !important;
        color: #ffffff !important;
    }
    .cbt-palette-btn.is-flagged {
        background-color: #f59e0b !important; /* Amber 500 */
        border-color: #d97706 !important;
        color: #ffffff !important;
    }
</style>
@endpush

@section('content')
<div x-data="cbtApp()" x-init="initApp()" class="max-w-7xl mx-auto pb-16">
    <!-- CBT Header Sticky -->
    <div class="bg-white/95 backdrop-blur-md rounded-2xl shadow-md border border-gray-200 p-4 sm:p-5 mb-6 sticky top-14 sm:top-16 z-30">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">
                        CBT Exam
                    </span>
                    @if($quiz->enable_proctoring)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 flex items-center gap-1">
                        <i data-feather="shield" class="w-3 h-3"></i> Proctoring
                    </span>
                    @endif
                    @if($quiz->require_webcam)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-700 flex items-center gap-1">
                        <i data-feather="video" class="w-3 h-3"></i> Webcam
                    </span>
                    @endif
                    @if($quiz->require_microphone)
                    @php
                        $sensLabel = $quiz->mic_sensitivity === 'high' ? 'Peka' : ($quiz->mic_sensitivity === 'low' ? 'Toleran' : 'Standar');
                    @endphp
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-pink-100 text-pink-700 flex items-center gap-1">
                        <i data-feather="mic" class="w-3 h-3"></i> Mic Wajib ({{ $sensLabel }})
                    </span>
                    @endif
                    @if($quiz->enable_browser_lockdown)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 flex items-center gap-1">
                        <i data-feather="lock" class="w-3 h-3"></i> Lockdown
                    </span>
                    @endif
                    <span class="text-xs text-gray-500">
                        Mulai: {{ $attempt->started_at->format('H:i') }} WIB
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900">{{ $quiz->title }}</h1>
            </div>

            <div class="flex items-center gap-4 flex-wrap">
                <!-- Violation Badge (Active when violations > 0) -->
                <template x-if="enableProctoring && violationCount > 0">
                    <div class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold border border-red-200 bg-red-50 text-red-700 animate-pulse">
                        <i data-feather="alert-triangle" class="w-4 h-4 text-red-600"></i>
                        <span>Pelanggaran: <span x-text="violationCount"></span>/{{ $quiz->max_violations ?? 3 }}</span>
                    </div>
                </template>

                <!-- Auto-save Status -->
                <div class="flex items-center gap-1.5 text-xs text-gray-500 bg-gray-50 px-3 py-2 rounded-xl border border-gray-200">
                    <template x-if="saving">
                        <span class="flex items-center gap-1.5 text-blue-600 font-medium">
                            <svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Menyimpan...
                        </span>
                    </template>
                    <template x-if="!saving">
                        <span class="flex items-center gap-1.5 text-emerald-600 font-medium">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span x-text="saveStatus"></span>
                        </span>
                    </template>
                </div>

                <!-- Timer -->
                @if($remainingSeconds !== null)
                <div class="flex items-center gap-2 px-4 py-2 rounded-xl border font-mono font-bold text-lg shadow-sm"
                     :class="remainingSeconds < 300 ? 'bg-red-50 text-red-600 border-red-200 animate-pulse' : 'bg-purple-50 text-purple-800 border-purple-200'">
                    <i data-feather="clock" class="w-5 h-5"></i>
                    <span x-text="timerDisplay">--:--</span>
                </div>
                @else
                <div class="flex items-center gap-1.5 text-xs font-semibold px-3 py-2 bg-gray-100 text-gray-600 rounded-xl">
                    <i data-feather="infinity" class="w-4 h-4"></i> Waktu Bebas
                </div>
                @endif

                <!-- Submit Button -->
                <button type="button" 
                        @click="openConfirmModal()"
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold shadow-md shadow-emerald-600/20 transition-all flex items-center gap-2 text-sm cursor-pointer">
                    <i data-feather="check-circle" class="w-4 h-4"></i> Kumpulkan Ujian
                </button>
            </div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Question Column -->
        <div class="lg:col-span-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 md:p-8 min-h-[500px] flex flex-col justify-between">
                <div>
                    <!-- Question Loop -->
                    @foreach($questions as $index => $question)
                    <div x-show="currentIndex === {{ $index }}" x-cloak class="space-y-6">
                        <!-- Top Meta -->
                        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold text-gray-500 uppercase tracking-wider">
                                    Soal Nomor <span class="text-gray-900 text-lg">{{ $index + 1 }}</span> dari {{ $questions->count() }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-600">
                                    Bobot: {{ $question->points ?? 1 }} Poin
                                </span>
                            </div>
                        </div>

                        <!-- Vignette / Case (if any) -->
                        @if($question->vignette)
                        <div class="bg-blue-50/60 rounded-xl p-5 border border-blue-100/80 text-gray-900 text-sm leading-relaxed">
                            <div class="flex items-center gap-2 font-bold text-blue-900 mb-2">
                                <i data-feather="file-text" class="w-4 h-4 text-blue-600"></i>
                                <span>Kasus / Vignette Klinis:</span>
                            </div>
                            <div class="prose prose-sm max-w-none text-gray-800">
                                {!! $question->vignette !!}
                            </div>
                        </div>
                        @endif

                        <!-- Question Lead-in / Question Text -->
                        <div class="text-base sm:text-lg font-medium text-gray-900 leading-relaxed prose max-w-none">
                            {!! $question->question_text !!}
                        </div>

                        <!-- Options -->
                        <div class="space-y-3 pt-2">
                            @foreach($question->options as $optIdx => $option)
                            @php
                                $label = chr(65 + $optIdx);
                            @endphp
                            <label class="cbt-option-item"
                                   :class="{ 'is-selected': answers[{{ $question->id }}] === {{ $option->id }} }">
                                <input type="radio" 
                                       name="question_{{ $question->id }}" 
                                       value="{{ $option->id }}"
                                       @change="selectAnswer({{ $question->id }}, {{ $option->id }})"
                                       :checked="answers[{{ $question->id }}] === {{ $option->id }}"
                                       class="sr-only">

                                <div class="cbt-option-badge">
                                    {{ $label }}
                                </div>

                                <div class="cbt-option-text prose prose-sm max-w-none">
                                    {!! $option->option_text !!}
                                </div>

                                <div class="cbt-option-check">
                                    <svg class="w-3.5 h-3.5 stroke-current" fill="none" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Bottom Navigation Buttons -->
                <div class="flex items-center justify-between border-t border-gray-100 pt-6 mt-8">
                    <button type="button" 
                            @click="prevQuestion()" 
                            :disabled="currentIndex === 0"
                            class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-semibold text-sm hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center gap-2 cursor-pointer">
                        <i data-feather="chevron-left" class="w-4 h-4"></i> Soal Sebelumnya
                    </button>

                    <!-- Toggle Ragu-ragu -->
                    <button type="button"
                            @click="toggleFlag(currentQuestionId)"
                            class="px-4 py-2.5 rounded-xl border font-semibold text-sm transition flex items-center gap-2 cursor-pointer"
                            :class="flagged[currentQuestionId] ? 'border-amber-400 bg-amber-50 text-amber-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                        <i data-feather="bookmark" class="w-4 h-4" :class="flagged[currentQuestionId] ? 'fill-amber-400 text-amber-500' : ''"></i>
                        <span x-text="flagged[currentQuestionId] ? 'Tandai Ragu-ragu (Aktif)' : 'Ragu-ragu'"></span>
                    </button>

                    <template x-if="currentIndex < totalQuestions - 1">
                        <button type="button" 
                                @click="nextQuestion()" 
                                class="px-6 py-2.5 rounded-xl pbm-primary font-semibold text-sm hover:opacity-95 transition flex items-center gap-2 shadow-sm cursor-pointer">
                            Soal Selanjutnya <i data-feather="chevron-right" class="w-4 h-4"></i>
                        </button>
                    </template>
                    <template x-if="currentIndex === totalQuestions - 1">
                        <button type="button" 
                                @click="openConfirmModal()" 
                                class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm transition flex items-center gap-2 shadow-md shadow-emerald-600/20 cursor-pointer">
                            Selesai & Kumpulkan <i data-feather="check" class="w-4 h-4"></i>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Question Navigator Palette -->
        <div class="lg:col-span-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 lg:sticky lg:top-20 max-h-[calc(100vh-6rem)] overflow-y-auto">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
                    <h2 class="font-bold text-gray-900 flex items-center gap-2 text-base">
                        <i data-feather="grid" class="w-4 h-4 text-purple-600"></i> Nomor Soal
                    </h2>
                    <span class="text-xs font-semibold px-2.5 py-1 bg-purple-50 text-purple-700 rounded-lg">
                        <span x-text="answeredCount"></span> / <span x-text="totalQuestions"></span> Terjawab
                    </span>
                </div>

                <!-- Palette Grid -->
                <div class="grid grid-cols-5 gap-2.5 mb-6">
                    @foreach($questions as $index => $q)
                    <button type="button"
                            @click="goToQuestion({{ $index }})"
                            class="cbt-palette-btn cursor-pointer"
                            :class="{
                                'is-active': currentIndex === {{ $index }},
                                'is-flagged': flagged[{{ $q->id }}],
                                'is-answered': answers[{{ $q->id }}] && !flagged[{{ $q->id }}]
                            }">
                        <span>{{ $index + 1 }}</span>
                        @if($q->points > 1)
                        <span class="absolute top-0.5 right-1 text-[9px] opacity-75 font-normal">{{ $q->points }}p</span>
                        @endif
                    </button>
                    @endforeach
                </div>

                <!-- Legend -->
                <div class="bg-gray-50/80 rounded-xl p-3 text-xs space-y-2 border border-gray-100">
                    <div class="font-semibold text-gray-600 mb-1">Keterangan:</div>
                    <div class="flex items-center gap-2">
                        <span class="w-3.5 h-3.5 rounded bg-emerald-500 border border-emerald-600 shrink-0"></span>
                        <span class="text-gray-700">Sudah Dijawab (<span x-text="answeredCount"></span>)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3.5 h-3.5 rounded bg-amber-400 border border-amber-500 shrink-0"></span>
                        <span class="text-gray-700">Ragu-ragu (<span x-text="flaggedCount"></span>)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3.5 h-3.5 rounded bg-gray-100 border border-gray-300 shrink-0"></span>
                        <span class="text-gray-700">Belum Dijawab (<span x-text="totalQuestions - answeredCount"></span>)</span>
                    </div>
                </div>

                <!-- Quick Submit Palette Button -->
                <button type="button"
                        @click="openConfirmModal()"
                        class="w-full mt-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-bold shadow-md shadow-emerald-600/20 hover:opacity-95 transition text-sm flex items-center justify-center gap-2">
                    <i data-feather="send" class="w-4 h-4"></i> Kumpulkan Hasil Ujian
                </button>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div x-show="showConfirmModal" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showConfirmModal = false"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-200">
                <div class="bg-white p-6 sm:p-8">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 mb-4">
                        <i data-feather="help-circle" class="w-8 h-8"></i>
                    </div>

                    <h3 class="text-xl font-bold text-center text-gray-900 mb-2">Konfirmasi Pengumpulan Ujian</h3>
                    <p class="text-sm text-center text-gray-500 mb-6">
                        Apakah Anda yakin ingin menyelesaikan kuis ini? Jawaban Anda akan dinilai secara otomatis.
                    </p>

                    <!-- Summary Stats Box -->
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200/80 mb-6 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Soal:</span>
                            <span class="font-bold text-gray-900" x-text="totalQuestions"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-emerald-700 font-medium">Sudah Dijawab:</span>
                            <span class="font-bold text-emerald-700" x-text="answeredCount"></span>
                        </div>
                        <div class="flex justify-between" x-show="totalQuestions - answeredCount > 0">
                            <span class="text-red-600 font-medium">Belum Dijawab:</span>
                            <span class="font-bold text-red-600" x-text="totalQuestions - answeredCount"></span>
                        </div>
                        <div class="flex justify-between" x-show="flaggedCount > 0">
                            <span class="text-amber-600 font-medium">Masih Ragu-ragu:</span>
                            <span class="font-bold text-amber-600" x-text="flaggedCount"></span>
                        </div>
                    </div>

                    <template x-if="totalQuestions - answeredCount > 0">
                        <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700 font-medium mb-6 flex items-start gap-2">
                            <i data-feather="alert-triangle" class="w-4 h-4 text-red-600 shrink-0 mt-0.5"></i>
                            <span>Perhatian: Masih terdapat butir soal yang belum dijawab. Soal yang tidak dijawab tidak akan mendapatkan nilai.</span>
                        </div>
                    </template>

                    <!-- Action Form -->
                    <form action="{{ route('pbm.quizzes.attempt.submit', $attempt->id) }}" method="POST" id="submitAttemptForm" @submit="stopMediaAndAudio()">
                        @csrf
                        <div class="flex gap-3">
                            <button type="button" 
                                    @click="showConfirmModal = false" 
                                    class="flex-1 py-3 px-4 rounded-xl border border-gray-300 text-gray-700 font-semibold text-sm hover:bg-gray-50 transition">
                                Kembali Mengerjakan
                            </button>
                            <button type="submit" 
                                    class="flex-1 py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-md shadow-emerald-600/30 transition">
                                Ya, Kumpulkan Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Canvas for Offscreen Snapshot Capture -->
    <canvas id="proctoringCanvas" width="320" height="240" class="hidden"></canvas>

    <!-- Floating Proctoring PIP Widget -->
    <div x-show="enableProctoring && (requireWebcam || requireMicrophone) && examStarted && !isLocked" 
         x-cloak 
         class="fixed bottom-4 right-4 z-40 bg-gray-900/90 backdrop-blur-md p-2.5 rounded-2xl shadow-2xl border border-purple-500/50 flex flex-col items-center gap-2 transition-all hover:scale-105">
        @if($quiz->require_webcam)
        <div class="relative w-36 h-28 sm:w-44 sm:h-32 rounded-xl overflow-hidden bg-black flex items-center justify-center">
            <video id="floatingProctorVideo" autoplay playsinline muted class="w-full h-full object-cover"></video>
            <div class="absolute top-1.5 left-1.5 flex items-center gap-1 bg-red-600/90 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-md shadow-xs">
                <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span> REC
            </div>
        </div>
        @endif

        @if($quiz->require_microphone)
        <!-- Live Microphone Audio Bar in PiP -->
        <div class="w-full px-2 py-1.5 bg-gray-800/90 rounded-xl flex items-center gap-2 text-[11px] text-white">
            <i data-feather="mic" class="w-3.5 h-3.5 shrink-0 transition-colors" :class="micVisualLevel >= normalizedThresholdPercent ? 'text-red-400 animate-pulse' : 'text-pink-400'"></i>
            <div class="relative flex-1 bg-gray-700 h-2 rounded-full overflow-hidden">
                <div class="h-full transition-all duration-75 rounded-full"
                     :class="micVisualLevel >= normalizedThresholdPercent ? 'bg-red-500' : 'bg-emerald-400'"
                     :style="`width: ${Math.min(100, Math.max(5, micVisualLevel))}%`"></div>
                <div class="absolute top-0 bottom-0 w-0.5 bg-amber-300 opacity-80"
                     :style="`left: ${normalizedThresholdPercent}%`"
                     title="Batas Ambang Suara"></div>
            </div>
            <span class="text-[9px] font-mono w-10 text-right font-semibold"
                  :class="micVisualLevel >= normalizedThresholdPercent ? 'text-red-400 animate-pulse' : 'text-emerald-400'"
                  x-text="micVisualLevel >= normalizedThresholdPercent ? 'Suara!' : 'Hening'"></span>
        </div>
        @endif

        <div class="w-full flex items-center justify-between text-[11px] text-white/90 px-1 font-mono">
            <span class="flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Live
            </span>
            <span :class="violationCount > 0 ? 'text-amber-400 font-bold' : 'text-gray-300'">
                Pelanggaran: <span x-text="violationCount"></span>/{{ $quiz->max_violations ?? 3 }}
            </span>
        </div>
    </div>

    <!-- Pre-Exam Readiness Modal -->
    <!-- Pre-Exam Readiness Modal -->
    <div x-show="showPreExamModal" 
         x-cloak 
         class="fixed inset-0 z-50 bg-gray-900/85 backdrop-blur-md overflow-y-auto p-3 sm:p-4 flex items-center justify-center min-h-screen">
        <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-200 flex flex-col max-h-[92vh] sm:max-h-[88vh] my-auto">
            <!-- Header (Fixed at top) -->
            <div class="bg-gradient-to-r from-purple-600 to-indigo-700 px-5 py-3.5 text-white text-center shrink-0">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mx-auto mb-1.5 backdrop-blur-sm">
                    <i data-feather="shield" class="w-5 h-5"></i>
                </div>
                <h3 class="text-base sm:text-lg font-bold">Pemeriksaan Kesiapan Ujian CBT</h3>
                <p class="text-xs text-purple-100">Sesi ujian ini dilengkapi dengan sistem pengawasan integritas.</p>
            </div>

            <!-- Scrollable Body (Scrolls smoothly with visible scrollbar) -->
            <div class="p-4 sm:p-5 space-y-3.5 overflow-y-auto flex-1 overscroll-contain">
                @if($quiz->require_webcam)
                <div class="text-center space-y-2.5">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Kamera Pengawas (Webcam)</label>
                    <div class="relative w-44 h-32 mx-auto rounded-2xl overflow-hidden bg-gray-900 border-2 border-purple-500 shadow-md flex items-center justify-center">
                        <video id="preExamVideo" autoplay playsinline muted class="w-full h-full object-cover"></video>
                        <div x-show="!cameraReady && !cameraError" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-900 text-white text-xs p-3">
                            <svg class="animate-spin h-6 w-6 text-purple-400 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Menghubungkan kamera...
                        </div>
                    </div>

                    <template x-if="cameraReady">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                            <i data-feather="check-circle" class="w-3.5 h-3.5"></i> Kamera Terhubung & Siap
                        </div>
                    </template>

                    <template x-if="cameraError">
                        <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700 space-y-2 text-left">
                            <p x-text="cameraError"></p>
                            <button type="button" @click="initMediaDevices()" class="px-3 py-1 bg-red-600 text-white rounded-lg text-xs font-bold hover:bg-red-700 transition">
                                Coba Hubungkan Ulang Kamera
                            </button>
                        </div>
                    </template>
                </div>
                @endif

                @if($quiz->require_microphone)
                <div class="p-3.5 bg-purple-50/60 border border-purple-200 rounded-2xl space-y-3 text-center">
                    <div>
                        <label class="block text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center justify-center gap-1.5">
                            <i data-feather="mic" class="w-4 h-4 text-purple-600"></i> Mikrofon Pengawas (Wajib On Mic)
                        </label>
                        <p class="text-xs text-gray-600 mt-0.5">
                            Sistem mendeteksi suara percakapan, obrolan, atau diskusi selama ujian berlangsung.
                        </p>
                    </div>

                    <!-- Live VU Meter for Microphone test with Threshold Line -->
                    <div class="max-w-sm mx-auto space-y-2 bg-white p-3 rounded-2xl border border-purple-100 shadow-xs text-left">
                        <div class="flex items-center justify-between text-[11px] font-semibold text-gray-700">
                            <span class="flex items-center gap-1">
                                <i data-feather="activity" class="w-3.5 h-3.5 text-purple-500"></i> 
                                Level Suara: <span class="font-mono text-purple-700" x-text="micVisualLevel + '%'"></span>
                            </span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                  :class="micVisualLevel >= normalizedThresholdPercent ? 'bg-red-100 text-red-700 animate-pulse' : 'bg-emerald-100 text-emerald-700'"
                                  x-text="micVisualLevel >= normalizedThresholdPercent ? '⚠️ Suara Terdeteksi!' : '✅ Aman / Hening'"></span>
                        </div>

                        <!-- Progress bar with threshold marker -->
                        <div class="relative w-full bg-gray-200 h-3 rounded-full overflow-hidden p-0.5 border border-gray-300">
                            <div class="h-full rounded-full transition-all duration-75"
                                 :class="micVisualLevel >= normalizedThresholdPercent ? 'bg-red-500' : 'bg-emerald-500'"
                                 :style="`width: ${Math.min(100, Math.max(3, micVisualLevel))}%`"></div>
                            <!-- Dynamic Threshold Mark -->
                            <div class="absolute top-0 bottom-0 w-1 bg-red-600 z-10 rounded-full shadow-xs"
                                 :style="`left: ${normalizedThresholdPercent}%`"
                                 title="Batas Ambang Deteksi Suara"></div>
                        </div>

                        <div class="flex items-center justify-between text-[10px] text-gray-500 pt-0.5">
                            <span>0% (Hening)</span>
                            <span class="text-red-600 font-bold" x-text="'Ambang: ' + normalizedThresholdPercent + '%'"></span>
                            <span>100%</span>
                        </div>

                        <!-- Sensitivity Setting Switcher -->
                        <div class="pt-1.5 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
                            <span class="text-[11px] font-bold text-gray-700">Tingkat Sensitivitas:</span>
                            <div class="inline-flex rounded-xl border border-gray-200 p-0.5 bg-gray-100/80 text-[11px] self-start sm:self-auto">
                                <button type="button" 
                                        @click="setMicSensitivity('low')" 
                                        :class="micSensitivity === 'low' ? 'bg-white shadow-xs text-purple-700 font-bold' : 'text-gray-600 hover:text-gray-900'" 
                                        class="px-2 py-0.5 rounded-lg transition" 
                                        title="Hanya mendeteksi suara yang jelas/lantang">
                                    Rendah (Toleran)
                                </button>
                                <button type="button" 
                                        @click="setMicSensitivity('medium')" 
                                        :class="micSensitivity === 'medium' ? 'bg-white shadow-xs text-purple-700 font-bold' : 'text-gray-600 hover:text-gray-900'" 
                                        class="px-2 py-0.5 rounded-lg transition" 
                                        title="Standar seimbang untuk ruangan wajar">
                                    Sedang (Standar)
                                </button>
                                <button type="button" 
                                        @click="setMicSensitivity('high')" 
                                        :class="micSensitivity === 'high' ? 'bg-white shadow-xs text-purple-700 font-bold' : 'text-gray-600 hover:text-gray-900'" 
                                        class="px-2 py-0.5 rounded-lg transition" 
                                        title="Peka hingga bisikan pelan">
                                    Tinggi (Ketat)
                                </button>
                            </div>
                        </div>

                        <!-- Noise Calibration Button -->
                        <div class="pt-1.5 border-t border-gray-100 flex flex-col items-center gap-1">
                            <button type="button" 
                                    @click="calibrateNoiseFloor()" 
                                    :disabled="!micReady || isCalibrating" 
                                    class="w-full py-1.5 px-3 rounded-xl border border-purple-200 bg-purple-50/50 hover:bg-purple-100 text-purple-800 text-[11px] font-bold transition flex items-center justify-center gap-1.5 disabled:opacity-50 cursor-pointer">
                                <i data-feather="sliders" class="w-3.5 h-3.5" :class="isCalibrating ? 'animate-spin' : ''"></i>
                                <span x-text="isCalibrating ? 'Merekam Suara Ruangan (2 Detik)...' : 'Kalibrasi Kebisingan Ruangan Anda'"></span>
                            </button>
                            <p x-show="calibrationFeedback" x-text="calibrationFeedback" class="text-[10px] text-purple-700 text-center font-medium bg-purple-50 px-2 py-1 rounded-lg w-full border border-purple-100"></p>
                        </div>
                    </div>

                    <template x-if="micReady">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                            <i data-feather="check-circle" class="w-3.5 h-3.5"></i> Mikrofon Aktif & Siap (<span x-text="sensitivityConfig.label"></span>)
                        </div>
                    </template>

                    <template x-if="micError">
                        <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700 space-y-2 text-left">
                            <p x-text="micError"></p>
                            <button type="button" @click="initMediaDevices()" class="px-3 py-1 bg-red-600 text-white rounded-lg text-xs font-bold hover:bg-red-700 transition">
                                Coba Hubungkan Mikrofon
                            </button>
                        </div>
                    </template>
                </div>
                @endif

                @if($quiz->enable_browser_lockdown)
                <div class="bg-amber-50/70 border border-amber-200 rounded-2xl p-3.5 text-xs text-amber-900 space-y-1.5">
                    <span class="font-bold flex items-center gap-1.5 text-amber-800 text-xs sm:text-sm">
                        <i data-feather="lock" class="w-4 h-4"></i> Aturan Integritas & Layar (Lockdown):
                    </span>
                    <ul class="list-disc list-inside space-y-1 text-amber-900/90 pl-1 leading-relaxed text-[11px] sm:text-xs">
                        <li>Ujian diwajibkan dalam <strong>Mode Layar Penuh (Fullscreen)</strong>.</li>
                        <li>Dilarang berpindah tab browser, minimize layar, atau membuka aplikasi lain.</li>
                        <li>Fitur <strong>Salin (Copy), Tempel (Paste), dan Klik Kanan</strong> diblokir.</li>
                        @if($quiz->require_microphone)
                        <li>Mikrofon <strong>wajib selalu aktif (On Mic)</strong>. Dilarang mengobrol atau bertanya dengan orang lain.</li>
                        @endif
                        <li>Setiap pelanggaran dicatat bersama foto snapshot. Ujian otomatis terkunci jika mencapai <strong>{{ $quiz->max_violations ?? 3 }} kali pelanggaran</strong>.</li>
                    </ul>
                </div>
                @endif
            </div>

            <!-- Footer (Always visible & pinned at bottom) -->
            <div class="p-3.5 sm:p-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between gap-3 shrink-0 sticky bottom-0 z-20 shadow-sm">
                <a href="{{ route('pbm.quizzes.show', $quiz->id) }}" class="px-4 py-2.5 text-xs font-semibold text-gray-600 hover:bg-gray-200 rounded-xl transition">
                    Batal & Keluar
                </a>
                <button type="button" 
                        @click="startExamSession()"
                        :disabled="(requireWebcam && !cameraReady) || (requireMicrophone && !micReady)"
                        class="px-5 sm:px-6 py-2.5 pbm-primary text-white rounded-xl text-xs sm:text-sm font-bold shadow-md hover:opacity-95 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 cursor-pointer">
                    <span>Saya Mengerti & Mulai Ujian</span>
                    <i data-feather="arrow-right" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Violation Warning Modal -->
    <div x-show="showViolationModal" x-cloak class="fixed inset-0 z-50 bg-red-950/80 backdrop-blur-sm overflow-y-auto p-4 flex items-center justify-center min-h-screen">
        <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden border-2 border-red-500 text-center p-6 sm:p-8 my-auto">
            <div class="w-16 h-16 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4 animate-bounce">
                <i data-feather="alert-triangle" class="w-8 h-8"></i>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-red-100 text-red-800 inline-block mb-2">
                Peringatan Pelanggaran
            </span>
            <h3 class="text-xl font-extrabold text-gray-900 mb-2">Aktivitas Terlarang Terdeteksi!</h3>
            <p class="text-xs sm:text-sm text-gray-600 mb-4" x-text="lastViolationMessage"></p>

            <div class="bg-red-50 rounded-2xl p-4 border border-red-200 mb-6 text-xs text-red-900 space-y-1.5 text-left font-mono">
                <div>Jenis: <span class="font-bold uppercase" x-text="lastViolationType.replace('_', ' ')"></span></div>
                <div>Pelanggaran ke-: <span class="font-bold text-red-600 text-sm" x-text="violationCount"></span> / {{ $quiz->max_violations ?? 3 }} kali toleransi</div>
                <div class="text-[11px] text-red-500 pt-1 font-sans">Foto snapshot dan log kejadian telah disimpan ke server pengawas.</div>
            </div>

            <button type="button" 
                    @click="reEnterFullscreen()"
                    class="w-full py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold text-sm shadow-md transition flex items-center justify-center gap-2 cursor-pointer">
                <i data-feather="maximize" class="w-4 h-4"></i> Saya Mengerti & Kembali ke Ujian
            </button>
        </div>
    </div>

    <!-- Auto-Lock Fullscreen Lockout Overlay -->
    <div x-show="isLocked" x-cloak class="fixed inset-0 z-50 bg-gray-950/95 backdrop-blur-md overflow-y-auto p-4 flex items-center justify-center min-h-screen">
        <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden border-2 border-red-600 text-center p-6 sm:p-8 my-auto">
            <div class="w-16 h-16 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i data-feather="lock" class="w-8 h-8"></i>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-red-100 text-red-800 inline-block mb-2">
                Ujian Terkunci
            </span>
            <h3 class="text-2xl font-extrabold text-gray-900 mb-2">Akses Ujian Dikunci</h3>
            <p class="text-xs sm:text-sm text-gray-600 mb-6 leading-relaxed">
                Anda telah mencapai batas maksimal toleransi pelanggaran (<span x-text="violationCount"></span> kali). Sesi pengerjaan Anda telah dikunci oleh sistem pengawas.
            </p>

            <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200 mb-6 text-xs text-gray-700 text-left space-y-1">
                <span class="font-bold block text-gray-900">Catatan Pengawas:</span>
                <p x-text="lockReason" class="font-mono text-red-600 text-[11px] bg-red-50 p-2 rounded border border-red-100"></p>
                <p class="text-[11px] text-gray-500 pt-1">Silakan lapor kepada dosen atau pengawas ujian untuk meminta pembukaan kunci.</p>
            </div>

            <a href="{{ route('pbm.quizzes.show', $quiz->id) }}" 
               class="w-full py-3 bg-gray-900 hover:bg-black text-white rounded-xl font-bold text-sm shadow-md transition flex items-center justify-center gap-2">
                <i data-feather="arrow-left" class="w-4 h-4"></i> Kembali ke Beranda Kuis
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function cbtApp() {
    return {
        currentIndex: 0,
        totalQuestions: {{ $questions->count() }},
        questionIds: @json($questions->pluck('id')),
        answers: @json((object)$existingAnswers),
        flagged: {},
        saving: false,
        saveStatus: 'Tersimpan',
        remainingSeconds: {{ $remainingSeconds !== null ? (int)$remainingSeconds : 'null' }},
        timerDisplay: '--:--',
        timerInterval: null,
        showConfirmModal: false,

        // Proctoring & Lockdown states
        enableProctoring: {{ $quiz->enable_proctoring ? 'true' : 'false' }},
        requireWebcam: {{ $quiz->require_webcam ? 'true' : 'false' }},
        requireMicrophone: {{ $quiz->require_microphone ? 'true' : 'false' }},
        enableBrowserLockdown: {{ $quiz->enable_browser_lockdown ? 'true' : 'false' }},
        maxViolations: {{ $quiz->max_violations ?? 3 }},
        violationCount: {{ (int) $attempt->violation_count }},
        isLocked: {{ $attempt->is_locked ? 'true' : 'false' }},
        lockReason: '{{ addslashes($attempt->lock_reason ?? '') }}',

        // Readiness, Camera & Microphone
        examStarted: {{ (!$quiz->enable_proctoring || (!$quiz->require_webcam && !$quiz->require_microphone && !$quiz->enable_browser_lockdown)) ? 'true' : 'false' }},
        showPreExamModal: {{ ($quiz->enable_proctoring && ($quiz->require_webcam || $quiz->require_microphone || $quiz->enable_browser_lockdown) && !$attempt->is_locked) ? 'true' : 'false' }},
        cameraReady: false,
        cameraError: null,
        micReady: false,
        micError: null,
        micVisualLevel: 0,
        mediaStream: null,
        audioContext: null,
        analyserNode: null,
        audioMonitoringInterval: null,
        consecutiveSpeechFrames: 0,
        lastVoiceViolationTime: 0,

        // Microphone Sensitivity & Calibration
        micSensitivity: '{{ $quiz->mic_sensitivity ?? 'medium' }}',
        noiseFloor: 8,
        isCalibrating: false,
        calibrationFeedback: null,

        // Violations warning modal
        showViolationModal: false,
        lastViolationType: '',
        lastViolationMessage: '',
        lastViolationTime: 0,
        snapshotInterval: null,

        get currentQuestionId() {
            return this.questionIds[this.currentIndex];
        },

        get answeredCount() {
            return Object.keys(this.answers).filter(k => this.answers[k] !== null && this.answers[k] !== undefined).length;
        },

        get flaggedCount() {
            return Object.keys(this.flagged).filter(k => this.flagged[k] === true).length;
        },

        get sensitivityConfig() {
            if (this.micSensitivity === 'high') {
                return { baseEnergy: 18, frames: 5, label: 'Tinggi (Peka Bisikan)' };
            } else if (this.micSensitivity === 'low') {
                return { baseEnergy: 40, frames: 9, label: 'Rendah (Toleran Bising)' };
            } else {
                return { baseEnergy: 28, frames: 7, label: 'Sedang (Standar)' };
            }
        },

        get effectiveThreshold() {
            return Math.max(this.sensitivityConfig.baseEnergy, this.noiseFloor + 12);
        },

        get normalizedThresholdPercent() {
            return Math.min(95, Math.max(15, Math.round((this.effectiveThreshold / 115) * 100)));
        },

        initApp() {
            this.updateTimerDisplay();
            if (this.remainingSeconds !== null && !this.isLocked) {
                this.startTimer();
            }

            if (this.enableProctoring) {
                if (this.requireWebcam || this.requireMicrophone) {
                    this.initMediaDevices();
                }
                if (this.enableBrowserLockdown) {
                    this.setupLockdownGuards();
                }
            }

            this.$nextTick(() => {
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            });
        },

        setMicSensitivity(val) {
            this.micSensitivity = val;
            this.$nextTick(() => {
                if (typeof feather !== 'undefined') feather.replace();
            });
        },

        calibrateNoiseFloor() {
            if (!this.analyserNode || this.isCalibrating) return;
            this.isCalibrating = true;
            this.calibrationFeedback = 'Mengukur kebisingan hening ruangan... Mohon jangan bersuara selama 2 detik.';

            const samples = [];
            const sampleInterval = setInterval(() => {
                if (!this.analyserNode) return;
                const dataArray = new Uint8Array(this.analyserNode.frequencyBinCount);
                this.analyserNode.getByteFrequencyData(dataArray);
                let sum = 0;
                const minBin = 2;
                const maxBin = Math.min(34, dataArray.length);
                for (let i = minBin; i < maxBin; i++) {
                    sum += dataArray[i];
                }
                samples.push(sum / (maxBin - minBin));
            }, 100);

            setTimeout(() => {
                clearInterval(sampleInterval);
                if (samples.length > 0) {
                    const avg = samples.reduce((a, b) => a + b, 0) / samples.length;
                    this.noiseFloor = Math.round(avg);
                    const noisePct = Math.min(100, Math.round((this.noiseFloor / 115) * 100));
                    this.calibrationFeedback = `Kalibrasi selesai! Kebisingan dasar ruangan: ${noisePct}%. Ambang batas deteksi suara disesuaikan ke ${this.normalizedThresholdPercent}%.`;
                }
                this.isCalibrating = false;
                this.$nextTick(() => {
                    if (typeof feather !== 'undefined') feather.replace();
                });
            }, 2000);
        },

        async initMediaDevices() {
            try {
                const constraints = {
                    video: this.requireWebcam ? { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' } : false,
                    audio: this.requireMicrophone ? {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true
                    } : false
                };

                this.mediaStream = await navigator.mediaDevices.getUserMedia(constraints);

                if (this.requireWebcam) {
                    this.cameraReady = true;
                    this.cameraError = null;
                }

                if (this.requireMicrophone) {
                    this.micReady = true;
                    this.micError = null;
                    this.initAudioMonitoring();
                }

                this.$nextTick(() => {
                    const preVideo = document.getElementById('preExamVideo');
                    if (preVideo && this.requireWebcam) {
                        preVideo.srcObject = this.mediaStream;
                    }
                    const pipVideo = document.getElementById('floatingProctorVideo');
                    if (pipVideo && this.requireWebcam) {
                        pipVideo.srcObject = this.mediaStream;
                    }
                    if (typeof feather !== 'undefined') feather.replace();
                });
            } catch (err) {
                console.error('Media devices access error:', err);
                if (this.requireWebcam && !this.cameraReady) {
                    this.cameraReady = false;
                    this.cameraError = 'Izin kamera ditolak atau perangkat kamera tidak terdeteksi. Harap izinkan akses kamera di browser Anda.';
                }
                if (this.requireMicrophone && !this.micReady) {
                    this.micReady = false;
                    this.micError = 'Izin mikrofon ditolak atau perangkat mikrofon tidak terdeteksi. Sesi ini mewajibkan mikrofon aktif (On Mic).';
                }
            }
        },

        initAudioMonitoring() {
            if (!this.mediaStream || !this.requireMicrophone) return;

            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;

                if (!this.audioContext) {
                    this.audioContext = new AudioCtx();
                }
                if (this.audioContext.state === 'suspended') {
                    this.audioContext.resume();
                }

                const source = this.audioContext.createMediaStreamSource(this.mediaStream);
                this.analyserNode = this.audioContext.createAnalyser();
                this.analyserNode.fftSize = 512;
                this.analyserNode.smoothingTimeConstant = 0.35;
                source.connect(this.analyserNode);

                const dataArray = new Uint8Array(this.analyserNode.frequencyBinCount);

                if (this.audioMonitoringInterval) {
                    clearInterval(this.audioMonitoringInterval);
                }

                // Sample every 100ms
                this.audioMonitoringInterval = setInterval(() => {
                    if (!this.analyserNode) return;
                    this.analyserNode.getByteFrequencyData(dataArray);

                    // Voice frequency range: bins 2 to 34 (~150Hz - 3000Hz)
                    let voiceSum = 0;
                    const minBin = 2;
                    const maxBin = Math.min(34, dataArray.length);
                    for (let i = minBin; i < maxBin; i++) {
                        voiceSum += dataArray[i];
                    }
                    const voiceAvg = voiceSum / (maxBin - minBin);

                    // Visual VU meter 0-100%
                    const normalizedLevel = Math.min(100, Math.round((voiceAvg / 115) * 100));
                    this.micVisualLevel = normalizedLevel;

                    // Dynamic threshold based on active sensitivity and room noise floor
                    const threshold = this.effectiveThreshold;
                    const requiredFrames = this.sensitivityConfig.frames;

                    // If exam is running and not locked, detect sustained conversation / talking
                    if (this.examStarted && !this.isLocked) {
                        if (voiceAvg > threshold) {
                            this.consecutiveSpeechFrames++;
                            if (this.consecutiveSpeechFrames >= requiredFrames) {
                                const now = Date.now();
                                if (now - this.lastVoiceViolationTime > 7000) { // 7s cooldown
                                    this.lastVoiceViolationTime = now;
                                    this.consecutiveSpeechFrames = 0;
                                    this.handleViolation('voice_detected', 'Terdeteksi suara obrolan/percakapan atau pertanyaan saat ujian (' + this.sensitivityConfig.label + ')');
                                }
                            }
                        } else {
                            if (this.consecutiveSpeechFrames > 0) {
                                this.consecutiveSpeechFrames = Math.max(0, this.consecutiveSpeechFrames - 2);
                            }
                        }
                    }
                }, 100);
            } catch (e) {
                console.warn('Audio monitoring init error:', e);
            }
        },

        async startExamSession() {
            if (this.requireWebcam && !this.cameraReady) {
                alert('Anda harus memberikan izin akses kamera sebelum dapat memulai ujian!');
                return;
            }

            if (this.requireMicrophone && !this.micReady) {
                alert('Anda harus memberikan izin akses mikrofon (On Mic) sebelum dapat memulai ujian!');
                return;
            }

            if (this.audioContext && this.audioContext.state === 'suspended') {
                try {
                    await this.audioContext.resume();
                } catch (e) {
                    console.warn('AudioContext resume error:', e);
                }
            }

            if (this.enableBrowserLockdown) {
                try {
                    if (document.documentElement.requestFullscreen && !document.fullscreenElement) {
                        await document.documentElement.requestFullscreen();
                    }
                } catch (e) {
                    console.warn('Fullscreen request failed:', e);
                }
            }

            this.showPreExamModal = false;
            this.examStarted = true;

            this.$nextTick(() => {
                const pipVideo = document.getElementById('floatingProctorVideo');
                if (pipVideo && this.mediaStream && this.requireWebcam) {
                    pipVideo.srcObject = this.mediaStream;
                }
                if (typeof feather !== 'undefined') feather.replace();
            });

            // Log event: exam_started
            this.sendProctoringEvent('exam_started', 'Peserta memulai sesi ujian');

            // Take initial snapshot and start 60-second periodic snapshots
            if (this.requireWebcam) {
                setTimeout(() => this.captureAndSendSnapshot(), 3000);
                this.snapshotInterval = setInterval(() => {
                    if (this.examStarted && !this.isLocked) {
                        this.captureAndSendSnapshot();
                    }
                }, 60000);
            }
        },

        captureSnapshotBase64() {
            const video = document.getElementById('floatingProctorVideo') || document.getElementById('preExamVideo');
            if (!video || !this.mediaStream) return null;

            try {
                const canvas = document.getElementById('proctoringCanvas');
                if (!canvas) return null;

                const ctx = canvas.getContext('2d');
                canvas.width = 320;
                canvas.height = 240;
                ctx.drawImage(video, 0, 0, 320, 240);
                return canvas.toDataURL('image/jpeg', 0.65);
            } catch (e) {
                console.warn('Snapshot capture failed:', e);
                return null;
            }
        },

        async captureAndSendSnapshot() {
            if (!this.requireWebcam || !this.mediaStream) return;
            const snap = this.captureSnapshotBase64();
            if (!snap) return;

            try {
                await fetch("{{ route('pbm.quizzes.attempt.proctoring-snapshot', $attempt->id) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ snapshot: snap })
                });
            } catch (e) {
                console.warn('Snapshot upload error:', e);
            }
        },

        setupLockdownGuards() {
            // Fullscreen exit detection
            document.addEventListener('fullscreenchange', () => {
                if (!document.fullscreenElement && this.examStarted && !this.isLocked) {
                    this.handleViolation('fullscreen_exit', 'Keluar dari mode layar penuh (Fullscreen)');
                }
            });

            // Visibility / tab switch detection
            document.addEventListener('visibilitychange', () => {
                if (document.hidden && this.examStarted && !this.isLocked) {
                    this.handleViolation('tab_switch', 'Berpindah tab browser atau minimize');
                }
            });

            // Window blur detection
            window.addEventListener('blur', () => {
                if (this.examStarted && !this.isLocked) {
                    this.handleViolation('window_blur', 'Jendela browser kehilangan fokus');
                }
            });

            // Copy, Cut, Paste prevention
            document.addEventListener('copy', (e) => {
                if (this.examStarted && !this.isLocked) {
                    e.preventDefault();
                    this.handleViolation('copy_paste', 'Mencoba menyalin teks (Copy)');
                }
            });
            document.addEventListener('paste', (e) => {
                if (this.examStarted && !this.isLocked) {
                    e.preventDefault();
                    this.handleViolation('copy_paste', 'Mencoba menempelkan teks (Paste)');
                }
            });
            document.addEventListener('cut', (e) => {
                if (this.examStarted && !this.isLocked) {
                    e.preventDefault();
                }
            });

            // Context Menu (Right click) prevention
            document.addEventListener('contextmenu', (e) => {
                if (this.examStarted && !this.isLocked) {
                    e.preventDefault();
                    this.handleViolation('right_click', 'Membuka menu klik kanan');
                }
            });

            // Keyboard shortcut prevention (DevTools, Print, Save, Source)
            document.addEventListener('keydown', (e) => {
                if (!this.examStarted || this.isLocked) return;
                if (
                    e.key === 'F12' || 
                    (e.ctrlKey && e.shiftKey && ['I','i','J','j','C','c'].includes(e.key)) ||
                    (e.ctrlKey && ['u','U','s','S','p','P'].includes(e.key))
                ) {
                    e.preventDefault();
                    this.handleViolation('devtools_open', 'Mencoba membuka inspect element / tombol pintas (' + e.key + ')');
                }
            });
        },

        async handleViolation(eventType, message) {
            if (!this.examStarted || this.isLocked) return;

            const now = Date.now();
            // Debounce: minimum 3.5 seconds between recorded violations
            if (now - this.lastViolationTime < 3500) {
                return;
            }
            this.lastViolationTime = now;

            this.lastViolationType = eventType;
            this.lastViolationMessage = message;

            const snap = this.captureSnapshotBase64();

            try {
                const response = await fetch("{{ route('pbm.quizzes.attempt.proctoring-event', $attempt->id) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        event_type: eventType,
                        details: {
                            description: message,
                            time: new Date().toISOString()
                        },
                        snapshot: snap
                    })
                });

                const data = await response.json();
                if (data.success) {
                    this.violationCount = data.violation_count;
                    if (data.is_locked) {
                        this.isLocked = true;
                        this.lockReason = data.lock_reason || 'Batas toleransi pelanggaran tercapai.';
                        if (this.timerInterval) clearInterval(this.timerInterval);
                        if (this.snapshotInterval) clearInterval(this.snapshotInterval);
                        this.stopMediaAndAudio();
                        this.showViolationModal = false;
                        return;
                    }
                }
            } catch (e) {
                console.warn('Violation log error:', e);
                this.violationCount++;
            }

            this.showViolationModal = true;
            this.$nextTick(() => {
                if (typeof feather !== 'undefined') feather.replace();
            });
        },

        async sendProctoringEvent(eventType, description) {
            try {
                await fetch("{{ route('pbm.quizzes.attempt.proctoring-event', $attempt->id) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        event_type: eventType,
                        details: { description: description }
                    })
                });
            } catch (e) {
                console.warn('Proctoring event error:', e);
            }
        },

        async reEnterFullscreen() {
            this.showViolationModal = false;
            if (this.enableBrowserLockdown) {
                try {
                    if (document.documentElement.requestFullscreen && !document.fullscreenElement) {
                        await document.documentElement.requestFullscreen();
                    }
                } catch (e) {
                    console.warn('Fullscreen re-entry failed:', e);
                }
            }
        },

        goToQuestion(idx) {
            this.currentIndex = idx;
            this.$nextTick(() => {
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            });
        },

        prevQuestion() {
            if (this.currentIndex > 0) {
                this.goToQuestion(this.currentIndex - 1);
            }
        },

        nextQuestion() {
            if (this.currentIndex < this.totalQuestions - 1) {
                this.goToQuestion(this.currentIndex + 1);
            }
        },

        toggleFlag(qId) {
            this.flagged[qId] = !this.flagged[qId];
            this.$nextTick(() => {
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            });
        },

        selectAnswer(questionId, optionId) {
            this.answers[questionId] = optionId;
            this.saveAnswerAjax(questionId, optionId);
        },

        async saveAnswerAjax(questionId, optionId) {
            this.saving = true;
            this.saveStatus = 'Menyimpan...';

            try {
                const response = await fetch("{{ route('pbm.quizzes.attempt.save-answer', $attempt->id) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        question_id: questionId,
                        option_id: optionId
                    })
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    this.saving = false;
                    this.saveStatus = 'Tersimpan ' + (data.saved_at || '');
                } else {
                    this.saving = false;
                    this.saveStatus = 'Gagal menyimpan';
                }
            } catch (err) {
                console.error('Save answer error:', err);
                this.saving = false;
                this.saveStatus = 'Koneksi terganggu';
            }
        },

        startTimer() {
            this.timerInterval = setInterval(() => {
                if (this.remainingSeconds > 0) {
                    this.remainingSeconds--;
                    this.updateTimerDisplay();
                } else {
                    clearInterval(this.timerInterval);
                    this.timerDisplay = '00:00';
                    alert('Waktu pengerjaan kuis telah habis! Jawaban Anda akan otomatis dikumpulkan.');
                    this.stopMediaAndAudio();
                    document.getElementById('submitAttemptForm').submit();
                }
            }, 1000);
        },

        stopMediaAndAudio() {
            if (this.audioMonitoringInterval) {
                clearInterval(this.audioMonitoringInterval);
                this.audioMonitoringInterval = null;
            }
            if (this.audioContext) {
                try {
                    this.audioContext.close();
                } catch (e) {}
                this.audioContext = null;
            }
            if (this.mediaStream) {
                this.mediaStream.getTracks().forEach(t => t.stop());
                this.mediaStream = null;
            }
        },

        updateTimerDisplay() {
            if (this.remainingSeconds === null) {
                this.timerDisplay = '--:--';
                return;
            }
            const hours = Math.floor(this.remainingSeconds / 3600);
            const minutes = Math.floor((this.remainingSeconds % 3600) / 60);
            const seconds = this.remainingSeconds % 60;

            if (hours > 0) {
                this.timerDisplay = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            } else {
                this.timerDisplay = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }
        },

        openConfirmModal() {
            this.showConfirmModal = true;
            this.$nextTick(() => {
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            });
        }
    };
}
</script>
@endpush
