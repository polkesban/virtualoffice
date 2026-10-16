<?php

namespace App\Http\Controllers\Pbm;

use App\Http\Controllers\Controller;
use App\Models\ClinicalClass;
use App\Models\ClinicalSession;
use App\Models\EvaluationRubric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    public function schedule()
    {
        $user = Auth::user();

        // Get all class IDs the user is a member of (or created)
        $classIds = ClinicalClass::where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->whereHas('members', function ($mq) use ($user) {
                    $mq->where('user_id', $user->id);
                    if ($user->mahasiswa_id ?? null) {
                        $mq->orWhere('mahasiswa_id', $user->mahasiswa_id);
                    }
                })->orWhere('created_by', $user->id);
            })->pluck('id');

        $upcomingSessions = ClinicalSession::whereIn('clinical_class_id', $classIds)
            ->where('end_time', '>=', now())
            ->with('clinicalClass')
            ->orderBy('start_time', 'asc')
            ->get();

        $pastSessions = ClinicalSession::whereIn('clinical_class_id', $classIds)
            ->where('end_time', '<', now())
            ->with('clinicalClass')
            ->orderBy('start_time', 'desc')
            ->limit(20)
            ->get();

        return view('pbm.sessions.schedule', compact('upcomingSessions', 'pastSessions'));
    }

    public function create(ClinicalClass $class)
    {
        return view('pbm.sessions.create', compact('class'));
    }

    public function store(Request $request, ClinicalClass $class)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'location_name' => 'nullable|string|max:255',
            'location_lat' => 'nullable|numeric',
            'location_lng' => 'nullable|numeric',
            'location_radius' => 'nullable|integer',
            'require_qr' => 'boolean',
            'require_gps' => 'boolean',
            'description' => 'nullable|string',
            'shifts' => 'nullable|string',
            'lokasi_praktik' => 'nullable|string',
        ]);

        $validated['require_qr'] = $request->has('require_qr');
        $validated['require_gps'] = $request->has('require_gps');
        $validated['created_by'] = Auth::id();
        $validated['clinical_class_id'] = $class->id;
        
        if (isset($validated['shifts']) && is_string($validated['shifts'])) {
            $validated['shifts'] = json_decode($validated['shifts'], true);
        }
        
        if (isset($validated['lokasi_praktik']) && is_string($validated['lokasi_praktik'])) {
            $validated['lokasi_praktik'] = json_decode($validated['lokasi_praktik'], true);
        }

        $class->sessions()->create($validated);

        return redirect()->route('pbm.classes.show', $class)->with('success', 'Sesi berhasil dibuat.');
    }

    public function show(ClinicalClass $class, ClinicalSession $session)
    {
        $session->load('attendances.mahasiswa', 'attendances.ciValidator', 'evaluationRubric.items', 'materials.creator', 'assignments.creator', 'quizzes.creator');

        // Check for overlap for the logged in user (if they are a lecturer)
        $hasOverlap = false;
        $overlapSessions = [];
        if (in_array(Auth::user()->role, ['admin', 'dosen'])) {
            $overlapSessions = ClinicalSession::where('created_by', Auth::id())
                ->where('id', '!=', $session->id)
                ->where(function ($query) use ($session) {
                    $query->whereBetween('start_time', [$session->start_time, $session->end_time])
                          ->orWhereBetween('end_time', [$session->start_time, $session->end_time])
                          ->orWhere(function ($q) use ($session) {
                              $q->where('start_time', '<=', $session->start_time)
                                ->where('end_time', '>=', $session->end_time);
                          });
                })->get();
                
            $hasOverlap = $overlapSessions->isNotEmpty();
        }

        // Available rubrics for attaching
        $availableRubrics = EvaluationRubric::where(function ($q) {
            $q->where('is_template', true)
              ->orWhere('created_by', Auth::id());
        })->orderBy('judul')->get();

        return view('pbm.sessions.show', compact('class', 'session', 'hasOverlap', 'overlapSessions', 'availableRubrics'));
    }

    public function edit(ClinicalClass $class, ClinicalSession $session)
    {
        return view('pbm.sessions.edit', compact('class', 'session'));
    }

    public function update(Request $request, ClinicalClass $class, ClinicalSession $session)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'location_name' => 'nullable|string|max:255',
            'location_lat' => 'nullable|numeric',
            'location_lng' => 'nullable|numeric',
            'location_radius' => 'nullable|integer',
            'description' => 'nullable|string',
            'shifts' => 'nullable|string',
            'lokasi_praktik' => 'nullable|string',
        ]);

        $validated['require_qr'] = $request->has('require_qr');
        $validated['require_gps'] = $request->has('require_gps');
        
        if (isset($validated['shifts']) && is_string($validated['shifts'])) {
            $validated['shifts'] = json_decode($validated['shifts'], true);
        }
        
        if (isset($validated['lokasi_praktik']) && is_string($validated['lokasi_praktik'])) {
            $validated['lokasi_praktik'] = json_decode($validated['lokasi_praktik'], true);
        }

        $session->update($validated);

        return redirect()->route('pbm.classes.sessions.show', [$class, $session])->with('success', 'Sesi berhasil diperbarui.');
    }

    public function destroy(ClinicalClass $class, ClinicalSession $session)
    {
        $session->delete();
        return redirect()->route('pbm.classes.show', $class)->with('success', 'Sesi berhasil dihapus.');
    }

    public function attachRubric(Request $request, ClinicalClass $class, ClinicalSession $session)
    {
        $validated = $request->validate([
            'evaluation_rubric_id' => 'required|exists:evaluation_rubrics,id',
        ]);

        $session->update(['evaluation_rubric_id' => $validated['evaluation_rubric_id']]);

        return redirect()->route('pbm.classes.sessions.show', [$class, $session])->with('success', 'Rubrik berhasil ditambahkan ke sesi.');
    }

    public function detachRubric(ClinicalClass $class, ClinicalSession $session)
    {
        $session->update(['evaluation_rubric_id' => null]);

        return redirect()->route('pbm.classes.sessions.show', [$class, $session])->with('success', 'Rubrik berhasil dilepas dari sesi.');
    }
}
