@extends('pbm.layouts.app')

@section('fullwidth', true)

@push('head')
<style>
    .cbt-review-item {
        border-width: 2px !important;
        border-style: solid !important;
        border-radius: 0.75rem !important;
        padding: 1rem !important;
        transition: all 0.15s ease !important;
    }
    .cbt-review-item.is-correct-key {
        background-color: #f0fdf4 !important; /* Emerald 50 */
        border-color: #34d399 !important; /* Emerald 400 */
        color: #064e3b !important;
    }
    .cbt-review-item.is-wrong-choice {
        background-color: #fff1f2 !important; /* Rose 50 */
        border-color: #fb7185 !important; /* Rose 400 */
        color: #881337 !important;
    }
    .cbt-review-item.is-neutral {
        background-color: #f9fafb !important;
        border-color: #e5e7eb !important;
        color: #374151 !important;
    }

    .cbt-review-badge {
        width: 1.85rem !important;
        height: 1.85rem !important;
        border-radius: 9999px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-weight: 800 !important;
        font-size: 0.85rem !important;
        flex-shrink: 0 !important;
    }
    .cbt-review-badge.bg-correct {
        background-color: #059669 !important;
        color: #ffffff !important;
    }
    .cbt-review-badge.bg-wrong {
        background-color: #e11d48 !important;
        color: #ffffff !important;
    }
    .cbt-review-badge.bg-neutral {
        background-color: #e5e7eb !important;
        color: #4b5563 !important;
    }
</style>
@endpush

@section('content')
<div class="max-w-5xl mx-auto pb-16">
    <!-- Back to Quiz Link -->
    <a href="{{ route('pbm.quizzes.show', $quiz->id) }}" class="inline-flex items-center text-sm font-semibold text-sky-600 hover:text-sky-700 mb-6 transition">
        <i data-feather="arrow-left" class="w-4 h-4 mr-1"></i> Kembali ke Detail Kuis
    </a>

    <!-- Result Banner -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden mb-8">
        <div class="p-8 md:p-10 text-white relative overflow-hidden" 
             style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #2563eb 100%);">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider backdrop-blur-sm inline-block mb-3">
                        Hasil Evaluasi CBT
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white mb-2">{{ $quiz->title }}</h1>
                    <p class="text-purple-100 text-sm max-w-xl">
                        Kuis telah selesai dikerjakan pada {{ $attempt->finished_at ? $attempt->finished_at->format('d M Y, H:i') : '-' }} WIB.
                    </p>
                </div>

                <!-- Score Circle / Badge -->
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20 text-center min-w-[180px] shadow-lg">
                    <span class="text-xs uppercase tracking-wider font-semibold text-purple-200 block mb-1">Nilai Akhir</span>
                    <div class="text-5xl font-black text-white tracking-tight">
                        {{ number_format($attempt->score, 1) }}
                    </div>
                    <span class="text-xs text-purple-200 block mt-1">Skala 100</span>
                </div>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="p-6 md:p-8 bg-gray-50/70 border-t border-gray-100 grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm text-center">
                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-2">
                    <i data-feather="check" class="w-4 h-4"></i>
                </div>
                <div class="text-2xl font-black text-emerald-600">{{ $correctCount }}</div>
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mt-0.5">Benar</div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm text-center">
                <div class="w-8 h-8 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-2">
                    <i data-feather="x" class="w-4 h-4"></i>
                </div>
                <div class="text-2xl font-black text-rose-600">{{ $incorrectCount }}</div>
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mt-0.5">Salah</div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm text-center">
                <div class="w-8 h-8 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center mx-auto mb-2">
                    <i data-feather="minus" class="w-4 h-4"></i>
                </div>
                <div class="text-2xl font-black text-gray-700">{{ $unansweredCount }}</div>
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mt-0.5">Kosong</div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm text-center">
                <div class="w-8 h-8 rounded-full bg-sky-100 text-sky-600 flex items-center justify-center mx-auto mb-2">
                    <i data-feather="clock" class="w-4 h-4"></i>
                </div>
                <div class="text-base font-bold text-gray-900 mt-1">{{ $durationFormatted }}</div>
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mt-1">Durasi</div>
            </div>
        </div>
    </div>

    <!-- Review Section -->
    @if($quiz->show_results || $isPrivileged)
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-extrabold text-gray-900 flex items-center gap-2">
                <i data-feather="book-open" class="w-5 h-5 text-purple-600"></i> Pembahasan Soal & Rasional Jawaban
            </h2>
            <span class="text-xs font-semibold px-3 py-1 bg-purple-50 text-purple-700 rounded-full border border-purple-200">
                Total {{ $totalQuestions }} Soal
            </span>
        </div>

        @foreach($quiz->questions as $index => $question)
        @php
            $myAnswer = $answersByQuestion->get($question->id);
            $selectedOptionId = $myAnswer ? $myAnswer->pbm_question_option_id : null;
            $isCorrectAnswer = $myAnswer ? (bool)$myAnswer->is_correct : false;
        @endphp
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 md:p-8 space-y-5">
            <!-- Question Status Header -->
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div class="flex items-center gap-3">
                    <span class="font-bold text-gray-800 text-base">
                        Soal No. {{ $index + 1 }}
                    </span>
                    @if($selectedOptionId === null)
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200 flex items-center gap-1">
                            <i data-feather="minus-circle" class="w-3.5 h-3.5"></i> Tidak Dijawab
                        </span>
                    @elseif($isCorrectAnswer)
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                            <i data-feather="check-circle" class="w-3.5 h-3.5"></i> Jawaban Benar (+{{ $question->points ?? 1 }} Poin)
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700 border border-rose-200 flex items-center gap-1">
                            <i data-feather="x-circle" class="w-3.5 h-3.5"></i> Jawaban Salah (0 Poin)
                        </span>
                    @endif
                </div>
                <div class="text-xs text-gray-400 font-medium">
                    Bobot: {{ $question->points ?? 1 }} Poin
                </div>
            </div>

            <!-- Vignette -->
            @if($question->vignette)
            <div class="bg-blue-50/60 rounded-xl p-5 border border-blue-100 text-gray-900 text-sm leading-relaxed">
                <div class="flex items-center gap-1.5 font-bold text-blue-900 mb-2">
                    <i data-feather="file-text" class="w-4 h-4 text-blue-600"></i>
                    <span>Kasus / Vignette:</span>
                </div>
                <div class="prose prose-sm max-w-none text-gray-800">
                    {!! $question->vignette !!}
                </div>
            </div>
            @endif

            <!-- Question Lead-in -->
            <div class="text-base font-semibold text-gray-900 leading-relaxed prose max-w-none">
                {!! $question->question_text !!}
            </div>

            <!-- Options Review with Rationale -->
            <div class="space-y-3 pt-2">
                @foreach($question->options as $optIdx => $option)
                @php
                    $isKey = (bool)$option->is_correct;
                    $isChosen = ($selectedOptionId === $option->id);
                    $label = chr(65 + $optIdx);

                    if ($isKey) {
                        $cardClass = 'cbt-review-item is-correct-key';
                        $circleClass = 'cbt-review-badge bg-correct';
                    } elseif ($isChosen && !$isKey) {
                        $cardClass = 'cbt-review-item is-wrong-choice';
                        $circleClass = 'cbt-review-badge bg-wrong';
                    } else {
                        $cardClass = 'cbt-review-item is-neutral';
                        $circleClass = 'cbt-review-badge bg-neutral';
                    }
                @endphp
                <div class="{{ $cardClass }}">
                    <div class="flex items-start gap-3.5">
                        <div class="{{ $circleClass }}">
                            {{ $label }}
                        </div>

                        <div class="flex-1 text-sm leading-relaxed pt-0.5 prose prose-sm max-w-none">
                            {!! $option->option_text !!}
                        </div>

                        <!-- Badges -->
                        <div class="flex items-center gap-2 shrink-0">
                            @if($isChosen && $isKey)
                                <span class="px-2 py-0.5 rounded-md text-xs font-bold bg-emerald-600 text-white flex items-center gap-1">
                                    <i data-feather="check" class="w-3 h-3"></i> Pilihan Anda & Kunci
                                </span>
                            @elseif($isChosen && !$isKey)
                                <span class="px-2 py-0.5 rounded-md text-xs font-bold bg-rose-600 text-white flex items-center gap-1">
                                    <i data-feather="x" class="w-3 h-3"></i> Pilihan Anda (Salah)
                                </span>
                            @elseif($isKey)
                                <span class="px-2 py-0.5 rounded-md text-xs font-bold bg-emerald-500 text-white flex items-center gap-1">
                                    <i data-feather="check" class="w-3 h-3"></i> Kunci Jawaban
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Rationale / Pembahasan -->
                    @if($option->rationale)
                    <div class="mt-3 ml-10 p-3 rounded-lg bg-white/90 border border-gray-200/80 text-xs text-gray-700 leading-relaxed">
                        <div class="font-bold flex items-center gap-1 mb-1 text-gray-800">
                            <i data-feather="info" class="w-3.5 h-3.5 text-sky-600"></i> Rasional Klinis:
                        </div>
                        <div class="text-gray-700">
                            {!! nl2br(e($option->rationale)) !!}
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center">
        <i data-feather="lock" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Pembahasan Ditutup</h3>
        <p class="text-gray-500 text-sm">
            Pengajar menonaktifkan tampilan kunci dan pembahasan soal untuk kuis ini.
        </p>
    </div>
    @endif

    <!-- Bottom Actions -->
    <div class="flex justify-center mt-10">
        <a href="{{ route('pbm.quizzes.show', $quiz->id) }}" 
           class="px-8 py-3.5 bg-gray-900 hover:bg-gray-800 text-white rounded-xl font-bold shadow-md transition flex items-center gap-2">
            <i data-feather="arrow-left" class="w-4 h-4"></i> Selesai & Kembali ke Kuis
        </a>
    </div>
</div>
@endsection
