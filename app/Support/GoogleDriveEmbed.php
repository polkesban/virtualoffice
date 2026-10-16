<?php

namespace App\Support;

class GoogleDriveEmbed
{
    /** @return array{input_url:string,embed_url:?string,open_url:?string,id:?string,kind:?string,mode:string} */
    public static function fromUrl(?string $url, ?string $mode = 'list'): array
    {
        $inputUrl = trim((string) $url);
        $mode = self::normalizeMode($mode);

        if ($inputUrl === '') {
            return self::payload($inputUrl, null, null, null, null, $mode);
        }

        $folderId = self::extractFolderId($inputUrl);
        if ($folderId) {
            return self::payload(
                $inputUrl,
                'https://drive.google.com/embeddedfolderview?id=' . $folderId . '#' . $mode,
                'https://drive.google.com/drive/folders/' . $folderId,
                $folderId,
                'folder',
                $mode,
            );
        }

        $fileId = self::extractFileId($inputUrl);
        if ($fileId) {
            return self::payload(
                $inputUrl,
                'https://drive.google.com/file/d/' . $fileId . '/preview',
                self::normalizeHttpUrl($inputUrl) ?? ('https://drive.google.com/file/d/' . $fileId . '/view'),
                $fileId,
                'file',
                $mode,
            );
        }

        return self::payload(
            $inputUrl,
            null,
            self::normalizeHttpUrl($inputUrl),
            null,
            'unknown',
            $mode,
        );
    }

    private static function extractFolderId(string $url): ?string
    {
        $matches = [];

        if (preg_match('~drive(?:/u/\d+)?/folders/([a-zA-Z0-9_-]+)~', $url, $matches)) {
            return $matches[1];
        }

        $queryId = self::queryId($url);
        if ($queryId && str_contains($url, 'embeddedfolderview')) {
            return $queryId;
        }

        return null;
    }

    public static function extractFileId(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $matches = [];

        if (preg_match('~(?:file|document|spreadsheets|presentation|forms)/(?:d|u/\d+/d)/([a-zA-Z0-9_-]+)~', $url, $matches)) {
            return $matches[1];
        }

        return self::queryId($url);
    }

    public static function directImageUrl(?string $url, int $width = 1000): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, 'https://lh3.googleusercontent.com/d/')) {
            return $url;
        }

        $fileId = self::extractFileId($url);
        if ($fileId) {
            return 'https://lh3.googleusercontent.com/d/' . $fileId . '=w' . $width;
        }

        return null;
    }

    private static function queryId(string $url): ?string
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (! is_string($query) || $query === '') {
            return null;
        }

        parse_str($query, $params);

        $id = trim((string) ($params['id'] ?? ''));

        return $id !== '' ? $id : null;
    }

    private static function normalizeHttpUrl(string $url): ?string
    {
        $candidate = trim($url);

        if ($candidate === '') {
            return null;
        }

        return filter_var($candidate, FILTER_VALIDATE_URL) ? $candidate : null;
    }

    private static function normalizeMode(?string $mode): string
    {
        return in_array($mode, ['grid', 'list'], true) ? $mode : 'list';
    }

    /** @return array{input_url:string,embed_url:?string,open_url:?string,id:?string,kind:?string,mode:string} */
    private static function payload(string $inputUrl, ?string $embedUrl, ?string $openUrl, ?string $id, ?string $kind, string $mode): array
    {
        return [
            'input_url' => $inputUrl,
            'embed_url' => $embedUrl,
            'open_url' => $openUrl,
            'id' => $id,
            'kind' => $kind,
            'mode' => $mode,
        ];
    }
}