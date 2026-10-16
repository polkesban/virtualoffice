<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentChunk extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'document_id', 'chunk_index', 'content', 'embedding', 'token_estimate',
    ];

    protected $casts = [
        'chunk_index'    => 'integer',
        'token_estimate' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'document_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Decode the JSON embedding float array. */
    public function getEmbeddingArray(): array
    {
        return $this->embedding ? json_decode($this->embedding, true) : [];
    }
}
