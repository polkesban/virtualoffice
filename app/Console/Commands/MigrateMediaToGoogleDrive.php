<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Support\GoogleDriveMediaStore;
use App\Support\MediaStorage;
use Illuminate\Support\Facades\Schema;

class MigrateMediaToGoogleDrive extends Command
{
    protected $signature = 'media:migrate-to-gdrive';
    protected $description = 'Migrate local media files to Google Drive and update database paths';

    public function handle()
    {
        $localDisk = config('media.local_disk', 'public');
        $files = Storage::disk($localDisk)->allFiles();

        $this->info("Found " . count($files) . " files on local disk.");

        $replacements = [];

        foreach ($files as $file) {
            if ($file === '.gitignore' || str_starts_with($file, '.')) {
                continue;
            }

            $this->info("Processing: {$file}");

            $contents = Storage::disk($localDisk)->get($file);
            $mimeType = Storage::disk($localDisk)->mimeType($file);
            $filename = basename($file);
            $directory = dirname($file);
            if ($directory === '.') {
                $directory = '';
            }

            try {
                $uploaded = GoogleDriveMediaStore::uploadContents(
                    $directory,
                    $filename,
                    $contents,
                    $mimeType,
                    true
                );

                $gdrivePath = MediaStorage::makeGoogleReference($uploaded['id'], $uploaded['name']);
                
                $replacements[$file] = $gdrivePath;
                $this->info(" -> Uploaded to GDrive: {$gdrivePath}");
            } catch (\Exception $e) {
                $this->error(" -> Failed to upload {$file}: " . $e->getMessage());
            }
        }

        $this->info("Updating database...");

        $tables = array_map('current', DB::select('SHOW TABLES'));

        foreach ($tables as $table) {
            $columns = Schema::getColumnListing($table);
            
            foreach ($replacements as $oldPath => $newPath) {
                // Determine if any column in this table contains the old path
                foreach ($columns as $column) {
                    try {
                        // We use a raw query because we don't know the exact column types
                        $count = DB::table($table)->where($column, 'like', "%{$oldPath}%")->count();
                        if ($count > 0) {
                            $this->info("   -> Found {$count} occurrences in {$table}.{$column}");
                            
                            // It might be an exact match or inside a JSON array. 
                            // We do a simple replace first. For JSON it might be tricky, but let's try direct update.
                            DB::table($table)->where($column, $oldPath)->update([
                                $column => $newPath
                            ]);
                            
                            // If it's a partial match (e.g. inside HTML or JSON)
                            DB::statement("UPDATE `{$table}` SET `{$column}` = REPLACE(`{$column}`, ?, ?) WHERE `{$column}` LIKE ?", [
                                $oldPath,
                                $newPath,
                                "%{$oldPath}%"
                            ]);
                        }
                    } catch (\Exception $e) {
                        // Column might not be searchable with LIKE (e.g. integer)
                        continue;
                    }
                }
            }
        }

        $this->info("Migration completed successfully.");
    }
}
