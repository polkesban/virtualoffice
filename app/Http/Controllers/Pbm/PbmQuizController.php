<?php

namespace App\Http\Controllers\Pbm;

use App\Http\Controllers\Controller;
use App\Models\ClinicalClass;
use App\Models\ClinicalSession;
use App\Models\PbmQuiz;
use App\Models\PbmQuizAttempt;
use App\Models\PbmQuizAnswer;
use App\Models\PbmQuestion;
use App\Models\PbmQuestionOption;
use App\Models\Mahasiswa;
use App\Models\PbmQuizProctoringLog;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PbmQuizController extends Controller
{
    public function index(ClinicalClass $class, ClinicalSession $session)
    {
        $quizzes = $session->quizzes()->latest()->get();
        return view('pbm.quizzes.index', compact('session', 'quizzes'));
    }

    public function store(Request $request, ClinicalClass $class, ClinicalSession $session)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
            'duration_minutes' => 'nullable|integer|min:1',
            'shuffle_questions' => 'boolean',
            'show_results' => 'boolean',
            'enable_proctoring' => 'boolean',
            'require_webcam' => 'boolean',
            'require_microphone' => 'boolean',
            'mic_sensitivity' => 'nullable|string|in:low,medium,high',
            'enable_browser_lockdown' => 'boolean',
            'max_violations' => 'nullable|integer|min:1|max:100',
            'require_seb' => 'boolean',
            'seb_quit_password' => 'nullable|string|max:50',
        ]);
        
        $data['created_by'] = auth()->id();
        $data['enable_proctoring'] = $request->boolean('enable_proctoring');
        $data['require_webcam'] = $request->boolean('require_webcam');
        $data['require_microphone'] = $request->boolean('require_microphone');
        $data['mic_sensitivity'] = in_array($request->mic_sensitivity, ['low', 'medium', 'high'], true) ? $request->mic_sensitivity : 'medium';
        $data['enable_browser_lockdown'] = $request->boolean('enable_browser_lockdown');
        $data['require_seb'] = $request->boolean('require_seb');
        $data['shuffle_questions'] = $request->boolean('shuffle_questions');
        $data['show_results'] = $request->boolean('show_results');
        $data['max_violations'] = $request->filled('max_violations') ? (int) $request->max_violations : 3;
        $data['seb_quit_password'] = $request->filled('seb_quit_password') ? $request->seb_quit_password : null;

        $session->quizzes()->create($data);
        
        return back()->with('success', 'Kuis berhasil ditambahkan.');
    }

    public function edit(PbmQuiz $quiz)
    {
        $user = Auth::user();
        $isPrivileged = $user && ($user->isSuperAdmin() || in_array($user->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum']) || $quiz->created_by === $user->id);
        if (!$isPrivileged) {
            abort(403, 'Akses ditolak.');
        }

        return view('pbm.quizzes.edit', compact('quiz'));
    }

    public function update(Request $request, PbmQuiz $quiz)
    {
        $user = Auth::user();
        $isPrivileged = $user && ($user->isSuperAdmin() || in_array($user->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum']) || $quiz->created_by === $user->id);
        if (!$isPrivileged) {
            abort(403, 'Akses ditolak.');
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
            'duration_minutes' => 'nullable|integer|min:1',
            'shuffle_questions' => 'boolean',
            'show_results' => 'boolean',
            'enable_proctoring' => 'boolean',
            'require_webcam' => 'boolean',
            'require_microphone' => 'boolean',
            'mic_sensitivity' => 'nullable|string|in:low,medium,high',
            'enable_browser_lockdown' => 'boolean',
            'max_violations' => 'nullable|integer|min:1|max:100',
            'require_seb' => 'boolean',
            'seb_quit_password' => 'nullable|string|max:50',
        ]);

        $data['enable_proctoring'] = $request->boolean('enable_proctoring');
        $data['require_webcam'] = $request->boolean('require_webcam');
        $data['require_microphone'] = $request->boolean('require_microphone');
        $data['mic_sensitivity'] = in_array($request->mic_sensitivity, ['low', 'medium', 'high'], true) ? $request->mic_sensitivity : 'medium';
        $data['enable_browser_lockdown'] = $request->boolean('enable_browser_lockdown');
        $data['require_seb'] = $request->boolean('require_seb');
        $data['shuffle_questions'] = $request->boolean('shuffle_questions');
        $data['show_results'] = $request->boolean('show_results');
        $data['max_violations'] = $request->filled('max_violations') ? (int) $request->max_violations : 3;
        $data['seb_quit_password'] = $request->filled('seb_quit_password') ? $request->seb_quit_password : null;

        $quiz->update($data);

        return redirect()->route('pbm.quizzes.show', $quiz->id)
            ->with('success', 'Pengaturan kuis & proktoring berhasil diperbarui.');
    }

    public function downloadSebConfig(PbmQuiz $quiz)
    {
        $startUrl = route('pbm.quizzes.show', $quiz->id);
        $quitUrl = route('pbm.quizzes.show', $quiz->id);
        $hashedPassword = $quiz->seb_quit_password ? strtoupper(hash('sha256', $quiz->seb_quit_password)) : '';

        $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        $xml .= '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">' . "\n";
        $xml .= '<plist version="1.0">' . "\n";
        $xml .= '<dict>' . "\n";
        $xml .= '    <key>originatorVersion</key>' . "\n";
        $xml .= '    <string>SEB_Win_3.4.1</string>' . "\n";
        $xml .= '    <key>startURL</key>' . "\n";
        $xml .= '    <string>' . htmlspecialchars($startUrl, ENT_XML1, 'UTF-8') . '</string>' . "\n";
        $xml .= '    <key>allowQuit</key>' . "\n";
        $xml .= '    <true/>' . "\n";
        $xml .= '    <key>quitURL</key>' . "\n";
        $xml .= '    <string>' . htmlspecialchars($quitUrl, ENT_XML1, 'UTF-8') . '</string>' . "\n";
        $xml .= '    <key>quitURLConfirm</key>' . "\n";
        $xml .= '    <true/>' . "\n";
        if ($hashedPassword) {
            $xml .= '    <key>hashedQuitPassword</key>' . "\n";
            $xml .= '    <string>' . $hashedPassword . '</string>' . "\n";
        }
        $xml .= '    <key>browserWindowAllowReload</key>' . "\n";
        $xml .= '    <true/>' . "\n";
        $xml .= '    <key>enableRightMouse</key>' . "\n";
        $xml .= '    <false/>' . "\n";
        $xml .= '    <key>allowSpellCheck</key>' . "\n";
        $xml .= '    <false/>' . "\n";
        $xml .= '    <key>allowDictionaryLookup</key>' . "\n";
        $xml .= '    <false/>' . "\n";
        $xml .= '    <key>allowFlashFullscreen</key>' . "\n";
        $xml .= '    <false/>' . "\n";
        $xml .= '    <key>allowUserSwitching</key>' . "\n";
        $xml .= '    <false/>' . "\n";
        $xml .= '    <key>enablePrintScreen</key>' . "\n";
        $xml .= '    <false/>' . "\n";
        $xml .= '</dict>' . "\n";
        $xml .= '</plist>';

        $safeTitle = Str::slug($quiz->title) ?: 'kuis-' . $quiz->id;
        $filename = $safeTitle . '-seb-config.seb';

        return response($xml, 200, [
            'Content-Type' => 'application/x-seb',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function show(PbmQuiz $quiz)
    {
        $quiz->load(['questions.options', 'attempts.mahasiswa', 'attempts.user']);
        
        $user = Auth::user();
        $myAttempt = null;
        if ($user) {
            $mahasiswa = Mahasiswa::where('nim', $user->username)
                ->orWhere('email', $user->email)
                ->first();
            $mahasiswaId = $mahasiswa ? $mahasiswa->id : null;

            $myAttempt = PbmQuizAttempt::where('pbm_quiz_id', $quiz->id)
                ->where(function($q) use ($user, $mahasiswaId) {
                    $q->where('user_id', $user->id);
                    if ($mahasiswaId) {
                        $q->orWhere('mahasiswa_id', $mahasiswaId);
                    }
                })
                ->latest()
                ->first();
        }

        return view('pbm.quizzes.show', compact('quiz', 'myAttempt'));
    }

    public function startAttempt(PbmQuiz $quiz)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($quiz->questions()->count() === 0) {
            return back()->with('error', 'Kuis ini belum memiliki butir soal.');
        }

        $isPrivileged = $user->isSuperAdmin() || in_array($user->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum']);
        if ($quiz->require_seb && !$isPrivileged) {
            $userAgent = request()->header('User-Agent') ?? '';
            if (stripos($userAgent, 'SEB') === false) {
                return redirect()->route('pbm.quizzes.show', $quiz->id)
                    ->with('error', 'Kuis ini diwajibkan menggunakan Safe Exam Browser (SEB). Silakan buka kuis melalui aplikasi SEB atau unduh file konfigurasinya.');
            }
        }

        if (!$isPrivileged) {
            if ($quiz->start_time && now()->lt($quiz->start_time)) {
                return back()->with('error', 'Kuis belum dibuka. Jadwal pengerjaan: ' . $quiz->start_time->format('d M Y, H:i'));
            }
            if ($quiz->end_time && now()->gt($quiz->end_time)) {
                return back()->with('error', 'Kuis telah ditutup pada ' . $quiz->end_time->format('d M Y, H:i'));
            }
        }

        $mahasiswa = Mahasiswa::where('nim', $user->username)
            ->orWhere('email', $user->email)
            ->first();
        $mahasiswaId = $mahasiswa ? $mahasiswa->id : null;

        // Check if there is an active ongoing attempt
        $existingAttempt = PbmQuizAttempt::where('pbm_quiz_id', $quiz->id)
            ->where(function($q) use ($user, $mahasiswaId) {
                $q->where('user_id', $user->id);
                if ($mahasiswaId) {
                    $q->orWhere('mahasiswa_id', $mahasiswaId);
                }
            })
            ->whereNull('finished_at')
            ->latest()
            ->first();

        if ($existingAttempt) {
            if ($quiz->duration_minutes) {
                $elapsedSeconds = now()->diffInSeconds($existingAttempt->started_at);
                $totalAllowed = $quiz->duration_minutes * 60;
                if ($elapsedSeconds > $totalAllowed) {
                    $this->finalizeAttempt($existingAttempt);
                    if (!$isPrivileged) {
                        return redirect()->route('pbm.quizzes.attempt.result', [$quiz->id, $existingAttempt->id])
                            ->with('info', 'Waktu pengerjaan sebelumnya telah habis.');
                    }
                } else {
                    return redirect()->route('pbm.quizzes.attempt.take', [$quiz->id, $existingAttempt->id]);
                }
            } else {
                return redirect()->route('pbm.quizzes.attempt.take', [$quiz->id, $existingAttempt->id]);
            }
        }

        // For non-privileged users, check if already finished
        $completedAttempt = PbmQuizAttempt::where('pbm_quiz_id', $quiz->id)
            ->where(function($q) use ($user, $mahasiswaId) {
                $q->where('user_id', $user->id);
                if ($mahasiswaId) {
                    $q->orWhere('mahasiswa_id', $mahasiswaId);
                }
            })
            ->whereNotNull('finished_at')
            ->latest()
            ->first();

        if ($completedAttempt && !$isPrivileged) {
            return redirect()->route('pbm.quizzes.attempt.result', [$quiz->id, $completedAttempt->id])
                ->with('info', 'Anda telah menyelesaikan kuis ini.');
        }

        // Create new attempt
        $attempt = PbmQuizAttempt::create([
            'pbm_quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'mahasiswa_id' => $mahasiswaId,
            'started_at' => now(),
        ]);

        return redirect()->route('pbm.quizzes.attempt.take', [$quiz->id, $attempt->id]);
    }

    public function takeAttempt(PbmQuiz $quiz, PbmQuizAttempt $attempt)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($attempt->pbm_quiz_id !== $quiz->id) {
            abort(404);
        }

        $isOwner = ($attempt->user_id === $user->id) || ($attempt->mahasiswa && $attempt->mahasiswa->nim === $user->username);
        $isPrivileged = $user->isSuperAdmin() || in_array($user->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum']);
        if (!$isOwner && !$isPrivileged) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if ($quiz->require_seb && !$isPrivileged) {
            $userAgent = request()->header('User-Agent') ?? '';
            if (stripos($userAgent, 'SEB') === false) {
                return redirect()->route('pbm.quizzes.show', $quiz->id)
                    ->with('error', 'Akses ditolak: Kuis ini mewajibkan penggunaan Safe Exam Browser (SEB).');
            }
        }

        if ($attempt->is_locked && !$isPrivileged) {
            return response()->view('pbm.quizzes.locked', compact('quiz', 'attempt'), 403);
        }

        if ($attempt->finished_at) {
            return redirect()->route('pbm.quizzes.attempt.result', [$quiz->id, $attempt->id]);
        }

        $remainingSeconds = null;
        if ($quiz->duration_minutes) {
            $elapsed = now()->diffInSeconds($attempt->started_at);
            $totalSeconds = $quiz->duration_minutes * 60;
            $remainingSeconds = max(0, $totalSeconds - $elapsed);

            if ($remainingSeconds <= 0) {
                $this->finalizeAttempt($attempt);
                return redirect()->route('pbm.quizzes.attempt.result', [$quiz->id, $attempt->id])
                    ->with('info', 'Waktu pengerjaan kuis telah habis.');
            }
        }

        $questions = $quiz->questions()->with('options')->orderBy('order')->orderBy('id')->get();
        if ($quiz->shuffle_questions) {
            $questions = $questions->shuffle($attempt->id);
        }

        $existingAnswers = PbmQuizAnswer::where('pbm_quiz_attempt_id', $attempt->id)
            ->pluck('pbm_question_option_id', 'pbm_question_id')
            ->toArray();

        return view('pbm.quizzes.take', compact('quiz', 'attempt', 'questions', 'existingAnswers', 'remainingSeconds'));
    }

    public function saveAnswer(Request $request, PbmQuizAttempt $attempt)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $isOwner = ($attempt->user_id === $user->id) || ($attempt->mahasiswa && $attempt->mahasiswa->nim === $user->username);
        $isPrivileged = $user->isSuperAdmin() || in_array($user->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum']);
        if (!$isOwner && !$isPrivileged) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if ($attempt->finished_at) {
            return response()->json(['error' => 'Kuis sudah selesai.'], 400);
        }

        $quiz = $attempt->quiz;
        if ($quiz->duration_minutes) {
            $elapsed = now()->diffInSeconds($attempt->started_at);
            if ($elapsed > ($quiz->duration_minutes * 60) + 15) {
                $this->finalizeAttempt($attempt);
                return response()->json(['error' => 'Waktu kuis telah habis.'], 400);
            }
        }

        $validated = $request->validate([
            'question_id' => 'required|integer|exists:pbm_questions,id',
            'option_id' => 'required|integer|exists:pbm_question_options,id',
        ]);

        $question = PbmQuestion::where('id', $validated['question_id'])
            ->where('pbm_quiz_id', $quiz->id)
            ->firstOrFail();

        $option = PbmQuestionOption::where('id', $validated['option_id'])
            ->where('pbm_question_id', $question->id)
            ->firstOrFail();

        $isCorrect = (bool) $option->is_correct;
        $points = $isCorrect ? ($question->points ?? 1) : 0;

        PbmQuizAnswer::updateOrCreate(
            [
                'pbm_quiz_attempt_id' => $attempt->id,
                'pbm_question_id' => $question->id,
            ],
            [
                'pbm_question_option_id' => $option->id,
                'is_correct' => $isCorrect,
                'points_awarded' => $points,
            ]
        );

        return response()->json([
            'success' => true,
            'question_id' => $question->id,
            'option_id' => $option->id,
            'saved_at' => now()->format('H:i:s'),
        ]);
    }

    public function submitAttempt(Request $request, PbmQuizAttempt $attempt)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $isOwner = ($attempt->user_id === $user->id) || ($attempt->mahasiswa && $attempt->mahasiswa->nim === $user->username);
        $isPrivileged = $user->isSuperAdmin() || in_array($user->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum']);
        if (!$isOwner && !$isPrivileged) {
            abort(403, 'Akses ditolak.');
        }

        if (!$attempt->finished_at) {
            $this->finalizeAttempt($attempt);
        }

        return redirect()->route('pbm.quizzes.attempt.result', [$attempt->pbm_quiz_id, $attempt->id])
            ->with('success', 'Kuis berhasil diselesaikan!');
    }

    protected function finalizeAttempt(PbmQuizAttempt $attempt)
    {
        $quiz = $attempt->quiz()->with('questions')->first();
        $answers = $attempt->answers()->get();

        $totalPossible = $quiz->questions->sum(function($q) {
            return ($q->points && $q->points > 0) ? $q->points : 1;
        });

        $earned = $answers->sum('points_awarded');
        $score = $totalPossible > 0 ? ($earned / $totalPossible) * 100 : 0;

        $attempt->update([
            'finished_at' => now(),
            'score' => round($score, 2),
        ]);

        return $attempt;
    }

    public function showResult(PbmQuiz $quiz, PbmQuizAttempt $attempt)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($attempt->pbm_quiz_id !== $quiz->id) {
            abort(404);
        }

        $isOwner = ($attempt->user_id === $user->id) || ($attempt->mahasiswa && $attempt->mahasiswa->nim === $user->username);
        $isPrivileged = $user->isSuperAdmin() || in_array($user->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum']);
        if (!$isOwner && !$isPrivileged) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if (!$attempt->finished_at) {
            return redirect()->route('pbm.quizzes.attempt.take', [$quiz->id, $attempt->id]);
        }

        $quiz->load(['questions.options']);
        $attempt->load('answers.option');

        $totalQuestions = $quiz->questions->count();
        $answeredCount = $attempt->answers->count();
        $correctCount = $attempt->answers->where('is_correct', true)->count();
        $incorrectCount = $answeredCount - $correctCount;
        $unansweredCount = max(0, $totalQuestions - $answeredCount);

        $durationFormatted = '-';
        if ($attempt->started_at && $attempt->finished_at) {
            $diffSeconds = $attempt->started_at->diffInSeconds($attempt->finished_at);
            $minutes = floor($diffSeconds / 60);
            $seconds = $diffSeconds % 60;
            $durationFormatted = ($minutes > 0 ? "{$minutes} Menit " : "") . "{$seconds} Detik";
        }

        $answersByQuestion = $attempt->answers->keyBy('pbm_question_id');

        return view('pbm.quizzes.result', compact(
            'quiz',
            'attempt',
            'totalQuestions',
            'answeredCount',
            'correctCount',
            'incorrectCount',
            'unansweredCount',
            'durationFormatted',
            'answersByQuestion',
            'isPrivileged'
        ));
    }

    public function analysis(PbmQuiz $quiz)
    {
        $analysisData = $this->getQuizAnalysisData($quiz);
        if (!$analysisData) {
            return back()->with('error', 'Belum ada mahasiswa yang mengerjakan kuis ini.');
        }

        return view('pbm.quizzes.analysis', $analysisData);
    }

    public function getQuizAnalysisData(PbmQuiz $quiz)
    {
        $quiz->load(['questions.options', 'attempts.answers']);
        $attempts = $quiz->attempts()->whereNotNull('finished_at')->get();
        if ($attempts->isEmpty()) {
            $attempts = $quiz->attempts;
        }
        $totalAttempts = $attempts->count();

        if ($totalAttempts === 0) {
            return null;
        }

        // Percentage scores (0-100)
        $scores = $attempts->pluck('score')->map(fn($s) => (float)$s)->toArray();
        sort($scores);
        $minScore = min($scores);
        $maxScore = max($scores);
        $meanScore = array_sum($scores) / $totalAttempts;

        // Median score
        $middle = (int) floor(($totalAttempts - 1) / 2);
        if ($totalAttempts % 2) {
            $medianScore = $scores[$middle];
        } else {
            $medianScore = ($scores[$middle] + $scores[$middle + 1]) / 2.0;
        }

        // Variance & SD of scores (0-100)
        $varianceScore = 0;
        foreach ($scores as $s) {
            $varianceScore += pow($s - $meanScore, 2);
        }
        $varianceScore = $totalAttempts > 1 ? $varianceScore / ($totalAttempts - 1) : 0;
        $sdScore = sqrt($varianceScore);

        // Sort attempts by score descending for Upper & Lower group partitioning
        $sortedAttempts = $attempts->sortByDesc('score')->values();

        // Kelley's 27% rule (adaptive for small sample sizes)
        $ng = (int) round($totalAttempts * 0.27);
        if ($ng < 1) {
            $ng = 1;
        }
        if ($totalAttempts >= 2 && $ng > floor($totalAttempts / 2)) {
            $ng = (int) floor($totalAttempts / 2);
        }

        $upperAttempts = $sortedAttempts->slice(0, $ng);
        $lowerAttempts = $sortedAttempts->slice(-$ng);

        $k = $quiz->questions->count();
        $sumPQ = 0;
        $rawScores = array_fill(0, $totalAttempts, 0);

        // First pass: compute raw scores per attempt (number of correct questions) for KR-20
        foreach ($quiz->questions as $question) {
            foreach ($sortedAttempts as $idx => $attempt) {
                $answer = $attempt->answers->where('pbm_question_id', $question->id)->first();
                if ($answer && $answer->is_correct) {
                    $rawScores[$idx]++;
                }
            }
        }

        // Raw score variance
        $meanRaw = array_sum($rawScores) / $totalAttempts;
        $varianceRaw = 0;
        foreach ($rawScores as $raw) {
            $varianceRaw += pow($raw - $meanRaw, 2);
        }
        $varianceRaw = $totalAttempts > 1 ? $varianceRaw / ($totalAttempts - 1) : 0;
        $sdRaw = sqrt($varianceRaw);

        $questionsData = [];
        $summaryCounts = [
            'diterima' => 0,
            'revisi_pengecoh' => 0,
            'revisi_stem' => 0,
            'ditolak' => 0,
        ];

        foreach ($quiz->questions as $index => $question) {
            $correctCount = 0;
            $upperCorrectCount = 0;
            $lowerCorrectCount = 0;
            $attemptScoresCorrect = [];

            // Tally overall and groups
            foreach ($sortedAttempts as $idx => $attempt) {
                $answer = $attempt->answers->where('pbm_question_id', $question->id)->first();
                $isCorrect = ($answer && $answer->is_correct) ? 1 : 0;
                if ($isCorrect) {
                    $correctCount++;
                    $attemptScoresCorrect[] = (float) $attempt->score;
                }
            }

            foreach ($upperAttempts as $attempt) {
                $answer = $attempt->answers->where('pbm_question_id', $question->id)->first();
                if ($answer && $answer->is_correct) {
                    $upperCorrectCount++;
                }
            }

            foreach ($lowerAttempts as $attempt) {
                $answer = $attempt->answers->where('pbm_question_id', $question->id)->first();
                if ($answer && $answer->is_correct) {
                    $lowerCorrectCount++;
                }
            }

            // Difficulty Index (P)
            $p = $totalAttempts > 0 ? ($correctCount / $totalAttempts) : 0;
            $q = 1.0 - $p;
            $sumPQ += ($p * $q);

            // Proportions Upper and Lower
            $pUpper = $ng > 0 ? ($upperCorrectCount / $ng) : 0;
            $pLower = $ng > 0 ? ($lowerCorrectCount / $ng) : 0;

            // Discrimination Index (D = Pu - Pl)
            $d = $pUpper - $pLower;

            // Point Biserial Correlation (r_pbis)
            $rpbis = 0;
            if ($varianceScore > 0 && $p > 0 && $q > 0 && $correctCount > 0) {
                $meanCorrect = array_sum($attemptScoresCorrect) / $correctCount;
                $rpbis = (($meanCorrect - $meanScore) / $sdScore) * sqrt($p / $q);
                // Bounded in [-1, 1]
                $rpbis = max(-1.0, min(1.0, $rpbis));
            }

            // Distractor Analysis per option
            $optionsData = [];
            $letterCode = ord('A');
            $keyLetter = '-';
            $nonFunctioningCount = 0;
            $misleadingCount = 0;
            $totalDistractors = 0;

            foreach ($question->options as $optIndex => $option) {
                $letter = chr($letterCode + $optIndex);
                if ($option->is_correct) {
                    $keyLetter = $letter;
                } else {
                    $totalDistractors++;
                }

                // Overall chosen count
                $optChosenCount = 0;
                foreach ($sortedAttempts as $attempt) {
                    $ans = $attempt->answers->where('pbm_question_id', $question->id)->first();
                    if ($ans && $ans->pbm_question_option_id === $option->id) {
                        $optChosenCount++;
                    }
                }

                // Upper & Lower chosen count
                $optUpperCount = 0;
                foreach ($upperAttempts as $attempt) {
                    $ans = $attempt->answers->where('pbm_question_id', $question->id)->first();
                    if ($ans && $ans->pbm_question_option_id === $option->id) {
                        $optUpperCount++;
                    }
                }

                $optLowerCount = 0;
                foreach ($lowerAttempts as $attempt) {
                    $ans = $attempt->answers->where('pbm_question_id', $question->id)->first();
                    if ($ans && $ans->pbm_question_option_id === $option->id) {
                        $optLowerCount++;
                    }
                }

                $optPct = $totalAttempts > 0 ? ($optChosenCount / $totalAttempts) * 100 : 0;
                $optFreq = $optPct / 100.0;

                // Effectiveness status
                if ($option->is_correct) {
                    $optStatus = 'Kunci Jawaban';
                    $optStatusType = 'key';
                    $isFunctioning = true;
                } else {
                    if ($optPct < 5.0) {
                        $optStatus = 'Tidak Berfungsi (<5%)';
                        $optStatusType = 'non_functioning';
                        $isFunctioning = false;
                        $nonFunctioningCount++;
                    } elseif ($optUpperCount > $optLowerCount) {
                        $optStatus = 'Menyesatkan (U > L)';
                        $optStatusType = 'misleading';
                        $isFunctioning = false;
                        $misleadingCount++;
                    } else {
                        $optStatus = 'Berfungsi Baik';
                        $optStatusType = 'functioning';
                        $isFunctioning = true;
                    }
                }

                // Save to DB
                $option->update([
                    'selection_frequency' => round($optFreq, 4),
                    'is_functioning_distractor' => $isFunctioning,
                ]);

                $optionsData[] = [
                    'id' => $option->id,
                    'letter' => $letter,
                    'text' => $option->option_text,
                    'is_correct' => (bool)$option->is_correct,
                    'count' => $optChosenCount,
                    'percentage' => round($optPct, 1),
                    'count_upper' => $optUpperCount,
                    'count_lower' => $optLowerCount,
                    'status' => $optStatus,
                    'status_type' => $optStatusType,
                    'rationale' => $option->rationale ?: 'Tidak ada catatan pembahasan spesifik untuk opsi ini.',
                ];
            }

            // Categories according to UKOM Standards
            // Difficulty Category
            if ($p < 0.25) {
                $pCategory = 'Sukar';
                $pBadgeColor = 'red';
            } elseif ($p > 0.75) {
                $pCategory = 'Mudah';
                $pBadgeColor = 'blue';
            } else {
                $pCategory = 'Sedang (Ideal UKOM)';
                $pBadgeColor = 'emerald';
            }

            // Discrimination Category
            if ($d >= 0.40) {
                $dCategory = 'Sangat Baik';
                $dBadgeColor = 'emerald';
            } elseif ($d >= 0.30) {
                $dCategory = 'Baik';
                $dBadgeColor = 'teal';
            } elseif ($d >= 0.20) {
                $dCategory = 'Cukup';
                $dBadgeColor = 'amber';
            } elseif ($d >= 0.00) {
                $dCategory = 'Buruk';
                $dBadgeColor = 'rose';
            } else {
                $dCategory = 'Sangat Buruk (Negatif)';
                $dBadgeColor = 'red';
            }

            // UKOM Verdict & Action Notes
            $functioningDistractors = $totalDistractors - $nonFunctioningCount - $misleadingCount;
            $ukomStatus = 'Diterima';
            $diagnosticNotes = '';

            if ($d < 0.00 || $rpbis < -0.05) {
                $ukomStatus = 'Ditolak';
                $diagnosticNotes = 'Daya beda bernilai negatif (D = ' . number_format($d, 2) . ', r_pbis = ' . number_format($rpbis, 2) . '). Peserta kelompok bawah lebih banyak menjawab benar dibandingkan kelompok atas. Kemungkinan kunci jawaban salah/tertukar, stem menyesatkan, atau konsep rancu. Butir direkomendasikan untuk DITOLAK / DIBUANG.';
            } elseif ($p < 0.25 || $p > 0.75 || $d < 0.20) {
                $ukomStatus = 'Revisi Kasus/Stem';
                if ($p > 0.75) {
                    $diagnosticNotes = 'Tingkat kesukaran terlalu mudah (P = ' . number_format($p, 2) . '). Sebagian besar peserta dapat menjawab benar tanpa penalaran klinis mendalam. Tinjau kembali kasus klinis (vignette) agar lebih aplikatif dan meningkatkan level kognitif.';
                } elseif ($p < 0.25) {
                    $diagnosticNotes = 'Tingkat kesukaran terlalu sukar (P = ' . number_format($p, 2) . '). Terlalu sedikit peserta yang menjawab benar. Periksa kelengkapan data anamnesis/pemeriksaan fisik dalam kasus atau kejelasan instruksi lead-in.';
                } else {
                    $diagnosticNotes = 'Daya pembeda lemah (D = ' . number_format($d, 2) . ', r_pbis = ' . number_format($rpbis, 2) . '). Butir kurang mampu membedakan peserta kompeten dan belum kompeten. Perbaiki konstruksi stem dan kasus klinis.';
                }
            } elseif ($misleadingCount > 0 || $nonFunctioningCount > 0) {
                $ukomStatus = 'Revisi Pengecoh';
                if ($misleadingCount > 0) {
                    $diagnosticNotes = 'Daya beda memadai (D = ' . number_format($d, 2) . '), namun terdapat ' . $misleadingCount . ' opsi pengecoh yang MENYESATKAN (lebih banyak dipilih oleh peserta kelompok atas dibanding kelompok bawah). Kalibrasi redaksi opsi pengecoh agar tidak multitafsir.';
                } else {
                    $diagnosticNotes = 'Daya beda baik (D = ' . number_format($d, 2) . ') dan tingkat kesukaran ideal (P = ' . number_format($p, 2) . '), namun terdapat ' . $nonFunctioningCount . ' dari ' . $totalDistractors . ' pengecoh tidak berfungsi (<5%). Ganti opsi yang tidak dipilih dengan distraktor yang lebih logis secara klinis.';
                }
            } else {
                $ukomStatus = 'Diterima';
                $diagnosticNotes = 'Memenuhi seluruh kriteria psikometrik standar UKOM: tingkat kesukaran ideal (P = ' . number_format($p, 2) . '), daya beda memuaskan (D = ' . number_format($d, 2) . ', r_pbis = ' . number_format($rpbis, 2) . '), dan seluruh pengecoh berfungsi efektif. Butir direkomendasikan masuk BANK SOAL UJI KOMPETENSI.';
            }

            // Update question DB record
            $question->update([
                'difficulty_index' => round($p, 4),
                'discrimination_index' => round($d, 4),
                'analysis_status' => $ukomStatus,
            ]);

            // Track summary count
            if ($ukomStatus === 'Diterima') {
                $summaryCounts['diterima']++;
            } elseif ($ukomStatus === 'Revisi Pengecoh') {
                $summaryCounts['revisi_pengecoh']++;
            } elseif ($ukomStatus === 'Revisi Kasus/Stem') {
                $summaryCounts['revisi_stem']++;
            } else {
                $summaryCounts['ditolak']++;
            }

            // Category for Point Biserial (r_pbis)
            if ($rpbis >= 0.40) {
                $rpbisCategory = 'Sangat Baik';
                $rpbisBadgeColor = 'emerald';
            } elseif ($rpbis >= 0.30) {
                $rpbisCategory = 'Baik';
                $rpbisBadgeColor = 'teal';
            } elseif ($rpbis >= 0.20) {
                $rpbisCategory = 'Cukup';
                $rpbisBadgeColor = 'amber';
            } elseif ($rpbis >= 0.00) {
                $rpbisCategory = 'Rendah';
                $rpbisBadgeColor = 'rose';
            } else {
                $rpbisCategory = 'Negatif';
                $rpbisBadgeColor = 'red';
            }

            $questionsData[] = [
                'id' => $question->id,
                'number' => $index + 1,
                'vignette' => $question->vignette,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'points' => $question->points,
                'key_letter' => $keyLetter,
                'p_value' => round($p, 2),
                'p_category' => $pCategory,
                'p_badge_color' => $pBadgeColor,
                'p_upper' => round($pUpper, 2),
                'p_lower' => round($pLower, 2),
                'd_value' => round($d, 2),
                'd_category' => $dCategory,
                'd_badge_color' => $dBadgeColor,
                'rpbis_value' => round($rpbis, 2),
                'rpbis_category' => $rpbisCategory,
                'rpbis_badge_color' => $rpbisBadgeColor,
                'options' => $optionsData,
                'total_distractors' => $totalDistractors,
                'functioning_distractors' => $functioningDistractors,
                'non_functioning_distractors' => $nonFunctioningCount,
                'misleading_distractors' => $misleadingCount,
                'ukom_status' => $ukomStatus,
                'diagnostic_notes' => $diagnosticNotes,
            ];
        }

        // KR-20 reliability calculation (based on raw score variance and sum of pq per item)
        $kr20 = 0.0;
        if ($k > 1 && $varianceRaw > 0) {
            $rawKr20 = ($k / ($k - 1)) * (1.0 - ($sumPQ / $varianceRaw));
            // Mathematically bound KR-20 within [0.00, 1.00] for display
            $kr20 = round(max(0.0, min(1.0, $rawKr20)), 2);
        }

        // KR-21 reliability calculation (based on mean raw score and raw score variance)
        $kr21 = 0.0;
        if ($k > 1 && $varianceRaw > 0) {
            $rawKr21 = ($k / ($k - 1)) * (1.0 - (($meanRaw * ($k - $meanRaw)) / ($k * $varianceRaw)));
            $kr21 = round(max(0.0, min(1.0, $rawKr21)), 2);
        }

        // KR-20 qualitative category
        if ($kr20 >= 0.80) {
            $kr20Status = 'Sangat Tinggi (Sangat Reliabel)';
            $kr20Color = 'emerald';
        } elseif ($kr20 >= 0.70) {
            $kr20Status = 'Tinggi (Memenuhi Standar UKOM)';
            $kr20Color = 'green';
        } elseif ($kr20 >= 0.50) {
            $kr20Status = 'Sedang (Cukup Reliabel)';
            $kr20Color = 'amber';
        } else {
            $kr20Status = 'Rendah (Kurang Reliabel)';
            $kr20Color = 'red';
        }

        // KR-21 qualitative category
        if ($kr21 >= 0.80) {
            $kr21Status = 'Sangat Tinggi (Sangat Reliabel)';
            $kr21Color = 'emerald';
        } elseif ($kr21 >= 0.70) {
            $kr21Status = 'Tinggi (Memenuhi Standar UKOM)';
            $kr21Color = 'green';
        } elseif ($kr21 >= 0.50) {
            $kr21Status = 'Sedang (Cukup Reliabel)';
            $kr21Color = 'amber';
        } else {
            $kr21Status = 'Rendah (Kurang Reliabel)';
            $kr21Color = 'red';
        }

        // Overall Point Biserial summary
        $allRpbis = array_column($questionsData, 'rpbis_value');
        $meanRpbis = count($allRpbis) > 0 ? round(array_sum($allRpbis) / count($allRpbis), 2) : 0.0;
        $maxRpbis = count($allRpbis) > 0 ? round(max($allRpbis), 2) : 0.0;
        $minRpbis = count($allRpbis) > 0 ? round(min($allRpbis), 2) : 0.0;

        if ($meanRpbis >= 0.40) {
            $meanRpbisCategory = 'Sangat Baik';
            $meanRpbisColor = 'emerald';
        } elseif ($meanRpbis >= 0.30) {
            $meanRpbisCategory = 'Baik';
            $meanRpbisColor = 'teal';
        } elseif ($meanRpbis >= 0.20) {
            $meanRpbisCategory = 'Cukup';
            $meanRpbisColor = 'amber';
        } else {
            $meanRpbisCategory = 'Rendah';
            $meanRpbisColor = 'rose';
        }

        // Standard Error of Measurement (SEM)
        $sem = $sdScore * sqrt(max(0, 1.0 - $kr20));

        // Percentages for summary distribution
        $summaryPercent = [
            'diterima' => $k > 0 ? round(($summaryCounts['diterima'] / $k) * 100, 1) : 0,
            'revisi_pengecoh' => $k > 0 ? round(($summaryCounts['revisi_pengecoh'] / $k) * 100, 1) : 0,
            'revisi_stem' => $k > 0 ? round(($summaryCounts['revisi_stem'] / $k) * 100, 1) : 0,
            'ditolak' => $k > 0 ? round(($summaryCounts['ditolak'] / $k) * 100, 1) : 0,
        ];

        return [
            'quiz' => $quiz,
            'totalAttempts' => $totalAttempts,
            'totalQuestions' => $k,
            'meanScore' => round($meanScore, 2),
            'medianScore' => round($medianScore, 2),
            'minScore' => round($minScore, 2),
            'maxScore' => round($maxScore, 2),
            'sdScore' => round($sdScore, 2),
            'varianceScore' => round($varianceScore, 2),
            'meanRaw' => round($meanRaw, 2),
            'sdRaw' => round($sdRaw, 2),
            'varianceRaw' => round($varianceRaw, 2),
            'kr20' => $kr20,
            'kr20Status' => $kr20Status,
            'kr20Color' => $kr20Color,
            'kr21' => $kr21,
            'kr21Status' => $kr21Status,
            'kr21Color' => $kr21Color,
            'meanRpbis' => $meanRpbis,
            'maxRpbis' => $maxRpbis,
            'minRpbis' => $minRpbis,
            'meanRpbisCategory' => $meanRpbisCategory,
            'meanRpbisColor' => $meanRpbisColor,
            'sem' => round($sem, 2),
            'upperGroupCount' => $ng,
            'lowerGroupCount' => $ng,
            'questionsData' => $questionsData,
            'summaryCounts' => $summaryCounts,
            'summaryPercent' => $summaryPercent,
        ];
    }

    public function exportAnalysisCsv(PbmQuiz $quiz)
    {
        $data = $this->getQuizAnalysisData($quiz);
        if (!$data) {
            return back()->with('error', 'Belum ada data pengerjaan untuk diekspor.');
        }

        $filename = 'analisis_item_properties_ukom_' . Str::slug($quiz->title) . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens with proper encoding
            fputs($file, "\xEF\xBB\xBF");

            // Header summary
            fputcsv($file, ['LAPORAN ANALISIS BUTIR SOAL & ITEM PROPERTIES STANDAR UKOM']);
            fputcsv($file, ['Judul Kuis / Ujian', $data['quiz']->title]);
            fputcsv($file, ['Jumlah Peserta (N)', $data['totalAttempts']]);
            fputcsv($file, ['Jumlah Butir Soal (K)', $data['totalQuestions']]);
            fputcsv($file, ['Rata-rata Nilai (Mean)', $data['meanScore']]);
            fputcsv($file, ['Median Nilai', $data['medianScore']]);
            fputcsv($file, ['Rentang Nilai (Min - Max)', $data['minScore'] . ' - ' . $data['maxScore']]);
            fputcsv($file, ['Standar Deviasi (SD)', $data['sdScore']]);
            fputcsv($file, ['Varians Skor', $data['varianceScore']]);
            fputcsv($file, ['Reliabilitas (KR-20)', $data['kr20'] . ' (' . $data['kr20Status'] . ')']);
            fputcsv($file, ['Reliabilitas (KR-21)', $data['kr21'] . ' (' . $data['kr21Status'] . ')']);
            fputcsv($file, ['Rata-rata Poin Biserial (r_pbis)', $data['meanRpbis'] . ' (' . $data['meanRpbisCategory'] . ')']);
            fputcsv($file, ['Kesalahan Pengukuran Baku (SEM)', $data['sem']]);
            fputcsv($file, ['Ukuran Kelompok Atas / Bawah', $data['upperGroupCount'] . ' peserta']);
            fputcsv($file, []);

            // Item table columns
            fputcsv($file, [
                'No',
                'Kasus Klinis (Vignette)',
                'Pertanyaan (Lead-in)',
                'Kunci Jawaban',
                'Tingkat Kesukaran (P)',
                'Kategori Kesukaran',
                'Proporsi Kelompok Atas (Pu)',
                'Proporsi Kelompok Bawah (Pl)',
                'Daya Pembeda (D)',
                'Kategori Daya Beda',
                'Point Biserial (r_pbis)',
                'Kategori Point Biserial',
                'Pengecoh Berfungsi',
                'Pengecoh Tidak Berfungsi',
                'Pengecoh Menyesatkan',
                'Rekomendasi Mutu UKOM',
                'Catatan Telaah / Saran Perbaikan',
                'Opsi A (Teks | Jml Pemilih | % | U | L | Status)',
                'Opsi B (Teks | Jml Pemilih | % | U | L | Status)',
                'Opsi C (Teks | Jml Pemilih | % | U | L | Status)',
                'Opsi D (Teks | Jml Pemilih | % | U | L | Status)',
                'Opsi E (Teks | Jml Pemilih | % | U | L | Status)',
            ]);

            foreach ($data['questionsData'] as $q) {
                $optionsFormatted = [];
                for ($i = 0; $i < 5; $i++) {
                    if (isset($q['options'][$i])) {
                        $opt = $q['options'][$i];
                        $optionsFormatted[] = $opt['letter'] . '. ' . $opt['text'] . ' [N=' . $opt['count'] . ' (' . $opt['percentage'] . '%), U=' . $opt['count_upper'] . ', L=' . $opt['count_lower'] . ' - ' . $opt['status'] . ']';
                    } else {
                        $optionsFormatted[] = '-';
                    }
                }

                fputcsv($file, [
                    $q['number'],
                    $q['vignette'] ?? '-',
                    $q['question_text'],
                    $q['key_letter'],
                    $q['p_value'],
                    $q['p_category'],
                    $q['p_upper'],
                    $q['p_lower'],
                    $q['d_value'],
                    $q['d_category'],
                    $q['rpbis_value'],
                    $q['rpbis_category'],
                    $q['functioning_distractors'] . ' dari ' . $q['total_distractors'],
                    $q['non_functioning_distractors'],
                    $q['misleading_distractors'],
                    $q['ukom_status'],
                    $q['diagnostic_notes'],
                    $optionsFormatted[0] ?? '-',
                    $optionsFormatted[1] ?? '-',
                    $optionsFormatted[2] ?? '-',
                    $optionsFormatted[3] ?? '-',
                    $optionsFormatted[4] ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function logProctoringEvent(Request $request, PbmQuizAttempt $attempt)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $isOwner = ($attempt->user_id === $user->id) || ($attempt->mahasiswa && $attempt->mahasiswa->nim === $user->username);
        $isPrivileged = $user->isSuperAdmin() || in_array($user->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum']);
        if (!$isOwner && !$isPrivileged) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $quiz = $attempt->quiz;
        if (!$quiz) {
            return response()->json(['error' => 'Quiz not found'], 404);
        }

        $validated = $request->validate([
            'event_type' => 'required|string|max:50',
            'details' => 'nullable|array',
            'snapshot' => 'nullable|string',
        ]);

        $snapshotPath = null;
        if (!empty($validated['snapshot'])) {
            $snapshotPath = $this->storeBase64Snapshot($validated['snapshot'], $attempt);
        }

        $log = PbmQuizProctoringLog::create([
            'pbm_quiz_attempt_id' => $attempt->id,
            'event_type' => $validated['event_type'],
            'snapshot_path' => $snapshotPath,
            'details' => $validated['details'] ?? null,
        ]);

        $nonViolationEvents = ['periodic_snapshot', 'exam_started', 'camera_ready'];
        $isViolation = !in_array($validated['event_type'], $nonViolationEvents, true);

        if ($isViolation) {
            $attempt->increment('violation_count');
            $attempt->refresh();

            $maxViolations = $quiz->max_violations ?? 3;
            if ($maxViolations > 0 && $attempt->violation_count >= $maxViolations && !$attempt->is_locked) {
                $attempt->update([
                    'is_locked' => true,
                    'lock_reason' => 'Mencapai batas maksimal pelanggaran (' . $attempt->violation_count . '/' . $maxViolations . ') - Event: ' . $validated['event_type'],
                ]);

                PbmQuizProctoringLog::create([
                    'pbm_quiz_attempt_id' => $attempt->id,
                    'event_type' => 'auto_lock',
                    'details' => [
                        'reason' => 'Batas maksimal pelanggaran tercapai (' . $attempt->violation_count . '/' . $maxViolations . ')',
                        'last_event' => $validated['event_type'],
                    ],
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'violation_count' => $attempt->violation_count,
            'max_violations' => $quiz->max_violations,
            'is_locked' => (bool)$attempt->is_locked,
            'lock_reason' => $attempt->lock_reason,
            'log_id' => $log->id,
        ]);
    }

    public function saveProctoringSnapshot(Request $request, PbmQuizAttempt $attempt)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $isOwner = ($attempt->user_id === $user->id) || ($attempt->mahasiswa && $attempt->mahasiswa->nim === $user->username);
        $isPrivileged = $user->isSuperAdmin() || in_array($user->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum']);
        if (!$isOwner && !$isPrivileged) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'snapshot' => 'required|string',
        ]);

        $snapshotPath = $this->storeBase64Snapshot($validated['snapshot'], $attempt);
        if (!$snapshotPath) {
            return response()->json(['error' => 'Failed to store snapshot'], 400);
        }

        $log = PbmQuizProctoringLog::create([
            'pbm_quiz_attempt_id' => $attempt->id,
            'event_type' => 'periodic_snapshot',
            'snapshot_path' => $snapshotPath,
            'details' => ['timestamp' => now()->toIso8601String()],
        ]);

        return response()->json([
            'success' => true,
            'log_id' => $log->id,
            'snapshot_url' => $log->snapshot_url,
        ]);
    }

    protected function storeBase64Snapshot(string $base64String, PbmQuizAttempt $attempt): ?string
    {
        try {
            if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $type)) {
                $data = substr($base64String, strpos($base64String, ',') + 1);
                $ext = strtolower($type[1]);
                if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                    return null;
                }
                $decoded = base64_decode($data);
                if ($decoded === false) {
                    return null;
                }

                // Limit size to max 2MB
                if (strlen($decoded) > 2 * 1024 * 1024) {
                    return null;
                }

                $filename = 'pbm/proctoring/attempt_' . $attempt->id . '/' . Str::random(24) . '.' . $ext;
                MediaStorage::put($filename, $decoded, 'image/' . $ext, true);
                return $filename;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[Proctoring Snapshot] Store error: ' . $e->getMessage());
        }

        return null;
    }

    public function proctoringDashboard(PbmQuiz $quiz)
    {
        $user = Auth::user();
        $isPrivileged = $user && ($user->isSuperAdmin() || in_array($user->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum']));
        if (!$isPrivileged) {
            abort(403, 'Akses khusus dosen/pengawas ujian.');
        }

        $quiz->load(['clinicalSession.clinicalClass']);
        $attempts = PbmQuizAttempt::where('pbm_quiz_id', $quiz->id)
            ->with(['user', 'mahasiswa', 'proctoringLogs' => function($q) {
                $q->latest();
            }])
            ->latest('started_at')
            ->get();

        $totalAttempts = $attempts->count();
        $activeAttempts = $attempts->whereNull('finished_at')->where('is_locked', false)->count();
        $finishedAttempts = $attempts->whereNotNull('finished_at')->count();
        $lockedAttempts = $attempts->where('is_locked', true)->count();
        $totalViolations = $attempts->sum('violation_count');

        return view('pbm.quizzes.proctoring', compact(
            'quiz', 
            'attempts', 
            'totalAttempts', 
            'activeAttempts', 
            'finishedAttempts', 
            'lockedAttempts', 
            'totalViolations'
        ));
    }

    public function unlockAttempt(PbmQuiz $quiz, PbmQuizAttempt $attempt)
    {
        $user = Auth::user();
        $isPrivileged = $user && ($user->isSuperAdmin() || in_array($user->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum']));
        if (!$isPrivileged) {
            abort(403, 'Akses ditolak.');
        }

        if ($attempt->pbm_quiz_id !== $quiz->id) {
            abort(404);
        }

        $attempt->update([
            'is_locked' => false,
            'lock_reason' => null,
        ]);

        PbmQuizProctoringLog::create([
            'pbm_quiz_attempt_id' => $attempt->id,
            'event_type' => 'unlocked_by_lecturer',
            'details' => [
                'unlocked_by' => $user->name ?? $user->username,
                'at' => now()->toDateTimeString(),
            ],
        ]);

        return back()->with('success', 'Akses ujian mahasiswa berhasil dibuka kembali.');
    }

    public function disqualifyAttempt(Request $request, PbmQuiz $quiz, PbmQuizAttempt $attempt)
    {
        $user = Auth::user();
        $isPrivileged = $user && ($user->isSuperAdmin() || in_array($user->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum']));
        if (!$isPrivileged) {
            abort(403, 'Akses ditolak.');
        }

        if ($attempt->pbm_quiz_id !== $quiz->id) {
            abort(404);
        }

        $reason = $request->input('reason', 'Diskualifikasi oleh pengawas ujian.');

        $attempt->update([
            'is_locked' => true,
            'finished_at' => now(),
            'score' => 0,
            'lock_reason' => $reason,
        ]);

        PbmQuizProctoringLog::create([
            'pbm_quiz_attempt_id' => $attempt->id,
            'event_type' => 'disqualified',
            'details' => [
                'by' => $user->name ?? $user->username,
                'reason' => $reason,
                'at' => now()->toDateTimeString(),
            ],
        ]);

        return back()->with('success', 'Peserta berhasil didiskualifikasi.');
    }
}
