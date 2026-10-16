<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\IndexDocumentJob;
use App\Models\KnowledgeDocument;
use App\Support\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeController extends Controller
{
    public function index(): View
    {
        $docs = KnowledgeDocument::withCount('chunks')
            ->latest()
            ->get();

        return view('admin.knowledge.index', compact('docs'));
    }

    public function upload(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type'        => 'required|in:file,url',
            'file'        => 'required_if:type,file|nullable|file|mimes:pdf,md,txt|max:20480',
            'url'         => 'required_if:type,url|nullable|url|max:2048',
            'name'        => 'required|string|max:200',
            'description' => 'nullable|string|max:500',
        ]);

        if ($request->input('type') === 'url') {
            $doc = KnowledgeDocument::create([
                'name'              => $validated['name'],
                'original_filename' => $validated['url'],
                'file_path'         => $validated['url'],
                'file_type'         => 'url',
                'description'       => $validated['description'] ?? null,
                'uploaded_by'       => auth()->id(),
            ]);
        } else {
            $file = $request->file('file');
            $ext  = strtolower($file->getClientOriginalExtension());
            $path = MediaStorage::storeUploadedFile($file, 'knowledge', false);

            $doc = KnowledgeDocument::create([
                'name'              => $validated['name'],
                'original_filename' => $file->getClientOriginalName(),
                'file_path'         => $path,
                'file_type'         => in_array($ext, ['md', 'txt']) ? $ext : 'pdf',
                'description'       => $validated['description'] ?? null,
                'uploaded_by'       => auth()->id(),
            ]);
        }

        IndexDocumentJob::dispatch($doc);

        return back()->with('success', "Dokumen \"{$doc->name}\" diunggah dan sedang diindeks...");
    }

    public function reindex(KnowledgeDocument $doc): RedirectResponse
    {
        $doc->update(['status' => 'pending', 'error_message' => null]);
        IndexDocumentJob::dispatch($doc);

        return back()->with('success', "Pengindeksan ulang \"{$doc->name}\" dimulai.");
    }

    public function destroy(KnowledgeDocument $doc): RedirectResponse
    {
        if ($doc->file_type !== 'url') {
            MediaStorage::delete($doc->file_path);
        }
        $doc->chunks()->delete();
        $doc->delete();

        return back()->with('success', "Dokumen \"{$doc->name}\" dihapus.");
    }
}
