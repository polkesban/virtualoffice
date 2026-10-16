<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WfhReport;
use App\Models\WfhActivity;
use App\Models\UnitPage;
use App\Models\ProgramStudi;
use App\Models\Jurusan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class WfhReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Filters validation
        $filters = $request->validate([
            'tanggal' => ['nullable', 'date'],
            'bulan' => ['nullable', 'integer', 'min:1', 'max:12'],
            'tahun' => ['nullable', 'integer', 'min:2020', 'max:' . (now()->year + 2)],
            'unit_page_id' => ['nullable', 'integer', 'exists:unit_pages,id'],
            'program_studi_id' => ['nullable', 'integer', 'exists:program_studi,id'],
            'jurusan_id' => ['nullable', 'integer', 'exists:jurusan,id'],
        ]);

        $query = WfhReport::query()
            ->with(['user.managedUnitPage', 'user.programStudi', 'user.jurusan', 'activities'])
            ->latest('tanggal');

        // Apply filters
        if (!empty($filters['tanggal'])) {
            $query->whereDate('tanggal', $filters['tanggal']);
        }
        if (!empty($filters['bulan'])) {
            $query->whereMonth('tanggal', $filters['bulan']);
        }
        if (!empty($filters['tahun'])) {
            $query->whereYear('tanggal', $filters['tahun']);
        }
        if (!empty($filters['unit_page_id'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('managed_unit_page_id', $filters['unit_page_id']);
            });
        }
        if (!empty($filters['program_studi_id'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('program_studi_id', $filters['program_studi_id']);
            });
        }
        if (!empty($filters['jurusan_id'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('jurusan_id', $filters['jurusan_id']);
            });
        }

        // Standard user can only see their own or see all in recap.
        // Let's allow everyone to see the general recap table, but filter by ownership if they want "My Reports"
        $myReportsQuery = WfhReport::query()
            ->where('user_id', $user->id)
            ->with(['activities'])
            ->latest('tanggal');

        $reports = $query->get();
        $myReports = $myReportsQuery->get();

        // Get filter options
        $unitPages = UnitPage::orderBy('judul')->get();
        $programStudis = ProgramStudi::orderBy('nama')->get();
        $jurusans = Jurusan::orderBy('nama')->get();

        return view('admin.wfh.index', [
            'reports' => $reports,
            'myReports' => $myReports,
            'filters' => $filters,
            'unitPages' => $unitPages,
            'programStudis' => $programStudis,
            'jurusans' => $jurusans,
        ]);
    }

    public function create(): View
    {
        return view('admin.wfh.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'tanggal' => ['required', 'date'],
            'activities' => ['required', 'array', 'min:1'],
            'activities.*.waktu' => ['nullable', 'string', 'max:50'],
            'activities.*.kegiatan' => ['required', 'string'],
            'activities.*.hasil' => ['nullable', 'string'],
            'activities.*.kendala' => ['nullable', 'string'],
            'activities.*.rtl' => ['nullable', 'string'],
            'activities.*.penanggung_jawab' => ['nullable', 'string', 'max:100'],
            'activities.*.target_waktu' => ['nullable', 'string', 'max:50'],
            'activities.*.keterangan' => ['nullable', 'string'],
        ]);

        $userId = Auth::id();
        $tanggal = $request->input('tanggal');

        DB::transaction(function () use ($userId, $tanggal, $request) {
            $report = WfhReport::create([
                'user_id' => $userId,
                'tanggal' => $tanggal,
            ]);

            foreach ($request->input('activities') as $actData) {
                $report->activities()->create($actData);
            }
        });

        return redirect()->route('admin.wfh.index')->with('success', 'Laporan WFH berhasil disimpan.');
    }

    public function edit(WfhReport $wfhReport): View
    {
        $user = Auth::user();
        if ($wfhReport->user_id !== $user->id && !$user->isSuperAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        $wfhReport->load('activities');
        return view('admin.wfh.edit', compact('wfhReport'));
    }

    public function update(Request $request, WfhReport $wfhReport): RedirectResponse
    {
        $user = Auth::user();
        if ($wfhReport->user_id !== $user->id && !$user->isSuperAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'tanggal' => ['required', 'date'],
            'activities' => ['required', 'array', 'min:1'],
            'activities.*.waktu' => ['nullable', 'string', 'max:50'],
            'activities.*.kegiatan' => ['required', 'string'],
            'activities.*.hasil' => ['nullable', 'string'],
            'activities.*.kendala' => ['nullable', 'string'],
            'activities.*.rtl' => ['nullable', 'string'],
            'activities.*.penanggung_jawab' => ['nullable', 'string', 'max:100'],
            'activities.*.target_waktu' => ['nullable', 'string', 'max:50'],
            'activities.*.keterangan' => ['nullable', 'string'],
        ]);

        $tanggal = $request->input('tanggal');

        DB::transaction(function () use ($wfhReport, $tanggal, $request) {
            $wfhReport->update([
                'tanggal' => $tanggal,
            ]);

            // Simple recreate strategy
            $wfhReport->activities()->delete();
            foreach ($request->input('activities') as $actData) {
                $wfhReport->activities()->create($actData);
            }
        });

        return redirect()->route('admin.wfh.index')->with('success', 'Laporan WFH berhasil diperbarui.');
    }

    public function show(WfhReport $wfhReport): View
    {
        $wfhReport->load(['user.managedUnitPage', 'user.programStudi', 'user.jurusan', 'activities']);
        return view('admin.wfh.show', compact('wfhReport'));
    }

    public function destroy(WfhReport $wfhReport): RedirectResponse
    {
        $user = Auth::user();
        if ($wfhReport->user_id !== $user->id && !$user->isSuperAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        $wfhReport->delete();
        return redirect()->route('admin.wfh.index')->with('success', 'Laporan WFH berhasil dihapus.');
    }

    public function updateNotes(Request $request, WfhReport $wfhReport): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->canAccessWfhRecaps()) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'catatan_wadir' => ['nullable', 'string'],
        ]);

        $wfhReport->update([
            'catatan_wadir' => $request->input('catatan_wadir'),
        ]);

        return back()->with('success', 'Catatan Wadir berhasil diperbarui.');
    }
}
