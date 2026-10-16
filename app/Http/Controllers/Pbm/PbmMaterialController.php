<?php

namespace App\Http\Controllers\Pbm;

use App\Http\Controllers\Controller;
use App\Models\ClinicalClass;
use App\Models\ClinicalSession;
use App\Models\PbmMaterial;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PbmMaterialController extends Controller
{
    public function store(Request $request, ClinicalClass $class, ClinicalSession $session)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->back()->with('error', 'Silakan login terlebih dahulu.');
        }

        // Check permissions (lecturer/admin)
        $isPrivileged = $user->isSuperAdmin() || in_array($user->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum', 'admin']);
        $isOwner = $class->created_by === $user->id || $session->created_by === $user->id;
        if (!$isPrivileged && !$isOwner) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menambah materi pada sesi ini.');
        }

        // Normalize URL if provided without protocol (e.g. drive.google.com, youtube.com)
        if ($request->filled('url')) {
            $inputUrl = trim((string) $request->input('url'));
            if ($inputUrl !== '' && !preg_match('~^(?:f|ht)tps?://~i', $inputUrl) && !str_starts_with($inputUrl, '/')) {
                $request->merge(['url' => 'https://' . $inputUrl]);
            }
        }

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'type'        => 'required|string|in:document,video,link',
            'description' => 'nullable|string|max:1000',
            'url'         => 'nullable|string|max:2048',
            'file'        => 'nullable|file|max:102400', // 100MB
        ], [
            'title.required' => 'Judul materi wajib diisi.',
            'type.required'  => 'Tipe materi wajib dipilih.',
            'file.max'       => 'Ukuran file tidak boleh melebihi 100MB.',
        ]);

        $filePath = null;
        $fileUrl = $validated['url'] ?? null;

        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');
            $filePath = MediaStorage::storeUploadedFile($uploadedFile, 'pbm/materials');
            $fileUrl = MediaStorage::url($filePath);

            // Auto-detect type if link was selected or type is generic
            $ext = strtolower($uploadedFile->getClientOriginalExtension());
            if (in_array($ext, ['mp4', 'mkv', 'webm', 'mov', 'avi'])) {
                $validated['type'] = 'video';
            } elseif (in_array($ext, ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'txt'])) {
                $validated['type'] = 'document';
            }
        }

        // Require at least one: file or url
        if (empty($filePath) && empty($fileUrl)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Silakan unggah file materi atau masukkan tautan URL.');
        }

        $material = new PbmMaterial([
            'clinical_session_id' => $session->id,
            'title'               => $validated['title'],
            'type'                => $validated['type'],
            'description'         => $validated['description'] ?? null,
            'file_path'           => $filePath,
            'url'                 => $fileUrl,
            'created_by'          => $user->id,
        ]);

        $session->materials()->save($material);

        return redirect()->back()->with('success', 'Materi "' . $material->title . '" berhasil ditambahkan.');
    }

    public function destroy(PbmMaterial $material)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->back()->with('error', 'Silakan login terlebih dahulu.');
        }

        $isPrivileged = $user->isSuperAdmin() || in_array($user->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum', 'admin']);
        $isOwner = $material->created_by === $user->id;
        if (!$isPrivileged && !$isOwner) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menghapus materi ini.');
        }

        if (!empty($material->file_path)) {
            MediaStorage::delete($material->file_path);
        }

        $title = $material->title;
        $material->delete();

        return redirect()->back()->with('success', 'Materi "' . $title . '" berhasil dihapus.');
    }

    public function stream(PbmMaterial $material)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        if (empty($material->file_path)) {
            if (!empty($material->url)) {
                return redirect()->away($material->url);
            }
            abort(404, 'Berkas materi tidak ditemukan.');
        }

        $tmpPath = MediaStorage::temporaryLocalPath($material->file_path);
        if (!$tmpPath || !file_exists($tmpPath)) {
            abort(404, 'Berkas materi tidak dapat diakses.');
        }

        $filename = $material->title ?: 'materi';
        $originalExt = pathinfo(MediaStorage::fileName($material->file_path), PATHINFO_EXTENSION) ?: 'pdf';
        if (!str_ends_with(strtolower($filename), '.' . strtolower($originalExt))) {
            $filename .= '.' . $originalExt;
        }

        $mimeType = MediaStorage::mimeType($material->file_path) ?: 'application/pdf';

        return response()->file($tmpPath, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename="' . str_replace(['"', "\r", "\n"], '', $filename) . '"',
            'Cache-Control'       => 'private, max-age=86400',
            'X-Frame-Options'     => 'SAMEORIGIN',
        ]);
    }

    public function viewer(PbmMaterial $material)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $streamUrl = route('pbm.materials.stream', $material->id, false);

        return response()->view('pbm.materials.viewer', [
            'material'  => $material,
            'streamUrl' => $streamUrl,
        ])->header('X-Frame-Options', 'SAMEORIGIN');
    }
}
