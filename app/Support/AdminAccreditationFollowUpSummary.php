<?php

namespace App\Support;

use App\Models\AccreditationEvidence;
use App\Models\AccreditationEvidenceAnnotation;
use App\Models\AccreditationFolder;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class AdminAccreditationFollowUpSummary
{
    private static array $cache = [];

    public function forUser(?User $user): ?array
    {
        $cacheKey = $user?->id ? 'user:' . $user->id : 'guest';

        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }

        return self::$cache[$cacheKey] = $this->build($user);
    }

    private function build(?User $user): ?array
    {
        if (! $user || (! $user->isSuperAdmin() && ! $user->hasRoleAccess('eviden-akreditasi'))) {
            return null;
        }

        if (! $this->moduleReady()) {
            return null;
        }

        $pendingStatuses = $this->pendingStatuses();
        $today = now()->toDateString();
        $inThirtyDays = now()->addDays(30)->toDateString();

        $pendingEvidenceQuery = AccreditationEvidence::query()->whereHas('annotations', function ($query) use ($pendingStatuses) {
            $query->whereIn('status', $pendingStatuses);
        });
        $missingSourcesQuery = AccreditationEvidence::query()
            ->whereNull('dokumen_mutu_id')
            ->whereNull('external_url')
            ->whereNull('file_path');
        $reviewDueSoonQuery = AccreditationEvidence::query()
            ->whereNotNull('review_due_at')
            ->whereDate('review_due_at', '>=', $today)
            ->whereDate('review_due_at', '<=', $inThirtyDays);
        $reviewOverdueQuery = AccreditationEvidence::query()
            ->whereNotNull('review_due_at')
            ->whereDate('review_due_at', '<', $today);
        $expiredQuery = AccreditationEvidence::query()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', $today);
        $expiresSoonQuery = AccreditationEvidence::query()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '>=', $today)
            ->whereDate('expires_at', '<=', $inThirtyDays);

        $open = AccreditationEvidenceAnnotation::query()
            ->where('status', AccreditationEvidenceAnnotation::STATUS_OPEN)
            ->count();
        $inProgress = AccreditationEvidenceAnnotation::query()
            ->where('status', AccreditationEvidenceAnnotation::STATUS_IN_PROGRESS)
            ->count();
        $resolved = AccreditationEvidenceAnnotation::query()
            ->where('status', AccreditationEvidenceAnnotation::STATUS_RESOLVED)
            ->count();

        return [
            'open' => $open,
            'in_progress' => $inProgress,
            'resolved' => $resolved,
            'active_annotations' => $open + $inProgress,
            'total' => AccreditationEvidenceAnnotation::query()->count(),
            'pending_evidences' => (clone $pendingEvidenceQuery)->count(),
            'missing_sources' => (clone $missingSourcesQuery)->count(),
            'review_overdue' => (clone $reviewOverdueQuery)->count(),
            'review_due_soon' => (clone $reviewDueSoonQuery)->count(),
            'expired' => (clone $expiredQuery)->count(),
            'expires_soon' => (clone $expiresSoonQuery)->count(),
            'priority_folders' => $this->priorityFoldersSummary($pendingStatuses, $today, $inThirtyDays),
            'priority_pics' => $this->priorityPicsSummary($pendingStatuses, $today, $inThirtyDays),
            'index_url' => route('admin.eviden-akreditasi.index', ['annotation_status' => 'needs_follow_up']),
            'active_annotations_url' => route('admin.eviden-akreditasi.index', ['annotation_status' => 'needs_follow_up']),
            'missing_sources_url' => route('admin.eviden-akreditasi.index', ['source_state' => 'missing']),
            'review_overdue_url' => route('admin.eviden-akreditasi.index', ['review' => 'review_overdue']),
            'review_overdue_csv_export_url' => route('admin.eviden-akreditasi.export', [
                'review' => 'review_overdue',
                'format' => 'csv',
            ]),
            'review_overdue_xlsx_export_url' => route('admin.eviden-akreditasi.export', [
                'review' => 'review_overdue',
                'format' => 'xlsx',
            ]),
            'review_due_soon_url' => route('admin.eviden-akreditasi.index', ['review' => 'review_30']),
            'expired_url' => route('admin.eviden-akreditasi.index', ['review' => 'expired']),
            'expires_soon_url' => route('admin.eviden-akreditasi.index', ['review' => 'expires_30']),
            'xlsx_export_url' => route('admin.eviden-akreditasi.annotations.export', [
                'annotation_status' => 'needs_follow_up',
                'format' => 'xlsx',
            ]),
        ];
    }

    private function moduleReady(): bool
    {
        return Schema::hasTable('accreditation_folders')
            && Schema::hasTable('accreditation_evidences')
            && Schema::hasTable('accreditation_evidence_annotations');
    }

    private function pendingStatuses(): array
    {
        return [
            AccreditationEvidenceAnnotation::STATUS_OPEN,
            AccreditationEvidenceAnnotation::STATUS_IN_PROGRESS,
        ];
    }

    private function priorityFoldersSummary(array $pendingStatuses, string $today, string $inThirtyDays): array
    {
        return AccreditationFolder::query()
            ->withCount('evidences')
            ->withCount([
                'evidences as pending_annotation_evidences_count' => fn ($query) => $query->whereHas('annotations', function ($annotationQuery) use ($pendingStatuses) {
                    $annotationQuery->whereIn('status', $pendingStatuses);
                }),
                'evidences as missing_source_evidences_count' => fn ($query) => $query
                    ->whereNull('dokumen_mutu_id')
                    ->whereNull('external_url')
                    ->whereNull('file_path'),
                'evidences as review_overdue_evidences_count' => fn ($query) => $query
                    ->whereNotNull('review_due_at')
                    ->whereDate('review_due_at', '<', $today),
                'evidences as review_due_soon_evidences_count' => fn ($query) => $query
                    ->whereNotNull('review_due_at')
                    ->whereDate('review_due_at', '>=', $today)
                    ->whereDate('review_due_at', '<=', $inThirtyDays),
                'evidences as expiry_attention_evidences_count' => fn ($query) => $query->where(function ($expiryQuery) use ($today, $inThirtyDays) {
                    $expiryQuery->where(function ($innerQuery) use ($today) {
                        $innerQuery->whereNotNull('expires_at')
                            ->whereDate('expires_at', '<', $today);
                    })->orWhere(function ($innerQuery) use ($today, $inThirtyDays) {
                        $innerQuery->whereNotNull('expires_at')
                            ->whereDate('expires_at', '>=', $today)
                            ->whereDate('expires_at', '<=', $inThirtyDays);
                    });
                }),
            ])
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->map(function (AccreditationFolder $folder) {
                $isEmpty = (int) $folder->evidences_count === 0;
                $priorityScore = ((int) $folder->pending_annotation_evidences_count * 100)
                    + ((int) $folder->missing_source_evidences_count * 20)
                    + (((int) $folder->review_overdue_evidences_count + (int) $folder->review_due_soon_evidences_count + (int) $folder->expiry_attention_evidences_count) * 10)
                    + ($isEmpty ? 5 : 0);

                return [
                    'label' => $folder->display_label,
                    'url' => route('admin.eviden-akreditasi.index', array_filter([
                        'folder' => $folder->id,
                        'annotation_status' => $folder->pending_annotation_evidences_count > 0 ? 'needs_follow_up' : null,
                    ], fn ($value) => filled($value))),
                    'review_overdue_url' => route('admin.eviden-akreditasi.index', [
                        'folder' => $folder->id,
                        'review' => 'review_overdue',
                    ]),
                    'review_due_soon_url' => route('admin.eviden-akreditasi.index', [
                        'folder' => $folder->id,
                        'review' => 'review_30',
                    ]),
                    'expiry_attention_url' => route('admin.eviden-akreditasi.index', [
                        'folder' => $folder->id,
                        'review' => 'expiry_attention',
                    ]),
                    'pending_annotations' => (int) $folder->pending_annotation_evidences_count,
                    'missing_sources' => (int) $folder->missing_source_evidences_count,
                    'review_overdue' => (int) $folder->review_overdue_evidences_count,
                    'review_due_soon' => (int) $folder->review_due_soon_evidences_count,
                    'expiry_attention' => (int) $folder->expiry_attention_evidences_count,
                    'evidences_count' => (int) $folder->evidences_count,
                    'is_empty' => $isEmpty,
                    'priority_score' => $priorityScore,
                ];
            })
            ->filter(fn (array $folder) => $folder['priority_score'] > 0)
            ->sortByDesc('priority_score')
            ->take(3)
            ->values()
            ->all();
    }

    private function priorityPicsSummary(array $pendingStatuses, string $today, string $inThirtyDays): array
    {
        return AccreditationEvidence::query()
            ->select(['id', 'pic_name', 'dokumen_mutu_id', 'external_url', 'file_path', 'review_due_at', 'expires_at'])
            ->withCount([
                'annotations as pending_annotations_count' => fn ($query) => $query->whereIn('status', $pendingStatuses),
            ])
            ->get()
            ->filter(fn (AccreditationEvidence $evidence) => filled(trim((string) $evidence->pic_name)))
            ->groupBy(fn (AccreditationEvidence $evidence) => trim((string) $evidence->pic_name))
            ->map(function ($evidences, string $picName) use ($today, $inThirtyDays) {
                $pendingEvidenceCount = $evidences->filter(fn (AccreditationEvidence $evidence) => (int) $evidence->pending_annotations_count > 0)->count();
                $reviewOverdueCount = $evidences->filter(function (AccreditationEvidence $evidence) use ($today) {
                    $reviewDueAt = $evidence->review_due_at?->toDateString();

                    return $reviewDueAt !== null && $reviewDueAt < $today;
                })->count();
                $missingSourceCount = $evidences->filter(function (AccreditationEvidence $evidence) {
                    return ! filled($evidence->dokumen_mutu_id)
                        && ! filled($evidence->external_url)
                        && ! filled($evidence->file_path);
                })->count();
                $reviewDueSoonCount = $evidences->filter(function (AccreditationEvidence $evidence) use ($today, $inThirtyDays) {
                    $reviewDueAt = $evidence->review_due_at?->toDateString();

                    return $reviewDueAt !== null && $reviewDueAt >= $today && $reviewDueAt <= $inThirtyDays;
                })->count();
                $expiryAttentionCount = $evidences->filter(function (AccreditationEvidence $evidence) use ($today, $inThirtyDays) {
                    $expiresAt = $evidence->expires_at?->toDateString();

                    return $expiresAt !== null && ($expiresAt < $today || ($expiresAt >= $today && $expiresAt <= $inThirtyDays));
                })->count();
                $priorityScore = ($pendingEvidenceCount * 100)
                    + ($missingSourceCount * 20)
                    + (($reviewOverdueCount + $reviewDueSoonCount + $expiryAttentionCount) * 10);

                return [
                    'name' => $picName,
                    'url' => route('admin.eviden-akreditasi.index', ['pic' => $picName]),
                    'review_overdue_url' => route('admin.eviden-akreditasi.index', [
                        'pic' => $picName,
                        'review' => 'review_overdue',
                    ]),
                    'review_overdue_csv_export_url' => route('admin.eviden-akreditasi.export', [
                        'pic' => $picName,
                        'review' => 'review_overdue',
                        'format' => 'csv',
                    ]),
                    'review_overdue_xlsx_export_url' => route('admin.eviden-akreditasi.export', [
                        'pic' => $picName,
                        'review' => 'review_overdue',
                        'format' => 'xlsx',
                    ]),
                    'pending_evidences' => $pendingEvidenceCount,
                    'review_overdue' => $reviewOverdueCount,
                    'review_due_soon' => $reviewDueSoonCount,
                    'expiry_attention' => $expiryAttentionCount,
                    'missing_sources' => $missingSourceCount,
                    'total_evidences' => $evidences->count(),
                    'priority_score' => $priorityScore,
                ];
            })
            ->filter(fn (array $pic) => $pic['priority_score'] > 0)
            ->sortByDesc('priority_score')
            ->take(4)
            ->values()
            ->all();
    }
}