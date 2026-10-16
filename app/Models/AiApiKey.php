<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiApiKey extends Model
{
    protected $fillable = [
        'label',
        'provider',
        'api_key',
        'priority',
        'is_active',
        'is_exhausted',
        'exhausted_at',
        'last_used_at',
        'fail_count',
    ];

    protected $casts = [
        'api_key'      => 'encrypted',
        'is_active'    => 'boolean',
        'is_exhausted' => 'boolean',
        'exhausted_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    // ── Scopes ─────────────────────────────────────────────────────────────────

    /** Keys that are active, not exhausted (or exhausted more than 2 minutes ago), ordered by priority asc */
    public function scopeAvailable($query, string $provider = 'gemini')
    {
        return $query->where('provider', $provider)
                     ->where('is_active', true)
                     ->where(function ($q) {
                         $q->where('is_exhausted', false)
                           ->orWhere('exhausted_at', '<', now()->subMinutes(2));
                     })
                     ->orderBy('priority')
                     ->orderBy('id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function statusBadge(): array
    {
        if (! $this->is_active) {
            return ['label' => 'Nonaktif', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'];
        }
        if ($this->is_exhausted) {
            return ['label' => 'Habis', 'class' => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300'];
        }
        return ['label' => 'Aktif', 'class' => 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300'];
    }

    /** Mask API key for display: AIza...Xy9 */
    public function maskedKey(): string
    {
        $key = $this->api_key;
        if (strlen($key) <= 8) {
            return str_repeat('*', strlen($key));
        }
        return substr($key, 0, 6) . '...' . substr($key, -4);
    }

    /** Reset exhausted status (refill detected or manual reset) */
    public function resetExhausted(): void
    {
        $this->update([
            'is_exhausted' => false,
            'exhausted_at' => null,
            'fail_count'   => 0,
        ]);
    }

    public function markExhausted(): void
    {
        $this->update([
            'is_exhausted' => true,
            'exhausted_at' => now(),
            'fail_count' => (int) $this->fail_count + 1,
        ]);
    }

    public function markUsed(): void
    {
        $this->update([
            'is_exhausted' => false,
            'exhausted_at' => null,
            'last_used_at' => now(),
            'fail_count' => 0,
        ]);
    }
}
