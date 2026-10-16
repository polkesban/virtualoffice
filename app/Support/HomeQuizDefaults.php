<?php

namespace App\Support;

use RuntimeException;

class HomeQuizDefaults
{
    private const PAYLOAD = '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';

    /** @var array<string, mixed>|null */
    private static ?array $payload = null;

    public static function questions(): array
    {
        return self::payload()['questions'] ?? [];
    }

    public static function map(): array
    {
        return self::payload()['map'] ?? [];
    }

    public static function map2(): array
    {
        return self::payload()['map2'] ?? [];
    }

    public static function defaultResultKey(): string
    {
        return (string) (self::payload()['default_result_key'] ?? 'kep-klinis-d4');
    }

    /** @return array<string, mixed> */
    private static function payload(): array
    {
        if (self::$payload !== null) {
            return self::$payload;
        }

        $decoded = base64_decode(self::PAYLOAD, true);
        if ($decoded === false) {
            throw new RuntimeException('Home quiz payload base64 is invalid.');
        }

        $json = gzdecode($decoded);
        if ($json === false) {
            throw new RuntimeException('Home quiz payload gzip is invalid.');
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::$payload = $payload;

        return self::$payload;
    }
}