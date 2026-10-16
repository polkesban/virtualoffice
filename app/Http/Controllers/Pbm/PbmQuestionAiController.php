<?php

namespace App\Http\Controllers\Pbm;

use App\Http\Controllers\Controller;
use App\Models\PbmQuiz;
use App\Models\PbmMaterial;
use App\Services\AI\QuestionGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PbmQuestionAiController extends Controller
{
    protected QuestionGeneratorService $generatorService;

    public function __construct(QuestionGeneratorService $generatorService)
    {
        $this->generatorService = $generatorService;
    }

    /**
     * Tampilkan formulir generator soal AI.
     */
    public function create(PbmQuiz $quiz)
    {
        $session = $quiz->clinicalSession;
        $class = $session ? $session->clinicalClass : null;

        // Bahan ajar pada sesi kuis ini
        $sessionMaterials = $session ? $session->materials()->get() : collect();

        // Bahan ajar dari sesi lain dalam kelas yang sama (misal RPS yang diupload di sesi 1 atau sesi lain)
        $otherMaterials = collect();
        if ($class) {
            $otherSessionIds = $class->sessions()
                ->where('id', '!=', $session->id)
                ->pluck('id');

            $otherMaterials = PbmMaterial::whereIn('clinical_session_id', $otherSessionIds)
                ->with('clinicalSession')
                ->get();
        }

        return view('pbm.quizzes.questions.ai_generate', compact(
            'quiz',
            'session',
            'class',
            'sessionMaterials',
            'otherMaterials'
        ));
    }

    /**
     * Generate soal secara asynchronous (AJAX) dan kembalikan JSON.
     */
    public function generate(Request $request, PbmQuiz $quiz)
    {
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '180');

        $request->validate([
            'material_ids'             => 'nullable|array',
            'material_ids.*'           => 'integer|exists:pbm_materials,id',
            'uploaded_file'            => 'nullable|file|mimes:pdf,docx,pptx,txt|max:51200', // 50MB
            'custom_text'              => 'nullable|string|max:30000',
            'question_count'           => 'required|integer|min:1|max:15',
            'difficulty'               => 'required|in:c1_c2,c3_c4,c5_c6',
            'format_type'              => 'required|in:vignette_case,conceptual',
            'option_count'             => 'required|integer|in:4,5',
            'additional_instructions'  => 'nullable|string|max:2000',
        ]);

        try {
            $contextParts = [];

            // Tambahkan info konteks kuis dan kelas
            $session = $quiz->clinicalSession;
            $class = $session ? $session->clinicalClass : null;
            $contextParts[] = "Mata Kuliah / Kelas: " . ($class->name ?? 'PBM');
            $contextParts[] = "Topik Kuis: " . $quiz->title;
            if ($quiz->description) {
                $contextParts[] = "Deskripsi Kuis: " . $quiz->description;
            }

            // 1. Ekstraksi materi yang dipilih dari kelas/sesi
            if ($request->filled('material_ids')) {
                $materials = PbmMaterial::whereIn('id', $request->input('material_ids'))->get();
                foreach ($materials as $mat) {
                    $matText = $this->generatorService->extractFromMaterial($mat);
                    if (!empty(trim($matText))) {
                        $contextParts[] = $matText;
                    }
                }
            }

            // 2. Ekstraksi berkas yang diunggah khusus
            if ($request->hasFile('uploaded_file')) {
                $uploadedText = $this->generatorService->extractFromUploadedFile($request->file('uploaded_file'));
                if (!empty(trim($uploadedText))) {
                    $contextParts[] = "### Berkas Tambahan yang Diunggah:\n" . $uploadedText;
                }
            }

            // 3. Tambahkan teks/topik manual jika ada
            if ($request->filled('custom_text')) {
                $contextParts[] = "### Catatan Materi / Pokok Bahasan:\n" . trim($request->input('custom_text'));
            }

            $combinedContext = trim(implode("\n\n", $contextParts));

            if (mb_strlen($combinedContext) < 20) {
                return response()->json([
                    'success' => false,
                    'message' => 'Materi acuan tidak ditemukan atau terlalu singkat. Harap pilih minimal satu bahan ajar, unggah berkas RPS/materi, atau tuliskan pokok materi.'
                ], 422);
            }

            $questions = $this->generatorService->generateQuestions([
                'context_text'            => $combinedContext,
                'question_count'          => (int) $request->input('question_count', 5),
                'difficulty'              => $request->input('difficulty', 'c3_c4'),
                'format_type'             => $request->input('format_type', 'vignette_case'),
                'option_count'            => (int) $request->input('option_count', 5),
                'additional_instructions' => $request->input('additional_instructions'),
            ]);

            return response()->json([
                'success'   => true,
                'questions' => $questions,
                'total'     => count($questions)
            ]);

        } catch (\Throwable $e) {
            Log::error("[PbmQuestionAiController@generate] Error: " . $e->getMessage(), [
                'quiz_id' => $quiz->id,
                'trace'   => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyusun soal dengan AI: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Simpan kumpulan soal yang telah ditinjau dan dipilih oleh dosen ke database.
     */
    public function store(Request $request, PbmQuiz $quiz)
    {
        $data = $request->validate([
            'questions'                        => 'required|array|min:1',
            'questions.*.question_text'        => 'required|string',
            'questions.*.vignette'             => 'nullable|string',
            'questions.*.points'               => 'required|integer|min:1',
            'questions.*.options'              => 'required|array|min:2',
            'questions.*.options.*.text'       => 'required|string',
            'questions.*.options.*.is_correct' => 'required',
            'questions.*.options.*.rationale'  => 'nullable|string',
        ]);

        $savedCount = 0;

        DB::transaction(function () use ($quiz, $data, &$savedCount) {
            $currentOrder = $quiz->questions()->count();

            foreach ($data['questions'] as $qData) {
                $currentOrder++;

                $question = $quiz->questions()->create([
                    'vignette'      => $qData['vignette'] ?? null,
                    'question_text' => $qData['question_text'],
                    'points'        => (int) ($qData['points'] ?? 1),
                    'order'         => $currentOrder,
                ]);

                foreach ($qData['options'] as $optData) {
                    $isCorrect = filter_var($optData['is_correct'], FILTER_VALIDATE_BOOLEAN);

                    $question->options()->create([
                        'option_text' => $optData['text'],
                        'rationale'   => $optData['rationale'] ?? null,
                        'is_correct'  => $isCorrect,
                    ]);
                }

                $savedCount++;
            }
        });

        return redirect()->route('pbm.quizzes.show', $quiz->id)
            ->with('success', "Berhasil menambahkan {$savedCount} butir soal dari AI ke dalam kuis.");
    }
}
