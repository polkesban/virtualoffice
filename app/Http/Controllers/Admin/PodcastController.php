<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Support\MediaStorage;
use App\Support\GoogleDriveMediaStore;

class PodcastController extends Controller
{
    private string $jsonPath = 'podcast_episodes.json';

    private function getEpisodes(): array
    {
        if (Storage::exists($this->jsonPath)) {
            return json_decode(Storage::get($this->jsonPath), true) ?: [];
        }
        return [];
    }

    private function saveEpisodes(array $episodes): void
    {
        Storage::put($this->jsonPath, json_encode(array_values($episodes), JSON_PRETTY_PRINT));
    }

    public function index()
    {
        $episodes = $this->getEpisodes();
        return view('admin.podcasts.index', compact('episodes'));
    }

    public function store(Request $request)
    {
        // Increase memory and timeout limits dynamically for handling large Google Drive API file buffers
        ini_set('memory_limit', '512M');
        set_time_limit(300);



        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'audio' => 'required|file|mimes:mp3,wav,ogg,mpga,m4a,mp4|max:102400', // max 100MB
        ];

        if ($request->hasFile('cover') && $request->file('cover')->isValid()) {
            $rules['cover'] = 'image|mimes:jpeg,jpg,png,webp|max:10240'; // max 10MB
        }

        try {
            $request->validate($rules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('Validation failed details:', $e->errors());
            throw $e;
        }

        $uuid = (string) Str::uuid();

        // 1. Upload audio to Google Drive with dynamic extension
        $audioFile = $request->file('audio');
        $ext = strtolower($audioFile->getClientOriginalExtension() ?: 'mp3');
        
        $audioRef = MediaStorage::storeUploadedFile($audioFile, 'podcasts', true, "podcast_{$uuid}.{$ext}");
        $audioUrl = MediaStorage::url($audioRef);

        // 2. Upload cover to Google Drive (optional)
        // 2. Upload cover to Google Drive (optional)
        $imageUrl = null;
        $imageRef = null;
        if ($request->hasFile('cover')) {
            $coverFile = $request->file('cover');
            
            // Compress to a temporary file first to save GDrive storage & upload time
            $tempPath = tempnam(sys_get_temp_dir(), 'podcast_cover_');
            $this->compressImage($coverFile->getRealPath(), $tempPath, 1200, 75);
            
            $imageRef = MediaStorage::storeUploadedFile(
                new \Illuminate\Http\UploadedFile($tempPath, "podcast_{$uuid}.jpeg", 'image/jpeg', null, true),
                'podcasts',
                true,
                "podcast_{$uuid}.jpeg"
            );
            $imageUrl = MediaStorage::url($imageRef);
            @unlink($tempPath);
        }

        // 3. Save a local copy in server cache immediately for instant background streaming
        try {
            $cacheFolder = public_path('storage/podcast_cache');
            if (!file_exists($cacheFolder)) {
                mkdir($cacheFolder, 0775, true);
                @chmod($cacheFolder, 0775);
            }
            // Copy uploaded file directly to local cache
            file_put_contents("{$cacheFolder}/{$uuid}.{$ext}", file_get_contents($audioFile->getRealPath()));
            @chmod("{$cacheFolder}/{$uuid}.{$ext}", 0664);
            
            if ($request->hasFile('cover')) {
                $this->compressImage($request->file('cover')->getRealPath(), "{$cacheFolder}/{$uuid}.jpeg", 1200, 75);
                @chmod("{$cacheFolder}/{$uuid}.jpeg", 0664);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to write local podcast cache: ' . $e->getMessage());
        }

        // 4. Save metadata to JSON database
        $episodes = $this->getEpisodes();
        $episodes[] = [
            'id' => $uuid,
            'title' => trim($request->input('title')),
            'description' => trim($request->input('description') ?? ''),
            'audio_url' => $audioUrl,
            'audio_ref' => $audioRef,
            'audio_ext' => $ext,
            'image_url' => $imageUrl,
            'image_ref' => $imageRef,
            'created_at' => now()->toIso8601String(),
        ];
        $this->saveEpisodes($episodes);

        return redirect()->route('admin.podcasts.index')->with('success', 'Podcast berhasil diunggah dan disimpan!');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|string',
        ]);

        $id = $request->input('id');
        $episodes = $this->getEpisodes();

        $key = array_search($id, array_column($episodes, 'id'));
        if ($key !== false) {
            $episode = $episodes[$key];
            $ext = $episode['audio_ext'] ?? 'mp3';

            // 1. Delete GDrive files
            if (!empty($episode['audio_ref'])) {
                MediaStorage::delete($episode['audio_ref']);
            }
            if (!empty($episode['image_ref'])) {
                MediaStorage::delete($episode['image_ref']);
            }

            // 2. Delete local cache files
            @unlink(public_path("storage/podcast_cache/{$id}.{$ext}"));
            @unlink(public_path("storage/podcast_cache/{$id}.jpeg"));

            // 3. Remove metadata
            unset($episodes[$key]);
            $this->saveEpisodes($episodes);

            return redirect()->route('admin.podcasts.index')->with('success', 'Podcast berhasil dihapus!');
        }

        return back()->with('error', 'Podcast tidak ditemukan.');
    }

    public function apiList()
    {
        $episodes = $this->getEpisodes();
        
        // Map local static caching URLs to prevent mobile background playback locks
        $mapped = array_map(function ($ep) {
            $id = $ep['id'];
            $ext = $ep['audio_ext'] ?? 'mp3';
            
            $cachePath = "storage/podcast_cache/{$id}.{$ext}";
            $coverCachePath = "storage/podcast_cache/{$id}.jpeg";
            
            if (file_exists(public_path($cachePath))) {
                $ep['audio_url'] = '/' . $cachePath;
            } else {
                $ep['audio_url'] = url('/api/podcast-stream/' . urlencode($id));
            }

            if (file_exists(public_path($coverCachePath))) {
                $ep['image_url'] = '/' . $coverCachePath;
            }
            
            return $ep;
        }, $episodes);

        return response()->json(array_values($mapped));
    }

    public function stream($id)
    {
        $episodes = $this->getEpisodes();
        $episode = collect($episodes)->firstWhere('id', $id);
        if (!$episode) {
            abort(404);
        }
        
        $ext = $episode['audio_ext'] ?? 'mp3';
        $cachePath = public_path("storage/podcast_cache/{$id}.{$ext}");
        if (file_exists($cachePath)) {
            $mime = (in_array($ext, ['m4a', 'mp4'])) ? 'audio/mp4' : 'audio/mpeg';
            return response()->file($cachePath, [
                'Content-Type' => $mime,
                'Accept-Ranges' => 'bytes',
            ]);
        }
        
        return redirect($episode['audio_url']);
    }

    private function compressImage(string $sourcePath, string $targetPath, int $maxWidth = 1200, int $quality = 75): bool
    {
        if (!extension_loaded('gd')) {
            return copy($sourcePath, $targetPath);
        }

        list($width, $height, $type) = @getimagesize($sourcePath);

        switch ($type) {
            case IMAGETYPE_JPEG:
                $srcImage = @imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $srcImage = @imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $srcImage = @imagecreatefromwebp($sourcePath);
                break;
            default:
                return copy($sourcePath, $targetPath);
        }

        if (!$srcImage) {
            return copy($sourcePath, $targetPath);
        }

        $newWidth = $width;
        $newHeight = $height;

        if ($width > $maxWidth || $height > $maxWidth) {
            if ($width > $height) {
                $newWidth = $maxWidth;
                $newHeight = (int) (($height / $width) * $maxWidth);
            } else {
                $newHeight = $maxWidth;
                $newWidth = (int) (($width / $height) * $maxWidth);
            }
        }

        $dstImage = imagecreatetruecolor($newWidth, $newHeight);

        // Keep alpha transparency channel info if saving transparency or solid white backgrounds
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
        
        $whiteBackground = imagecolorallocate($dstImage, 255, 255, 255);
        imagefilledrectangle($dstImage, 0, 0, $newWidth, $newHeight, $whiteBackground);

        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $result = imagejpeg($dstImage, $targetPath, $quality);

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        return $result;
    }
}
