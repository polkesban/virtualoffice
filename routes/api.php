<?php

use App\Http\Controllers\ConciergeController;
use Illuminate\Support\Facades\Route;

Route::post('/concierge/chat', [ConciergeController::class, 'chat'])
    ->middleware('throttle:20,1')
    ->name('concierge.chat');

Route::get('/suno-playlist', function () {
    $jsonPath = 'suno_songs.json';
    
    $songs = [];
    if (\Illuminate\Support\Facades\Storage::exists($jsonPath)) {
        $songs = json_decode(\Illuminate\Support\Facades\Storage::get($jsonPath), true) ?: [];
    }
    
    // Check if the file and cover art are cached on the server, serve the direct static Nginx URLs for maximum stability
    $songs = array_map(function ($song) {
        $id = $song['id'];
        
        // Cache paths
        $cacheRelPath = 'storage/suno_cache/' . $id . '.mp3';
        $coverCacheRelPath = 'storage/suno_cache/' . $id . '.jpeg';
        
        // Map audio URL
        if (file_exists(public_path($cacheRelPath))) {
            $song['audio_url'] = '/' . $cacheRelPath;
        } else {
            if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
                $song['audio_url'] = 'https://cdn1.suno.ai/' . $id . '.mp3';
            } else {
                $song['audio_url'] = url('/api/suno-stream/' . urlencode($id));
            }
        }
        
        // Map cover image URL
        if (file_exists(public_path($coverCacheRelPath))) {
            $song['image_url'] = '/' . $coverCacheRelPath;
        } else {
            if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
                $song['image_url'] = 'https://cdn2.suno.ai/image_' . $id . '.jpeg';
            }
        }
        
        return $song;
    }, $songs);
    
    return response()->json($songs);
})->name('suno.playlist');

Route::get('/suno-stream/{id}', function ($id) {
    $jsonPath = 'suno_songs.json';
    if (!\Illuminate\Support\Facades\Storage::exists($jsonPath)) {
        abort(404);
    }
    
    $songs = json_decode(\Illuminate\Support\Facades\Storage::get($jsonPath), true) ?: [];
    $song = collect($songs)->firstWhere('id', $id);
    if (!$song) {
        abort(404);
    }

    $cachePath = public_path('storage/suno_cache/' . $id . '.mp3');
    
    // If cached successfully, stream the local file (supports HTTP Range/Accept-Ranges natively)
    if (file_exists($cachePath)) {
        return response()->file($cachePath, [
            'Content-Type' => 'audio/mpeg',
            'Accept-Ranges' => 'bytes',
        ]);
    }
    
    // If NOT cached, redirect immediately to the CDN or source URL to avoid blocking PHP-FPM threads
    if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
        return redirect('https://cdn1.suno.ai/' . $id . '.mp3');
    }
    
    return redirect($song['audio_url']);
})->name('suno.stream');

Route::get('/podcast-playlist', [\App\Http\Controllers\Admin\PodcastController::class, 'apiList'])->name('podcast.playlist');
Route::get('/podcast-stream/{id}', [\App\Http\Controllers\Admin\PodcastController::class, 'stream'])->name('podcast.stream');

Route::get('/video-playlist', [\App\Http\Controllers\Admin\VideoController::class, 'apiList'])->name('video.playlist');
Route::get('/video-stream/{id}', [\App\Http\Controllers\Admin\VideoController::class, 'stream'])->name('video.stream');

// ─── OSDM Microsite API ──────────────────────────────────────────────────────
Route::prefix('osdm')->group(function () {
    // Portal Config
    Route::get('/portal/live', [\App\Http\Controllers\Api\OsdmApiController::class, 'getPortalLive']);
    Route::post('/portal/publish', [\App\Http\Controllers\Api\OsdmApiController::class, 'publishPortal']);
    Route::get('/portal/draft', [\App\Http\Controllers\Api\OsdmApiController::class, 'getPortalDraft']);
    Route::post('/portal/draft', [\App\Http\Controllers\Api\OsdmApiController::class, 'savePortalDraft']);

    // Security / Admin PIN
    Route::get('/security/pin', [\App\Http\Controllers\Api\OsdmApiController::class, 'getAdminPin']);
    Route::post('/security/pin', [\App\Http\Controllers\Api\OsdmApiController::class, 'saveAdminPin']);
    Route::post('/security/wfa-pin', [\App\Http\Controllers\Api\OsdmApiController::class, 'saveWfaAdminPin']);
    Route::post('/security/kebugaran-pin', [\App\Http\Controllers\Api\OsdmApiController::class, 'saveKebugaranAdminPin']);
    Route::post('/security/meeting-pin', [\App\Http\Controllers\Api\OsdmApiController::class, 'saveMeetingAdminPin']);
    Route::post('/security/monitoring-all-pin', [\App\Http\Controllers\Api\OsdmApiController::class, 'saveMonitoringAllAdminPin']);

    // Employees
    Route::get('/employees', [\App\Http\Controllers\Api\OsdmApiController::class, 'getEmployees']);
    Route::post('/employees', [\App\Http\Controllers\Api\OsdmApiController::class, 'createEmployee']);
    Route::put('/employees/{nip}', [\App\Http\Controllers\Api\OsdmApiController::class, 'updateEmployee']);
    Route::delete('/employees/{nip}', [\App\Http\Controllers\Api\OsdmApiController::class, 'deleteEmployee']);

    // WFA Submissions
    Route::get('/wfa', [\App\Http\Controllers\Api\OsdmApiController::class, 'getWfaSubmissions']);
    Route::post('/wfa', [\App\Http\Controllers\Api\OsdmApiController::class, 'createWfaSubmission']);
    Route::put('/wfa/{id}/status', [\App\Http\Controllers\Api\OsdmApiController::class, 'updateWfaStatus']);
    Route::put('/wfa/{id}/link', [\App\Http\Controllers\Api\OsdmApiController::class, 'updateWfaLinkSuratTugas']);
    Route::delete('/wfa/{id}', [\App\Http\Controllers\Api\OsdmApiController::class, 'deleteWfaSubmission']);

    // Kebugaran Submissions
    Route::get('/kebugaran', [\App\Http\Controllers\Api\OsdmApiController::class, 'getKebugaranSubmissions']);
    Route::post('/kebugaran', [\App\Http\Controllers\Api\OsdmApiController::class, 'createKebugaranSubmission']);
    Route::delete('/kebugaran/{id}', [\App\Http\Controllers\Api\OsdmApiController::class, 'deleteKebugaranSubmission']);

    // Click Logs
    Route::get('/click-logs', [\App\Http\Controllers\Api\OsdmApiController::class, 'getClickLogs']);
    Route::post('/click-logs', [\App\Http\Controllers\Api\OsdmApiController::class, 'createClickLog']);
    Route::delete('/click-logs', [\App\Http\Controllers\Api\OsdmApiController::class, 'deleteClickLogs']);

    // Meeting Sessions, Attendees & Agendas (Cloud Sync across all devices)
    Route::get('/meeting/sessions', [\App\Http\Controllers\Api\OsdmApiController::class, 'getMeetingSessions']);
    Route::post('/meeting/sessions', [\App\Http\Controllers\Api\OsdmApiController::class, 'saveMeetingSession']);
    Route::delete('/meeting/sessions/{id}', [\App\Http\Controllers\Api\OsdmApiController::class, 'deleteMeetingSession']);
    Route::get('/meeting/session', [\App\Http\Controllers\Api\OsdmApiController::class, 'getMeetingSession']);
    Route::post('/meeting/session', [\App\Http\Controllers\Api\OsdmApiController::class, 'saveMeetingSession']);
    Route::get('/meeting/attendees', [\App\Http\Controllers\Api\OsdmApiController::class, 'getMeetingAttendees']);
    Route::post('/meeting/attendees', [\App\Http\Controllers\Api\OsdmApiController::class, 'saveMeetingAttendee']);
    Route::delete('/meeting/attendees/{id}', [\App\Http\Controllers\Api\OsdmApiController::class, 'deleteMeetingAttendee']);
    Route::get('/meeting/agendas', [\App\Http\Controllers\Api\OsdmApiController::class, 'getMeetingAgendas']);
    Route::post('/meeting/agendas', [\App\Http\Controllers\Api\OsdmApiController::class, 'saveMeetingAgenda']);
    Route::delete('/meeting/agendas', [\App\Http\Controllers\Api\OsdmApiController::class, 'deleteMeetingAgenda']);
    Route::post('/meeting/agendas/delete', [\App\Http\Controllers\Api\OsdmApiController::class, 'deleteMeetingAgenda']);

    // Pegawai Forms & Survei Dinamis
    Route::get('/pegawai/forms', [\App\Http\Controllers\Api\OsdmApiController::class, 'getPegawaiForms']);
    Route::post('/pegawai/forms', [\App\Http\Controllers\Api\OsdmApiController::class, 'savePegawaiForm']);
    Route::delete('/pegawai/forms/{id}', [\App\Http\Controllers\Api\OsdmApiController::class, 'deletePegawaiForm']);
    Route::get('/pegawai/form-responses', [\App\Http\Controllers\Api\OsdmApiController::class, 'getPegawaiFormResponses']);
    Route::post('/pegawai/form-responses', [\App\Http\Controllers\Api\OsdmApiController::class, 'savePegawaiFormResponse']);
    Route::delete('/pegawai/form-responses/{id}', [\App\Http\Controllers\Api\OsdmApiController::class, 'deletePegawaiFormResponse']);
});