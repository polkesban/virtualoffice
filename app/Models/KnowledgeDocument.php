<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeDocument extends Model
{
    protected $fillable = [
        'name', 'original_filename', 'file_path', 'file_type',
        'status', 'chunk_count', 'description', 'error_message',
        'indexed_at', 'uploaded_by',
    ];

    protected $casts = [
        'chunk_count' => 'integer',
        'indexed_at'  => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class, 'document_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isIndexed(): bool
    {
        return $this->status === 'indexed';
    }

    public function statusBadge(): array
    {
        if ($this->status === 'indexed') {
            return ['label' => 'Terindeks', 'class' => 'bg-green-100 text-green-800', 'progress' => 100];
        }

        if (str_starts_with($this->status, 'indexing:')) {
            $progressStr = substr($this->status, 9);
            $parts = explode('/', $progressStr);
            $current = (int)($parts[0] ?? 0);
            $total = (int)($parts[1] ?? 1);
            $pct = $total > 0 ? round(($current / $total) * 100) : 0;
            return [
                'label' => "Mengindeks… ({$pct}%)",
                'class' => 'bg-blue-100 text-blue-800',
                'progress' => $pct
            ];
        }

        if ($this->status === 'indexing') {
            return ['label' => 'Mengindeks…', 'class' => 'bg-blue-100 text-blue-800', 'progress' => 0];
        }

        return ['label' => 'Dalam Antrean', 'class' => 'bg-yellow-100 text-yellow-800', 'progress' => 0];
    }
}
