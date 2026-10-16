<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SunoPlaylistController extends Controller
{
    private string $jsonPath = 'suno_playlists.json';

    private function getPlaylists(): array
    {
        if (Storage::exists($this->jsonPath)) {
            return json_decode(Storage::get($this->jsonPath), true) ?: [];
        }

        // Fallback to current env list
        $default = explode(',', env('SUNO_PLAYLIST_URLS', 'https://suno.com/playlist/3ee502f0-9521-4199-8bca-97bf2d0a9d05,https://suno.com/playlist/268b0601-ece5-45ad-b18d-6a95b4f2ae14,https://suno.com/playlist/12a80331-8d40-4ed8-b36d-d5a6a79e28e1'));
        $default = array_map('trim', $default);
        $default = array_filter($default);

        return array_values($default);
    }

    private function savePlaylists(array $playlists): void
    {
        Storage::put($this->jsonPath, json_encode(array_values($playlists), JSON_PRETTY_PRINT));
    }

    public function index()
    {
        $playlists = $this->getPlaylists();
        return view('admin.suno-playlists.index', compact('playlists'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'url' => 'required|url'
        ]);

        $url = trim($request->string('url')->toString());
        
        if (!str_contains($url, 'suno.com/playlist/')) {
            return back()->with('error', 'URL harus berupa playlist Suno yang valid (contoh: https://suno.com/playlist/...)');
        }

        $playlists = $this->getPlaylists();
        if (in_array($url, $playlists)) {
            return back()->with('error', 'Playlist tersebut sudah terdaftar.');
        }

        $playlists[] = $url;
        $this->savePlaylists($playlists);

        $this->triggerSync();

        return redirect()->route('admin.suno-playlists.index')->with('success', 'Playlist berhasil ditambahkan! Proses sinkronisasi lagu sedang berjalan di latar belakang.');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'url' => 'required|string'
        ]);

        $url = trim($request->string('url')->toString());
        $playlists = $this->getPlaylists();
        
        if (($key = array_search($url, $playlists)) !== false) {
            unset($playlists[$key]);
            $this->savePlaylists($playlists);
            
            $this->triggerSync();
            
            return redirect()->route('admin.suno-playlists.index')->with('success', 'Playlist berhasil dihapus! Pembaruan daftar lagu sedang disinkronkan.');
        }

        return back()->with('error', 'Playlist tidak ditemukan.');
    }

    public function sync()
    {
        try {
            $this->triggerSync();
            return redirect()->route('admin.suno-playlists.index')->with('success', 'Proses sinkronisasi seluruh playlist berhasil dimulai di latar belakang.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memicu sinkronisasi: ' . $e->getMessage());
        }
    }

    public function syncStatus()
    {
        if (Storage::exists('suno_sync_status.json')) {
            $data = json_decode(Storage::get('suno_sync_status.json'), true);
            return response()->json($data);
        }
        return response()->json([
            'status' => 'idle',
            'total_songs' => 0,
            'processed_songs' => 0,
            'current_action' => '',
            'updated_at' => null
        ]);
    }
    private function triggerSync()
    {
        try {
            $phpBinary = PHP_BINARY;
            if (str_contains($phpBinary, 'php-fpm') || str_contains($phpBinary, 'php-cgi') || str_contains($phpBinary, 'passenger')) {
                $possiblePaths = [
                    '/www/server/php/83/bin/php',
                    '/www/server/php/82/bin/php',
                    '/www/server/php/81/bin/php',
                    '/usr/bin/php8.3',
                    '/usr/bin/php8.2',
                    '/usr/bin/php',
                    '/usr/local/bin/php',
                    'php'
                ];
                foreach ($possiblePaths as $path) {
                    if ($path === 'php' || @is_executable($path)) {
                        $phpBinary = $path;
                        break;
                    }
                }
            }
            $command = $phpBinary . ' ' . base_path('artisan') . ' suno:sync > /dev/null 2>&1 &';
            exec($command);
        } catch (\Exception $e) {
            Log::error('Background suno:sync trigger failed: ' . $e->getMessage());
        }
    }
}
