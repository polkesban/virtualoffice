<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesUnitPageAccess;
use App\Http\Controllers\Controller;
use App\Models\UnitEvent;
use App\Models\UnitEventDocumentation;
use App\Models\UnitEventPhoto;
use App\Models\UnitPage;
use App\Support\MediaStorage;
use App\Services\AI\AiClient;
use App\Services\AI\UnitEventDocumentationAiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UnitEventDocumentationController extends Controller
{
    use AuthorizesUnitPageAccess;

    public function index(UnitPage $unitPage, UnitEvent $unitEvent): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $documentation = $unitEvent->documentation()->firstOrNew([
            'unit_event_id' => $unitEvent->id,
        ]);

        $photos = $unitEvent->photos()->get();
        $participantStats = $this->participantStats($unitEvent);

        return view('admin.unit-events.documentation.index', compact(
            'unitPage',
            'unitEvent',
            'documentation',
            'photos',
            'participantStats',
        ));
    }

    public function update(Request $request, UnitPage $unitPage, UnitEvent $unitEvent): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $this->persistDocumentation($request, $unitEvent);

        return redirect()->route('admin.unit-events.documentation.index', [$unitPage, $unitEvent])
            ->with('success', 'Dokumentasi event berhasil disimpan.');
    }

    public function autosave(Request $request, UnitPage $unitPage, UnitEvent $unitEvent): \Illuminate\Http\JsonResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $documentation = $this->persistDocumentation($request, $unitEvent);

        return response()->json([
            'success' => true,
            'saved_at' => now()->format('H:i:s'),
            'message' => 'Draft transkrip berhasil disimpan otomatis ke server.',
            'updated_at' => $documentation->updated_at?->toISOString(),
        ]);
    }

    public function generateSummary(Request $request, UnitPage $unitPage, UnitEvent $unitEvent, UnitEventDocumentationAiService $aiService): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $documentation = $this->persistDocumentation($request, $unitEvent);
        $this->ensureDocumentationHasSource($documentation);

        try {
            $result = $aiService->generateSummary($unitEvent, $documentation, $unitEvent->photos()->get());

            $documentation->update([
                'ai_summary_html' => $result['html'],
                'ai_model_used' => $result['model'],
                'ai_summary_generated_at' => now(),
            ]);

            return redirect()->route('admin.unit-events.documentation.index', [$unitPage, $unitEvent])
                ->with('success', 'Resume hasil rapat berhasil dibuat oleh AI dan masih bisa Anda edit.');
        } catch (\Throwable $exception) {
            return back()->withErrors([
                'ai_summary_html' => 'Gagal membuat resume AI: ' . $exception->getMessage(),
            ])->withInput();
        }
    }

    public function generateReport(Request $request, UnitPage $unitPage, UnitEvent $unitEvent, UnitEventDocumentationAiService $aiService): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $documentation = $this->persistDocumentation($request, $unitEvent);
        $this->ensureDocumentationHasSource($documentation);

        try {
            $result = $aiService->generateReport($unitEvent, $documentation, $unitEvent->photos()->get());

            $documentation->update([
                'report_html' => $result['html'],
                'ai_model_used' => $result['model'],
                'report_generated_at' => now(),
            ]);

            return redirect()->route('admin.unit-events.documentation.index', [$unitPage, $unitEvent])
                ->with('success', 'Draft laporan berhasil dibuat oleh AI dan dapat Anda revisi sebelum dicetak.');
        } catch (\Throwable $exception) {
            return back()->withErrors([
                'report_html' => 'Gagal membuat draft laporan AI: ' . $exception->getMessage(),
            ])->withInput();
        }
    }

    public function transcribeAudio(Request $request, UnitPage $unitPage, UnitEvent $unitEvent, AiClient $ai): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $documentation = $this->persistDocumentation($request, $unitEvent);

        $data = $request->validate([
            'audio_transcription_file' => 'required|file|mimes:mp3,wav,m4a,ogg,oga,webm,mp4|max:12288',
            'transcription_mode' => 'nullable|string|in:append,replace',
            'audio_language' => 'nullable|string|max:10',
        ]);

        $storedPath = $request->file('audio_transcription_file')->store('tmp/unit-event-transcriptions', 'local');
        $absolutePath = Storage::disk('local')->path($storedPath);
        $mode = $data['transcription_mode'] ?? 'append';
        $language = $data['audio_language'] ?? 'id';

        try {
            $transcript = trim($ai->transcribeAudio($absolutePath, $language));
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPath);

            return back()->withErrors([
                'audio_transcription_file' => 'Gagal mentranskripsi rekaman audio: ' . $exception->getMessage(),
            ])->withInput();
        }

        Storage::disk('local')->delete($storedPath);

        if ($transcript === '') {
            return back()->withErrors([
                'audio_transcription_file' => 'Transkrip audio kosong. Coba unggah ulang rekaman dengan suara lebih jelas.',
            ])->withInput();
        }

        $documentation->transcript_text = $mode === 'replace'
            ? $transcript
            : $this->mergeTranscriptText($documentation->transcript_text, $transcript);
        $documentation->save();

        return redirect()->route('admin.unit-events.documentation.index', [$unitPage, $unitEvent])
            ->with('success', 'Rekaman audio berhasil ditranskripsi ke Bahasa Indonesia. Voice to text browser tetap bisa dipakai untuk penambahan cepat.');
    }

    public function storePhotos(Request $request, UnitPage $unitPage, UnitEvent $unitEvent): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $data = $request->validate([
            'photos' => 'required|array|min:1|max:12',
            'photos.*' => 'required|image|max:6144',
            'caption' => 'nullable|string|max:2000',
            'taken_at' => 'nullable|date',
        ]);

        $caption = trim((string) ($data['caption'] ?? '')) ?: null;
        $takenAt = $data['taken_at'] ?? null;
        $nextSortOrder = (int) ($unitEvent->photos()->max('sort_order') ?? -1) + 1;
        $hasCover = $unitEvent->photos()->where('is_cover', true)->exists();

        foreach ($request->file('photos', []) as $index => $photoFile) {
            $storedPath = MediaStorage::storeUploadedFile($photoFile, 'unit-events/documentation-photos');

            UnitEventPhoto::create([
                'unit_event_id' => $unitEvent->id,
                'title' => $this->defaultPhotoTitle($photoFile->getClientOriginalName()),
                'caption' => $caption,
                'image_path' => $storedPath,
                'taken_at' => $takenAt,
                'sort_order' => $nextSortOrder + $index,
                'is_cover' => ! $hasCover && $index === 0,
            ]);
        }

        return redirect()->route('admin.unit-events.documentation.index', [$unitPage, $unitEvent])
            ->with('success', 'Foto kegiatan berhasil diunggah.');
    }

    public function updatePhoto(Request $request, UnitPage $unitPage, UnitEvent $unitEvent, UnitEventPhoto $photo): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);
        $this->authorizePhoto($unitEvent, $photo);

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:2000',
            'taken_at' => 'nullable|date',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_cover' => 'boolean',
        ]);

        $data['is_cover'] = $request->boolean('is_cover');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        if ($data['is_cover']) {
            $unitEvent->photos()->where('id', '!=', $photo->id)->update(['is_cover' => false]);
        }

        $photo->update($data);

        return redirect()->route('admin.unit-events.documentation.index', [$unitPage, $unitEvent])
            ->with('success', 'Keterangan foto kegiatan berhasil diperbarui.');
    }

    public function destroyPhoto(UnitPage $unitPage, UnitEvent $unitEvent, UnitEventPhoto $photo): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);
        $this->authorizePhoto($unitEvent, $photo);

        MediaStorage::delete($photo->image_path);
        $photo->delete();

        if (! $unitEvent->photos()->where('is_cover', true)->exists()) {
            $fallbackPhoto = $unitEvent->photos()->orderBy('sort_order')->orderBy('id')->first();
            $fallbackPhoto?->update(['is_cover' => true]);
        }

        return redirect()->route('admin.unit-events.documentation.index', [$unitPage, $unitEvent])
            ->with('success', 'Foto kegiatan berhasil dihapus.');
    }

    public function reportPrint(UnitPage $unitPage, UnitEvent $unitEvent): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $documentation = $unitEvent->documentation()->firstOrNew([
            'unit_event_id' => $unitEvent->id,
        ]);

        $photos = $unitEvent->photos()->get();
        $participantStats = $this->participantStats($unitEvent);

        return view('admin.unit-events.documentation.report-print', compact(
            'unitPage',
            'unitEvent',
            'documentation',
            'photos',
            'participantStats',
        ));
    }

    private function persistDocumentation(Request $request, UnitEvent $unitEvent): UnitEventDocumentation
    {
        $data = $request->validate([
            'transcript_text' => 'nullable|string',
            'minutes_html' => 'nullable|string',
            'ai_summary_html' => 'nullable|string',
            'report_html' => 'nullable|string',
            'report_notes' => 'nullable|string|max:5000',
            'berita_acara_judul' => 'nullable|string|max:255',
            'berita_acara_ringkasan' => 'nullable|string|max:1000',
            'berita_acara_catatan' => 'nullable|string',
        ]);

        $documentation = $unitEvent->documentation()->firstOrNew([
            'unit_event_id' => $unitEvent->id,
        ]);

        $documentation->fill($request->only([
            'transcript_text', 'minutes_html', 'ai_summary_html', 'report_html', 'report_notes'
        ]));
        $documentation->unit_event_id = $unitEvent->id;
        $documentation->save();

        $unitEvent->update($request->only([
            'berita_acara_judul', 'berita_acara_ringkasan', 'berita_acara_catatan'
        ]));

        return $documentation;
    }

    private function ensureDocumentationHasSource(UnitEventDocumentation $documentation): void
    {
        $transcriptText = trim((string) $documentation->transcript_text);
        $minutesText = trim(strip_tags((string) $documentation->minutes_html));

        if ($transcriptText === '' && $minutesText === '') {
            throw ValidationException::withMessages([
                'transcript_text' => 'Isi transkrip voice to text atau notulen terlebih dahulu sebelum menjalankan generator AI.',
            ]);
        }
    }

    private function authorizePhoto(UnitEvent $unitEvent, UnitEventPhoto $photo): void
    {
        abort_if((int) $photo->unit_event_id !== (int) $unitEvent->id, 404);
    }

    private function defaultPhotoTitle(string $originalName): string
    {
        $name = Str::of(pathinfo($originalName, PATHINFO_FILENAME))
            ->replace(['_', '-'], ' ')
            ->squish()
            ->title()
            ->toString();

        return $name !== '' ? $name : 'Dokumentasi Kegiatan';
    }

    private function participantStats(UnitEvent $unitEvent): array
    {
        return [
            'total' => $unitEvent->participants()->count(),
            'participant' => $unitEvent->participants()->where('peran', 'participant')->count(),
            'speaker' => $unitEvent->participants()->where('peran', 'speaker')->count(),
            'committee' => $unitEvent->participants()->where('peran', 'committee')->count(),
        ];
    }

    private function mergeTranscriptText(?string $existingTranscript, string $newTranscript): string
    {
        $existingTranscript = trim((string) $existingTranscript);
        $newTranscript = trim($newTranscript);

        if ($existingTranscript === '') {
            return $newTranscript;
        }

        return $existingTranscript . "\n\n" . $newTranscript;
    }
}
