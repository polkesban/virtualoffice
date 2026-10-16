<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AiInteraction extends Model
{
    // Tabel ini append-only: tidak pernah di-update
    public const UPDATED_AT = null;

    protected $fillable = [
        'session_hash',
        'page_path', 'referrer_path',
        'interaction_type',
        'prompt_preview', 'input_tokens', 'detected_language',
        'model_used', 'output_tokens', 'response_time_ms',
        'was_helpful', 'rating', 'was_fallback',
        'cost_microcents',
        'device_type', 'browser_family',
    ];

    protected $casts = [
        'input_tokens'    => 'integer',
        'output_tokens'   => 'integer',
        'response_time_ms'=> 'integer',
        'cost_microcents' => 'integer',
        'was_helpful'     => 'boolean',
        'was_fallback'    => 'boolean',
        'rating'          => 'integer',
        'created_at'      => 'datetime',
    ];

    // ── Factory helper ────────────────────────────────────────────────────────

    /**
     * Catat interaksi AI secara anonim.
     *
     * Contoh penggunaan di controller:
     *   AiInteraction::log([
     *       'interaction_type' => 'chatbot',
     *       'prompt_preview'   => substr($userMessage, 0, 200),
     *       'input_tokens'     => $tokens,
     *       'model_used'       => 'gemini-1.5-flash',
     *       'response_time_ms' => $elapsed,
     *   ]);
     */
    public static function log(array $data): static
    {
        return static::create(array_merge([
            'session_hash'    => static::hashSession(),
            'page_path'       => static::sanitizePath(request()->path()),
            'referrer_path'   => static::sanitizePath(
                parse_url(request()->header('referer', ''), PHP_URL_PATH) ?? ''
            ),
            'device_type'     => static::detectDevice(),
            'browser_family'  => static::detectBrowser(),
        ], $data));
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** Interaksi hari ini */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /** Filter berdasarkan tipe */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('interaction_type', $type);
    }

    /** Hanya yang sudah dinilai pengguna */
    public function scopeRated(Builder $query): Builder
    {
        return $query->whereNotNull('was_helpful');
    }

    // ── Analitik helpers ─────────────────────────────────────────────────────

    /** Total biaya dalam rupiah (asumsi kurs + harga API) */
    public static function totalCostRupiah(int $microcents, float $kursUsd = 16200): float
    {
        $usd = $microcents / 10_000_000; // microcents → USD
        return round($usd * $kursUsd, 2);
    }

    /** Persentase helpful dari rentang waktu */
    public static function helpfulRate(\DateTimeInterface $from, \DateTimeInterface $to): float
    {
        $rated = static::whereBetween('created_at', [$from, $to])->rated()->count();
        if ($rated === 0) {
            return 0.0;
        }
        $helpful = static::whereBetween('created_at', [$from, $to])
                         ->where('was_helpful', true)
                         ->count();
        return round(($helpful / $rated) * 100, 1);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private static function hashSession(): string
    {
        $sessionId = session()->getId() ?: request()->ip();
        // Salt harian mencegah tracking antar hari (privacy by design)
        $dailySalt = config('app.key') . date('Y-m-d');
        return hash('sha256', $sessionId . $dailySalt);
    }

    private static function sanitizePath(string $path): string
    {
        // Hapus query string agar tidak menyimpan token/data sensitif dari URL
        $clean = strtok($path, '?');
        return '/' . ltrim($clean ?: '', '/');
    }

    private static function detectDevice(): string
    {
        $ua = strtolower(request()->userAgent() ?? '');
        if (str_contains($ua, 'bot') || str_contains($ua, 'crawler') || str_contains($ua, 'spider')) {
            return 'bot';
        }
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'tablet';
        }
        return 'desktop';
    }

    private static function detectBrowser(): string
    {
        $ua = request()->userAgent() ?? '';
        return match (true) {
            str_contains($ua, 'Edg')     => 'Edge',
            str_contains($ua, 'OPR')     => 'Opera',
            str_contains($ua, 'Chrome')  => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari')  => 'Safari',
            default                      => 'Other',
        };
    }
}
