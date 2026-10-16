<?php

namespace App\Support;

use App\Services\Localization\PublicWebsiteTranslator;
use Illuminate\Http\Request;

class PublicLocale
{
    public static function supported(): array
    {
        return config('app.supported_locales', [
            'id' => 'Bahasa Indonesia',
            'en' => 'English',
        ]);
    }

    public static function normalize(?string $locale): string
    {
        $supported = array_keys(self::supported());
        $fallback = (string) config('app.fallback_locale', config('app.locale', 'id'));
        $candidate = is_string($locale) ? strtolower(str_replace('_', '-', trim($locale))) : '';

        if ($candidate !== '') {
            $baseLocale = explode('-', $candidate)[0];

            if (in_array($baseLocale, $supported, true)) {
                return $baseLocale;
            }
        }

        return in_array($fallback, $supported, true) ? $fallback : ($supported[0] ?? 'id');
    }

    public static function current(): string
    {
        return self::normalize(app()->getLocale());
    }

    public static function default(): string
    {
        return self::normalize((string) config('app.fallback_locale', config('app.locale', 'id')));
    }

    public static function isDefault(?string $locale = null): bool
    {
        return self::normalize($locale ?? self::current()) === self::default();
    }

    public static function cookieName(): string
    {
        return (string) config('ai.website_translation.locale_cookie', 'polkesban_locale');
    }

    public static function localeTag(?string $locale = null): string
    {
        return match (self::normalize($locale ?? self::current())) {
            'en' => 'en-US',
            default => 'id-ID',
        };
    }

    public static function currencyCode(?string $locale = null): string
    {
        return 'IDR';
    }

    public static function translateData(mixed $value, ?string $locale = null): mixed
    {
        $targetLocale = self::normalize($locale ?? self::current());

        if (self::isDefault($targetLocale)) {
            return $value;
        }

        return app(PublicWebsiteTranslator::class)->translateData($value, $targetLocale);
    }

    public static function switchUrl(string $locale, ?Request $request = null): string
    {
        $request ??= request();

        return $request->fullUrlWithQuery([
            'lang' => self::normalize($locale),
        ]);
    }
}