<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateContentJob;
use App\Models\ContentJob;
use App\Support\AdminAccreditationFollowUpSummary;
use App\Services\AI\ContentEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ContentEngineController extends Controller
{
    public function __construct(private readonly ContentEngineService $engine) {}

    public function index(AdminAccreditationFollowUpSummary $accreditationFollowUpSummary): View
    {
        $jobs = ContentJob::with('user')
            ->latest()
            ->get();

        return view('admin.content-engine.index', [
            'jobs' => $jobs,
            'accreditationFollowUp' => $accreditationFollowUpSummary->forUser(auth()->user()),
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'media'    => 'required|file|mimes:jpg,jpeg,png,webp,mp4,mov|max:51200',
            'place'    => 'required|string|max:200',
            'date'     => 'required|date',
            'subject'  => 'required|string|max:300',
            'kategori' => 'required|in:berita,kegiatan,pengumuman,prestasi',
            'notes'    => 'nullable|string|max:2000',
            'tags'     => 'nullable|string|max:500',
            'title'    => 'nullable|string|max:300',
        ]);

        $file      = $request->file('media');
        $mediaType = in_array(strtolower($file->extension()), ['mp4', 'mov']) ? 'video' : 'image';
        $path      = $file->store('content-engine', 'local');

        $job = ContentJob::create([
            'user_id'    => auth()->id(),
            'title'      => $validated['title'] ?? null,
            'media_path' => $path,
            'media_type' => $mediaType,
            'metadata'   => [
                'place'    => $validated['place'],
                'date'     => $validated['date'],
                'subject'  => $validated['subject'],
                'kategori' => $validated['kategori'],
                'notes'    => $validated['notes'] ?? '',
                'tags'     => $validated['tags']   ?? '',
            ],
        ]);

        GenerateContentJob::dispatch($job);

        return response()->json([
            'job_id'   => $job->id,
            'message'  => 'Upload berhasil! AI sedang memproses konten...',
            'poll_url' => route('admin.content-engine.status', $job->id),
        ]);
    }

    /** Return job status + result for polling. */
    public function status(ContentJob $job): JsonResponse
    {
        $badge = $job->statusBadge();

        return response()->json([
            'status'      => $job->status,
            'badge_label' => $badge['label'],
            'badge_class' => $badge['class'],
            'show_url'    => $job->status === 'done' ? route('admin.content-engine.show', $job->id) : null,
        ]);
    }

    public function show(ContentJob $job): View
    {
        return view('admin.content-engine.show', compact('job'));
    }

    public function regenerate(ContentJob $job): RedirectResponse
    {
        $job->update(['status' => 'pending', 'error_message' => null]);
        GenerateContentJob::dispatch($job);

        return back()->with('success', 'Regenerasi konten dimulai...');
    }

    public function publish(Request $request, ContentJob $job): RedirectResponse
    {
        if ($job->status !== 'done') {
            return back()->with('error', 'Konten belum selesai diproses.');
        }

        // Allow admin to override generated title/article before publishing
        if ($request->filled('generated_title')) {
            $job->update([
                'generated_title'   => strip_tags($request->input('generated_title')),
                'generated_article' => $request->input('generated_article'),
                'generated_caption' => $request->input('generated_caption'),
            ]);
            $job->refresh();
        }

        $berita = $this->engine->publish($job);

        return redirect()
            ->route('admin.content-engine.index')
            ->with('success', "✅ Berhasil dipublikasi: \"{$berita->judul}\"");
    }

    /** Serve local media file securely through this controller. */
    public function media(ContentJob $job): Response
    {
        $path = storage_path('app/' . $job->media_path);

        abort_unless(file_exists($path), 404);

        $mime = mime_content_type($path) ?: 'application/octet-stream';

        return response(file_get_contents($path), 200, [
            'Content-Type'        => $mime,
            'Cache-Control'       => 'private, max-age=3600',
            'Content-Disposition' => 'inline',
        ]);
    }

    public function destroy(ContentJob $job): RedirectResponse
    {
        Storage::disk('local')->delete($job->media_path);
        $job->delete();

        return back()->with('success', 'Job dihapus.');
    }

    /** Generate HTML block content via AI (used by section form AI block type) */
    public function generateBlock(Request $request): JsonResponse
    {
        $request->validate([
            'type'    => 'nullable|string|in:hero,teks,ai_teks,richtext,statistik,tabel,tim,formulir',
            'acara'   => 'required|string|max:500',
            'tempat'  => 'nullable|string|max:200',
            'tanggal' => 'nullable|string|max:100',
            'catatan' => 'nullable|string|max:500',
            'context_title' => 'nullable|string|max:200',
            'media'   => 'nullable|file|mimes:jpg,jpeg,png,webp,gif|max:10240',
        ]);

        $mediaPath = null;
        $uploaded  = $request->file('media');

        if ($uploaded) {
            // Store in a private temp location (not publicly accessible)
            $mediaPath = $uploaded->store('tmp/ai-blocks', 'local');
            $mediaPath = storage_path("app/{$mediaPath}");
        }

        try {
            $payload = $this->engine->generateBuilderDraft($request->input('type', 'ai_teks'), [
                'acara'   => $request->acara,
                'tempat'  => $request->tempat,
                'tanggal' => $request->tanggal,
                'catatan' => $request->catatan,
                'context_title' => $request->context_title,
            ], $mediaPath);

            return response()->json(array_merge(['payload' => $payload], $payload));
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Gagal generate: ' . $e->getMessage()], 500);
        } finally {
            // Clean up temp file
            if ($mediaPath && file_exists($mediaPath)) {
                @unlink($mediaPath);
            }
        }
    }
}
