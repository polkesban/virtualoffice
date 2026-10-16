<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class MediaStorage
{
    private const GOOGLE_PREFIX = 'gdrive:';

    /** @var array<string, array{id:string,name:string,mime_type:string,size:int}> */
    private static array $metadataCache = [];

    public static function storeUploadedFile(UploadedFile $file, string $directory, bool $public = true, ?string $filename = null): string
    {
        if (self::storeOnGoogleDrive()) {
            try {
                $uploaded = GoogleDriveMediaStore::uploadUploadedFile($file, $directory, $public, $filename);

                return self::makeGoogleReference($uploaded['id'], $uploaded['name']);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[MediaStorage] Google Drive upload failed, falling back to local storage', [
                    'directory' => $directory,
                    'filename'  => $filename ?: $file->getClientOriginalName(),
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        $path = $filename !== null
            ? $file->storeAs($directory, $filename, self::localDisk())
            : $file->store($directory, self::localDisk());

        if ($path === false) {
            throw new \RuntimeException('Failed to store file on local disk: ' . self::localDisk());
        }

        return $path;
    }

    public static function put(string $path, string $contents, ?string $mimeType = null, bool $public = true): string
    {
        $normalizedPath = ltrim($path, '/');

        if (self::storeOnGoogleDrive()) {
            try {
                $uploaded = GoogleDriveMediaStore::uploadContents(
                    trim((string) dirname($normalizedPath), './'),
                    basename($normalizedPath),
                    $contents,
                    $mimeType,
                    $public,
                );

                return self::makeGoogleReference($uploaded['id'], $uploaded['name']);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[MediaStorage] Google Drive put failed, falling back to local storage', [
                    'path'  => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Storage::disk(self::localDisk())->put($normalizedPath, $contents);

        return $normalizedPath;
    }

    public static function delete(?string $path, ?string $legacyDirectory = null): void
    {
        $normalized = self::normalizePath($path, $legacyDirectory);
        if ($normalized === null || self::isExternalUrl($normalized)) {
            return;
        }

        if (self::isGoogleReference($normalized)) {
            GoogleDriveMediaStore::delete(self::parseGoogleReference($normalized)['id']);
            unset(self::$metadataCache[$normalized]);

            return;
        }

        Storage::disk(self::localDisk())->delete($normalized);
    }

    public static function exists(?string $path, ?string $legacyDirectory = null): bool
    {
        $normalized = self::normalizePath($path, $legacyDirectory);
        if ($normalized === null) {
            return false;
        }

        if (self::isExternalUrl($normalized)) {
            return true;
        }

        if (self::isGoogleReference($normalized)) {
            return GoogleDriveMediaStore::exists(self::parseGoogleReference($normalized)['id']);
        }

        return Storage::disk(self::localDisk())->exists($normalized);
    }

    public static function url(?string $path, ?string $legacyDirectory = null): ?string
    {
        $normalized = self::normalizePath($path, $legacyDirectory);
        if ($normalized === null) {
            return null;
        }

        if (self::isExternalUrl($normalized)) {
            if (str_contains($normalized, 'drive.google.com')) {
                $direct = GoogleDriveEmbed::directImageUrl($normalized);
                if ($direct) {
                    return $direct;
                }
            }

            return $normalized;
        }

        if (self::isGoogleReference($normalized)) {
            $parsed = self::parseGoogleReference($normalized);
            $ext = strtolower(pathinfo($parsed['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                return 'https://lh3.googleusercontent.com/d/' . $parsed['id'] . '=w1000';
            }
            
            return 'https://drive.google.com/uc?export=view&id=' . $parsed['id'];
        }

        return Storage::disk(self::localDisk())->url($normalized);
    }

    public static function browserUrl(?string $path, ?string $legacyDirectory = null): ?string
    {
        $normalized = self::normalizePath($path, $legacyDirectory);
        if ($normalized === null) {
            return null;
        }

        if (self::isExternalUrl($normalized)) {
            return $normalized;
        }

        if (self::isGoogleReference($normalized)) {
            return 'https://drive.google.com/file/d/' . self::parseGoogleReference($normalized)['id'] . '/view';
        }

        return Storage::disk(self::localDisk())->url($normalized);
    }

    public static function previewUrl(?string $path, ?string $legacyDirectory = null): ?string
    {
        $normalized = self::normalizePath($path, $legacyDirectory);
        if ($normalized === null) {
            return null;
        }

        if (self::isExternalUrl($normalized)) {
            return $normalized;
        }

        if (self::isGoogleReference($normalized)) {
            return 'https://drive.google.com/file/d/' . self::parseGoogleReference($normalized)['id'] . '/preview';
        }

        return Storage::disk(self::localDisk())->url($normalized);
    }

    public static function downloadUrl(?string $path, ?string $legacyDirectory = null): ?string
    {
        $normalized = self::normalizePath($path, $legacyDirectory);
        if ($normalized === null) {
            return null;
        }

        if (self::isExternalUrl($normalized)) {
            return $normalized;
        }

        if (self::isGoogleReference($normalized)) {
            return 'https://drive.google.com/uc?export=download&id=' . self::parseGoogleReference($normalized)['id'];
        }

        return Storage::disk(self::localDisk())->url($normalized);
    }

    public static function fileName(?string $path, ?string $fallback = null, ?string $legacyDirectory = null): ?string
    {
        $normalized = self::normalizePath($path, $legacyDirectory);
        if ($normalized === null) {
            return $fallback;
        }

        if (self::isGoogleReference($normalized)) {
            $parsed = self::parseGoogleReference($normalized);

            return $parsed['name'] ?: ($fallback ?: self::metadata($normalized)['name']);
        }

        if (self::isExternalUrl($normalized)) {
            return $fallback ?: basename(parse_url($normalized, PHP_URL_PATH) ?: $normalized);
        }

        return $fallback ?: basename($normalized);
    }

    public static function mimeType(?string $path, ?string $legacyDirectory = null): ?string
    {
        $normalized = self::normalizePath($path, $legacyDirectory);
        if ($normalized === null) {
            return null;
        }

        if (self::isGoogleReference($normalized)) {
            return self::metadata($normalized)['mime_type'] ?: 'application/octet-stream';
        }

        if (self::isExternalUrl($normalized)) {
            return null;
        }

        return Storage::disk(self::localDisk())->mimeType($normalized) ?: 'application/octet-stream';
    }

    public static function contents(?string $path, ?string $legacyDirectory = null): string
    {
        $normalized = self::normalizePath($path, $legacyDirectory);
        if ($normalized === null) {
            return '';
        }

        if (self::isGoogleReference($normalized)) {
            return GoogleDriveMediaStore::contents(self::parseGoogleReference($normalized)['id']);
        }

        return Storage::disk(self::localDisk())->get($normalized);
    }

    public static function temporaryLocalPath(?string $path, ?string $legacyDirectory = null): ?string
    {
        $normalized = self::normalizePath($path, $legacyDirectory);
        if ($normalized === null) {
            return null;
        }

        if (! self::isGoogleReference($normalized)) {
            return Storage::disk(self::localDisk())->path($normalized);
        }

        $fileName = self::safeTempFileName(self::fileName($normalized) ?: 'media.bin');
        $tmpPath = sys_get_temp_dir() . '/polkesban-media-' . md5($normalized) . '-' . $fileName;

        if (! file_exists($tmpPath)) {
            file_put_contents($tmpPath, self::contents($normalized));
        }

        return $tmpPath;
    }

    public static function inlineResponse(string $path, string $filename, bool $download = false, ?string $mimeType = null, ?string $legacyDirectory = null): SymfonyResponse
    {
        $mimeType ??= self::mimeType($path, $legacyDirectory) ?: 'application/octet-stream';
        $safeFilename = str_replace(["\r", "\n", '"'], '', $filename);

        return Response::make(self::contents($path, $legacyDirectory), 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=3600',
            'Content-Disposition' => ($download ? 'attachment' : 'inline') . '; filename="' . $safeFilename . '"',
        ]);
    }

    /** @return array{id:string,name:string,mime_type:string,size:int} */
    public static function metadata(string $path): array
    {
        if (! isset(self::$metadataCache[$path])) {
            $parsed = self::parseGoogleReference($path);
            $metadata = GoogleDriveMediaStore::metadata($parsed['id']);

            if ($parsed['name'] !== '') {
                $metadata['name'] = $parsed['name'];
            }

            self::$metadataCache[$path] = $metadata;
        }

        return self::$metadataCache[$path];
    }

    public static function normalizePath(?string $path, ?string $legacyDirectory = null): ?string
    {
        $value = trim((string) $path);
        if ($value === '') {
            return null;
        }

        if (self::isGoogleReference($value) || self::isExternalUrl($value)) {
            return $value;
        }

        $value = ltrim($value, '/');

        if (str_starts_with($value, 'storage/')) {
            $value = substr($value, 8);
        }

        if ($legacyDirectory !== null && ! str_contains($value, '/')) {
            return trim($legacyDirectory, '/') . '/' . $value;
        }

        return $value;
    }

    public static function makeGoogleReference(string $fileId, string $fileName): string
    {
        return self::GOOGLE_PREFIX . $fileId . '|' . rawurlencode($fileName);
    }

    public static function isGoogleReference(string $path): bool
    {
        return str_starts_with($path, self::GOOGLE_PREFIX);
    }

    /** @return array{id:string,name:string} */
    public static function parseGoogleReference(string $path): array
    {
        $payload = substr($path, strlen(self::GOOGLE_PREFIX));
        [$id, $encodedName] = array_pad(explode('|', $payload, 2), 2, '');

        return [
            'id' => $id,
            'name' => rawurldecode($encodedName),
        ];
    }

    private static function storeOnGoogleDrive(): bool
    {
        return config('media.disk') === 'google_drive';
    }

    private static function localDisk(): string
    {
        return (string) config('media.local_disk', 'public');
    }

    private static function isExternalUrl(string $path): bool
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }

    private static function safeTempFileName(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?? 'media.bin';

        return Str::limit(trim($name, '-'), 80, '');
    }
}