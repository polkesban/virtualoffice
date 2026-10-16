<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Support\GoogleDriveMediaStore;
use App\Support\MediaStorage;

class SyncSunoPlaylist extends Command
{
    protected $signature = 'suno:sync';
    protected $description = 'Sync Suno playlist and manually uploaded Google Drive songs, auto-fetching metadata';

    public function handle()
    {
        $this->updateSyncStatus('running', 0, 0, 'Menghubungkan ke playlist Suno...');
        $this->info('Starting Suno playlist and Google Drive sync...');
        
        // 1. Scrape songs from Suno Playlists defined in suno_playlists.json or .env fallback
        $scrapedSongs = [];
        $playlistsPath = 'suno_playlists.json';
        $playlistUrls = [];
        if (Storage::exists($playlistsPath)) {
            $playlistUrls = json_decode(Storage::get($playlistsPath), true) ?: [];
        }
        if (empty($playlistUrls)) {
            $playlistUrls = explode(',', env('SUNO_PLAYLIST_URLS', 'https://suno.com/playlist/3ee502f0-9521-4199-8bca-97bf2d0a9d05,https://suno.com/playlist/268b0601-ece5-45ad-b18d-6a95b4f2ae14,https://suno.com/playlist/12a80331-8d40-4ed8-b36d-d5a6a79e28e1'));
        }
        
        foreach ($playlistUrls as $url) {
            $url = trim($url);
            if (empty($url)) {
                continue;
            }
            
            $this->updateSyncStatus('running', 0, 0, "Membaca playlist: " . basename($url) . "...");
            $this->info("Scraping playlist: {$url}...");
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                ])->timeout(15)->get($url);
                
                if ($response->successful()) {
                    $html = $response->body();
                    $pattern = '/\\\\\\"title\\\\\\":\\\\\\"(.*?)\\\\\\",.*?\\\\\\"id\\\\\\":\\\\\\"([a-f0-9\-]+)\\\\\\",.*?\\\\\\"duration\\\\\\":([0-9\.]+)/';
                    
                    if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
                        foreach ($matches as $match) {
                            $title = $match[1];
                            $id = $match[2];
                            $durationSeconds = (float)$match[3];
                            
                            $minutes = floor($durationSeconds / 60);
                            $seconds = $durationSeconds % 60;
                            $durationStr = sprintf('%d:%02d', $minutes, $seconds);
                            
                            $version = 'v5.5';
                            if (preg_match('/major_model_version\\\\\\":\\\\\\"(.*?)\\\\\\"/', $match[0], $vMatch)) {
                                $version = $vMatch[1];
                            }
                            
                            $tags = '';
                            if (preg_match('/tags\\\\\\":\\\\\\"(.*?)\\\\\\"/', $match[0], $tMatch)) {
                                $tags = $tMatch[1];
                                $tags = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($uMatch) {
                                    return mb_convert_encoding(pack('H*', $uMatch[1]), 'UTF-8', 'UCS-2BE');
                                }, $tags);
                            }
                            
                            if (strlen($tags) > 80) {
                                $tags = substr($tags, 0, 77) . '...';
                            }
                            
                            $audioUrl = '';
                            if (preg_match('/\\\\"audio_url\\\\":\\\\"(.*?)\\\\"/', $match[0], $aMatch)) {
                                $audioUrl = $aMatch[1];
                            }
                            
                            $imageUrl = '';
                            if (preg_match('/\\\\"image_url\\\\":\\\\"(.*?)\\\\"/', $match[0], $iMatch)) {
                                $imageUrl = $iMatch[1];
                            }
                            
                            $scrapedSongs[$id] = [
                                'id' => $id,
                                'title' => $title,
                                'duration' => $durationStr,
                                'desc' => $version . ($tags ? ' | ' . $tags : ''),
                                'audio_url' => $audioUrl,
                                'image_url' => $imageUrl
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error("Scraping playlist '{$url}' failed: " . $e->getMessage());
            }
        }
        
        $this->info('Scraped ' . count($scrapedSongs) . ' songs from the public playlist page.');
        
        // 2. Scan Google Drive suno_music folder for MP3s
        $this->updateSyncStatus('running', 0, 0, 'Memindai file cadangan di Google Drive...');
        $gdriveAudioMap = [];
        $gdriveImageMap = [];
        try {
            $reflector = new \ReflectionClass(GoogleDriveMediaStore::class);
            $methodEnsure = $reflector->getMethod('ensureFolderPath');
            $methodEnsure->setAccessible(true);
            $methodService = $reflector->getMethod('service');
            $methodService->setAccessible(true);
            $methodListParams = $reflector->getMethod('listDriveParams');
            $methodListParams->setAccessible(true);
            
            $folderId = $methodEnsure->invoke(null, 'suno_music');
            $service = $methodService->invoke(null);
            $listParams = $methodListParams->invoke(null, [
                'q' => sprintf("'%s' in parents and trashed = false", $folderId),
                'fields' => 'files(id,name,mimeType,size)',
                'pageSize' => 500,
            ]);
            
            $response = $service->files->listFiles($listParams);
            $files = $response->getFiles();
            
            foreach ($files as $file) {
                if (preg_match('/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/i', $file->name, $uuidMatch)) {
                    $uuid = strtolower($uuidMatch[1]);
                    if (str_ends_with(strtolower($file->name), '.mp3')) {
                        // Keep the first file ID found for this UUID to avoid duplicate file reference issues
                        if (!isset($gdriveAudioMap[$uuid])) {
                            $gdriveAudioMap[$uuid] = $file->id;
                        }
                    } elseif (str_ends_with(strtolower($file->name), '.jpeg') || str_ends_with(strtolower($file->name), '.jpg') || str_ends_with(strtolower($file->name), '.png')) {
                        if (!isset($gdriveImageMap[$uuid])) {
                            $gdriveImageMap[$uuid] = $file->id;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error('Failed to list files from Google Drive: ' . $e->getMessage());
        }
        
        $this->info('Found ' . count($gdriveAudioMap) . ' unique song files in Google Drive suno_music folder.');
        
        // 3. Load existing local metadata from suno_songs.json
        $jsonPath = 'suno_songs.json';
        $existingSongs = [];
        if (Storage::exists($jsonPath)) {
            $existingSongs = json_decode(Storage::get($jsonPath), true) ?: [];
        }
        
        $existingMap = [];
        foreach ($existingSongs as $song) {
            if (isset($song['id'])) {
                $existingMap[$song['id']] = $song;
            }
        }
        
        // Combine all song IDs (from scraped playlist + from Google Drive files)
        $allSongIds = array_unique(array_merge(array_keys($scrapedSongs), array_keys($gdriveAudioMap)));
        $updatedSongs = [];
        
        $totalCount = count($allSongIds);
        $processedCount = 0;
        $addedCount = 0;
        
        foreach ($allSongIds as $id) {
            $processedCount++;
            $isNewSong = !isset($existingMap[$id]);
            if ($isNewSong) {
                $addedCount++;
            }
            $songTitle = $scrapedSongs[$id]['title'] ?? ($existingMap[$id]['title'] ?? 'Lagu Baru');
            $this->updateSyncStatus('running', $totalCount, $processedCount, "Sinkronisasi: {$songTitle} ({$processedCount}/{$totalCount})", $addedCount);
            $this->info("Processing song ID: {$id}...");
            
            $lyricsText = $existingMap[$id]['lyrics'] ?? '';
            $hasLyrics = !empty($lyricsText) 
                && strlen($lyricsText) > 20 
                && !str_contains($lyricsText, 'self.__next_f') 
                && !str_contains($lyricsText, '__next_f.push');

            // Check if we already have valid GDrive URLs and metadata in local JSON
            if (isset($existingMap[$id]) && !empty($existingMap[$id]['audio_url']) && str_contains($existingMap[$id]['audio_url'], 'drive.google.com') && $hasLyrics) {
                // Check if Google Drive file still exists or if it was manually deleted
                $uuid = strtolower($id);
                if (isset($gdriveAudioMap[$uuid])) {
                    $this->info(" -> Song metadata, GDrive file, and lyrics exist in local cache.");
                    $updatedSongs[] = $existingMap[$id];
                    continue;
                }
            }
            
            // Metadata placeholders
            $title = 'Lagu Tanpa Judul';
            $durationStr = '3:00';
            $desc = 'v5.5';
            $sunoAudioUrl = $scrapedSongs[$id]['audio_url'] ?? '';
            $sunoImageUrl = $scrapedSongs[$id]['image_url'] ?? '';
            $lyrics = null;
            
            // If the song is in scraped list, we have the metadata
            if (isset($scrapedSongs[$id])) {
                $title = $scrapedSongs[$id]['title'];
                $durationStr = $scrapedSongs[$id]['duration'];
                $desc = $scrapedSongs[$id]['desc'];
            } else {
                // If it is NOT in scraped list (manual upload), fetch metadata from Suno song page
                try {
                    $this->info(" -> Fetching metadata from Suno song page https://suno.com/song/{$id}...");
                    $songResponse = Http::withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                    ])->timeout(10)->get("https://suno.com/song/{$id}");
                    
                    if ($songResponse->successful()) {
                        $sHtml = $songResponse->body();
                        
                        // Extract Title
                        if (preg_match('/\\\\"title\\\\":\\\\"(.*?)\\\\"/', $sHtml, $tMatch)) {
                            $title = $tMatch[1];
                        }
                        
                        // Extract Duration
                        if (preg_match('/\\\\"duration\\\\":([0-9.]+)/', $sHtml, $dMatch)) {
                            $durationSeconds = (float)$dMatch[1];
                            $minutes = floor($durationSeconds / 60);
                            $seconds = $durationSeconds % 60;
                            $durationStr = sprintf('%d:%02d', $minutes, $seconds);
                        }
                        
                        // Extract Model Version
                        $version = 'v5.5';
                        if (preg_match('/\\\\"major_model_version\\\\":\\\\"(.*?)\\\\"/', $sHtml, $vMatch)) {
                            $version = $vMatch[1];
                        }
                        
                        // Extract Tags
                        $tags = '';
                        if (preg_match('/\\\\"tags\\\\":\\\\"(.*?)\\\\"/', $sHtml, $tMatch)) {
                            $tags = $tMatch[1];
                            $tags = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($uMatch) {
                                return mb_convert_encoding(pack('H*', $uMatch[1]), 'UTF-8', 'UCS-2BE');
                            }, $tags);
                        }
                        
                        if (strlen($tags) > 80) {
                            $tags = substr($tags, 0, 77) . '...';
                        }
                        
                        $desc = $version . ($tags ? ' | ' . $tags : '');
                        
                        // Extract URLs
                        if (preg_match('/\\\\"audio_url\\\\":\\\\"(.*?)\\\\"/', $sHtml, $aMatch)) {
                            $sunoAudioUrl = $aMatch[1];
                        }
                        if (preg_match('/\\\\"image_url\\\\":\\\\"(.*?)\\\\"/', $sHtml, $iMatch)) {
                            $sunoImageUrl = $iMatch[1];
                        }

                        // Extract Lyrics using Next.js RSC push chunks (prompt metadata / references)
                        if (preg_match_all('/self\.__next_f\.push\(\[\d+,\s*"(.*?)"\s*\]\)/is', $sHtml, $pushMatches)) {
                            foreach ($pushMatches[1] as $pushStr) {
                                $decoded = stripcslashes($pushStr);
                                if (str_contains($decoded, '[Verse') || str_contains($decoded, '[Chorus') || str_contains($decoded, '[Intro') || str_contains($decoded, '[Outro')) {
                                    $rawLyrics = str_replace("\xE2\x80\x8B", "", $decoded);
                                    $lyrics = trim($rawLyrics);
                                    break;
                                }
                            }
                            if (!$lyrics) {
                                $promptRef = null;
                                foreach ($pushMatches[1] as $pushStr) {
                                    $decoded = stripcslashes($pushStr);
                                    if (preg_match('/"prompt"\s*:\s*"\$([a-zA-Z0-9_-]+)"/', $decoded, $refMatch)) {
                                        $promptRef = $refMatch[1];
                                        break;
                                    }
                                }
                                if ($promptRef) {
                                    foreach ($pushMatches[1] as $pushStr) {
                                        $decoded = stripcslashes($pushStr);
                                        if (preg_match('/^' . preg_quote($promptRef, '/') . ':(?:[A-Z][0-9a-fA-F]+,)?(.*)/s', $decoded, $lyricMatch)) {
                                            $rawLyrics = $lyricMatch[1];
                                            $rawLyrics = str_replace("\xE2\x80\x8B", "", $rawLyrics);
                                            $lyrics = trim($rawLyrics);
                                            break;
                                        }
                                    }
                                }
                            }
                            if (!$lyrics) {
                                foreach ($pushMatches[1] as $pushStr) {
                                    $decoded = stripcslashes($pushStr);
                                    if (preg_match('/"prompt"\s*:\s*"(.*?)"/s', $decoded, $promptMatch)) {
                                        if (!str_starts_with($promptMatch[1], '$')) {
                                            $rawLyrics = json_decode('"' . $promptMatch[1] . '"');
                                            if ($rawLyrics) {
                                                $lyrics = trim(str_replace("\xE2\x80\x8B", "", $rawLyrics));
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->error(" -> Failed to fetch metadata for {$id}: " . $e->getMessage());
                }
            }

            // If we don't have lyrics yet, let's fetch them from the individual song page (only fall back to existing if they are valid!)
            $existingLyrics = $existingMap[$id]['lyrics'] ?? '';
            $validExisting = !empty($existingLyrics) 
                && strlen($existingLyrics) > 20 
                && !str_contains($existingLyrics, 'self.__next_f') 
                && !str_contains($existingLyrics, '__next_f.push');
            
            $lyrics = $validExisting ? $existingLyrics : ($lyrics ?? null);
            if (!$lyrics) {
                try {
                    $this->info(" -> Fetching missing lyrics from Suno song page https://suno.com/song/{$id}...");
                    $songResponse = Http::withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                    ])->timeout(10)->get("https://suno.com/song/{$id}");
                    
                    if ($songResponse->successful()) {
                        $sHtml = $songResponse->body();
                        if (preg_match_all('/self\.__next_f\.push\(\[\d+,\s*"(.*?)"\s*\]\)/is', $sHtml, $pushMatches)) {
                            foreach ($pushMatches[1] as $pushStr) {
                                $decoded = stripcslashes($pushStr);
                                if (str_contains($decoded, '[Verse') || str_contains($decoded, '[Chorus') || str_contains($decoded, '[Intro') || str_contains($decoded, '[Outro')) {
                                    $rawLyrics = str_replace("\xE2\x80\x8B", "", $decoded);
                                    $lyrics = trim($rawLyrics);
                                    $this->info(" -> Found lyrics!");
                                    break;
                                }
                            }
                            if (!$lyrics) {
                                $promptRef = null;
                                foreach ($pushMatches[1] as $pushStr) {
                                    $decoded = stripcslashes($pushStr);
                                    if (preg_match('/"prompt"\s*:\s*"\$([a-zA-Z0-9_-]+)"/', $decoded, $refMatch)) {
                                        $promptRef = $refMatch[1];
                                        break;
                                    }
                                }
                                if ($promptRef) {
                                    foreach ($pushMatches[1] as $pushStr) {
                                        $decoded = stripcslashes($pushStr);
                                        if (preg_match('/^' . preg_quote($promptRef, '/') . ':(?:[A-Z][0-9a-fA-F]+,)?(.*)/s', $decoded, $lyricMatch)) {
                                            $rawLyrics = $lyricMatch[1];
                                            $rawLyrics = str_replace("\xE2\x80\x8B", "", $rawLyrics);
                                            $lyrics = trim($rawLyrics);
                                            $this->info(" -> Found lyrics!");
                                            break;
                                        }
                                    }
                                }
                            }
                            if (!$lyrics) {
                                foreach ($pushMatches[1] as $pushStr) {
                                    $decoded = stripcslashes($pushStr);
                                    if (preg_match('/"prompt"\s*:\s*"(.*?)"/s', $decoded, $promptMatch)) {
                                        if (!str_starts_with($promptMatch[1], '$')) {
                                            $rawLyrics = json_decode('"' . $promptMatch[1] . '"');
                                            if ($rawLyrics) {
                                                $lyrics = trim(str_replace("\xE2\x80\x8B", "", $rawLyrics));
                                                $this->info(" -> Found lyrics!");
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->error(" -> Failed to fetch missing lyrics: " . $e->getMessage());
                }
            }
            
            // Check if audio file exists on GDrive, otherwise upload it
            $uuid = strtolower($id);
            $gdriveAudioId = $gdriveAudioMap[$uuid] ?? null;
            
            if (!$gdriveAudioId && $sunoAudioUrl) {
                try {
                    $this->info(" -> Downloading and uploading audio to Google Drive...");
                    $audioContents = Http::timeout(25)->get($sunoAudioUrl)->body();
                    if ($audioContents) {
                        $uploadedAudio = GoogleDriveMediaStore::uploadContents(
                            'suno_music',
                            "suno_{$id}.mp3",
                            $audioContents,
                            'audio/mpeg',
                            true
                        );
                        $gdriveAudioId = $uploadedAudio['id'];
                        $gdriveAudioMap[$uuid] = $gdriveAudioId;
                    }
                } catch (\Exception $e) {
                    $this->error(" -> Audio upload failed: " . $e->getMessage());
                }
            }
            
            // Check if image file exists on GDrive, otherwise upload it
            $gdriveImageId = $gdriveImageMap[$uuid] ?? null;
            if (!$gdriveImageId && $sunoImageUrl) {
                try {
                    $this->info(" -> Downloading and uploading cover art to Google Drive...");
                    $imageContents = Http::timeout(10)->get($sunoImageUrl)->body();
                    if ($imageContents) {
                        $uploadedImage = GoogleDriveMediaStore::uploadContents(
                            'suno_music',
                            "suno_{$id}.jpeg",
                            $imageContents,
                            'image/jpeg',
                            true
                        );
                        $gdriveImageId = $uploadedImage['id'];
                        $gdriveImageMap[$uuid] = $gdriveImageId;
                    }
                } catch (\Exception $e) {
                    $this->error(" -> Image upload failed: " . $e->getMessage());
                }
            }
            
            // Generate GDrive stream URLs
            $gdriveAudioUrl = '';
            if ($gdriveAudioId) {
                $ref = MediaStorage::makeGoogleReference($gdriveAudioId, "suno_{$id}.mp3");
                $gdriveAudioUrl = MediaStorage::url($ref);
            }
            
            $gdriveImageUrl = '';
            if ($gdriveImageId) {
                $ref = MediaStorage::makeGoogleReference($gdriveImageId, "suno_{$id}.jpeg");
                $gdriveImageUrl = MediaStorage::url($ref);
            }
            
            // Ensure cached locally on server to support background playback and same-origin range requests
            $localCacheFolder = public_path('storage/suno_cache');
            $localCacheFile = "{$localCacheFolder}/{$id}.mp3";
            if (!file_exists($localCacheFile)) {
                $this->info(" -> Downloading and caching song locally...");
                $audioData = null;
                if ($sunoAudioUrl) {
                    try {
                        $audioData = Http::timeout(25)->get($sunoAudioUrl)->body();
                    } catch (\Exception $e) {}
                }
                if (!$audioData && $gdriveAudioId) {
                    try {
                        $audioData = GoogleDriveMediaStore::contents($gdriveAudioId);
                    } catch (\Exception $e) {}
                }
                if ($audioData) {
                    if (!file_exists($localCacheFolder)) {
                        mkdir($localCacheFolder, 0775, true);
                        @chmod($localCacheFolder, 0775);
                    }
                    file_put_contents($localCacheFile, $audioData);
                    @chmod($localCacheFile, 0664);
                }
            }

            $updatedSongs[] = [
                'id' => $id,
                'title' => $title,
                'duration' => $durationStr,
                'desc' => $desc,
                'audio_url' => $gdriveAudioUrl ?: $sunoAudioUrl,
                'image_url' => $gdriveImageUrl ?: $sunoImageUrl,
                'lyrics' => $lyrics
            ];
        }
        
        // Save combined list to storage/app/suno_songs.json
        Storage::put($jsonPath, json_encode($updatedSongs, JSON_PRETTY_PRINT));
        $this->info('Suno playlist and GDrive sync completed. Saved to ' . Storage::path($jsonPath));
        
        $this->updateSyncStatus('completed', count($allSongIds), count($allSongIds), 'Sinkronisasi selesai dengan sukses!', $addedCount ?? 0);
        
        return 0;
    }

    private function updateSyncStatus(string $status, int $total, int $processed, string $action, int $added = 0)
    {
        try {
            Storage::put('suno_sync_status.json', json_encode([
                'status' => $status,
                'total_songs' => $total,
                'processed_songs' => $processed,
                'added_songs' => $added,
                'current_action' => $action,
                'updated_at' => now()->toIso8601String()
            ], JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            $this->error('Failed to write sync status: ' . $e->getMessage());
        }
    }
}
