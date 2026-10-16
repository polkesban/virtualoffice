<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccreditationEvidence;
use App\Models\AccreditationEvidenceAnnotation;
use App\Models\AccreditationFolder;
use App\Models\DokumenMutu;
use App\Support\AccreditationEvidenceAnnotationExport;
use App\Support\AccreditationEvidenceExport;
use App\Support\AccreditationEvidenceTextPreview;
use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

class AccreditationEvidenceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeEvidenceRoom();
        $this->ensureModuleReady();

        $today = now()->toDateString();
        $inThirtyDays = now()->addDays(30)->toDateString();
        $annotationsModuleReady = $this->annotationsModuleReady();

        $foldersQuery = AccreditationFolder::query()
            ->withCount('evidences')
            ->withCount([
                'evidences as ready_evidences_count' => fn ($query) => $query->whereIn('verification_status', [
                    AccreditationEvidence::STATUS_READY,
                    AccreditationEvidence::STATUS_VERIFIED,
                ]),
                'evidences as verified_evidences_count' => fn ($query) => $query->where('verification_status', AccreditationEvidence::STATUS_VERIFIED),
                'evidences as missing_source_evidences_count' => fn ($query) => $query
                    ->whereNull('dokumen_mutu_id')
                    ->whereNull('external_url')
                    ->whereNull('file_path'),
                'evidences as attention_evidences_count' => fn ($query) => $query->where(function ($attentionQuery) use ($today, $inThirtyDays) {
                    $attentionQuery->where(function ($innerQuery) use ($today) {
                        $innerQuery->whereNotNull('expires_at')
                            ->whereDate('expires_at', '<', $today);
                    })->orWhere(function ($innerQuery) use ($today, $inThirtyDays) {
                        $innerQuery->whereNotNull('expires_at')
                            ->whereDate('expires_at', '>=', $today)
                            ->whereDate('expires_at', '<=', $inThirtyDays);
                    })->orWhere(function ($innerQuery) use ($inThirtyDays) {
                        $innerQuery->whereNotNull('review_due_at')
                            ->whereDate('review_due_at', '<=', $inThirtyDays);
                    });
                }),
            ]);

        if ($annotationsModuleReady) {
            $foldersQuery->withCount([
                'evidences as pending_annotation_evidences_count' => fn ($query) => $query->whereHas('annotations', function ($annotationQuery) {
                    $annotationQuery->whereIn('status', $this->pendingAnnotationStatuses());
                }),
            ]);
        }

        $folders = $foldersQuery
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        $items = $this->buildEvidenceIndexQuery($request, $annotationsModuleReady)->get();
        $summary = $this->summaryCounts($annotationsModuleReady);
        $criteriaSummary = $this->criteriaSummary($folders);

        return view('admin.eviden-akreditasi.index', [
            'folders' => $folders,
            'items' => $items,
            'summary' => $summary,
            'criteriaSummary' => $criteriaSummary,
            'statusOptions' => AccreditationEvidence::verificationStatusOptions(),
            'sourceFilterOptions' => $this->sourceFilterOptions(),
            'annotationsModuleReady' => $annotationsModuleReady,
            'annotationFilterOptions' => $annotationsModuleReady ? $this->annotationFilterOptions() : [],
        ]);
    }

    public function exportAnnotations(
        Request $request,
        AccreditationEvidenceAnnotationExport $annotationExport,
    ): StreamedResponse
    {
        $this->authorizeEvidenceRoom();
        $this->ensureModuleReady();
        $this->ensureAnnotationsReady();

        $annotations = $this->buildAnnotationExportQuery($request)->get();
        $rows = $annotationExport->rows($annotations, fn ($evidence) => route('admin.eviden-akreditasi.show', $evidence));
        $format = $this->normalizedExportFormat($request);

        if ($format === 'xlsx') {
            $filename = 'tindak-lanjut-reviewer-akreditasi-' . now()->format('Ymd-His') . '.xlsx';

            return response()->streamDownload(function () use ($annotationExport, $annotations) {
                $spreadsheet = $annotationExport->spreadsheet(
                    $annotations,
                    fn ($evidence) => route('admin.eviden-akreditasi.show', $evidence)
                );

                (new Xlsx($spreadsheet))->save('php://output');
                $spreadsheet->disconnectWorksheets();
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        $filename = 'tindak-lanjut-reviewer-akreditasi-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($annotationExport, $rows) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $annotationExport->headings());

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function export(
        Request $request,
        AccreditationEvidenceExport $evidenceExport,
    ): StreamedResponse
    {
        $this->authorizeEvidenceRoom();
        $this->ensureModuleReady();

        $annotationsModuleReady = $this->annotationsModuleReady();
        $items = $this->buildEvidenceIndexQuery($request, $annotationsModuleReady)->get();
        $rows = $evidenceExport->rows($items, fn ($evidence) => route('admin.eviden-akreditasi.show', $evidence));
        $format = $this->normalizedExportFormat($request);

        if ($format === 'xlsx') {
            $filename = 'daftar-eviden-akreditasi-' . now()->format('Ymd-His') . '.xlsx';

            return response()->streamDownload(function () use ($evidenceExport, $items) {
                $spreadsheet = $evidenceExport->spreadsheet(
                    $items,
                    fn ($evidence) => route('admin.eviden-akreditasi.show', $evidence)
                );

                (new Xlsx($spreadsheet))->save('php://output');
                $spreadsheet->disconnectWorksheets();
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        $filename = 'daftar-eviden-akreditasi-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($evidenceExport, $rows) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $evidenceExport->headings());

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function create(): View
    {
        $this->authorizeEvidenceRoom();
        $this->ensureModuleReady();

        return view('admin.eviden-akreditasi.form', $this->formPayload(new AccreditationEvidence([
            'verification_status' => AccreditationEvidence::STATUS_DRAFT,
        ])));
    }

    public function show(
        Request $request,
        AccreditationEvidence $accreditationEvidence,
        AccreditationEvidenceTextPreview $textPreviewBuilder,
    ): View
    {
        $this->authorizeEvidenceRoom();
        $this->ensureModuleReady();

        $annotationsModuleReady = $this->annotationsModuleReady();

        $accreditationEvidence->loadMissing(['folder', 'dokumenMutu', 'creator', 'editor']);

        if ($annotationsModuleReady) {
            $accreditationEvidence->loadMissing(['annotations.creator', 'annotations.editor']);
        }

        $sources = $this->availableSources($accreditationEvidence);
        $selectedSourceKey = $request->string('source')->toString();
        $selectedPreview = $sources[$selectedSourceKey] ?? (count($sources) > 0 ? array_values($sources)[0] : null);
        $textPreview = ($selectedPreview['key'] ?? null) === 'private_file'
            ? $textPreviewBuilder->build($accreditationEvidence)
            : null;
        $textSearchQuery = trim($request->string('text_q')->toString());
        $textSearch = $this->buildTextSearch($textPreview, $textSearchQuery);

        return view('admin.eviden-akreditasi.show', [
            'evidence' => $accreditationEvidence,
            'sources' => $sources,
            'selectedPreview' => $selectedPreview,
            'textPreview' => $textPreview,
            'textSearch' => $textSearch,
            'annotationsModuleReady' => $annotationsModuleReady,
            'annotationStatusOptions' => $annotationsModuleReady ? AccreditationEvidenceAnnotation::statusOptions() : [],
        ]);
    }

    public function storeAnnotation(Request $request, AccreditationEvidence $accreditationEvidence): RedirectResponse
    {
        $this->authorizeEvidenceRoom();
        $this->ensureModuleReady();
        $this->ensureAnnotationsReady();

        $data = $this->validateAnnotation($request);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $annotation = $accreditationEvidence->annotations()->create($data);

        return $this->redirectToEvidenceDetail(
            $request,
            $accreditationEvidence,
            'Anotasi reviewer berhasil ditambahkan.',
            'annotation-' . $annotation->id
        );
    }

    public function updateAnnotation(
        Request $request,
        AccreditationEvidence $accreditationEvidence,
        AccreditationEvidenceAnnotation $annotation,
    ): RedirectResponse {
        $this->authorizeEvidenceRoom();
        $this->ensureModuleReady();
        $this->ensureAnnotationsReady();
        $this->ensureAnnotationBelongsToEvidence($accreditationEvidence, $annotation);

        $data = $this->validateAnnotation($request);
        $data['updated_by'] = auth()->id();

        $annotation->update($data);

        return $this->redirectToEvidenceDetail(
            $request,
            $accreditationEvidence,
            'Anotasi reviewer berhasil diperbarui.',
            'annotation-' . $annotation->id
        );
    }

    public function destroyAnnotation(
        Request $request,
        AccreditationEvidence $accreditationEvidence,
        AccreditationEvidenceAnnotation $annotation,
    ): RedirectResponse {
        $this->authorizeEvidenceRoom();
        $this->ensureModuleReady();
        $this->ensureAnnotationsReady();
        $this->ensureAnnotationBelongsToEvidence($accreditationEvidence, $annotation);

        $annotation->delete();

        return $this->redirectToEvidenceDetail(
            $request,
            $accreditationEvidence,
            'Anotasi reviewer berhasil dihapus.'
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeEvidenceRoom();
        $this->ensureModuleReady();

        $data = $this->validateEvidence($request);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        $data['verified_at'] = $data['verification_status'] === AccreditationEvidence::STATUS_VERIFIED ? now() : null;

        if ($request->hasFile('evidence_file')) {
            $upload = $request->file('evidence_file');
            $data['file_path'] = MediaStorage::storeUploadedFile($upload, 'accreditation-evidence', false);
            $data['file_name'] = $upload->getClientOriginalName();
            $data['mime_type'] = $upload->getClientMimeType();
            $data['size_bytes'] = $upload->getSize();
        }

        AccreditationEvidence::create($data);

        return redirect()->route('admin.eviden-akreditasi.index')
            ->with('success', 'Eviden akreditasi berhasil ditambahkan.');
    }

    public function edit(AccreditationEvidence $accreditationEvidence): View
    {
        $this->authorizeEvidenceRoom();
        $this->ensureModuleReady();

        return view('admin.eviden-akreditasi.form', $this->formPayload($accreditationEvidence));
    }

    public function update(Request $request, AccreditationEvidence $accreditationEvidence): RedirectResponse
    {
        $this->authorizeEvidenceRoom();
        $this->ensureModuleReady();

        $data = $this->validateEvidence($request, $accreditationEvidence);
        $data['updated_by'] = auth()->id();

        if ($request->boolean('remove_file') && $accreditationEvidence->file_path) {
            MediaStorage::delete($accreditationEvidence->file_path);
            $data['file_path'] = null;
            $data['file_name'] = null;
            $data['mime_type'] = null;
            $data['size_bytes'] = null;
        }

        if ($request->hasFile('evidence_file')) {
            MediaStorage::delete($accreditationEvidence->file_path);

            $upload = $request->file('evidence_file');
            $data['file_path'] = MediaStorage::storeUploadedFile($upload, 'accreditation-evidence', false);
            $data['file_name'] = $upload->getClientOriginalName();
            $data['mime_type'] = $upload->getClientMimeType();
            $data['size_bytes'] = $upload->getSize();
        }

        if ($data['verification_status'] === AccreditationEvidence::STATUS_VERIFIED) {
            $data['verified_at'] = $accreditationEvidence->verified_at ?: now();
        } else {
            $data['verified_at'] = null;
        }

        $accreditationEvidence->update($data);

        return redirect()->route('admin.eviden-akreditasi.index')
            ->with('success', 'Eviden akreditasi berhasil diperbarui.');
    }

    public function destroy(AccreditationEvidence $accreditationEvidence): RedirectResponse
    {
        $this->authorizeEvidenceRoom();
        $this->ensureModuleReady();

        MediaStorage::delete($accreditationEvidence->file_path);

        $accreditationEvidence->delete();

        return back()->with('success', 'Eviden akreditasi berhasil dihapus.');
    }

    public function download(AccreditationEvidence $accreditationEvidence): Response
    {
        $this->authorizeEvidenceRoom();
        $this->ensureModuleReady();

        abort_unless(
            filled($accreditationEvidence->file_path)
            && MediaStorage::exists($accreditationEvidence->file_path),
            404
        );

        return MediaStorage::inlineResponse(
            $accreditationEvidence->file_path,
            $accreditationEvidence->file_name ?: basename($accreditationEvidence->file_path),
            true,
            $accreditationEvidence->mime_type,
        );
    }

    public function preview(AccreditationEvidence $accreditationEvidence): Response
    {
        $this->authorizeEvidenceRoom();
        $this->ensureModuleReady();

        abort_unless(
            filled($accreditationEvidence->file_path)
            && MediaStorage::exists($accreditationEvidence->file_path),
            404
        );

        $filename = $accreditationEvidence->file_name ?: basename($accreditationEvidence->file_path);

        return $this->respondWithInlinePrivateFile(
            $accreditationEvidence->file_path,
            $filename,
            $accreditationEvidence->mime_type
        );
    }

    private function formPayload(AccreditationEvidence $evidence): array
    {
        return [
            'evidence' => $evidence,
            'folders' => AccreditationFolder::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get(),
            'dokumenMutuList' => DokumenMutu::query()
                ->whereNotNull('file_url')
                ->orderByDesc('tanggal_terbit')
                ->orderBy('judul')
                ->get(),
            'statusOptions' => AccreditationEvidence::verificationStatusOptions(),
        ];
    }

    private function buildEvidenceIndexQuery(Request $request, bool $annotationsModuleReady): Builder
    {
        $itemsQuery = AccreditationEvidence::query()
            ->with(['folder', 'dokumenMutu', 'creator', 'editor'])
            ->orderBy('folder_id')
            ->orderBy('subcriterion_code')
            ->orderBy('title');

        if ($annotationsModuleReady) {
            $itemsQuery->withCount([
                'annotations',
                'annotations as open_annotations_count' => fn ($query) => $query->where('status', AccreditationEvidenceAnnotation::STATUS_OPEN),
                'annotations as in_progress_annotations_count' => fn ($query) => $query->where('status', AccreditationEvidenceAnnotation::STATUS_IN_PROGRESS),
                'annotations as resolved_annotations_count' => fn ($query) => $query->where('status', AccreditationEvidenceAnnotation::STATUS_RESOLVED),
            ]);
        }

        $this->applyEvidenceBaseFilters($itemsQuery, $request);

        if ($annotationsModuleReady) {
            $this->applyEvidenceAnnotationFilter($itemsQuery, $this->normalizedAnnotationStatusFilter($request));
        }

        return $itemsQuery;
    }

    private function buildAnnotationExportQuery(Request $request): Builder
    {
        $annotationStatus = $this->normalizedAnnotationStatusFilter($request);

        $query = AccreditationEvidenceAnnotation::query()
            ->with(['evidence.folder', 'creator', 'editor'])
            ->whereHas('evidence', function (Builder $evidenceQuery) use ($request) {
                $this->applyEvidenceBaseFilters($evidenceQuery, $request);
            })
            ->orderBy('accreditation_evidence_id')
            ->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($annotationStatus === 'needs_follow_up') {
            $query->whereIn('status', $this->pendingAnnotationStatuses());
        } elseif ($annotationStatus === 'none') {
            $query->whereRaw('1 = 0');
        } elseif ($annotationStatus !== null && array_key_exists($annotationStatus, AccreditationEvidenceAnnotation::statusOptions())) {
            $query->where('status', $annotationStatus);
        }

        return $query;
    }

    private function applyEvidenceBaseFilters(Builder $query, Request $request): void
    {
        if ($request->filled('folder')) {
            $query->where('folder_id', $request->integer('folder'));
        }

        if ($request->filled('status')) {
            $query->where('verification_status', $request->string('status')->toString());
        }

        if ($request->filled('pic')) {
            $query->where('pic_name', 'like', '%' . trim($request->string('pic')->toString()) . '%');
        }

        if ($request->filled('review')) {
            $review = $request->string('review')->toString();

            if ($review === 'review_overdue') {
                $query->whereNotNull('review_due_at')
                    ->whereDate('review_due_at', '<', now()->toDateString());
            }

            if ($review === 'expiry_attention') {
                $query->where(function (Builder $expiryQuery) {
                    $expiryQuery->where(function (Builder $expiredQuery) {
                        $expiredQuery->whereNotNull('expires_at')
                            ->whereDate('expires_at', '<', now()->toDateString());
                    })->orWhere(function (Builder $expiresSoonQuery) {
                        $expiresSoonQuery->whereNotNull('expires_at')
                            ->whereDate('expires_at', '>=', now()->toDateString())
                            ->whereDate('expires_at', '<=', now()->addDays(30)->toDateString());
                    });
                });
            }

            if ($review === 'expired') {
                $query->whereNotNull('expires_at')
                    ->whereDate('expires_at', '<', now()->toDateString());
            }

            if ($review === 'expires_30') {
                $query->whereNotNull('expires_at')
                    ->whereDate('expires_at', '>=', now()->toDateString())
                    ->whereDate('expires_at', '<=', now()->addDays(30)->toDateString());
            }

            if ($review === 'review_30') {
                $query->whereNotNull('review_due_at')
                    ->whereDate('review_due_at', '>=', now()->toDateString())
                    ->whereDate('review_due_at', '<=', now()->addDays(30)->toDateString());
            }
        }

        if ($request->filled('source_state')) {
            $sourceState = $request->string('source_state')->toString();

            if ($sourceState === 'missing') {
                $query->whereNull('dokumen_mutu_id')
                    ->whereNull('external_url')
                    ->whereNull('file_path');
            }

            if ($sourceState === 'private_file') {
                $query->whereNotNull('file_path');
            }

            if ($sourceState === 'dokumen_mutu') {
                $query->whereNotNull('dokumen_mutu_id');
            }

            if ($sourceState === 'external_url') {
                $query->whereNotNull('external_url');
            }
        }

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->where(function (Builder $innerQuery) use ($keyword) {
                $innerQuery->where('title', 'like', '%' . $keyword . '%')
                    ->orWhere('subcriterion_code', 'like', '%' . $keyword . '%')
                    ->orWhere('pic_name', 'like', '%' . $keyword . '%')
                    ->orWhere('summary', 'like', '%' . $keyword . '%')
                    ->orWhere('notes', 'like', '%' . $keyword . '%');
            });
        }
    }

    private function applyEvidenceAnnotationFilter(Builder $query, ?string $annotationStatus): void
    {
        if ($annotationStatus === 'needs_follow_up') {
            $query->whereHas('annotations', function (Builder $annotationQuery) {
                $annotationQuery->whereIn('status', $this->pendingAnnotationStatuses());
            });

            return;
        }

        if ($annotationStatus === 'none') {
            $query->doesntHave('annotations');

            return;
        }

        if ($annotationStatus !== null && array_key_exists($annotationStatus, AccreditationEvidenceAnnotation::statusOptions())) {
            $query->whereHas('annotations', function (Builder $annotationQuery) use ($annotationStatus) {
                $annotationQuery->where('status', $annotationStatus);
            });
        }
    }

    private function normalizedAnnotationStatusFilter(Request $request): ?string
    {
        if (! $request->filled('annotation_status')) {
            return null;
        }

        return $request->string('annotation_status')->toString();
    }

    private function normalizedExportFormat(Request $request): string
    {
        return $request->string('format')->lower()->toString() === 'xlsx' ? 'xlsx' : 'csv';
    }

    private function availableSources(AccreditationEvidence $evidence): array
    {
        $sources = [];

        if ($evidence->hasUploadedFile()) {
            $kind = $this->detectPreviewKind($evidence->mime_type, $evidence->file_name ?: $evidence->file_path, false);

            $sources['private_file'] = [
                'key' => 'private_file',
                'title' => 'File Privat',
                'summary' => trim(($evidence->file_name ?: basename((string) $evidence->file_path)) . ($evidence->human_file_size ? ' · ' . $evidence->human_file_size : '')),
                'description' => 'Disimpan privat di server dan hanya bisa dibuka dari panel admin.',
                'preview_url' => route('admin.eviden-akreditasi.preview', $evidence),
                'open_url' => route('admin.eviden-akreditasi.preview', $evidence),
                'download_url' => route('admin.eviden-akreditasi.download', $evidence),
                'kind' => $kind,
                'can_embed' => $this->canEmbedPreviewKind($kind),
                'is_remote' => false,
            ];
        }

        if ($evidence->hasDokumenMutuLink()) {
            $kind = $this->detectPreviewKind(null, $evidence->dokumenMutu->file_url, true);

            $sources['dokumen_mutu'] = [
                'key' => 'dokumen_mutu',
                'title' => 'Dokumen Mutu',
                'summary' => $evidence->dokumenMutu->judul,
                'description' => 'Sumber berasal dari modul Dokumen Mutu yang sudah dipublikasikan.',
                'preview_url' => $evidence->dokumenMutu->file_url,
                'open_url' => $evidence->dokumenMutu->file_url,
                'download_url' => null,
                'kind' => $kind,
                'can_embed' => $this->canEmbedPreviewKind($kind),
                'is_remote' => true,
            ];
        }

        if ($evidence->hasExternalUrl()) {
            $kind = $this->detectPreviewKind(null, $evidence->external_url, true);

            $sources['external_url'] = [
                'key' => 'external_url',
                'title' => 'URL Eksternal',
                'summary' => $evidence->external_url,
                'description' => 'Gunakan untuk eviden yang masih berada di sistem atau repositori eksternal.',
                'preview_url' => $evidence->external_url,
                'open_url' => $evidence->external_url,
                'download_url' => null,
                'kind' => $kind,
                'can_embed' => $this->canEmbedPreviewKind($kind),
                'is_remote' => true,
            ];
        }

        return $sources;
    }

    private function validateEvidence(Request $request, ?AccreditationEvidence $existing = null): array
    {
        $validator = Validator::make($request->all(), [
            'folder_id' => ['required', Rule::exists('accreditation_folders', 'id')],
            'dokumen_mutu_id' => ['nullable', Rule::exists('dokumen_mutu', 'id')],
            'title' => 'required|string|max:300',
            'subcriterion_code' => 'nullable|string|max:50',
            'pic_name' => 'nullable|string|max:180',
            'verification_status' => ['required', Rule::in(array_keys(AccreditationEvidence::verificationStatusOptions()))],
            'summary' => 'nullable|string|max:4000',
            'notes' => 'nullable|string',
            'issued_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:issued_at',
            'review_due_at' => 'nullable|date',
            'external_url' => 'nullable|url|max:1000',
            'evidence_file' => 'nullable|file|max:15360',
            'remove_file' => 'nullable|boolean',
        ]);

        $validator->after(function ($validator) use ($request, $existing) {
            $dokumenMutuId = $request->input('dokumen_mutu_id');
            $externalUrl = trim((string) $request->input('external_url'));
            $hasExistingFile = filled($existing?->file_path) && ! $request->boolean('remove_file');
            $hasUploadedFile = $request->hasFile('evidence_file') || $hasExistingFile;

            if (filled($dokumenMutuId)) {
                $dokumenMutu = DokumenMutu::query()->find($dokumenMutuId);

                if (! filled($dokumenMutu?->file_url)) {
                    $validator->errors()->add('dokumen_mutu_id', 'Dokumen mutu yang dipilih belum memiliki URL dokumen yang bisa dibuka.');
                }
            }

            if (! filled($dokumenMutuId) && $externalUrl === '' && ! $hasUploadedFile) {
                $validator->errors()->add('source', 'Pilih minimal satu sumber eviden: tautan Dokumen Mutu, URL eksternal, atau file unggahan.');
            }
        });

        $data = $validator->validate();

        $data['dokumen_mutu_id'] = filled($data['dokumen_mutu_id'] ?? null) ? (int) $data['dokumen_mutu_id'] : null;
        $data['external_url'] = filled($data['external_url'] ?? null) ? trim((string) $data['external_url']) : null;
        $data['summary'] = filled($data['summary'] ?? null) ? trim((string) $data['summary']) : null;
        $data['notes'] = filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null;
        $data['subcriterion_code'] = filled($data['subcriterion_code'] ?? null) ? trim((string) $data['subcriterion_code']) : null;
        $data['pic_name'] = filled($data['pic_name'] ?? null) ? trim((string) $data['pic_name']) : null;

        return $data;
    }

    private function validateAnnotation(Request $request): array
    {
        $data = Validator::make($request->all(), [
            'title' => 'required|string|max:180',
            'excerpt' => 'nullable|string|max:5000',
            'note' => 'nullable|string|max:10000',
            'status' => ['required', Rule::in(array_keys(AccreditationEvidenceAnnotation::statusOptions()))],
            'is_pinned' => 'nullable|boolean',
        ])->validate();

        return [
            'title' => trim((string) $data['title']),
            'excerpt' => filled($data['excerpt'] ?? null) ? trim((string) $data['excerpt']) : null,
            'note' => filled($data['note'] ?? null) ? trim((string) $data['note']) : null,
            'status' => (string) $data['status'],
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
        ];
    }

    private function summaryCounts(bool $annotationsModuleReady = false): array
    {
        $summary = [
            'total' => AccreditationEvidence::query()->count(),
            'verified' => AccreditationEvidence::query()->where('verification_status', AccreditationEvidence::STATUS_VERIFIED)->count(),
            'expired' => AccreditationEvidence::query()
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '<', now()->toDateString())
                ->count(),
            'due_soon' => AccreditationEvidence::query()
                ->where(function ($query) {
                    $query->where(function ($inner) {
                        $inner->whereNotNull('expires_at')
                            ->whereDate('expires_at', '>=', now()->toDateString())
                            ->whereDate('expires_at', '<=', now()->addDays(30)->toDateString());
                    })->orWhere(function ($inner) {
                        $inner->whereNotNull('review_due_at')
                            ->whereDate('review_due_at', '<=', now()->addDays(30)->toDateString());
                    });
                })
                ->count(),
        ];

        if (! $annotationsModuleReady) {
            return $summary;
        }

        $summary['annotations_total'] = AccreditationEvidenceAnnotation::query()->count();
        $summary['annotations_open'] = AccreditationEvidenceAnnotation::query()
            ->where('status', AccreditationEvidenceAnnotation::STATUS_OPEN)
            ->count();
        $summary['annotations_in_progress'] = AccreditationEvidenceAnnotation::query()
            ->where('status', AccreditationEvidenceAnnotation::STATUS_IN_PROGRESS)
            ->count();
        $summary['annotations_resolved'] = AccreditationEvidenceAnnotation::query()
            ->where('status', AccreditationEvidenceAnnotation::STATUS_RESOLVED)
            ->count();
        $summary['evidences_with_pending_annotations'] = AccreditationEvidence::query()
            ->whereHas('annotations', function ($query) {
                $query->whereIn('status', $this->pendingAnnotationStatuses());
            })
            ->count();

        return $summary;
    }

    private function criteriaSummary($folders): array
    {
        $totalFolders = $folders->count();
        $coveredFolders = $folders->where('evidences_count', '>', 0)->count();
        $readyFolders = $folders->where('ready_evidences_count', '>', 0)->count();
        $attentionFolders = $folders->filter(function (AccreditationFolder $folder) {
            return $folder->attention_evidences_count > 0
                || $folder->missing_source_evidences_count > 0
                || (int) ($folder->pending_annotation_evidences_count ?? 0) > 0;
        })->count();
        $emptyFolders = $folders->where('evidences_count', 0)->count();

        return [
            'total' => $totalFolders,
            'covered' => $coveredFolders,
            'ready' => $readyFolders,
            'attention' => $attentionFolders,
            'empty' => $emptyFolders,
            'coverage_percent' => $totalFolders > 0 ? (int) round(($coveredFolders / $totalFolders) * 100) : 0,
            'readiness_percent' => $totalFolders > 0 ? (int) round(($readyFolders / $totalFolders) * 100) : 0,
        ];
    }

    private function buildTextSearch(?array $textPreview, string $query): ?array
    {
        if (($textPreview['status'] ?? null) !== 'ready') {
            return null;
        }

        $content = (string) ($textPreview['content'] ?? '');

        return [
            'query' => $query,
            'occurrence_count' => $query !== '' ? $this->countTextOccurrences($content, $query) : 0,
            'snippets' => $query !== '' ? $this->buildTextSnippets($content, $query) : [],
            'highlighted_html' => $this->highlightTextMatches($content, $query),
        ];
    }

    private function countTextOccurrences(string $content, string $query): int
    {
        if ($query === '') {
            return 0;
        }

        $count = 0;
        $offset = 0;
        $queryLength = max(1, mb_strlen($query));

        while (($position = mb_stripos($content, $query, $offset)) !== false) {
            $count++;
            $offset = $position + $queryLength;
        }

        return $count;
    }

    private function buildTextSnippets(string $content, string $query, int $limit = 5): array
    {
        if ($query === '') {
            return [];
        }

        $snippets = [];
        $offset = 0;
        $queryLength = max(1, mb_strlen($query));
        $contentLength = mb_strlen($content);

        while (($position = mb_stripos($content, $query, $offset)) !== false && count($snippets) < $limit) {
            $start = max(0, $position - 90);
            $end = min($contentLength, $position + $queryLength + 90);
            $snippet = mb_substr($content, $start, $end - $start);

            $snippets[] = [
                'position' => $position + 1,
                'html' => ($start > 0 ? '... ' : '')
                    . $this->highlightTextMatches($snippet, $query)
                    . ($end < $contentLength ? ' ...' : ''),
            ];

            $offset = $position + $queryLength;
        }

        return $snippets;
    }

    private function highlightTextMatches(string $content, string $query): string
    {
        if ($query === '') {
            return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $html = '';
        $offset = 0;
        $queryLength = max(1, mb_strlen($query));

        while (($position = mb_stripos($content, $query, $offset)) !== false) {
            $html .= htmlspecialchars(mb_substr($content, $offset, $position - $offset), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html .= '<mark class="evidence-text-match rounded bg-amber-300 px-0.5 text-gray-950 transition">'
                . htmlspecialchars(mb_substr($content, $position, $queryLength), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</mark>';
            $offset = $position + $queryLength;
        }

        $html .= htmlspecialchars(mb_substr($content, $offset), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return $html;
    }

    private function annotationsModuleReady(): bool
    {
        return Schema::hasTable('accreditation_evidence_annotations');
    }

    private function pendingAnnotationStatuses(): array
    {
        return [
            AccreditationEvidenceAnnotation::STATUS_OPEN,
            AccreditationEvidenceAnnotation::STATUS_IN_PROGRESS,
        ];
    }

    private function annotationFilterOptions(): array
    {
        return [
            'needs_follow_up' => 'Butuh tindak lanjut',
            AccreditationEvidenceAnnotation::STATUS_OPEN => 'Belum ditindaklanjuti',
            AccreditationEvidenceAnnotation::STATUS_IN_PROGRESS => 'Sedang ditindaklanjuti',
            AccreditationEvidenceAnnotation::STATUS_RESOLVED => 'Selesai',
            'none' => 'Tanpa anotasi',
        ];
    }

    private function sourceFilterOptions(): array
    {
        return [
            'missing' => 'Tanpa sumber',
            'private_file' => 'File privat',
            'dokumen_mutu' => 'Dokumen Mutu',
            'external_url' => 'URL eksternal',
        ];
    }

    private function ensureAnnotationsReady(): void
    {
        abort_unless(
            $this->annotationsModuleReady(),
            503,
            'Fitur anotasi reviewer belum diaktifkan. Jalankan migrasi anotasi pada waktu yang aman.'
        );
    }

    private function ensureAnnotationBelongsToEvidence(
        AccreditationEvidence $accreditationEvidence,
        AccreditationEvidenceAnnotation $annotation,
    ): void {
        abort_unless($annotation->accreditation_evidence_id === $accreditationEvidence->id, 404);
    }

    private function redirectToEvidenceDetail(
        Request $request,
        AccreditationEvidence $accreditationEvidence,
        string $message,
        ?string $fragment = null,
    ): RedirectResponse {
        $params = ['accreditationEvidence' => $accreditationEvidence];

        if ($request->filled('source')) {
            $params['source'] = $request->input('source');
        }

        if ($request->filled('text_q')) {
            $params['text_q'] = $request->input('text_q');
        }

        $url = route('admin.eviden-akreditasi.show', $params);

        if ($fragment) {
            $url .= '#' . $fragment;
        }

        return redirect($url)->with('success', $message);
    }

    private function detectPreviewKind(?string $mimeType, ?string $pathOrUrl, bool $remote): string
    {
        $mimeType = strtolower((string) $mimeType);

        if ($mimeType !== '') {
            if ($mimeType === 'application/pdf') {
                return 'pdf';
            }

            if (str_starts_with($mimeType, 'image/')) {
                return 'image';
            }

            if (str_starts_with($mimeType, 'audio/')) {
                return 'audio';
            }

            if (str_starts_with($mimeType, 'video/')) {
                return 'video';
            }

            if (str_starts_with($mimeType, 'text/')) {
                return 'text';
            }

            if ($this->isOfficeDocumentMime($mimeType)) {
                return 'document';
            }
        }

        $path = parse_url((string) $pathOrUrl, PHP_URL_PATH) ?: (string) $pathOrUrl;
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => 'pdf',
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' => 'image',
            'mp3', 'wav', 'ogg', 'm4a' => 'audio',
            'mp4', 'mov', 'webm', 'ogv' => 'video',
            'txt', 'csv', 'json', 'log', 'md' => 'text',
            'html', 'htm' => 'web',
            'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx' => 'document',
            default => $remote ? 'web' : 'file',
        };
    }

    private function isOfficeDocumentMime(string $mimeType): bool
    {
        return in_array($mimeType, [
            'application/msword',
            'application/vnd.ms-excel',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ], true);
    }

    private function canEmbedPreviewKind(string $kind): bool
    {
        return in_array($kind, ['pdf', 'image', 'audio', 'video', 'text', 'web'], true);
    }

    private function respondWithInlinePrivateFile(string $path, string $filename, ?string $mimeType = null): Response
    {
        return MediaStorage::inlineResponse($path, $filename, false, $mimeType);
    }

    private function authorizeEvidenceRoom(): void
    {
        abort_unless(
            auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->hasRoleAccess('eviden-akreditasi')),
            403,
            'Akses ke ruang eviden akreditasi ditolak.'
        );
    }

    private function ensureModuleReady(): void
    {
        abort_unless(
            Schema::hasTable('accreditation_folders') && Schema::hasTable('accreditation_evidences'),
            503,
            'Modul ruang eviden akreditasi belum diaktifkan. Jalankan migrasi terlebih dahulu pada waktu yang aman.'
        );
    }
}