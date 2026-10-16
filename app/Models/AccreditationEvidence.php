<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccreditationEvidence extends Model
{
    public const STORAGE_DISK = 'local';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY = 'ready';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'accreditation_evidences';

    protected $fillable = [
        'folder_id',
        'dokumen_mutu_id',
        'title',
        'subcriterion_code',
        'pic_name',
        'verification_status',
        'summary',
        'notes',
        'issued_at',
        'expires_at',
        'review_due_at',
        'verified_at',
        'external_url',
        'file_path',
        'file_name',
        'mime_type',
        'size_bytes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
        'review_due_at' => 'date',
        'verified_at' => 'datetime',
        'size_bytes' => 'integer',
    ];

    public static function verificationStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_READY => 'Siap Review',
            self::STATUS_VERIFIED => 'Terverifikasi',
            self::STATUS_ARCHIVED => 'Arsip',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(AccreditationFolder::class, 'folder_id');
    }

    public function dokumenMutu(): BelongsTo
    {
        return $this->belongsTo(DokumenMutu::class, 'dokumen_mutu_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(AccreditationEvidenceAnnotation::class, 'accreditation_evidence_id')
            ->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->orderByDesc('id');
    }

    public function getVerificationStatusLabelAttribute(): string
    {
        return self::verificationStatusOptions()[$this->verification_status] ?? $this->verification_status;
    }

    public function hasUploadedFile(): bool
    {
        return filled($this->file_path);
    }

    public function hasDokumenMutuLink(): bool
    {
        return filled($this->dokumenMutu?->file_url);
    }

    public function hasExternalUrl(): bool
    {
        return filled($this->external_url);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    public function getExpiresSoonAttribute(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->expires_at->isFuture() && $this->expires_at->lessThanOrEqualTo(now()->addDays(30));
    }

    public function getReviewDueSoonAttribute(): bool
    {
        if (! $this->review_due_at) {
            return false;
        }

        return $this->review_due_at->greaterThanOrEqualTo(now()->startOfDay())
            && $this->review_due_at->lessThanOrEqualTo(now()->addDays(30));
    }

    public function getReviewOverdueAttribute(): bool
    {
        if (! $this->review_due_at) {
            return false;
        }

        return $this->review_due_at->lessThan(now()->startOfDay());
    }

    public function getHumanFileSizeAttribute(): ?string
    {
        if (! $this->size_bytes) {
            return null;
        }

        $size = (float) $this->size_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return number_format($size, $unitIndex === 0 ? 0 : 1, ',', '.') . ' ' . $units[$unitIndex];
    }
}