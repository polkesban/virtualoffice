<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnitEvent;
use App\Models\UnitPage;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MeetingRecapController extends Controller
{
    private const RECAP_EVENT_TYPES = ['rapat', 'lainnya'];

    public function index(Request $request): View
    {
        $this->authorizeAccess();

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'unit_page_id' => ['nullable', 'integer', 'exists:unit_pages,id'],
            'status' => ['nullable', Rule::in(array_keys(UnitEvent::$statusLabels))],
            'year' => ['nullable', 'integer', 'min:2020', 'max:' . (now()->year + 1)],
            'completeness' => ['nullable', Rule::in(['complete', 'incomplete'])],
        ]);

        $events = UnitEvent::query()
            ->with([
                'page:id,slug,judul,role',
                'documentation:id,unit_event_id,transcript_text,minutes_html,ai_summary_html,report_html,updated_at',
            ])
            ->withCount([
                'participants',
                'attendances',
                'materials',
                'photos',
                'participants as participant_assignment_letter_count' => fn ($query) => $query->whereNotNull('surat_tugas_path'),
            ])
            ->whereIn('jenis_kegiatan', self::RECAP_EVENT_TYPES)
            ->when($filters['unit_page_id'] ?? null, fn ($query, $unitPageId) => $query->where('unit_page_id', $unitPageId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['year'] ?? null, fn ($query, $year) => $query->whereYear('mulai_at', $year))
            ->when($filters['q'] ?? null, function ($query, $keyword) {
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery->where('judul', 'like', '%' . $keyword . '%')
                        ->orWhere('ringkasan', 'like', '%' . $keyword . '%')
                        ->orWhere('lokasi', 'like', '%' . $keyword . '%')
                        ->orWhereHas('page', fn ($pageQuery) => $pageQuery->where('judul', 'like', '%' . $keyword . '%'));
                });
            })
            ->orderByDesc('mulai_at')
            ->get();

        $recaps = $events
            ->map(fn (UnitEvent $event) => $this->buildRecap($event));

        if (($filters['completeness'] ?? null) === 'complete') {
            $recaps = $recaps->where('is_complete', true)->values();
        }

        if (($filters['completeness'] ?? null) === 'incomplete') {
            $recaps = $recaps->where('is_complete', false)->values();
        }

        $stats = [
            'meetings' => $recaps->count(),
            'units' => $recaps->pluck('event.unit_page_id')->filter()->unique()->count(),
            'complete' => $recaps->where('is_complete', true)->count(),
            'follow_up' => $recaps->where('is_complete', false)->count(),
        ];

        $unitPages = UnitPage::query()
            ->whereHas('events', fn ($query) => $query->whereIn('jenis_kegiatan', self::RECAP_EVENT_TYPES))
            ->orderBy('judul')
            ->get(['id', 'judul', 'slug']);

        $availableYears = UnitEvent::query()
            ->whereIn('jenis_kegiatan', self::RECAP_EVENT_TYPES)
            ->whereNotNull('mulai_at')
            ->orderByDesc('mulai_at')
            ->get(['mulai_at'])
            ->pluck('mulai_at')
            ->filter()
            ->map(fn ($date) => $date->year)
            ->unique()
            ->values();

        return view('admin.meeting-recaps.index', [
            'recaps' => $recaps,
            'stats' => $stats,
            'filters' => $filters,
            'unitPages' => $unitPages,
            'availableYears' => $availableYears,
        ]);
    }

    public function show(UnitEvent $unitEvent): View
    {
        $this->authorizeAccess();

        $unitEvent->load([
            'page:id,slug,judul,role',
            'creator:id,name',
            'documentation:id,unit_event_id,transcript_text,minutes_html,ai_summary_html,report_html,report_notes,updated_at',
            'photos',
        ]);

        $unitEvent->loadCount([
            'participants',
            'attendances',
            'materials',
            'photos',
            'participants as participant_assignment_letter_count' => fn ($query) => $query->whereNotNull('surat_tugas_path'),
        ]);

        $recap = $this->buildRecap($unitEvent);

        $documentSections = [
            [
                'title' => 'Transkrip Rapat',
                'content' => $unitEvent->documentation?->transcript_text,
                'is_html' => false,
                'empty' => 'Belum ada hasil voice to text atau transkripsi audio yang tersimpan.',
            ],
            [
                'title' => 'Notulen Final',
                'content' => $unitEvent->documentation?->minutes_html,
                'is_html' => true,
                'empty' => 'Notulen final belum diisi.',
            ],
            [
                'title' => 'Resume AI',
                'content' => $unitEvent->documentation?->ai_summary_html,
                'is_html' => true,
                'empty' => 'Resume AI belum dihasilkan.',
            ],
            [
                'title' => 'Draft Laporan',
                'content' => $unitEvent->documentation?->report_html,
                'is_html' => true,
                'empty' => 'Laporan rapat belum disusun.',
            ],
        ];

        return view('admin.meeting-recaps.show', [
            'unitEvent' => $unitEvent,
            'recap' => $recap,
            'documentSections' => $documentSections,
        ]);
    }

    private function authorizeAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && $user->canAccessMeetingRecaps(),
            403,
            'Akses ditolak. Halaman rekap rapat hanya tersedia untuk pimpinan yang berwenang.'
        );
    }

    private function buildRecap(UnitEvent $event): array
    {
        $documentation = $event->documentation;
        $participantsCount = (int) ($event->participants_count ?? 0);
        $attendanceRecordsCount = (int) ($event->attendances_count ?? 0);
        $materialsCount = (int) ($event->materials_count ?? 0);
        $photosCount = (int) ($event->photos_count ?? 0);
        $participantAssignmentLetterCount = (int) ($event->participant_assignment_letter_count ?? 0);

        $checklist = [
            'surat_tugas' => [
                'label' => 'Surat tugas kegiatan',
                'ready' => filled($event->surat_tugas_event_path),
                'detail' => filled($event->surat_tugas_event_path)
                    ? 'Dokumen surat tugas kegiatan sudah diunggah.'
                    : 'Belum ada surat tugas kegiatan.',
            ],
            'participants' => [
                'label' => 'Daftar peserta',
                'ready' => $participantsCount > 0,
                'detail' => $participantsCount > 0
                    ? $participantsCount . ' peserta, panitia, atau narasumber sudah tercatat.'
                    : 'Belum ada daftar peserta yang tercatat.',
            ],
            'attendance' => [
                'label' => 'Daftar hadir',
                'ready' => $attendanceRecordsCount > 0,
                'detail' => $attendanceRecordsCount > 0
                    ? $attendanceRecordsCount . ' catatan kehadiran sudah masuk.'
                    : 'Belum ada catatan kehadiran yang terekam.',
            ],
            'transcript' => [
                'label' => 'Transkrip rapat',
                'ready' => $this->hasPlainText($documentation?->transcript_text),
                'detail' => $this->hasPlainText($documentation?->transcript_text)
                    ? $this->excerpt($documentation?->transcript_text, false, 120)
                    : 'Belum ada hasil transkripsi rapat.',
            ],
            'minutes' => [
                'label' => 'Notulen final',
                'ready' => $this->hasHtmlContent($documentation?->minutes_html),
                'detail' => $this->hasHtmlContent($documentation?->minutes_html)
                    ? $this->excerpt($documentation?->minutes_html, true, 120)
                    : 'Notulen final belum disusun.',
            ],
            'summary' => [
                'label' => 'Resume AI',
                'ready' => $this->hasHtmlContent($documentation?->ai_summary_html),
                'detail' => $this->hasHtmlContent($documentation?->ai_summary_html)
                    ? $this->excerpt($documentation?->ai_summary_html, true, 120)
                    : 'Resume AI belum tersedia.',
            ],
            'report' => [
                'label' => 'Laporan rapat',
                'ready' => $this->hasHtmlContent($documentation?->report_html),
                'detail' => $this->hasHtmlContent($documentation?->report_html)
                    ? $this->excerpt($documentation?->report_html, true, 120)
                    : 'Laporan rapat belum diisi.',
            ],
            'photos' => [
                'label' => 'Foto kegiatan',
                'ready' => $photosCount > 0,
                'detail' => $photosCount > 0
                    ? $photosCount . ' foto kegiatan terdokumentasi.'
                    : 'Belum ada foto kegiatan yang diunggah.',
            ],
        ];

        $readyCount = collect($checklist)->where('ready', true)->count();
        $totalCount = count($checklist);
        $completionPercent = (int) round(($readyCount / max($totalCount, 1)) * 100);
        $missingLabels = collect($checklist)
            ->reject(fn (array $item) => $item['ready'])
            ->map(fn (array $item) => $item['label'])
            ->values()
            ->all();

        return [
            'event' => $event,
            'checklist' => $checklist,
            'ready_count' => $readyCount,
            'total_count' => $totalCount,
            'completion_percent' => $completionPercent,
            'is_complete' => $readyCount === $totalCount,
            'missing_labels' => $missingLabels,
            'participants_count' => $participantsCount,
            'attendance_records_count' => $attendanceRecordsCount,
            'materials_count' => $materialsCount,
            'photos_count' => $photosCount,
            'participant_assignment_letter_count' => $participantAssignmentLetterCount,
            'document_links' => [
                'surat_tugas' => $this->publicStorageUrl($event->surat_tugas_event_path),
                'flyer' => $this->publicStorageUrl($event->flyer_path),
                'public_event' => $event->page && in_array($event->status, ['published', 'completed'], true)
                    ? route('unit.events.show', [$event->page->slug, $event->slug])
                    : null,
            ],
        ];
    }

    private function publicStorageUrl(?string $path): ?string
    {
        return MediaStorage::url($path);
    }

    private function hasHtmlContent(?string $value): bool
    {
        return $this->normalizedText($value, true) !== '';
    }

    private function hasPlainText(?string $value): bool
    {
        return $this->normalizedText($value, false) !== '';
    }

    private function excerpt(?string $value, bool $stripHtml, int $limit): string
    {
        return Str::limit($this->normalizedText($value, $stripHtml), $limit);
    }

    private function normalizedText(?string $value, bool $stripHtml): string
    {
        $text = (string) ($value ?? '');

        if ($stripHtml) {
            $text = strip_tags($text);
        }

        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';

        return trim($text);
    }
}