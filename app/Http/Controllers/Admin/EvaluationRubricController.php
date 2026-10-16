<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluationRubric;
use App\Models\EvaluationRubricItem;
use App\Models\UnitEvent;
use App\Models\UnitPage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EvaluationRubricController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = EvaluationRubric::with(['page', 'event', 'creator'])->withCount('items');

        if (! $user->isSuperAdmin()) {
            $allowedPageIds = array_filter([$user->managed_unit_page_id]);
            $query->where(function ($q) use ($user, $allowedPageIds): void {
                $q->where('created_by', $user->id);
                if (! empty($allowedPageIds)) {
                    $q->orWhereIn('unit_page_id', $allowedPageIds);
                }
            });
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->input('kategori'));
        }

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search): void {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        $rubrics = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();

        return view('admin.rubrik.index', compact('rubrics'));
    }

    public function create(Request $request): View
    {
        $user = auth()->user();
        $unitPage = null;
        $unitEvent = null;

        if ($request->filled('unit_page_id')) {
            $unitPage = UnitPage::find($request->input('unit_page_id'));
        } elseif ($user->managedUnitPage) {
            $unitPage = $user->managedUnitPage;
        }

        if ($request->filled('unit_event_id')) {
            $unitEvent = UnitEvent::find($request->input('unit_event_id'));
            if ($unitEvent && ! $unitPage) {
                $unitPage = $unitEvent->page;
            }
        }

        $rubrik = new EvaluationRubric([
            'kategori'        => $request->input('kategori', 'umum'),
            'jenis_rubrik'    => $request->input('jenis_rubrik', 'analytic'),
            'rumus_penilaian' => $request->input('rumus_penilaian', 'weighted_percentage'),
            'skala_max'       => 4,
        ]);

        return view('admin.rubrik.form', compact('rubrik', 'unitPage', 'unitEvent'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->validateRubricRequest($request);

        $user = auth()->user();
        $data = $request->only(['judul', 'deskripsi', 'kategori', 'jenis_rubrik', 'rumus_penilaian', 'skala_max', 'unit_page_id', 'unit_event_id']);
        $data['created_by'] = $user->id;
        $data['slug']       = $this->uniqueSlug($data['judul']);
        $data['is_template'] = $request->boolean('is_template');

        $rubrik = EvaluationRubric::create($data);

        $this->saveItems($rubrik, $request->input('items', []));

        return redirect()->route('admin.rubrik.show', $rubrik)
            ->with('success', 'Rubrik penilaian "' . $rubrik->judul . '" berhasil dibuat.');
    }

    public function show(EvaluationRubric $rubrik): View
    {
        $this->authorizeAccess($rubrik);
        $rubrik->load(['items', 'page', 'event', 'creator']);

        return view('admin.rubrik.show', compact('rubrik'));
    }

    public function edit(EvaluationRubric $rubrik): View
    {
        $this->authorizeAccess($rubrik);
        $rubrik->load(['rootItems.subItems', 'items']);

        $unitPage = $rubrik->page;
        $unitEvent = $rubrik->event;

        return view('admin.rubrik.form', compact('rubrik', 'unitPage', 'unitEvent'));
    }

    public function update(Request $request, EvaluationRubric $rubrik): RedirectResponse
    {
        $this->authorizeAccess($rubrik);
        $this->validateRubricRequest($request, $rubrik);

        $data = $request->only(['judul', 'deskripsi', 'kategori', 'jenis_rubrik', 'rumus_penilaian', 'skala_max', 'unit_page_id', 'unit_event_id']);
        $data['slug'] = $this->uniqueSlug($data['judul'], $rubrik->id);
        $data['is_template'] = $request->boolean('is_template');

        $rubrik->update($data);
        $this->saveItems($rubrik, $request->input('items', []));

        return redirect()->route('admin.rubrik.show', $rubrik)
            ->with('success', 'Rubrik penilaian berhasil diperbarui.');
    }

    public function destroy(EvaluationRubric $rubrik): RedirectResponse
    {
        $this->authorizeAccess($rubrik);
        $rubrik->delete();

        return redirect()->route('admin.rubrik.index')
            ->with('success', 'Rubrik penilaian berhasil dihapus.');
    }

    public function print(EvaluationRubric $rubrik): View
    {
        $this->authorizeAccess($rubrik);
        $rubrik->load(['items', 'page', 'event', 'creator']);

        return view('admin.rubrik.print', compact('rubrik'));
    }

    public function getPreset(string $key)
    {
        $presets = EvaluationRubric::getPresetTemplates();
        if (! isset($presets[$key])) {
            return response()->json(['error' => 'Preset template tidak ditemukan.'], 404);
        }

        return response()->json($presets[$key]);
    }

    private function authorizeAccess(EvaluationRubric $rubrik): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            return;
        }

        if ((int) $rubrik->created_by === (int) $user->id) {
            return;
        }

        if ($user->managed_unit_page_id && (int) $rubrik->unit_page_id === (int) $user->managed_unit_page_id) {
            return;
        }

        if ($user->hasRoleAccess('halaman-unit')) {
            return;
        }

        abort(403, 'Anda tidak memiliki akses ke rubrik penilaian ini.');
    }

    private function validateRubricRequest(Request $request, ?EvaluationRubric $rubrik = null): void
    {
        $request->validate([
            'judul'           => 'required|string|max:255',
            'deskripsi'       => 'nullable|string',
            'kategori'        => 'required|string|in:' . implode(',', array_keys(EvaluationRubric::$kategoriLabels)),
            'jenis_rubrik'    => 'required|string|in:' . implode(',', array_keys(EvaluationRubric::$jenisRubrikLabels)),
            'rumus_penilaian' => 'required|string|in:' . implode(',', array_keys(EvaluationRubric::$rumusPenilaianLabels)),
            'skala_max'       => 'required|numeric|min:1|max:100',
            'unit_page_id'    => 'nullable|exists:unit_pages,id',
            'unit_event_id'   => 'nullable|exists:unit_events,id',
            'items'           => 'required|array|min:1',
            'items.*.nama_kriteria' => 'required|string|max:255',
            'items.*.bobot'   => 'required|numeric|min:0|max:100',
        ], [
            'items.required' => 'Rubrik harus memiliki minimal 1 kriteria penilaian.',
            'items.*.nama_kriteria.required' => 'Nama kriteria tidak boleh kosong.',
        ]);
    }

    private function saveItems(EvaluationRubric $rubrik, array $itemsInput): void
    {
        $existingItemIds = $rubrik->items()->pluck('id')->toArray();
        $keepItemIds = [];

        foreach ($itemsInput as $i => $itemData) {
            $parentItemId = $this->saveSingleItem($rubrik, $itemData, $i + 1, null, $existingItemIds, $keepItemIds);

            if (! empty($itemData['sub_items']) && is_array($itemData['sub_items'])) {
                foreach ($itemData['sub_items'] as $j => $subData) {
                    $this->saveSingleItem($rubrik, $subData, $j + 1, $parentItemId, $existingItemIds, $keepItemIds);
                }
            }
        }

        // Delete items removed from form
        $toDelete = array_diff($existingItemIds, $keepItemIds);
        if (! empty($toDelete)) {
            EvaluationRubricItem::whereIn('id', $toDelete)->delete();
        }
    }

    private function saveSingleItem(EvaluationRubric $rubrik, array $itemData, int $urutan, ?int $parentId, array $existingItemIds, array &$keepItemIds): int
    {
        $levels = [];
        if (! empty($itemData['levels']) && is_array($itemData['levels'])) {
            foreach ($itemData['levels'] as $lvl) {
                if (isset($lvl['skor']) || ! empty($lvl['label'])) {
                    $levels[] = [
                        'skor'      => (float) ($lvl['skor'] ?? 0),
                        'label'     => trim((string) ($lvl['label'] ?? '')),
                        'deskripsi' => trim((string) ($lvl['deskripsi'] ?? '')),
                    ];
                }
            }
        }

        if (empty($levels) && empty($parentId)) {
            $skalaMax = (float) $rubrik->skala_max;
            if ($skalaMax <= 5) {
                for ($s = (int) $skalaMax; $s >= 1; $s--) {
                    $label = match ($s) {
                        5 => 'Sangat Baik',
                        4 => 'Baik',
                        3 => 'Cukup',
                        2 => 'Kurang',
                        1 => 'Sangat Kurang',
                        default => "Skor $s",
                    };
                    $levels[] = ['skor' => (float)$s, 'label' => $label, 'deskripsi' => ''];
                }
            }
        }

        $itemPayload = [
            'parent_id'     => $parentId,
            'nama_kriteria' => trim((string) ($itemData['nama_kriteria'] ?? '')),
            'deskripsi'     => trim((string) ($itemData['deskripsi'] ?? '')),
            'bobot'         => (float) ($itemData['bobot'] ?? 0),
            'urutan'        => $urutan,
            'levels'        => $levels,
        ];

        if (! empty($itemData['id']) && in_array((int) $itemData['id'], $existingItemIds, true)) {
            $item = EvaluationRubricItem::find($itemData['id']);
            $item->update($itemPayload);
            $keepItemIds[] = $item->id;
            return $item->id;
        } else {
            $newItem = $rubrik->items()->create($itemPayload);
            $keepItemIds[] = $newItem->id;
            return $newItem->id;
        }
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        if (empty($slug)) {
            $slug = 'rubrik-' . time();
        }

        $query = EvaluationRubric::where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $count = 1;
        $originalSlug = $slug;
        while ($query->exists()) {
            $slug = $originalSlug . '-' . $count;
            $query = EvaluationRubric::where('slug', $slug);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
            $count++;
        }

        return $slug;
    }
}
