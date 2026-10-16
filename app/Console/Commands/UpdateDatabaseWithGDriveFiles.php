<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Support\GoogleDriveMediaStore;
use App\Support\MediaStorage;
use Illuminate\Support\Facades\Schema;

class UpdateDatabaseWithGDriveFiles extends Command
{
    protected $signature = 'media:update-db-gdrive';
    protected $description = 'Update database paths based on existing Google Drive files';

    public function handle()
    {
        $localDisk = config('media.local_disk', 'public');
        $files = Storage::disk($localDisk)->allFiles();

        $this->info("Found " . count($files) . " files on local disk.");

        // Use Reflection to get the Drive service
        $reflection = new \ReflectionClass(GoogleDriveMediaStore::class);
        $method = $reflection->getMethod('service');
        $method->setAccessible(true);
        $service = $method->invoke(null);

        $this->info("Fetching existing files from Google Drive...");
        
        $gdriveFiles = [];
        $pageToken = null;
        do {
            $response = $service->files->listFiles([
                'q' => "trashed = false and mimeType != 'application/vnd.google-apps.folder'",
                'fields' => 'nextPageToken, files(id, name)',
                'pageToken' => $pageToken,
                'pageSize' => 1000,
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true,
            ]);

            foreach ($response->getFiles() as $file) {
                $gdriveFiles[$file->getName()] = $file->getId();
            }
            $pageToken = $response->getNextPageToken();
        } while ($pageToken != null);

        $this->info("Found " . count($gdriveFiles) . " files in Google Drive.");

        $replacements = [];

        foreach ($files as $file) {
            if ($file === '.gitignore' || str_starts_with($file, '.')) {
                continue;
            }

            $filename = basename($file);
            
            if (isset($gdriveFiles[$filename])) {
                $id = $gdriveFiles[$filename];
                $gdrivePath = MediaStorage::makeGoogleReference($id, $filename);
                $replacements[$file] = $gdrivePath;
            } else {
                $this->warn("File not found in Google Drive: {$filename}");
            }
        }

        $this->info("Prepared " . count($replacements) . " replacements. Updating database...");

        $tables = array_map('current', DB::select('SHOW TABLES'));

        foreach ($tables as $table) {
            $columns = Schema::getColumnListing($table);
            
            foreach ($replacements as $oldPath => $newPath) {
                foreach ($columns as $column) {
                    try {
                        $count = DB::table($table)->where($column, 'like', "%{$oldPath}%")->count();
                        if ($count > 0) {
                            $this->info("   -> Found {$count} occurrences in {$table}.{$column} for {$oldPath}");
                            
                            DB::table($table)->where($column, $oldPath)->update([
                                $column => $newPath
                            ]);
                            
                            DB::statement("UPDATE `{$table}` SET `{$column}` = REPLACE(`{$column}`, ?, ?) WHERE `{$column}` LIKE ?", [
                                $oldPath,
                                $newPath,
                                "%{$oldPath}%"
                            ]);
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        }

        $this->info("Database update completed successfully.");
    }
}
