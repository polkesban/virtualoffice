<?php

namespace App\Console\Commands;

use App\Models\Berita;
use App\Models\UnitPage;
use App\Services\Localization\PublicWebsiteTranslator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Crawl all public pages and store every text-node translation in DB.
 *
 *   php artisan translations:crawl
 *   php artisan translations:crawl --locale=en
 *   php artisan translations:crawl --only=unit,berita   (comma-separated groups)
 */
class CrawlTranslations extends Command
{
    protected $signature = 'translations:crawl
                            {--locale=en  : Target locale}
                            {--only=      : Comma-separated groups: static,unit,berita,profil}
                            {--delay=200  : Milliseconds between requests}';

    protected $description = 'Crawl all public pages and store translations permanently in DB';

    public function __construct(
        private readonly PublicWebsiteTranslator $translator,
        private readonly Kernel $kernel
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $locale = (string) $this->option('locale');
        $only   = $this->option('only') ? explode(',', (string) $this->option('only')) : null;
        $delay  = max(0, (int) $this->option('delay'));

        $this->info("🕷  Translation Crawler — locale: <comment>{$locale}</comment>");
        $this->newLine();

        $urls = $this->collectUrls($only);
        $total = count($urls);
        $this->info("  Found <comment>{$total}</comment> pages to crawl.");

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('starting…');
        $bar->start();

        $done = $errors = 0;

        foreach ($urls as [$path, $label]) {
            $bar->setMessage(mb_strimwidth($label, 0, 50, '…'));

            try {
                $this->crawlPage($path, $locale);
                $done++;
            } catch (Throwable $e) {
                $errors++;
                $this->newLine();
                $this->warn("  ⚠ [{$label}]: " . $e->getMessage());
            }

            $bar->advance();

            if ($delay > 0) {
                usleep($delay * 1000);
            }
        }

        $bar->setMessage('done!');
        $bar->finish();
        $this->newLine(2);

        $this->info('Clearing Laravel cache…');
        Cache::flush();

        $dbCount = DB::table('translation_cache')->where('locale', $locale)->count();
        $this->info("✅ Crawled <comment>{$done}</comment> pages. Errors: <comment>{$errors}</comment>.");
        $this->info("📊 Total translations in DB [{$locale}]: <comment>{$dbCount}</comment>");

        return self::SUCCESS;
    }

    /** Dispatch a request through the Laravel HTTP kernel (triggers all middleware). */
    private function crawlPage(string $path, string $locale): void
    {
        $url  = rtrim(config('app.url'), '/') . $path;
        $sep  = str_contains($path, '?') ? '&' : '?';
        $full = $url . $sep . 'lang=' . $locale;

        $request = Request::create($full, 'GET', [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => $locale,
            'HTTP_X_REQUESTED_WITH' => '',
        ]);

        $response = $this->kernel->handle($request);
        $this->kernel->terminate($request, $response);
    }

    /** Build the full list of URLs to crawl. */
    private function collectUrls(?array $only): array
    {
        $urls = [];
        $all  = $only === null;

        // ── Static / core pages ───────────────────────────────────────────
        if ($all || in_array('static', $only)) {
            foreach ([
                ['/',                    'Homepage'],
                ['/berita',              'News list'],
                ['/pengumuman',          'Announcements'],
                ['/galeri',              'Gallery'],
                ['/agenda',              'Agenda'],
                ['/kontak',              'Contact'],
                ['/program-studi',       'Study Programs'],
                ['/pojok-alumni',        'Alumni Corner'],
                ['/portal-mahasiswa',    'Student Portal'],
                ['/pengaduan',           'Feedback Form'],
                ['/penerimaan',          'Admissions'],
                ['/mahasiswa-berprestasi', 'Outstanding Students'],
                ['/layanan-bisnis',      'Layanan Bisnis'],
                ['/layanan-lab',         'Layanan Lab'],
                ['/fasilitas-asrama',    'Fasilitas Asrama'],
                ['/koleksi-perpustakaan', 'Koleksi Perpustakaan'],
                ['/publikasi',           'Publikasi'],
                ['/dokumen-mutu',        'Dokumen Mutu'],
                ['/layanan-ti',          'Layanan TI'],
                ['/pelatihan',           'Pelatihan'],
            ] as $entry) {
                $urls[] = $entry;
            }
        }

        // ── Profile pages ─────────────────────────────────────────────────
        if ($all || in_array('profil', $only)) {
            foreach ([
                ['/profil/sejarah',   'History'],
                ['/profil/visi-misi', 'Vision & Mission'],
                ['/profil/struktur',  'Organizational Structure'],
                ['/profil/akreditasi','Accreditation'],
            ] as $entry) {
                $urls[] = $entry;
            }
        }

        // ── Unit pages ────────────────────────────────────────────────────
        if ($all || in_array('unit', $only)) {
            try {
                $units = UnitPage::all();
                foreach ($units as $unit) {
                    $urls[] = [$unit->publicPath(), "Unit: {$unit->judul}"];
                }
            } catch (Throwable $e) {}
        }

        // ── Program Studi pages ───────────────────────────────────────────
        if ($all || in_array('prodi', $only)) {
            try {
                $prodis = \App\Models\ProgramStudi::where('aktif', true)->get();
                foreach ($prodis as $prodi) {
                    $urls[] = ['/program-studi/' . $prodi->slug, "Prodi: {$prodi->nama}"];
                }
            } catch (Throwable $e) {}
        }

        // ── Berita articles ───────────────────────────────────────────────
        if ($all || in_array('berita', $only)) {
            try {
                $articles = Berita::where('status', 'published')
                    ->orderByDesc('published_at')
                    ->pluck('judul', 'slug');

                foreach ($articles as $slug => $judul) {
                    $urls[] = ['/berita/' . $slug, "Berita: " . mb_strimwidth($judul, 0, 40, '…')];
                }
            } catch (Throwable $e) {}
        }

        return $urls;
    }
}
