<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Berita extends Model
{
    protected $table = 'berita';

    protected $fillable = [
        'user_id', 'judul', 'slug', 'ringkasan', 'isi',
        'gambar_sampul', 'gambar_sampul_alt', 'kategori',
        'tags', 'status', 'published_at', 'meta_description',
    ];

    protected $casts = [
        'tags'         => 'array',
        'published_at' => 'datetime',
        'views'        => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function penulis(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
                     ->where('published_at', '<=', now());
    }

    public function scopeKategori(Builder $query, string $kategori): Builder
    {
        return $query->where('kategori', $kategori);
    }

    public function scopeTerbaru(Builder $query): Builder
    {
        return $query->orderBy('published_at', 'desc');
    }

    // ── Route Model Binding ──────────────────────────────────────────────────

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ── Accessor ────────────────────────────────────────────────────────────

    public function getTanggalFormatAttribute(): string
    {
        return $this->published_at
            ? $this->published_at->translatedFormat('d F Y')
            : '-';
    }
}
