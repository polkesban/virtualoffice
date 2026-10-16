<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesUnitPageAccess;
use App\Http\Controllers\Controller;
use App\Models\UnitEvent;
use App\Models\UnitEventQuestion;
use App\Models\UnitPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UnitEventQuestionController extends Controller
{
    use AuthorizesUnitPageAccess;

    public function index(UnitPage $unitPage, UnitEvent $unitEvent): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $questions = $unitEvent->questions()->get()->groupBy('fase');

        return view('admin.unit-events.questions.index', compact('unitPage', 'unitEvent', 'questions'));
    }

    public function create(UnitPage $unitPage, UnitEvent $unitEvent): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $question = new UnitEventQuestion([
            'fase' => 'pre_test',
            'tipe_pertanyaan' => 'multiple_choice',
            'wajib' => true,
            'bobot' => 1,
            'urutan' => (($unitEvent->questions()->max('urutan') ?? -1) + 1),
        ]);

        return view('admin.unit-events.questions.form', compact('unitPage', 'unitEvent', 'question'));
    }

    public function store(Request $request, UnitPage $unitPage, UnitEvent $unitEvent): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $data = $this->validatedData($request);
        $data['unit_event_id'] = $unitEvent->id;

        UnitEventQuestion::create($data);

        return redirect()->route('admin.unit-events.questions.index', [$unitPage, $unitEvent])
            ->with('success', 'Pertanyaan berhasil ditambahkan.');
    }

    public function edit(UnitPage $unitPage, UnitEvent $unitEvent, UnitEventQuestion $question): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);
        abort_if((int) $question->unit_event_id !== (int) $unitEvent->id, 404);

        return view('admin.unit-events.questions.form', compact('unitPage', 'unitEvent', 'question'));
    }

    public function update(Request $request, UnitPage $unitPage, UnitEvent $unitEvent, UnitEventQuestion $question): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);
        abort_if((int) $question->unit_event_id !== (int) $unitEvent->id, 404);

        $question->update($this->validatedData($request));

        return redirect()->route('admin.unit-events.questions.index', [$unitPage, $unitEvent])
            ->with('success', 'Pertanyaan berhasil diperbarui.');
    }

    public function destroy(UnitPage $unitPage, UnitEvent $unitEvent, UnitEventQuestion $question): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);
        abort_if((int) $question->unit_event_id !== (int) $unitEvent->id, 404);

        $question->delete();

        return redirect()->route('admin.unit-events.questions.index', [$unitPage, $unitEvent])
            ->with('success', 'Pertanyaan berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'fase' => 'required|string|in:' . implode(',', array_keys(UnitEventQuestion::$faseLabels)),
            'tipe_pertanyaan' => 'required|string|in:' . implode(',', array_keys(UnitEventQuestion::$tipeLabels)),
            'pertanyaan' => 'required|string|max:5000',
            'jawaban_benar' => 'nullable|string|max:500',
            'wajib' => 'boolean',
            'bobot' => 'nullable|integer|min:0|max:100',
            'urutan' => 'nullable|integer|min:0|max:9999',
        ]);

        $opsi = collect(preg_split('/\r\n|\r|\n/', (string) $request->input('opsi_teks', '')))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();

        if ($data['tipe_pertanyaan'] === 'multiple_choice' && count($opsi) < 2) {
            throw ValidationException::withMessages([
                'opsi_teks' => 'Pertanyaan pilihan ganda membutuhkan minimal dua opsi jawaban.',
            ]);
        }

        if ($data['tipe_pertanyaan'] === 'multiple_choice' && $data['jawaban_benar'] && ! in_array($data['jawaban_benar'], $opsi, true)) {
            throw ValidationException::withMessages([
                'jawaban_benar' => 'Jawaban benar harus sama persis dengan salah satu opsi.',
            ]);
        }

        if ($data['tipe_pertanyaan'] !== 'multiple_choice') {
            $data['jawaban_benar'] = null;
            $opsi = null;
        }

        $data['opsi'] = $opsi;
        $data['wajib'] = $request->boolean('wajib');
        $data['bobot'] = $data['bobot'] ?? 1;
        $data['urutan'] = $data['urutan'] ?? 0;

        return $data;
    }
}