<?php

use App\Http\Controllers\BeritaController;
use App\Http\Controllers\ConciergeController;
use App\Http\Controllers\KontakController;
use App\Http\Controllers\LayananBisnisOrderController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PortalMahasiswaController;
use App\Http\Controllers\Pegawai\PegawaiAuthController;
use App\Http\Controllers\PojokAlumniController;
use App\Http\Controllers\PengumumanController;
use App\Http\Controllers\PublicSectionController;
use App\Http\Controllers\ProgramStudiController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SpeedTestController;
use App\Http\Controllers\UnitEventController as PublicUnitEventController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminPasswordResetController;
use App\Http\Controllers\Admin\AgendaController;
use App\Http\Controllers\Admin\AccreditationEvidenceController;
use App\Http\Controllers\Admin\ApiKeyController;
use App\Http\Controllers\Admin\BeritaController as AdminBeritaController;
use App\Http\Controllers\Admin\ContentEngineController;
use App\Http\Controllers\Admin\GaleriController;
use App\Http\Controllers\Admin\HalamanStatisController;
use App\Http\Controllers\Admin\NavMenuController;
use App\Http\Controllers\Admin\AdminNavConfigController;
use App\Http\Controllers\Admin\KnowledgeController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SiteAppearanceController;
use App\Http\Controllers\Admin\DokumenMutuController;
use App\Http\Controllers\Admin\FasilitasAsramaController;
use App\Http\Controllers\Admin\GuideCenterController;
use App\Http\Controllers\Admin\KoleksiPerpusController;
use App\Http\Controllers\Admin\LayananBisnisController;
use App\Http\Controllers\Admin\LayananLabController;
use App\Http\Controllers\Admin\LayananTIController;
use App\Http\Controllers\Admin\MeetingRecapController;
use App\Http\Controllers\Admin\PageSectionResponseController;
use App\Http\Controllers\Admin\PelatihanController;
use App\Http\Controllers\Admin\PublikasiController;
use App\Http\Controllers\Admin\MahasiswaBerprestasiController;
use App\Http\Controllers\Admin\HalamanUnitController;
use App\Http\Controllers\Admin\UnitEventController;
use App\Http\Controllers\Admin\UnitEventDocumentationController;
use App\Http\Controllers\Admin\UnitEventMaterialController;
use App\Http\Controllers\Admin\UnitEventParticipantController;
use App\Http\Controllers\Admin\UnitEventQuestionController;
use App\Http\Controllers\Admin\UnitEventAiDocumentController;
use App\Http\Controllers\Admin\EvaluationRubricController;
use App\Http\Controllers\Admin\WfhReportController;
use App\Http\Controllers\UnitPageController;
use App\Http\Controllers\PengaduanController;
use App\Http\Controllers\Admin\PengaduanUnitController;
use App\Http\Controllers\Admin\PengumumanController as AdminPengumumanController;
use App\Http\Controllers\Admin\ProgramStudiController as AdminProgramStudiController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Mahasiswa\MahasiswaAuthController;
use App\Http\Controllers\Mahasiswa\MahasiswaPasswordResetController;
use App\Http\Controllers\Mahasiswa\PortofolioController;
use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\EnsureMahasiswa;
use Illuminate\Support\Facades\Route;

// ─── Halaman Utama ────────────────────────────────────────────────────────────
Route::get('/', fn () => redirect()->away('https://vo.poltekkesbandung.ac.id/unit'))->name('home');
Route::get('/login', [MahasiswaAuthController::class, 'showLogin'])->name('login');

Route::get('/suno-player/embed', function () {
    $jsonPath = 'suno_songs.json';
    $sunoSongs = [];
    if (\Illuminate\Support\Facades\Storage::exists($jsonPath)) {
        $sunoSongs = json_decode(\Illuminate\Support\Facades\Storage::get($jsonPath), true) ?: [];
        $sunoSongs = array_map(function ($song) {
            $id = $song['id'];
            
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
        }, $sunoSongs);
    }
    
    // Load podcast episodes
    $podcastPath = 'podcast_episodes.json';
    $podcastEpisodes = [];
    if (\Illuminate\Support\Facades\Storage::exists($podcastPath)) {
        $podcastEpisodes = json_decode(\Illuminate\Support\Facades\Storage::get($podcastPath), true) ?: [];
        $podcastEpisodes = array_map(function ($ep) {
            $id = $ep['id'];
            
            $cachePath = 'public/podcast_cache/' . $id . '.mp3';
            $coverCachePath = 'public/podcast_cache/' . $id . '.jpeg';
            
            if (\Illuminate\Support\Facades\Storage::exists($cachePath)) {
                $ep['audio_url'] = asset('storage/podcast_cache/' . $id . '.mp3');
            } else {
                $ep['audio_url'] = url('/api/podcast-stream/' . urlencode($id));
            }

            if (\Illuminate\Support\Facades\Storage::exists($coverCachePath)) {
                $ep['image_url'] = asset('storage/podcast_cache/' . $id . '.jpeg');
            }
            
            return $ep;
        }, $podcastEpisodes);
    }

    // Load videos
    $videoPath = 'video_items.json';
    $videoItems = [];
    if (\Illuminate\Support\Facades\Storage::exists($videoPath)) {
        $videoItems = json_decode(\Illuminate\Support\Facades\Storage::get($videoPath), true) ?: [];
        $videoItems = array_map(function ($video) {
            $id = $video['id'];
            
            $cachePath = 'public/video_cache/' . $id . '.mp4';
            $coverCachePath = 'public/video_cache/' . $id . '.jpeg';
            
            if (\Illuminate\Support\Facades\Storage::exists($cachePath)) {
                $video['video_url'] = asset('storage/video_cache/' . $id . '.mp4');
            } else {
                $video['video_url'] = url('/api/video-stream/' . urlencode($id));
            }

            if (\Illuminate\Support\Facades\Storage::exists($coverCachePath)) {
                $video['image_url'] = asset('storage/video_cache/' . $id . '.jpeg');
            }
            
            return $video;
        }, $videoItems);
    }
    
    return response()
        ->view('public-sections.suno-embed', compact('sunoSongs', 'podcastEpisodes', 'videoItems'))
        ->header('X-Frame-Options', 'ALLOWALL')
        ->header('Content-Security-Policy', "frame-ancestors 'self' https://beta.poltekkesbandung.ac.id https://vo.poltekkesbandung.ac.id https://polkesban.ac.id");
})->name('suno.embed');


// ─── Program Studi ────────────────────────────────────────────────────────────
Route::get('/program-studi', [ProgramStudiController::class, 'index'])->name('program-studi');
Route::get('/program-studi/{slug}', [ProgramStudiController::class, 'show'])->name('program-studi.show');

// ─── Berita ───────────────────────────────────────────────────────────────────
Route::get('/berita', [BeritaController::class, 'index'])->name('berita');
Route::get('/berita/{slug}', [BeritaController::class, 'show'])->name('berita.show');

// ─── Kontak ───────────────────────────────────────────────────────────────────
Route::get('/kontak', [KontakController::class, 'index'])->name('kontak');
Route::post('/kontak', [KontakController::class, 'store'])->middleware('throttle:public-form')->name('kontak.store');

// ─── Newsletter ───────────────────────────────────────────────────────────────
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->middleware('throttle:public-form')->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
Route::get('/newsletter/confirm/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');

// ─── Halaman Publik Terkelola ────────────────────────────────────────────────
Route::get('/galeri', [PublicSectionController::class, 'galeri'])->name('galeri');
Route::get('/agenda', [PublicSectionController::class, 'agenda'])->name('agenda');
Route::get('/mahasiswa-berprestasi', [PublicSectionController::class, 'berprestasi'])->name('berprestasi');
Route::get('/layanan-bisnis', [PublicSectionController::class, 'layananBisnis'])->name('layanan-bisnis');
Route::get('/layanan-lab', [PublicSectionController::class, 'layananLab'])->name('layanan-lab');
Route::get('/fasilitas-asrama', [PublicSectionController::class, 'fasilitasAsrama'])->name('fasilitas-asrama');
Route::get('/koleksi-perpustakaan', [PublicSectionController::class, 'koleksiPerpus'])->name('koleksi-perpus');
Route::get('/publikasi', [PublicSectionController::class, 'publikasi'])->name('publikasi');
Route::get('/dokumen-mutu', [PublicSectionController::class, 'dokumenMutu'])->name('dokumen-mutu');
Route::get('/layanan-ti', [PublicSectionController::class, 'layananTI'])->name('layanan-ti');
Route::get('/pelatihan', [PublicSectionController::class, 'pelatihan'])->name('pelatihan');
Route::get('/unit', [PublicSectionController::class, 'unitIndex'])->name('unit.index');
Route::get('/portal-mahasiswa', [PortalMahasiswaController::class, 'index'])->name('portal-mahasiswa');
Route::get('/portal-mahasiswa/{slug}', [PortalMahasiswaController::class, 'show'])->name('portal-mahasiswa.show');
Route::get('/pegawai/login', [PegawaiAuthController::class, 'showLogin'])->name('pegawai.login');
Route::post('/pegawai/login', [PegawaiAuthController::class, 'login'])->middleware('throttle:public-auth')->name('pegawai.login.post');
Route::post('/pegawai/logout', [PegawaiAuthController::class, 'logout'])->middleware('auth')->name('pegawai.logout');
Route::get('/pojok-alumni', [PojokAlumniController::class, 'index'])->name('pojok-alumni');
Route::post('/pojok-alumni', [PojokAlumniController::class, 'store'])->middleware('throttle:public-form')->name('pojok-alumni.store');

// ─── Speedtest ────────────────────────────────────────────────────────────────
Route::get('/speedtest', [SpeedTestController::class, 'index'])->name('speedtest.index');
Route::prefix('speedtest')->name('speedtest.')->middleware('throttle:90,1')->group(function () {
    Route::get('/ping', [SpeedTestController::class, 'ping'])->name('ping');
    Route::get('/download', [SpeedTestController::class, 'download'])->name('download');
    Route::post('/upload', [SpeedTestController::class, 'upload'])->name('upload');
});

// ─── Placeholder halaman statis ───────────────────────────────────────────────
Route::get('/profil',                fn () => redirect('/profil/sejarah'))->name('profil');
Route::get('/profil/sejarah',        fn () => view('profil.sejarah'))->name('profil.sejarah');
Route::get('/profil/visi-misi',      fn () => view('profil.visi-misi'))->name('profil.visi-misi');
Route::get('/profil/struktur',       fn () => view('profil.struktur'))->name('profil.struktur');
Route::get('/profil/akreditasi',     fn () => view('profil.akreditasi'))->name('profil.akreditasi');

// ─── Pengumuman ───────────────────────────────────────────────────────────────
Route::get('/pengumuman', [PengumumanController::class, 'index'])->name('pengumuman');
Route::get('/layanan-bisnis/pesan', [LayananBisnisOrderController::class, 'create'])->name('layanan-bisnis.order');
Route::post('/layanan-bisnis/pesan', [LayananBisnisOrderController::class, 'store'])->middleware('throttle:public-form')->name('layanan-bisnis.order.store');
Route::get('/akademik',              fn () => view('welcome'))->name('akademik');
Route::get('/akademik/kalender',     fn () => view('welcome'))->name('akademik.kalender');
Route::get('/penelitian',            fn () => view('welcome'))->name('penelitian');
Route::get('/penerimaan',            fn () => redirect()->away('https://spmb.poltekkesbandung.ac.id/'))->name('penerimaan');
Route::get('/kerjasama',             fn () => view('welcome'))->name('kerjasama');
Route::get('/perpustakaan',          fn () => redirect()->away('https://library.poltekkesbandung.ac.id'))->name('perpustakaan');
Route::get('/portal',                fn () => redirect()->away('https://siak.poltekkesbandung.ac.id/'))->name('portal');
Route::get('/kebijakan-privasi',     fn () => view('welcome'))->name('kebijakan-privasi');
Route::get('/syarat-ketentuan',      fn () => view('welcome'))->name('syarat-ketentuan');

// ─── Sitemap ──────────────────────────────────────────────────────────────────
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// ─── Pengaduan / Kritik / Saran / Keluhan (Public) ───────────────────────────
Route::get('/pengaduan',  [PengaduanController::class, 'create'])->name('pengaduan.create');
Route::post('/pengaduan', [PengaduanController::class, 'store'])->middleware('throttle:public-form')->name('pengaduan.store');
Route::get('/pengaduan/sukses', [PengaduanController::class, 'sukses'])->name('pengaduan.sukses');

// ─── Admin ────────────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {

    // Auth (unauthenticated)
    Route::get('/login',  [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:public-auth')->name('login.post');
    Route::get('/lupa-password', [AdminPasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/lupa-password', [AdminPasswordResetController::class, 'sendResetLinkEmail'])->middleware('throttle:public-auth')->name('password.email');
    Route::get('/reset-password/{token}', [AdminPasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AdminPasswordResetController::class, 'reset'])->middleware('throttle:public-auth')->name('password.update');
    Route::post('/logout',[AdminAuthController::class, 'logout'])->middleware('auth')->name('logout');

    // Protected
    Route::middleware([EnsureIsAdmin::class])->group(function () {

        Route::get('/', function () {
            if (! auth()->user()->isSuperAdmin()) {
                return redirect()->route('admin.halaman-unit.index');
            }
            return redirect()->route('admin.content-engine.index');
        })->name('dashboard');

        // Suno Playlists Manager
        Route::get('/suno-playlists', [\App\Http\Controllers\Admin\SunoPlaylistController::class, 'index'])->name('suno-playlists.index');
        Route::post('/suno-playlists', [\App\Http\Controllers\Admin\SunoPlaylistController::class, 'store'])->name('suno-playlists.store');
        Route::delete('/suno-playlists', [\App\Http\Controllers\Admin\SunoPlaylistController::class, 'destroy'])->name('suno-playlists.destroy');
        Route::post('/suno-playlists/sync', [\App\Http\Controllers\Admin\SunoPlaylistController::class, 'sync'])->name('suno-playlists.sync');
        Route::get('/suno-playlists/status', [\App\Http\Controllers\Admin\SunoPlaylistController::class, 'syncStatus'])->name('suno-playlists.status');

        // Podcast Manager
        Route::get('/podcasts', [\App\Http\Controllers\Admin\PodcastController::class, 'index'])->name('podcasts.index');
        Route::post('/podcasts', [\App\Http\Controllers\Admin\PodcastController::class, 'store'])->name('podcasts.store');
        Route::delete('/podcasts', [\App\Http\Controllers\Admin\PodcastController::class, 'destroy'])->name('podcasts.destroy');

        // Video Manager
        Route::get('/videos', [\App\Http\Controllers\Admin\VideoController::class, 'index'])->name('videos.index');
        Route::post('/videos', [\App\Http\Controllers\Admin\VideoController::class, 'store'])->name('videos.store');
        Route::delete('/videos', [\App\Http\Controllers\Admin\VideoController::class, 'destroy'])->name('videos.destroy');

        // Content Engine
        Route::prefix('content-engine')->name('content-engine.')->group(function () {
            Route::get('/',                          [ContentEngineController::class, 'index'])->name('index');
            Route::post('/upload',                   [ContentEngineController::class, 'upload'])->name('upload');
            Route::post('/generate-block',           [ContentEngineController::class, 'generateBlock'])->name('generate-block');
            Route::get('/{job}',                     [ContentEngineController::class, 'show'])->name('show');
            Route::get('/{job}/status',              [ContentEngineController::class, 'status'])->name('status');
            Route::get('/{job}/media',               [ContentEngineController::class, 'media'])->name('media');
            Route::post('/{job}/regenerate',         [ContentEngineController::class, 'regenerate'])->name('regenerate');
            Route::post('/{job}/publish',            [ContentEngineController::class, 'publish'])->name('publish');
            Route::delete('/{job}',                  [ContentEngineController::class, 'destroy'])->name('destroy');
        });

        // Knowledge Base
        Route::prefix('knowledge')->name('knowledge.')->group(function () {
            Route::get('/',                          [KnowledgeController::class, 'index'])->name('index');
            Route::post('/upload',                   [KnowledgeController::class, 'upload'])->name('upload');
            Route::post('/{doc}/reindex',            [KnowledgeController::class, 'reindex'])->name('reindex');
            Route::delete('/{doc}',                  [KnowledgeController::class, 'destroy'])->name('destroy');
        });

        // API Keys
        Route::prefix('api-keys')->name('api-keys.')->group(function () {
            Route::get('/',             [ApiKeyController::class, 'index'])->name('index');
            Route::get('/tinymce/runtime', [ApiKeyController::class, 'tinymceRuntime'])->name('tinymce.runtime');
            Route::post('/',            [ApiKeyController::class, 'store'])->name('store');
            Route::post('/active-provider', [ApiKeyController::class, 'updateActiveProvider'])->name('active-provider.update');
            Route::post('/models',      [ApiKeyController::class, 'updateChatModels'])->name('models.update');
            Route::post('/{key}/test', [ApiKeyController::class, 'test'])->name('test');
            Route::post('/{key}/tinymce/used', [ApiKeyController::class, 'tinymceReportUsed'])->name('tinymce.used');
            Route::post('/{key}/tinymce/exhausted', [ApiKeyController::class, 'tinymceReportExhausted'])->name('tinymce.exhausted');
            Route::patch('/{key}',      [ApiKeyController::class, 'update'])->name('update');
            Route::post('/reset-all',   [ApiKeyController::class, 'resetAll'])->name('reset-all');
            Route::post('/{key}/reset', [ApiKeyController::class, 'resetExhausted'])->name('reset');
            Route::delete('/{key}',     [ApiKeyController::class, 'destroy'])->name('destroy');
        });

        // Berita
        Route::resource('berita', AdminBeritaController::class)
            ->parameter('berita', 'berita')
            ->names('berita');

        // Pengumuman
        Route::resource('pengumuman', AdminPengumumanController::class)->names('pengumuman');
        Route::patch('pengumuman/{pengumuman}/toggle', [AdminPengumumanController::class, 'toggleAktif'])->name('pengumuman.toggle');

        // Galeri
        Route::resource('galeri', GaleriController::class)->names('galeri');
        Route::patch('galeri/{galeri}/toggle', [GaleriController::class, 'toggleTampil'])->name('galeri.toggle');

        // Agenda
        Route::resource('agenda', AgendaController::class)->names('agenda');

        // Program Studi
        Route::resource('program-studi', AdminProgramStudiController::class)
            ->parameter('program-studi', 'programStudi')
            ->names('program-studi');
        Route::get('program-studi/{programStudi}/konten', [AdminProgramStudiController::class, 'manageContent'])->name('program-studi.content');

        // Navigasi Publik (super admin)
        Route::get('nav-menus',                         [NavMenuController::class, 'index'])->name('nav-menus.index');
        Route::post('nav-menus',                        [NavMenuController::class, 'store'])->name('nav-menus.store');
        Route::put('nav-menus/{navMenu}',               [NavMenuController::class, 'update'])->name('nav-menus.update');
        Route::delete('nav-menus/{navMenu}',            [NavMenuController::class, 'destroy'])->name('nav-menus.destroy');
        Route::patch('nav-menus/{navMenu}/move',        [NavMenuController::class, 'move'])->name('nav-menus.move');
        Route::patch('nav-menus/{navMenu}/toggle',      [NavMenuController::class, 'toggleActive'])->name('nav-menus.toggle');

        // Admin Sidebar Config (super admin)
        Route::get('nav-admin',                         [AdminNavConfigController::class, 'index'])->name('nav-admin.index');
        Route::post('nav-admin/update-all',             [AdminNavConfigController::class, 'update'])->name('nav-admin.update');

        // Pusat Panduan (super admin)
        Route::get('guides',                            [GuideCenterController::class, 'index'])->name('guides.index');

        // Editor Situs (super admin)
        Route::get('site-appearance',                  [SiteAppearanceController::class, 'edit'])->name('site-appearance.edit');
        Route::put('site-appearance',                  [SiteAppearanceController::class, 'update'])->name('site-appearance.update');

        // Halaman Statis
        Route::get('halaman-statis',              [HalamanStatisController::class, 'index'])->name('halaman-statis.index');
        Route::post('halaman-statis',             [HalamanStatisController::class, 'storeMeta'])->name('halaman-statis.store-meta');
        Route::delete('halaman-statis/{kunci}',   [HalamanStatisController::class, 'destroyMeta'])->name('halaman-statis.destroy-meta');
        Route::get('halaman-statis/{kunci}/edit', [HalamanStatisController::class, 'edit'])->name('halaman-statis.edit');
        Route::put('halaman-statis/{kunci}',      [HalamanStatisController::class, 'update'])->name('halaman-statis.update');
        // Halaman Statis sections (block builder)
        Route::get('halaman-statis/{kunci}/section/create',  [HalamanStatisController::class, 'createSection'])->name('halaman-statis.section.create');
        Route::post('halaman-statis/{kunci}/section',        [HalamanStatisController::class, 'storeSection'])->name('halaman-statis.section.store');
        Route::get('halaman-statis/section/{section}/edit',  [HalamanStatisController::class, 'editSection'])->name('halaman-statis.section.edit');
        Route::put('halaman-statis/section/{section}',       [HalamanStatisController::class, 'updateSection'])->name('halaman-statis.section.update');
        Route::delete('halaman-statis/section/{section}',    [HalamanStatisController::class, 'destroySection'])->name('halaman-statis.section.destroy');
        Route::patch('halaman-statis/section/{section}/toggle', [HalamanStatisController::class, 'toggleSection'])->name('halaman-statis.section.toggle');
        Route::patch('halaman-statis/section/{section}/move',   [HalamanStatisController::class, 'moveSection'])->name('halaman-statis.section.move');
        Route::get('page-sections/{section}/responses', [PageSectionResponseController::class, 'show'])->name('page-sections.responses.show');
        Route::get('page-sections/{section}/responses/summary', [PageSectionResponseController::class, 'summary'])->name('page-sections.responses.summary');
        Route::get('page-sections/{section}/responses/export', [PageSectionResponseController::class, 'export'])->name('page-sections.responses.export');

        // Layanan Unit Bisnis
        Route::get('layanan-bisnis/pesanan/{order}', [LayananBisnisController::class, 'showOrder'])->name('layanan-bisnis.orders.show');
        Route::put('layanan-bisnis/pesanan/{order}', [LayananBisnisController::class, 'updateOrder'])->name('layanan-bisnis.orders.update');
        Route::patch('layanan-bisnis/pesanan/{order}/quick-verify', [LayananBisnisController::class, 'quickVerifyOrder'])->name('layanan-bisnis.orders.quick-verify');
        Route::resource('layanan-bisnis', LayananBisnisController::class)
            ->parameter('layanan-bisnis', 'layananBisnis')
            ->except('show')
            ->names('layanan-bisnis');
        Route::patch('layanan-bisnis/{layananBisnis}/toggle', [LayananBisnisController::class, 'toggleAktif'])->name('layanan-bisnis.toggle');
        Route::patch('layanan-bisnis/{layananBisnis}/toggle-tersedia', [LayananBisnisController::class, 'toggleTersedia'])->name('layanan-bisnis.toggle-tersedia');

        // Layanan Laboratorium
        Route::resource('layanan-lab', LayananLabController::class)
            ->parameter('layanan-lab', 'layananLab')
            ->names('layanan-lab');
        Route::patch('layanan-lab/{layananLab}/toggle', [LayananLabController::class, 'toggleAktif'])->name('layanan-lab.toggle');

        // Fasilitas Asrama
        Route::resource('fasilitas-asrama', FasilitasAsramaController::class)
            ->parameter('fasilitas-asrama', 'fasilitasAsrama')
            ->names('fasilitas-asrama');

        // Koleksi Perpustakaan
        Route::resource('koleksi-perpus', KoleksiPerpusController::class)
            ->parameter('koleksi-perpus', 'koleksiPerpus')
            ->names('koleksi-perpus');

        // Publikasi & Penelitian
        Route::resource('publikasi', PublikasiController::class)->names('publikasi');

        // Dokumen Mutu
        Route::resource('dokumen-mutu', DokumenMutuController::class)
            ->parameter('dokumen-mutu', 'dokumenMutu')
            ->names('dokumen-mutu');

        // Ruang Eviden Akreditasi
        Route::get('eviden-akreditasi', [AccreditationEvidenceController::class, 'index'])->name('eviden-akreditasi.index');
        Route::get('eviden-akreditasi/export', [AccreditationEvidenceController::class, 'export'])->name('eviden-akreditasi.export');
        Route::get('eviden-akreditasi/create', [AccreditationEvidenceController::class, 'create'])->name('eviden-akreditasi.create');
        Route::get('eviden-akreditasi/annotations/export', [AccreditationEvidenceController::class, 'exportAnnotations'])->name('eviden-akreditasi.annotations.export');
        Route::post('eviden-akreditasi', [AccreditationEvidenceController::class, 'store'])->name('eviden-akreditasi.store');
        Route::get('eviden-akreditasi/{accreditationEvidence}', [AccreditationEvidenceController::class, 'show'])->name('eviden-akreditasi.show');
        Route::get('eviden-akreditasi/{accreditationEvidence}/edit', [AccreditationEvidenceController::class, 'edit'])->name('eviden-akreditasi.edit');
        Route::put('eviden-akreditasi/{accreditationEvidence}', [AccreditationEvidenceController::class, 'update'])->name('eviden-akreditasi.update');
        Route::delete('eviden-akreditasi/{accreditationEvidence}', [AccreditationEvidenceController::class, 'destroy'])->name('eviden-akreditasi.destroy');
        Route::get('eviden-akreditasi/{accreditationEvidence}/preview', [AccreditationEvidenceController::class, 'preview'])->name('eviden-akreditasi.preview');
        Route::get('eviden-akreditasi/{accreditationEvidence}/download', [AccreditationEvidenceController::class, 'download'])->name('eviden-akreditasi.download');
        Route::post('eviden-akreditasi/{accreditationEvidence}/annotations', [AccreditationEvidenceController::class, 'storeAnnotation'])->name('eviden-akreditasi.annotations.store');
        Route::put('eviden-akreditasi/{accreditationEvidence}/annotations/{annotation}', [AccreditationEvidenceController::class, 'updateAnnotation'])->name('eviden-akreditasi.annotations.update');
        Route::delete('eviden-akreditasi/{accreditationEvidence}/annotations/{annotation}', [AccreditationEvidenceController::class, 'destroyAnnotation'])->name('eviden-akreditasi.annotations.destroy');

        // Layanan TI
        Route::resource('layanan-ti', LayananTIController::class)
            ->parameter('layanan-ti', 'layananTI')
            ->names('layanan-ti');

        // Program Pelatihan (Pusat P3)
        Route::resource('pelatihan', PelatihanController::class)->names('pelatihan');

        // ─── Halaman Unit (Page Builder) ──────────────────────────────────────
        Route::get('halaman-unit',                               [HalamanUnitController::class, 'index'])->name('halaman-unit.index');
        Route::get('halaman-unit/create-page',                   [HalamanUnitController::class, 'createPage'])->name('halaman-unit.create-page');
        Route::post('halaman-unit/create-page',                  [HalamanUnitController::class, 'storePage'])->name('halaman-unit.store-page');
        Route::delete('halaman-unit/{unitPage}/destroy-page',    [HalamanUnitController::class, 'destroyPage'])->name('halaman-unit.destroy-page');
        Route::get('halaman-unit/{unitPage}',                    [HalamanUnitController::class, 'manage'])->name('halaman-unit.manage');
        Route::get('halaman-unit/{unitPage}/edit',               [HalamanUnitController::class, 'edit'])->name('halaman-unit.edit');
        Route::put('halaman-unit/{unitPage}',                    [HalamanUnitController::class, 'update'])->name('halaman-unit.update');
        Route::get('halaman-unit/{unitPage}/section/create',     [HalamanUnitController::class, 'createSection'])->name('halaman-unit.section.create');
        Route::post('halaman-unit/{unitPage}/section',           [HalamanUnitController::class, 'storeSection'])->name('halaman-unit.section.store');
        Route::get('halaman-unit/section/{section}/edit',        [HalamanUnitController::class, 'editSection'])->name('halaman-unit.section.edit');
        Route::put('halaman-unit/section/{section}',             [HalamanUnitController::class, 'updateSection'])->name('halaman-unit.section.update');
        Route::delete('halaman-unit/section/{section}',          [HalamanUnitController::class, 'destroySection'])->name('halaman-unit.section.destroy');
        Route::patch('halaman-unit/section/{section}/toggle',    [HalamanUnitController::class, 'toggleSection'])->name('halaman-unit.section.toggle');
        Route::patch('halaman-unit/section/{section}/move',      [HalamanUnitController::class, 'moveSection'])->name('halaman-unit.section.move');

        Route::get('rekap-rapat', [MeetingRecapController::class, 'index'])->name('meeting-recaps.index');
        Route::get('rekap-rapat/{unitEvent}', [MeetingRecapController::class, 'show'])->name('meeting-recaps.show');

        // ─── WFH Reports ─────────────────────────────────────────────────────
        Route::get('wfh', [WfhReportController::class, 'index'])->name('wfh.index');
        Route::get('wfh/create', [WfhReportController::class, 'create'])->name('wfh.create');
        Route::post('wfh', [WfhReportController::class, 'store'])->name('wfh.store');
        Route::get('wfh/{wfhReport}', [WfhReportController::class, 'show'])->name('wfh.show');
        Route::get('wfh/{wfhReport}/edit', [WfhReportController::class, 'edit'])->name('wfh.edit');
        Route::put('wfh/{wfhReport}', [WfhReportController::class, 'update'])->name('wfh.update');
        Route::delete('wfh/{wfhReport}', [WfhReportController::class, 'destroy'])->name('wfh.destroy');
        Route::post('wfh/{wfhReport}/catatan', [WfhReportController::class, 'updateNotes'])->name('wfh.update-notes');

        // ─── Event Unit ─────────────────────────────────────────────────────
        Route::get('halaman-unit/{unitPage}/events', [UnitEventController::class, 'index'])->name('unit-events.index');
        Route::get('halaman-unit/{unitPage}/events/create', [UnitEventController::class, 'create'])->name('unit-events.create');
        Route::post('halaman-unit/{unitPage}/events', [UnitEventController::class, 'store'])->name('unit-events.store');
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/edit', [UnitEventController::class, 'edit'])->name('unit-events.edit');
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/flyer', [UnitEventController::class, 'flyerPreview'])->name('unit-events.flyer');
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/signature', [UnitEventController::class, 'signatureForm'])->name('unit-events.signature');
        Route::post('halaman-unit/{unitPage}/events/{unitEvent}/signature', [UnitEventController::class, 'signatureStore'])->name('unit-events.signature.store');
        Route::delete('halaman-unit/{unitPage}/events/{unitEvent}/signature', [UnitEventController::class, 'signatureDestroy'])->name('unit-events.signature.destroy');
        Route::put('halaman-unit/{unitPage}/events/{unitEvent}', [UnitEventController::class, 'update'])->name('unit-events.update');
        Route::delete('halaman-unit/{unitPage}/events/{unitEvent}', [UnitEventController::class, 'destroy'])->name('unit-events.destroy');

        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/materials', [UnitEventMaterialController::class, 'index'])->name('unit-events.materials.index');
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/materials/create', [UnitEventMaterialController::class, 'create'])->name('unit-events.materials.create');
        Route::post('halaman-unit/{unitPage}/events/{unitEvent}/materials', [UnitEventMaterialController::class, 'store'])->name('unit-events.materials.store');
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/materials/{material}/edit', [UnitEventMaterialController::class, 'edit'])->name('unit-events.materials.edit');
        Route::put('halaman-unit/{unitPage}/events/{unitEvent}/materials/{material}', [UnitEventMaterialController::class, 'update'])->name('unit-events.materials.update');
        Route::delete('halaman-unit/{unitPage}/events/{unitEvent}/materials/{material}', [UnitEventMaterialController::class, 'destroy'])->name('unit-events.materials.destroy');

        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/questions', [UnitEventQuestionController::class, 'index'])->name('unit-events.questions.index');
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/questions/create', [UnitEventQuestionController::class, 'create'])->name('unit-events.questions.create');
        Route::post('halaman-unit/{unitPage}/events/{unitEvent}/questions', [UnitEventQuestionController::class, 'store'])->name('unit-events.questions.store');
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/questions/{question}/edit', [UnitEventQuestionController::class, 'edit'])->name('unit-events.questions.edit');
        Route::put('halaman-unit/{unitPage}/events/{unitEvent}/questions/{question}', [UnitEventQuestionController::class, 'update'])->name('unit-events.questions.update');
        Route::delete('halaman-unit/{unitPage}/events/{unitEvent}/questions/{question}', [UnitEventQuestionController::class, 'destroy'])->name('unit-events.questions.destroy');

        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/documentation', [UnitEventDocumentationController::class, 'index'])->name('unit-events.documentation.index');
        Route::put('halaman-unit/{unitPage}/events/{unitEvent}/documentation', [UnitEventDocumentationController::class, 'update'])->name('unit-events.documentation.update');
        Route::post('halaman-unit/{unitPage}/events/{unitEvent}/documentation/autosave', [UnitEventDocumentationController::class, 'autosave'])->name('unit-events.documentation.autosave');
        Route::put('halaman-unit/{unitPage}/events/{unitEvent}/documentation/transcribe-audio', [UnitEventDocumentationController::class, 'transcribeAudio'])->name('unit-events.documentation.transcribe-audio');
        Route::put('halaman-unit/{unitPage}/events/{unitEvent}/documentation/generate-summary', [UnitEventDocumentationController::class, 'generateSummary'])->name('unit-events.documentation.generate-summary');
        Route::put('halaman-unit/{unitPage}/events/{unitEvent}/documentation/generate-report', [UnitEventDocumentationController::class, 'generateReport'])->name('unit-events.documentation.generate-report');
        Route::post('halaman-unit/{unitPage}/events/{unitEvent}/documentation/photos', [UnitEventDocumentationController::class, 'storePhotos'])->name('unit-events.documentation.photos.store');
        Route::put('halaman-unit/{unitPage}/events/{unitEvent}/documentation/photos/{photo}', [UnitEventDocumentationController::class, 'updatePhoto'])->name('unit-events.documentation.photos.update');
        Route::delete('halaman-unit/{unitPage}/events/{unitEvent}/documentation/photos/{photo}', [UnitEventDocumentationController::class, 'destroyPhoto'])->name('unit-events.documentation.photos.destroy');
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/documentation/report-print', [UnitEventDocumentationController::class, 'reportPrint'])->name('unit-events.documentation.report-print');

        // AI Documents
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/ai-documents', [UnitEventAiDocumentController::class, 'index'])->name('unit-events.ai-documents.index');
        Route::post('halaman-unit/{unitPage}/events/{unitEvent}/ai-documents/generate', [UnitEventAiDocumentController::class, 'generate'])->name('unit-events.ai-documents.generate');
        Route::post('halaman-unit/{unitPage}/events/{unitEvent}/ai-documents/save', [UnitEventAiDocumentController::class, 'save'])->name('unit-events.ai-documents.save');
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/ai-documents/{aiDocument}/export', [UnitEventAiDocumentController::class, 'export'])->name('unit-events.ai-documents.export');

        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/participants', [UnitEventParticipantController::class, 'index'])->name('unit-events.participants.index');
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/participants/attendance-print', [UnitEventParticipantController::class, 'attendancePrint'])->name('unit-events.participants.attendance-print');
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/participants/credentials-print', [UnitEventParticipantController::class, 'credentialsPrint'])->name('unit-events.participants.credentials-print');
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/participants/credentials-cards-print', [UnitEventParticipantController::class, 'credentialsCardsPrint'])->name('unit-events.participants.credentials-cards-print');
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/participants/invitation-print', [UnitEventParticipantController::class, 'invitationPrint'])->name('unit-events.participants.invitation-print');
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/participants/scanner', [UnitEventParticipantController::class, 'scanner'])->name('unit-events.participants.scanner');
        Route::post('halaman-unit/{unitPage}/events/{unitEvent}/participants/scanner/scan', [UnitEventParticipantController::class, 'processScan'])->name('unit-events.participants.scanner.scan');
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/participants/scan-direct/{token}', [UnitEventParticipantController::class, 'scanDirect'])->name('unit-events.participants.scan-direct');
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/participants/export-excel', [UnitEventParticipantController::class, 'exportExcel'])->name('unit-events.participants.export-excel');
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/participants/import-template', [UnitEventParticipantController::class, 'downloadImportTemplate'])->name('unit-events.participants.import-template');
        Route::post('halaman-unit/{unitPage}/events/{unitEvent}/participants/import', [UnitEventParticipantController::class, 'import'])->name('unit-events.participants.import');
        Route::post('halaman-unit/{unitPage}/events/{unitEvent}/participants/import-pdf-page', [UnitEventParticipantController::class, 'importPdfPage'])->name('unit-events.participants.import-pdf-page');
        Route::post('halaman-unit/{unitPage}/events/{unitEvent}/participants/attach-employees', [UnitEventParticipantController::class, 'attachEmployees'])->name('unit-events.participants.attach-employees');
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/participants/create', [UnitEventParticipantController::class, 'create'])->name('unit-events.participants.create');
        Route::post('halaman-unit/{unitPage}/events/{unitEvent}/participants', [UnitEventParticipantController::class, 'store'])->name('unit-events.participants.store');
        Route::get('halaman-unit/{unitPage}/events/{unitEvent}/participants/{participant}/edit', [UnitEventParticipantController::class, 'edit'])->name('unit-events.participants.edit');
        Route::put('halaman-unit/{unitPage}/events/{unitEvent}/participants/{participant}', [UnitEventParticipantController::class, 'update'])->name('unit-events.participants.update');
        Route::delete('halaman-unit/{unitPage}/events/{unitEvent}/participants/{participant}', [UnitEventParticipantController::class, 'destroy'])->name('unit-events.participants.destroy');
        Route::post('halaman-unit/{unitPage}/events/{unitEvent}/participants/destroy-bulk', [UnitEventParticipantController::class, 'destroyBulk'])->name('unit-events.participants.destroy-bulk');

        // Rubrik Penilaian
        Route::get('rubrik/preset/{key}', [EvaluationRubricController::class, 'getPreset'])->name('rubrik.preset');
        Route::get('rubrik/{rubrik}/print', [EvaluationRubricController::class, 'print'])->name('rubrik.print');
        Route::resource('rubrik', EvaluationRubricController::class)->names('rubrik');

        // Users
        Route::resource('roles', RoleController::class)
            ->except('show')
            ->names('roles');
        Route::get('users/credentials',       [UserController::class, 'credentials'])->name('users.credentials');
        Route::get('users/credentials/print', [UserController::class, 'credentialsPrint'])->name('users.credentials.print');
        Route::get('users/employee-credentials', [UserController::class, 'employeeCredentials'])->name('users.employee-credentials');
        Route::get('users/employee-credentials/print', [UserController::class, 'employeeCredentialsPrint'])->name('users.employee-credentials.print');
        Route::get('users/employee-credentials/download', [UserController::class, 'downloadEmployeeCredentials'])->name('users.employee-credentials.download');
        Route::post('users/{user}/login-as',  [UserController::class, 'loginAs'])->name('users.login-as');
        Route::post('return-to-admin',        [UserController::class, 'returnToAdmin'])->name('users.return-to-admin');
        Route::get('ubah-password',           [UserController::class, 'showChangePasswordForm'])->name('ubah-password');
        Route::post('ubah-password',          [UserController::class, 'changePassword'])->name('ubah-password.update');
        Route::resource('users', UserController::class)->names('users');

        // ─── Pengaduan / Kritik / Saran (Admin Global — super admin) ─────────
        Route::prefix('pengaduan')->name('pengaduan.')->group(function () {
            Route::get('/',                              [PengaduanUnitController::class, 'index'])->name('index');
            Route::get('/{pengaduan}',                  [PengaduanUnitController::class, 'show'])->name('show');
            Route::put('/{pengaduan}',                  [PengaduanUnitController::class, 'update'])->name('update');
            Route::delete('/{pengaduan}',               [PengaduanUnitController::class, 'destroy'])->name('destroy');
        });

        // ─── Pengaduan per Unit ───────────────────────────────────────────────
        Route::prefix('halaman-unit/{unitPage}/pengaduan')->name('pengaduan.unit.')->group(function () {
            Route::get('/',            [PengaduanUnitController::class, 'index'])->name('index');
            Route::get('/{pengaduan}', [PengaduanUnitController::class, 'show'])->name('show');
            Route::put('/{pengaduan}', [PengaduanUnitController::class, 'update'])->name('update');
            Route::delete('/{pengaduan}', [PengaduanUnitController::class, 'destroy'])->name('destroy');
        });

        // Mahasiswa Berprestasi
        Route::prefix('mahasiswa-berprestasi')->name('berprestasi.')->group(function () {
            Route::get('/',          [MahasiswaBerprestasiController::class, 'index'])->name('index');
            Route::get('/create',    [MahasiswaBerprestasiController::class, 'create'])->name('create');
            Route::post('/',         [MahasiswaBerprestasiController::class, 'store'])->name('store');
            Route::get('/{berprestasi}/edit', [MahasiswaBerprestasiController::class, 'edit'])->name('edit');
            Route::put('/{berprestasi}',      [MahasiswaBerprestasiController::class, 'update'])->name('update');
            Route::patch('/{berprestasi}/toggle', [MahasiswaBerprestasiController::class, 'toggle'])->name('toggle');
            Route::delete('/{berprestasi}',   [MahasiswaBerprestasiController::class, 'destroy'])->name('destroy');
        });
    });
});

// ─── Halaman Unit (Public) ────────────────────────────────────────────────────
Route::get('/unit/bidang-kerjasama', fn () => redirect('/unit/unit-humas-kerjasama', 301));
Route::get('/unit/asrama', fn () => redirect('/fasilitas-asrama', 301));
Route::get('/unit/bidang-administrasi-umum/lakip', [UnitPageController::class, 'showLakipAdministrasiUmum'])->name('unit.administrasi-umum.lakip');
Route::get('/unit/bidang-administrasi-umum/lakip-2024', [UnitPageController::class, 'showLakipAdministrasiUmum2024'])->name('unit.administrasi-umum.lakip.2024');
Route::get('/unit/bidang-administrasi-umum/lakip-2023', [UnitPageController::class, 'showLakipAdministrasiUmum2023'])->name('unit.administrasi-umum.lakip.2023');
// Legacy deep links from the previous 2025 facsimile flow now land on the matching HTML sections.
Route::get('/unit/bidang-administrasi-umum/lakip/dokumen/{section}', [UnitPageController::class, 'redirectLakipAdministrasiUmum2025LegacySection'])->name('unit.administrasi-umum.lakip.section');
// Legacy deep links from the previous 2024 facsimile flow now land on the matching HTML sections.
Route::get('/unit/bidang-administrasi-umum/lakip-2024/dokumen/{section}', [UnitPageController::class, 'redirectLakipAdministrasiUmum2024LegacySection'])->name('unit.administrasi-umum.lakip.2024.section');
Route::get('/unit/bidang-administrasi-umum/lakip-2023/dokumen/{section}', [UnitPageController::class, 'redirectLakipAdministrasiUmum2023LegacySection'])->name('unit.administrasi-umum.lakip.2023.section');
Route::get('/unit/{unitSlug}/event/{eventSlug}', [PublicUnitEventController::class, 'show'])->name('unit.events.show');
Route::get('/unit/{unitSlug}/event/{eventSlug}/slides', [PublicUnitEventController::class, 'slides'])->name('unit.events.slides');
Route::get('/unit/{unitSlug}/event/{eventSlug}/flyer', [PublicUnitEventController::class, 'flyer'])->name('unit.events.flyer');
Route::get('/unit/{unitSlug}/event/{eventSlug}/flyer-og-image.png', [PublicUnitEventController::class, 'flyerOgImage'])->name('unit.events.flyer.og-image');
Route::get('/unit/{unitSlug}/event/{eventSlug}/surat-tugas-kegiatan', [PublicUnitEventController::class, 'showEventAssignmentLetter'])->name('unit.events.event-assignment-letter.show');
Route::post('/unit/{unitSlug}/event/{eventSlug}/register', [PublicUnitEventController::class, 'register'])->name('unit.events.register');
Route::get('/unit/{unitSlug}/event/{eventSlug}/masuk', [PublicUnitEventController::class, 'showParticipantLogin'])->name('unit.events.login.show');
Route::post('/unit/{unitSlug}/event/{eventSlug}/masuk', [PublicUnitEventController::class, 'loginParticipant'])->middleware('throttle:public-auth')->name('unit.events.login.submit');
Route::get('/unit/{unitSlug}/event/{eventSlug}/hadir-langsung', [PublicUnitEventController::class, 'showDirectAttendance'])->name('unit.events.attendance.direct.show');
Route::post('/unit/{unitSlug}/event/{eventSlug}/hadir-langsung', [PublicUnitEventController::class, 'submitDirectAttendance'])->name('unit.events.attendance.direct.submit');
Route::get('/unit/{unitSlug}/event/{eventSlug}/nip-lookup', [PublicUnitEventController::class, 'lookupNip'])->name('unit.events.attendance.nip-lookup');
Route::get('/unit/{unitSlug}/event/{eventSlug}/berita-acara', [PublicUnitEventController::class, 'generateBeritaAcara'])->name('unit.events.berita-acara');
Route::get('/unit/{unitSlug}/event/{eventSlug}/hadir', [PublicUnitEventController::class, 'directAttendance'])->name('unit.events.attendance.direct');
Route::get('/unit/{unitSlug}/event/{eventSlug}/akses/{token}', [PublicUnitEventController::class, 'access'])->name('unit.events.access');
Route::get('/unit/{unitSlug}/event/{eventSlug}/akses/{token}/hadir', [PublicUnitEventController::class, 'showAttendance'])->name('unit.events.attendance.show');
Route::post('/unit/{unitSlug}/event/{eventSlug}/akses/{token}/hadir', [PublicUnitEventController::class, 'submitAttendance'])->name('unit.events.attendance.submit');
Route::get('/unit/{unitSlug}/event/{eventSlug}/akses/{token}/pre-test', [PublicUnitEventController::class, 'showPreTest'])->name('unit.events.pre-test.show');
Route::post('/unit/{unitSlug}/event/{eventSlug}/akses/{token}/pre-test', [PublicUnitEventController::class, 'submitPreTest'])->name('unit.events.pre-test.submit');
Route::get('/unit/{unitSlug}/event/{eventSlug}/akses/{token}/post-test', [PublicUnitEventController::class, 'showPostTest'])->name('unit.events.post-test.show');
Route::post('/unit/{unitSlug}/event/{eventSlug}/akses/{token}/post-test', [PublicUnitEventController::class, 'submitPostTest'])->name('unit.events.post-test.submit');
Route::get('/unit/{unitSlug}/event/{eventSlug}/akses/{token}/evaluasi', [PublicUnitEventController::class, 'showEvaluation'])->name('unit.events.evaluation.show');
Route::post('/unit/{unitSlug}/event/{eventSlug}/akses/{token}/evaluasi', [PublicUnitEventController::class, 'submitEvaluation'])->name('unit.events.evaluation.submit');
Route::get('/unit/{unitSlug}/event/{eventSlug}/akses/{token}/rubrik', [PublicUnitEventController::class, 'showRubrik'])->name('unit.events.rubrik.show');
Route::get('/unit/{unitSlug}/event/{eventSlug}/akses/{token}/surat-tugas', [PublicUnitEventController::class, 'showAssignmentLetter'])->name('unit.events.assignment-letter.show');
Route::get('/unit/{unitSlug}/event/{eventSlug}/akses/{token}/sertifikat', [PublicUnitEventController::class, 'downloadCertificate'])->name('unit.events.certificate.download');
Route::get('/unit/{unitSlug}/event/{eventSlug}/akses/{token}/print-undangan', [PublicUnitEventController::class, 'printInvitation'])->name('unit.events.invitation.print-single');
Route::get('/login-virtual-office', [PublicSectionController::class, 'showVirtualOfficeLogin'])->name('login.virtual-office');
Route::post('/logout-virtual-office', [PublicSectionController::class, 'logoutVirtualOffice'])->name('logout.virtual-office');
Route::get('/auth/google', [PublicSectionController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [PublicSectionController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::post('/auth/google/link', [PublicSectionController::class, 'linkGoogleAccount'])->name('auth.google.link');
Route::post('/auth/google/dismiss', [PublicSectionController::class, 'dismissLinkGoogleAccount'])->name('auth.google.dismiss');
Route::get('/unit/{slug}', [UnitPageController::class, 'show'])->name('unit.show');

// ─── Interactive Block Endpoints (Public) ────────────────────────────────────
    Route::match(['GET','POST'], '/blok/{section}/vote',   [\App\Http\Controllers\PageBlockController::class, 'vote'])->name('block.vote');
Route::post('/blok/{section}/authorize', [\App\Http\Controllers\PageBlockController::class, 'authorizeFormAccess'])->name('block.authorize');
Route::post('/blok/{section}/submit', [\App\Http\Controllers\PageBlockController::class, 'submit'])->name('block.submit');
// ─── PBM (Edlink Style) ───────────────────────────────────────────────────────
Route::prefix('pbm')->name('pbm.')->middleware('auth')->group(function () {
    Route::get('ci-dashboard', [\App\Http\Controllers\Pbm\AttendanceController::class, 'index'])->name('ci.dashboard');
    Route::get('schedule', [\App\Http\Controllers\Pbm\SessionController::class, 'schedule'])->name('schedule');
    Route::resource('classes', \App\Http\Controllers\Pbm\ClassController::class);
    Route::post('classes/{class}/generate-cover-ai', [\App\Http\Controllers\Pbm\ClassController::class, 'generateCoverAi'])->name('classes.generate-cover-ai');
    Route::post('classes/{class}/apply-preset-cover', [\App\Http\Controllers\Pbm\ClassController::class, 'applyPresetCover'])->name('classes.apply-preset-cover');
    Route::post('classes/{class}/members', [\App\Http\Controllers\Pbm\ClassController::class, 'addMember'])->name('classes.members.store');
    Route::post('classes/{class}/members/batch', [\App\Http\Controllers\Pbm\ClassController::class, 'addBatchMembers'])->name('classes.members.batch');
    Route::post('classes/{class}/members/bulk-destroy', [\App\Http\Controllers\Pbm\ClassController::class, 'bulkDestroy'])->name('classes.members.bulk-destroy');
    Route::post('classes/{class}/members/bulk-assign-group', [\App\Http\Controllers\Pbm\ClassController::class, 'bulkAssignGroup'])->name('classes.members.bulk-assign-group');
    Route::delete('classes/{class}/members/{member}', [\App\Http\Controllers\Pbm\ClassController::class, 'removeMember'])->name('classes.members.destroy');
    Route::put('classes/{class}/members/{member}/group', [\App\Http\Controllers\Pbm\ClassController::class, 'updateMemberGroup'])->name('classes.members.group.update');
    Route::post('classes/{class}/auto-assign-groups', [\App\Http\Controllers\Pbm\ClassController::class, 'autoAssignGroups'])->name('classes.auto-assign-groups');
    Route::resource('classes.sessions', \App\Http\Controllers\Pbm\SessionController::class);
    Route::post('classes/{class}/sessions/{session}/attach-rubric', [\App\Http\Controllers\Pbm\SessionController::class, 'attachRubric'])->name('classes.sessions.attach-rubric');
    Route::delete('classes/{class}/sessions/{session}/detach-rubric', [\App\Http\Controllers\Pbm\SessionController::class, 'detachRubric'])->name('classes.sessions.detach-rubric');
    Route::get('classes/{class}/sessions/{session}/export', [\App\Http\Controllers\Pbm\AttendanceController::class, 'export'])->name('classes.sessions.export');
    Route::resource('sessions.attendances', \App\Http\Controllers\Pbm\AttendanceController::class)->only(['store', 'update']);
    Route::get('sessions/{session}/scan', [\App\Http\Controllers\Pbm\AttendanceController::class, 'scanQr'])->name('sessions.scan');
    Route::post('sessions/{session}/scan', [\App\Http\Controllers\Pbm\AttendanceController::class, 'processScan'])->name('sessions.process-scan');
    Route::get('materials/{material}/stream', [\App\Http\Controllers\Pbm\PbmMaterialController::class, 'stream'])->name('materials.stream');
    Route::get('materials/{material}/viewer', [\App\Http\Controllers\Pbm\PbmMaterialController::class, 'viewer'])->name('materials.viewer');
    Route::resource('classes.sessions.materials', \App\Http\Controllers\Pbm\PbmMaterialController::class)->shallow();
    Route::resource('classes.sessions.assignments', \App\Http\Controllers\Pbm\PbmAssignmentController::class)->shallow();
    Route::resource('classes.sessions.quizzes', \App\Http\Controllers\Pbm\PbmQuizController::class)->shallow();
    
    // Submissions are scoped to assignments
    Route::post('assignments/{assignment}/submissions', [\App\Http\Controllers\Pbm\PbmSubmissionController::class, 'store'])->name('assignments.submissions.store');
    Route::put('submissions/{submission}/grade', [\App\Http\Controllers\Pbm\PbmSubmissionController::class, 'grade'])->name('submissions.grade');
    
    // Quiz Questions and Item Analysis
    Route::resource('quizzes.questions', \App\Http\Controllers\Pbm\PbmQuestionController::class)->shallow();
    Route::get('quizzes/{quiz}/analysis', [\App\Http\Controllers\Pbm\PbmQuizController::class, 'analysis'])->name('quizzes.analysis');
    Route::get('quizzes/{quiz}/analysis/export', [\App\Http\Controllers\Pbm\PbmQuizController::class, 'exportAnalysisCsv'])->name('quizzes.analysis.export');
    Route::post('quizzes/{quiz}/questions/import', [\App\Http\Controllers\Pbm\PbmQuestionController::class, 'import'])->name('quizzes.questions.import');
    Route::get('quizzes/{quiz}/questions/ai-generate', [\App\Http\Controllers\Pbm\PbmQuestionAiController::class, 'create'])->name('quizzes.questions.ai.create');
    Route::post('quizzes/{quiz}/questions/ai-generate', [\App\Http\Controllers\Pbm\PbmQuestionAiController::class, 'generate'])->name('quizzes.questions.ai.generate');
    Route::post('quizzes/{quiz}/questions/ai-store', [\App\Http\Controllers\Pbm\PbmQuestionAiController::class, 'store'])->name('quizzes.questions.ai.store');
    Route::post('quizzes/{quiz}/attempt', [\App\Http\Controllers\Pbm\PbmQuizController::class, 'startAttempt'])->name('quizzes.attempt.start');
    Route::get('quizzes/{quiz}/attempts/{attempt}', [\App\Http\Controllers\Pbm\PbmQuizController::class, 'takeAttempt'])->name('quizzes.attempt.take');
    Route::post('quizzes/attempts/{attempt}/save-answer', [\App\Http\Controllers\Pbm\PbmQuizController::class, 'saveAnswer'])->name('quizzes.attempt.save-answer');
    Route::post('quizzes/attempts/{attempt}/submit', [\App\Http\Controllers\Pbm\PbmQuizController::class, 'submitAttempt'])->name('quizzes.attempt.submit');
    Route::get('quizzes/{quiz}/attempts/{attempt}/result', [\App\Http\Controllers\Pbm\PbmQuizController::class, 'showResult'])->name('quizzes.attempt.result');
    
    // CBT Proctoring & Safe Exam Browser (SEB)
    Route::get('quizzes/{quiz}/seb-config', [\App\Http\Controllers\Pbm\PbmQuizController::class, 'downloadSebConfig'])->name('quizzes.seb-config');
    Route::get('quizzes/{quiz}/proctoring', [\App\Http\Controllers\Pbm\PbmQuizController::class, 'proctoringDashboard'])->name('quizzes.proctoring');
    Route::post('quizzes/attempts/{attempt}/proctoring-event', [\App\Http\Controllers\Pbm\PbmQuizController::class, 'logProctoringEvent'])->name('quizzes.attempt.proctoring-event');
    Route::post('quizzes/attempts/{attempt}/proctoring-snapshot', [\App\Http\Controllers\Pbm\PbmQuizController::class, 'saveProctoringSnapshot'])->name('quizzes.attempt.proctoring-snapshot');
    Route::post('quizzes/{quiz}/attempts/{attempt}/unlock', [\App\Http\Controllers\Pbm\PbmQuizController::class, 'unlockAttempt'])->name('quizzes.attempts.unlock');
    Route::post('quizzes/{quiz}/attempts/{attempt}/disqualify', [\App\Http\Controllers\Pbm\PbmQuizController::class, 'disqualifyAttempt'])->name('quizzes.attempts.disqualify');
    
    // Logbooks
    Route::resource('classes.logbooks', \App\Http\Controllers\Pbm\PbmLogbookController::class)->shallow();
    Route::put('logbooks/{logbook}/verify', [\App\Http\Controllers\Pbm\PbmLogbookController::class, 'verify'])->name('logbooks.verify');
});

// ─── Portal Mahasiswa ─────────────────────────────────────────────────────────
Route::prefix('mahasiswa')->name('mahasiswa.')->group(function () {

    // Auth (publik)
    Route::get('/login',    [MahasiswaAuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [MahasiswaAuthController::class, 'login'])->middleware('throttle:public-auth')->name('login.post');
    Route::get('/lupa-password', [MahasiswaPasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/lupa-password', [MahasiswaPasswordResetController::class, 'sendResetLinkEmail'])->middleware('throttle:public-auth')->name('password.email');
    Route::get('/reset-password/{token}', [MahasiswaPasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [MahasiswaPasswordResetController::class, 'reset'])->middleware('throttle:public-auth')->name('password.update');
    Route::get('/register', [MahasiswaAuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[MahasiswaAuthController::class, 'register'])->middleware('throttle:public-auth')->name('register.post');
    Route::post('/logout',  [MahasiswaAuthController::class, 'logout'])->middleware(EnsureMahasiswa::class)->name('logout');

    // Halaman terproteksi
    Route::middleware([EnsureMahasiswa::class])->group(function () {
        // Portofolio
        Route::get('/portofolio',               [PortofolioController::class, 'index'])->name('portofolio.index');
        Route::get('/portofolio/create',        [PortofolioController::class, 'create'])->name('portofolio.create');
        Route::post('/portofolio',              [PortofolioController::class, 'store'])->name('portofolio.store');
        Route::get('/portofolio/{portofolio}/edit',    [PortofolioController::class, 'edit'])->name('portofolio.edit');
        Route::put('/portofolio/{portofolio}',         [PortofolioController::class, 'update'])->name('portofolio.update');
        Route::delete('/portofolio/{portofolio}',      [PortofolioController::class, 'destroy'])->name('portofolio.destroy');

        // Portofolio OBE (Buku Portofolio Pembelajaran Mahasiswa)
        Route::prefix('obe')->name('obe.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Mahasiswa\MahasiswaObePortofolioController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Mahasiswa\MahasiswaObePortofolioController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Mahasiswa\MahasiswaObePortofolioController::class, 'store'])->name('store');
            Route::get('/{obe}/edit', [\App\Http\Controllers\Mahasiswa\MahasiswaObePortofolioController::class, 'edit'])->name('edit');
            Route::put('/{obe}', [\App\Http\Controllers\Mahasiswa\MahasiswaObePortofolioController::class, 'update'])->name('update');
            Route::delete('/{obe}', [\App\Http\Controllers\Mahasiswa\MahasiswaObePortofolioController::class, 'destroy'])->name('destroy');
            Route::get('/{obe}/print', [\App\Http\Controllers\Mahasiswa\MahasiswaObePortofolioController::class, 'print'])->name('print');
            Route::post('/{obe}/evidence', [\App\Http\Controllers\Mahasiswa\MahasiswaObePortofolioController::class, 'uploadEvidence'])->name('evidence.upload');
            Route::delete('/{obe}/evidence/{evidence}', [\App\Http\Controllers\Mahasiswa\MahasiswaObePortofolioController::class, 'deleteEvidence'])->name('evidence.destroy');
        });
        // Kegiatan / PBM
        Route::get('/kegiatan',                 [PortofolioController::class, 'kegiatan'])->name('kegiatan.index');
        // Profil
        Route::get('/profil',  [PortofolioController::class, 'editProfile'])->name('profil.edit');
        Route::put('/profil',  [PortofolioController::class, 'updateProfile'])->name('profil.update');
    });
});
