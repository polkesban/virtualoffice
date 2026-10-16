<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccreditationEvidenceAnnotation extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';

    protected $table = 'accreditation_evidence_annotations';

    protected $attributes = [
        'status' => self::STATUS_OPEN,
        'is_pinned' => false,
    ];

    protected $fillable = [
        'accreditation_evidence_id',
        'title',
        'excerpt',
        'note',
        'status',
        'is_pinned',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_OPEN => 'Belum Ditindaklanjuti',
            self::STATUS_IN_PROGRESS => 'Sedang Ditindaklanjuti',
            self::STATUS_RESOLVED => 'Selesai',
        ];
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(AccreditationEvidence::class, 'accreditation_evidence_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions()[$this->status ?: self::STATUS_OPEN] ?? ($this->status ?: self::STATUS_OPEN);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status ?: self::STATUS_OPEN) {
            self::STATUS_IN_PROGRESS => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
            self::STATUS_RESOLVED => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
            default => 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
        };
    }
}