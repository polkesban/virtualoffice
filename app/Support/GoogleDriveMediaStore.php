<?php

namespace App\Support;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class GoogleDriveMediaStore
{
    /** @var array<string, string> */
    private static array $folderCache = [];

    private static ?Client $client = null;
    private static ?Drive $service = null;

    public static function configured(): bool
    {
        return filled(config('media.google_drive.client_id'))
            && filled(config('media.google_drive.client_secret'))
            && filled(config('media.google_drive.refresh_token'));
    }

    public static function uploadUploadedFile(UploadedFile $file, string $directory, bool $public = true, ?string $filename = null): array
    {
        $targetName = $filename ?: $file->hashName();

        return self::uploadContents(
            $directory,
            $targetName,
            (string) file_get_contents($file->getRealPath()),
            $file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream',
            $public,
        );
    }

    public static function uploadContents(string $directory, string $filename, string $contents, ?string $mimeType = null, bool $public = true): array
    {
        $mimeType ??= 'application/octet-stream';
        $parentId = self::ensureFolderPath($directory);

        $metadata = new DriveFile([
            'name' => $filename,
            'parents' => [$parentId],
        ]);

        $created = self::service()->files->create($metadata, self::sharedDriveParams([
            'data' => $contents,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id,name,mimeType,size,webViewLink,webContentLink,thumbnailLink',
        ]));

        if ($public && config('media.google_drive.share_publicly', true)) {
            self::service()->permissions->create(
                $created->id,
                new Permission(['type' => 'anyone', 'role' => 'reader']),
                self::sharedDriveParams(['fields' => 'id'])
            );
        }

        return [
            'id' => (string) $created->id,
            'name' => (string) ($created->name ?: $filename),
            'mime_type' => (string) ($created->mimeType ?: $mimeType),
            'size' => (int) ($created->size ?? 0),
        ];
    }

    public static function delete(string $fileId): void
    {
        try {
            self::service()->files->delete($fileId, self::sharedDriveParams());
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[GoogleDriveMediaStore] Delete failed: ' . $e->getMessage());
        }
    }

    public static function exists(string $fileId): bool
    {
        try {
            self::metadata($fileId);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array{id:string,name:string,mime_type:string,size:int} */
    public static function metadata(string $fileId): array
    {
        $file = self::service()->files->get(
            $fileId,
            self::sharedDriveParams(['fields' => 'id,name,mimeType,size'])
        );

        return [
            'id' => (string) $file->id,
            'name' => (string) ($file->name ?: $fileId),
            'mime_type' => (string) ($file->mimeType ?: 'application/octet-stream'),
            'size' => (int) ($file->size ?? 0),
        ];
    }

    public static function contents(string $fileId): string
    {
        $response = self::service()->files->get($fileId, self::sharedDriveParams(['alt' => 'media']));

        if ($response instanceof ResponseInterface) {
            return (string) $response->getBody();
        }

        if (is_object($response) && method_exists($response, 'getBody')) {
            return (string) $response->getBody();
        }

        throw new RuntimeException('Google Drive media response could not be read.');
    }

    private static function client(): Client
    {
        if (self::$client !== null) {
            return self::$client;
        }

        if (! self::configured()) {
            throw new RuntimeException('Google Drive media storage is not configured.');
        }

        $client = new Client();
        $client->setApplicationName((string) config('media.google_drive.application_name'));
        $client->setClientId((string) config('media.google_drive.client_id'));
        $client->setClientSecret((string) config('media.google_drive.client_secret'));
        $client->setScopes([Drive::DRIVE]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $accessToken = $client->fetchAccessTokenWithRefreshToken((string) config('media.google_drive.refresh_token'));

        if (! is_array($accessToken) || isset($accessToken['error'])) {
            throw new RuntimeException('Google Drive refresh token could not be exchanged for an access token.');
        }

        self::$client = $client;

        return self::$client;
    }

    private static function service(): Drive
    {
        if (self::$service !== null) {
            return self::$service;
        }

        self::$service = new Drive(self::client());

        return self::$service;
    }

    private static function ensureFolderPath(string $directory): string
    {
        $directory = trim($directory, '/');
        $rootId = (string) config('media.google_drive.folder_id', 'root');

        if ($directory === '') {
            return $rootId;
        }

        $cacheKey = $rootId . ':' . $directory;
        if (isset(self::$folderCache[$cacheKey])) {
            return self::$folderCache[$cacheKey];
        }

        $parentId = $rootId;

        foreach (explode('/', $directory) as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            $segmentKey = $parentId . '/' . $segment;
            if (! isset(self::$folderCache[$segmentKey])) {
                self::$folderCache[$segmentKey] = self::findOrCreateFolder($parentId, $segment);
            }

            $parentId = self::$folderCache[$segmentKey];
        }

        self::$folderCache[$cacheKey] = $parentId;

        return $parentId;
    }

    private static function findOrCreateFolder(string $parentId, string $name): string
    {
        $escaped = str_replace(["\\", "'"], ["\\\\", "\\'"], $name);

        $existing = self::service()->files->listFiles(self::listDriveParams([
            'q' => sprintf(
                "mimeType = 'application/vnd.google-apps.folder' and trashed = false and '%s' in parents and name = '%s'",
                $parentId,
                $escaped,
            ),
            'fields' => 'files(id,name)',
            'pageSize' => 1,
        ]));

        $file = $existing->getFiles()[0] ?? null;
        if ($file) {
            return (string) $file->id;
        }

        $folder = self::service()->files->create(
            new DriveFile([
                'name' => $name,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$parentId],
            ]),
            self::sharedDriveParams(['fields' => 'id'])
        );

        return (string) $folder->id;
    }

    /** @param array<string, mixed> $params */
    private static function sharedDriveParams(array $params = []): array
    {
        return $params + [
            'supportsAllDrives' => true,
        ];
    }

    /** @param array<string, mixed> $params */
    private static function listDriveParams(array $params = []): array
    {
        return $params + [
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ];
    }
}