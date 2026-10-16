<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentJob extends Model
{
    protected $fillable = [
        'user_id', 'title', 'media_path', 'media_type', 'metadata',
        'status', 'ai_image_description', 'generated_title',
        'generated_article', 'generated_caption', 'generated_hashtags',
        'model_used', 'tokens_used', 'error_message', 'published_at',
    ];

    protected $casts = [
        'metadata'           => 'array',
        'generated_hashtags' => 'array',
        'tokens_used'        => 'integer',
        'published_at'       => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function statusBadge(): array
    {
        return match ($this->status) {
            'done'        => ['label' => 'Siap',         'class' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'],
            'processing'  => ['label' => 'Memproses…',   'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'],
            'published'   => ['label' => 'Dipublikasi',  'class' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'],
            'error'       => ['label' => 'Error',        'class' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'],
            default       => ['label' => 'Pending',      'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'],
        };
    }

    public function isImage(): bool
    {
        return $this->media_type === 'image';
    }

    /** Return public URL for the uploaded media (proxied through admin route). */
    public function mediaPreviewUrl(): string
    {
        return route('admin.content-engine.media', $this->id);
    }
}
