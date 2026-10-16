<?php

namespace App\Http\Controllers\Pbm;

use App\Http\Controllers\Controller;
use App\Models\PbmQuiz;
use App\Models\PbmQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PbmQuestionController extends Controller
{
    public function create(PbmQuiz $quiz)
    {
        return view('pbm.quizzes.questions.create', compact('quiz'));
    }

    public function edit(PbmQuestion $question)
    {
        $question->load('options');
        $quiz = $question->quiz;
        return view('pbm.quizzes.questions.edit', compact('question', 'quiz'));
    }

    public function store(Request $request, PbmQuiz $quiz)
    {
        $data = $request->validate([
            'question_text' => 'required|string',
            'vignette' => 'nullable|string',
            'points' => 'required|integer|min:1',
            'options' => 'required|array|min:2',
            'options.*.text' => 'required|string',
            'options.*.rationale' => 'nullable|string',
            'correct_option_index' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($quiz, $data) {
            $question = $quiz->questions()->create([
                'vignette' => $data['vignette'] ?? null,
                'question_text' => $data['question_text'],
                'points' => $data['points'],
                'order' => $quiz->questions()->count() + 1,
            ]);

            foreach ($data['options'] as $index => $optionData) {
                $question->options()->create([
                    'option_text' => $optionData['text'],
                    'rationale' => $optionData['rationale'] ?? null,
                    'is_correct' => ($index == $data['correct_option_index']),
                ]);
            }
        });

        return redirect()->route('pbm.quizzes.show', $quiz->id)->with('success', 'Soal berhasil ditambahkan.');
    }

    public function update(Request $request, PbmQuestion $question)
    {
        $data = $request->validate([
            'question_text' => 'required|string',
            'vignette' => 'nullable|string',
            'points' => 'required|integer|min:1',
            'options' => 'required|array|min:2',
            'options.*.id' => 'nullable|exists:pbm_question_options,id',
            'options.*.text' => 'required|string',
            'options.*.rationale' => 'nullable|string',
            'correct_option_index' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($question, $data) {
            $question->update([
                'vignette' => $data['vignette'] ?? null,
                'question_text' => $data['question_text'],
                'points' => $data['points'],
            ]);

            // Track existing option IDs that we want to keep
            $keepIds = [];
            foreach ($data['options'] as $index => $optionData) {
                if (isset($optionData['id'])) {
                    $option = $question->options()->find($optionData['id']);
                    if ($option) {
                        $option->update([
                            'option_text' => $optionData['text'],
                            'rationale' => $optionData['rationale'] ?? null,
                            'is_correct' => ($index == $data['correct_option_index']),
                        ]);
                        $keepIds[] = $option->id;
                    }
                } else {
                    $newOption = $question->options()->create([
                        'option_text' => $optionData['text'],
                        'rationale' => $optionData['rationale'] ?? null,
                        'is_correct' => ($index == $data['correct_option_index']),
                    ]);
                    $keepIds[] = $newOption->id;
                }
            }
            
            // Delete removed options
            $question->options()->whereNotIn('id', $keepIds)->delete();
        });

        return redirect()->route('pbm.quizzes.show', $question->pbm_quiz_id)->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroy(PbmQuestion $question)
    {
        $quizId = $question->pbm_quiz_id;
        $question->delete();
        return redirect()->route('pbm.quizzes.show', $quizId)->with('success', 'Soal berhasil dihapus.');
    }

    public function import(Request $request, PbmQuiz $quiz)
    {
        $request->validate([
            'aiken_file' => 'required|file',
        ]);

        $content = file_get_contents($request->file('aiken_file')->getRealPath());
        
        // Normalize line endings
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", $content);
        
        $questionsCount = 0;
        $currentQuestionText = '';
        $currentOptions = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // If line starts with "ANSWER:", it's the end of a question block
                if (str_starts_with(strtoupper($line), 'ANSWER:')) {
                    $currentAnswerStr = trim(substr($line, 7)); // e.g., "A", "B", "C"
                    
                    if (!empty($currentQuestionText) && !empty($currentOptions)) {
                        // Determine which option index is correct based on the letter (A=0, B=1, etc.)
                        $letterToIndex = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5];
                        $correctIndex = $letterToIndex[strtoupper($currentAnswerStr)] ?? 0;
                        
                        // Save to DB
                        $q = $quiz->questions()->create([
                            'question_text' => $currentQuestionText,
                            'points' => 1,
                            'order' => $quiz->questions()->count() + 1,
                        ]);
                        
                        foreach ($currentOptions as $idx => $optText) {
                            $q->options()->create([
                                'option_text' => $optText,
                                'is_correct' => ($idx == $correctIndex),
                            ]);
                        }
                        
                        $questionsCount++;
                    }
                    
                    // Reset for next question
                    $currentQuestionText = '';
                    $currentOptions = [];
                    
                } elseif (preg_match('/^[A-Z][\.\)]\s+(.+)$/i', $line, $matches)) {
                    // Line is an option (e.g. "A. Option one" or "a) Option one")
                    $currentOptions[] = trim($matches[1]);
                } else {
                    // Line is part of the question text
                    if (!empty($currentQuestionText)) {
                        $currentQuestionText .= "\n" . $line;
                    } else {
                        $currentQuestionText = $line;
                    }
                }
            }
            
            DB::commit();
            return back()->with('success', "Berhasil mengimpor $questionsCount soal dari file Aiken.");
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', "Gagal mengimpor file: Format Aiken mungkin tidak valid. Detail: " . $e->getMessage());
        }
    }
}
