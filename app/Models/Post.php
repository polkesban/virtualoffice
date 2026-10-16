<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'title', 'slug', 'excerpt', 'content', 'content_format',
        'featured_image', 'featured_image_alt', 'featured_image_caption',
        'type', 'tags', 'category',
        'status', 'published_at', 'scheduled_at',
        'views', 'reading_time_minutes', 'is_featured', 'allow_comments',
        'meta_title', 'meta_description', 'meta_keywords',
        'og_title', 'og_description', 'og_image', 'canonical_url', 'robots',
        'source_url', 'source_author',
    ];

    protected $casts = [
        'tags'                  => 'array',
        'published_at'          => 'datetime',
        'scheduled_at'          => 'datetime',
        'is_featured'           => 'boolean',
        'allow_comments'        => 'boolean',
        'views'                 => 'integer',
        'reading_time_minutes'  => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
                     ->where('published_at', '<=', now());
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /** Full-text search — menggunakan MariaDB FULLTEXT index ft_posts_search */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->whereRaw(
            'MATCH(title, excerpt, content) AGAINST(? IN BOOLEAN MODE)',
            ['+' . implode('* +', explode(' ', trim($term))) . '*']
        );
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    /** Hitung reading time dari jumlah kata (rata-rata 200 kata/menit) */
    public function recalculateReadingTime(): int
    {
        $words = str_word_count(strip_tags($this->content ?? ''));
        return max(1, (int) ceil($words / 200));
    }

    /** SEO title: meta_title → title situs */
    public function getSeoTitleAttribute(): string
    {
        return $this->meta_title ?: $this->title;
    }

    /** SEO description: meta_description → excerpt → 160 char konten */
    public function getSeoDescriptionAttribute(): string
    {
        return $this->meta_description
            ?: $this->excerpt
            ?: Str::limit(strip_tags($this->content ?? ''), 160);
    }

    // ── Mutators ────────────────────────────────────────────────────────────

    /** Auto-generate slug dari title jika belum diisi */
    protected static function booted(): void
    {
        static::creating(function (Post $post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
            if (empty($post->reading_time_minutes)) {
                $post->reading_time_minutes = $post->recalculateReadingTime();
            }
        });
    }
}
