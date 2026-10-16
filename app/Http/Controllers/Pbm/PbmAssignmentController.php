<?php

namespace App\Http\Controllers\Pbm;

use App\Http\Controllers\Controller;
use App\Models\ClinicalClass;
use App\Models\ClinicalSession;
use App\Models\PbmAssignment;
use Illuminate\Http\Request;

class PbmAssignmentController extends Controller
{
    public function store(Request $request, ClinicalClass $class, ClinicalSession $session)
    {
        $data = $request->validate([
            'title'                => 'required|string|max:255',
            'description'          => 'nullable|string',
            'due_date'             => 'required|date',
            'is_practical'         => 'required|boolean',
            'evaluation_rubric_id' => 'nullable|exists:evaluation_rubrics,id',
        ]);

        $data['created_by'] = auth()->id();
        $session->assignments()->create($data);

        return redirect()->back()->with('success', 'Tugas berhasil dibuat.');
    }

    public function destroy(PbmAssignment $assignment)
    {
        $assignment->delete();
        return redirect()->back()->with('success', 'Tugas berhasil dihapus.');
    }
}
