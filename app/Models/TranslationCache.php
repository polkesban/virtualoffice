<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Permanent database store for AI-translated strings.
 *
 * @property int    $id
 * @property string $locale
 * @property string $source_hash   SHA-256 of trimmed source text
 * @property string $source_text
 * @property string $translated_text
 * @property string $source         'ai' | 'glossary' | 'manual'
 */
class TranslationCache extends Model
{
    protected $table = 'translation_cache';

    protected $fillable = [
        'locale',
        'source_hash',
        'source_text',
        'translated_text',
        'source',
    ];

    // ── Helpers ──────────────────────────────────────────────────────────────

    public static function hashFor(string $text): string
    {
        return hash('sha256', trim($text));
    }

    /** Find a stored translation or return null. */
    public static function findTranslation(string $locale, string $text): ?string
    {
        $row = static::where('locale', $locale)
            ->where('source_hash', static::hashFor($text))
            ->value('translated_text');

        return is_string($row) && $row !== '' ? $row : null;
    }

    /** Upsert (insert or update) a translation. */
    public static function storeTranslation(
        string $locale,
        string $sourceText,
        string $translatedText,
        string $source = 'ai',
    ): void {
        static::updateOrCreate(
            [
                'locale'      => $locale,
                'source_hash' => static::hashFor($sourceText),
            ],
            [
                'source_text'     => mb_substr(trim($sourceText), 0, 65535),
                'translated_text' => $translatedText,
                'source'          => $source,
            ]
        );
    }

    /** Count untranslated strings for a locale (used in artisan command). */
    public static function countForLocale(string $locale): int
    {
        return static::where('locale', $locale)->count();
    }
}
