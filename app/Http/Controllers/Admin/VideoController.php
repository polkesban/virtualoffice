<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Support\MediaStorage;

class VideoController extends Controller
{
    private string $jsonPath = 'video_items.json';

    private function getVideos(): array
    {
        if (Storage::exists($this->jsonPath)) {
            return json_decode(Storage::get($this->jsonPath), true) ?: [];
        }
        return [];
    }

    private function saveVideos(array $videos): void
    {
        Storage::put($this->jsonPath, json_encode(array_values($videos), JSON_PRETTY_PRINT));
    }

    public function index()
    {
        $videos = $this->getVideos();
        return view('admin.videos.index', compact('videos'));
    }

    public function store(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video' => 'required|file|mimes:mp4|max:153600', // max 150MB
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

        // 1. Upload video to Google Drive
        $videoFile = $request->file('video');
        $ext = strtolower($videoFile->getClientOriginalExtension() ?: 'mp4');
        
        $videoRef = MediaStorage::storeUploadedFile($videoFile, 'videos', true, "video_{$uuid}.{$ext}");
        $videoUrl = MediaStorage::url($videoRef);

        // 2. Upload cover to Google Drive (optional)
        $imageUrl = null;
        $imageRef = null;
        if ($request->hasFile('cover')) {
            $coverFile = $request->file('cover');
            
            $tempPath = tempnam(sys_get_temp_dir(), 'video_cover_');
            $this->compressImage($coverFile->getRealPath(), $tempPath, 1200, 75);
            
            $imageRef = MediaStorage::storeUploadedFile(
                new \Illuminate\Http\UploadedFile($tempPath, "video_{$uuid}.jpeg", 'image/jpeg', null, true),
                'videos',
                true,
                "video_{$uuid}.jpeg"
            );
            $imageUrl = MediaStorage::url($imageRef);
            @unlink($tempPath);
        }

        // 3. Save a local copy in server cache immediately
        try {
            $cacheFolder = public_path('storage/video_cache');
            if (!file_exists($cacheFolder)) {
                mkdir($cacheFolder, 0775, true);
                @chmod($cacheFolder, 0775);
            }
            file_put_contents("{$cacheFolder}/{$uuid}.{$ext}", file_get_contents($videoFile->getRealPath()));
            @chmod("{$cacheFolder}/{$uuid}.{$ext}", 0664);
            
            if ($request->hasFile('cover')) {
                $this->compressImage($request->file('cover')->getRealPath(), "{$cacheFolder}/{$uuid}.jpeg", 1200, 75);
                @chmod("{$cacheFolder}/{$uuid}.jpeg", 0664);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to write local video cache: ' . $e->getMessage());
        }

        // 4. Save metadata to JSON database
        $videos = $this->getVideos();
        $videos[] = [
            'id' => $uuid,
            'title' => trim($request->input('title')),
            'description' => trim($request->input('description') ?? ''),
            'video_url' => $videoUrl,
            'video_ref' => $videoRef,
            'video_ext' => $ext,
            'image_url' => $imageUrl,
            'image_ref' => $imageRef,
            'duration' => '0:00', // Default duration
            'created_at' => now()->toIso8601String(),
        ];
        $this->saveVideos($videos);

        return redirect()->route('admin.videos.index')->with('success', 'Video berhasil diunggah dan disimpan!');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|string',
        ]);

        $id = $request->input('id');
        $videos = $this->getVideos();

        $key = array_search($id, array_column($videos, 'id'));
        if ($key !== false) {
            $video = $videos[$key];
            $ext = $video['video_ext'] ?? 'mp4';

            // 1. Delete GDrive files
            if (!empty($video['video_ref'])) {
                MediaStorage::delete($video['video_ref']);
            }
            if (!empty($video['image_ref'])) {
                MediaStorage::delete($video['image_ref']);
            }

            // 2. Delete local cache files
            @unlink(public_path("storage/video_cache/{$id}.{$ext}"));
            @unlink(public_path("storage/video_cache/{$id}.jpeg"));

            // 3. Remove metadata
            unset($videos[$key]);
            $this->saveVideos($videos);

            return redirect()->route('admin.videos.index')->with('success', 'Video berhasil dihapus!');
        }

        return back()->with('error', 'Video tidak ditemukan.');
    }

    public function apiList()
    {
        $videos = $this->getVideos();
        
        $mapped = array_map(function ($video) {
            $id = $video['id'];
            $ext = $video['video_ext'] ?? 'mp4';
            
            $cachePath = "storage/video_cache/{$id}.{$ext}";
            $coverCachePath = "storage/video_cache/{$id}.jpeg";
            
            if (file_exists(public_path($cachePath))) {
                $video['video_url'] = '/' . $cachePath;
            } else {
                $video['video_url'] = url('/api/video-stream/' . urlencode($id));
            }

            if (file_exists(public_path($coverCachePath))) {
                $video['image_url'] = '/' . $coverCachePath;
            }
            
            return $video;
        }, $videos);

        return response()->json(array_values($mapped));
    }

    public function stream($id)
    {
        $videos = $this->getVideos();
        $video = collect($videos)->firstWhere('id', $id);
        if (!$video) {
            abort(404);
        }
        
        $ext = $video['video_ext'] ?? 'mp4';
        $cachePath = public_path("storage/video_cache/{$id}.{$ext}");
        if (file_exists($cachePath)) {
            return response()->file($cachePath, [
                'Content-Type' => 'video/mp4',
                'Accept-Ranges' => 'bytes',
            ]);
        }
        
        return redirect($video['video_url']);
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
