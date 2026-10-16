<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesUnitPageAccess;
use App\Http\Controllers\Controller;
use App\Models\UnitEvent;
use App\Models\UnitEventMaterial;
use App\Models\UnitPage;
use App\Support\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UnitEventMaterialController extends Controller
{
    use AuthorizesUnitPageAccess;

    public function index(UnitPage $unitPage, UnitEvent $unitEvent): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $materials = $unitEvent->materials()->get();

        return view('admin.unit-events.materials.index', compact('unitPage', 'unitEvent', 'materials'));
    }

    public function create(UnitPage $unitPage, UnitEvent $unitEvent): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $material = new UnitEventMaterial([
            'tipe' => 'file',
            'downloadable' => true,
            'publik' => true,
            'urutan' => (($unitEvent->materials()->max('urutan') ?? -1) + 1),
        ]);

        return view('admin.unit-events.materials.form', compact('unitPage', 'unitEvent', 'material'));
    }

    public function store(Request $request, UnitPage $unitPage, UnitEvent $unitEvent): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $data = $this->validatedData($request);
        $data['unit_event_id'] = $unitEvent->id;

        if ($request->hasFile('file_upload')) {
            $data['file_path'] = MediaStorage::storeUploadedFile($request->file('file_upload'), 'unit-events/materials');
        }

        UnitEventMaterial::create($data);

        return redirect()->route('admin.unit-events.materials.index', [$unitPage, $unitEvent])
            ->with('success', 'Materi event berhasil ditambahkan.');
    }

    public function edit(UnitPage $unitPage, UnitEvent $unitEvent, UnitEventMaterial $material): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);
        abort_if((int) $material->unit_event_id !== (int) $unitEvent->id, 404);

        return view('admin.unit-events.materials.form', compact('unitPage', 'unitEvent', 'material'));
    }

    public function update(Request $request, UnitPage $unitPage, UnitEvent $unitEvent, UnitEventMaterial $material): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);
        abort_if((int) $material->unit_event_id !== (int) $unitEvent->id, 404);

        $oldFilePath = $material->file_path;
        $data = $this->validatedData($request, $material);

        if ($material->tipe === 'file' && $data['tipe'] !== 'file' && $oldFilePath) {
            MediaStorage::delete($oldFilePath);
            $data['file_path'] = null;
        }

        if ($request->hasFile('file_upload')) {
            MediaStorage::delete($oldFilePath);
            $data['file_path'] = MediaStorage::storeUploadedFile($request->file('file_upload'), 'unit-events/materials');
        }

        $material->update($data);

        return redirect()->route('admin.unit-events.materials.index', [$unitPage, $unitEvent])
            ->with('success', 'Materi event berhasil diperbarui.');
    }

    public function destroy(UnitPage $unitPage, UnitEvent $unitEvent, UnitEventMaterial $material): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);
        abort_if((int) $material->unit_event_id !== (int) $unitEvent->id, 404);

        MediaStorage::delete($material->file_path);

        $material->delete();

        return redirect()->route('admin.unit-events.materials.index', [$unitPage, $unitEvent])
            ->with('success', 'Materi event berhasil dihapus.');
    }

    private function validatedData(Request $request, ?UnitEventMaterial $material = null): array
    {
        $data = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:2000',
            'tipe' => 'required|string|in:' . implode(',', array_keys(UnitEventMaterial::$tipeLabels)),
            'link_url' => 'nullable|url|max:2000',
            'embed_url' => 'nullable|url|max:4000',
            'downloadable' => 'boolean',
            'publik' => 'boolean',
            'urutan' => 'nullable|integer|min:0|max:9999',
            'file_upload' => 'nullable|file|max:10240',
        ]);

        $data['downloadable'] = $request->boolean('downloadable');
        $data['publik'] = $request->boolean('publik');
        $data['urutan'] = $data['urutan'] ?? 0;

        if ($data['tipe'] === 'file' && ! $request->hasFile('file_upload') && ! $material?->file_path) {
            throw ValidationException::withMessages([
                'file_upload' => 'File materi wajib diunggah untuk tipe file.',
            ]);
        }

        if ($data['tipe'] === 'link' && empty($data['link_url'])) {
            throw ValidationException::withMessages([
                'link_url' => 'Tautan materi wajib diisi untuk tipe link.',
            ]);
        }

        if ($data['tipe'] === 'embed' && empty($data['embed_url'])) {
            throw ValidationException::withMessages([
                'embed_url' => 'Embed URL wajib diisi untuk tipe embed.',
            ]);
        }

        if ($data['tipe'] !== 'link') {
            $data['link_url'] = null;
        }

        if ($data['tipe'] !== 'embed') {
            $data['embed_url'] = null;
        }

        if ($data['tipe'] !== 'file' && ! array_key_exists('file_path', $data)) {
            $data['file_path'] = null;
        }

        return $data;
    }
}