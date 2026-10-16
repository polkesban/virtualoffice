<?php

namespace App\Console\Commands;

use App\Models\Berita;
use App\Models\HalamanStatis;
use App\Models\PageSection;
use App\Models\TranslationCache;
use App\Models\UnitPage;
use App\Services\Localization\PublicWebsiteTranslator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Pre-translate all website content into the permanent translation_cache table.
 *
 * Usage:
 *   php artisan translations:sync              → Phase 1 (strings) + Phase 2 (HTML content)
 *   php artisan translations:sync --strings    → Phase 1 only (short labels/strings)
 *   php artisan translations:sync --content    → Phase 2 only (full HTML article bodies)
 *   php artisan translations:sync --force      → overwrite existing DB entries
 *   php artisan translations:sync --dry-run    → preview counts without saving
 *   php artisan translations:sync --locale=en  → target locale (default: en)
 */
class SyncTranslations extends Command
{
    protected $signature = 'translations:sync
                            {--locale=en   : Target locale (default: en)}
                            {--force       : Re-translate all, even if already in DB}
                            {--dry-run     : Show counts without translating}
                            {--strings     : Run Phase 1 only (short strings)}
                            {--content     : Run Phase 2 only (full HTML content)}';

    protected $description = 'Pre-translate all site content (strings + HTML) and store permanently in DB';

    public function __construct(private readonly PublicWebsiteTranslator $translator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $locale  = (string) $this->option('locale');
        $force   = (bool)   $this->option('force');
        $dryRun  = (bool)   $this->option('dry-run');
        $onlyStr = (bool)   $this->option('strings');
        $onlyCon = (bool)   $this->option('content');

        // Default: run both phases
        $runStrings = ! $onlyCon || $onlyStr;
        $runContent = ! $onlyStr || $onlyCon;

        $this->info("🌐  Translations Sync — locale: <comment>{$locale}</comment>");
        $this->info($force  ? '    Mode: <comment>FORCE</comment> (overwrite existing)'
                            : '    Mode: incremental (skip already-translated)');
        $this->info($dryRun ? '    ⚠  DRY RUN — nothing will be saved.'
                            : '    Storage: permanent DB (translation_cache)');
        $this->newLine();

        $exitCode = self::SUCCESS;

        if ($runStrings) {
            $exitCode = max($exitCode, $this->runPhase1Strings($locale, $force, $dryRun));
        }

        if ($runContent) {
            $exitCode = max($exitCode, $this->runPhase2Content($locale, $force, $dryRun));
        }

        // Clear L1 cache so fresh DB data is loaded on next request
        if (! $dryRun) {
            $this->info('Clearing Laravel L1 cache…');
            Cache::flush();
        }

        $dbCount = TranslationCache::countForLocale($locale);
        $this->newLine();
        $this->info("📊 Total permanent translations in DB for [{$locale}]: <comment>{$dbCount}</comment>");

        return $exitCode;
    }

    // =========================================================================
    // PHASE 1 — Short strings (judul, label, UI text, meta)
    // =========================================================================

    private function runPhase1Strings(string $locale, bool $force, bool $dryRun): int
    {
        $this->info('━━━ Phase 1: Short strings (labels, titles, meta) ━━━');

        $strings = $this->collectStrings();
        $total   = count($strings);
        $this->info("  Found <comment>{$total}</comment> unique strings.");

        if (! $force) {
            $hashes   = array_map(fn($s) => TranslationCache::hashFor($s), $strings);
            $existing = TranslationCache::where('locale', $locale)
                ->whereIn('source_hash', $hashes)
                ->pluck('source_hash')->flip()->toArray();

            $strings = array_values(array_filter(
                $strings,
                fn($s) => ! isset($existing[TranslationCache::hashFor($s)])
            ));
            $this->info("  Skipping <comment>" . ($total - count($strings)) . "</comment> already translated.");
        }

        if ($dryRun) {
            $this->warn("  Would translate " . count($strings) . " strings.");
            return self::SUCCESS;
        }

        if (empty($strings)) {
            $this->info('  ✅ All strings already translated!');
            return self::SUCCESS;
        }

        $batchSize = max(1, (int) config('ai.website_translation.batch_size', 20));
        $chunks    = array_chunk($strings, $batchSize);
        $done = $errors = 0;

        $bar = $this->output->createProgressBar(count($chunks));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('starting…');
        $bar->start();

        foreach ($chunks as $chunk) {
            $bar->setMessage(count($chunk) . ' strings…');
            try {
                $this->translateChunk($chunk, $locale);
                $done += count($chunk);
            } catch (Throwable $e) {
                $errors++;
                $this->newLine();
                $this->warn('  ⚠ ' . $e->getMessage());
            }
            $bar->advance();
            usleep(150_000);
        }

        $bar->setMessage('done!');
        $bar->finish();
        $this->newLine();

        // Warm glossary entries into DB
        $this->warmGlossaryToDb($locale);

        $this->info("  ✅ Phase 1: <comment>{$done}</comment> strings translated. Errors: {$errors}.");
        return self::SUCCESS;
    }

    // =========================================================================
    // PHASE 2 — Full HTML content (berita body, halaman statis, page sections)
    // =========================================================================

    private function runPhase2Content(string $locale, bool $force, bool $dryRun): int
    {
        $this->newLine();
        $this->info('━━━ Phase 2: Full HTML content (articles, pages, sections) ━━━');

        $items = $this->collectHtmlItems();
        $total = count($items);
        $this->info("  Found <comment>{$total}</comment> HTML content items.");

        if ($dryRun) {
            foreach ($items as $item) {
                $preview = mb_strimwidth(strip_tags($item['html']), 0, 70, '…');
                $this->line("  [{$item['type']}] {$item['label']}: {$preview}");
            }
            $this->warn("  Would translate {$total} HTML content items.");
            return self::SUCCESS;
        }

        if (empty($items)) {
            $this->info('  ✅ No HTML content found!');
            return self::SUCCESS;
        }

        $done = $errors = $skipped = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('starting…');
        $bar->start();

        foreach ($items as $item) {
            $html    = $item['html'];
            $label   = $item['label'];
            $hashKey = TranslationCache::hashFor('html:' . $html);

            // Check if this HTML block has already been processed
            if (! $force) {
                $alreadyDone = TranslationCache::where('locale', $locale)
                    ->where('source_hash', $hashKey)
                    ->exists();

                if ($alreadyDone) {
                    $skipped++;
                    $bar->setMessage("skip: {$label}");
                    $bar->advance();
                    continue;
                }
            }

            $bar->setMessage(mb_strimwidth($label, 0, 40, '…'));

            try {
                // Wrap HTML in a minimal document and translate
                // The translator extracts text nodes and stores each to DB
                $wrapped = "<!DOCTYPE html><html lang=\"id\"><head><meta charset=\"utf-8\"><title>sync</title></head><body>{$html}</body></html>";
                $this->translator->translateHtml($wrapped, $locale);

                // Mark this HTML block as "processed" so we can skip it next run
                TranslationCache::storeTranslation(
                    $locale,
                    'html:' . $html,
                    'processed:' . $label,
                    'html-block'
                );

                $done++;
            } catch (Throwable $e) {
                $errors++;
                $this->newLine();
                $this->warn("  ⚠ [{$label}]: " . $e->getMessage());
            }

            $bar->advance();
            usleep(300_000); // 300ms between HTML blocks (they're larger)
        }

        $bar->setMessage('done!');
        $bar->finish();
        $this->newLine();

        $this->info("  ✅ Phase 2: <comment>{$done}</comment> translated, <comment>{$skipped}</comment> skipped, <comment>{$errors}</comment> errors.");
        return self::SUCCESS;
    }

    /**
     * Collect all HTML content items to be translated.
     * Returns array of ['type', 'label', 'html'].
     */
    private function collectHtmlItems(): array
    {
        $items = [];

        // ── Berita articles (78 published) ────────────────────────────────
        try {
            $articles = DB::table('berita')
                ->where('status', 'published')
                ->get(['id', 'judul', 'isi', 'ringkasan']);

            foreach ($articles as $article) {
                // Full article body (HTML)
                if (! empty($article->isi) && mb_strlen(strip_tags($article->isi)) > 20) {
                    $items[] = [
                        'type'  => 'berita',
                        'label' => "Berita #{$article->id}: " . mb_strimwidth($article->judul, 0, 40, '…'),
                        'html'  => $article->isi,
                    ];
                }
                // Ringkasan (summary — usually plain text but may have HTML)
                if (! empty($article->ringkasan) && mb_strlen(strip_tags($article->ringkasan)) > 20) {
                    $items[] = [
                        'type'  => 'berita-ringkasan',
                        'label' => "Ringkasan #{$article->id}",
                        'html'  => "<p>{$article->ringkasan}</p>",
                    ];
                }
            }
        } catch (Throwable $e) {}

        // ── HalamanStatis (3 pages) ───────────────────────────────────────
        try {
            $pages = DB::table('halaman_statis')
                ->get(['id', 'judul', 'konten']);

            foreach ($pages as $page) {
                if (! empty($page->konten) && mb_strlen(strip_tags($page->konten)) > 20) {
                    $items[] = [
                        'type'  => 'halaman-statis',
                        'label' => "Halaman: {$page->judul}",
                        'html'  => $page->konten,
                    ];
                }
            }
        } catch (Throwable $e) {}

        // ── PageSection HTML blocks ───────────────────────────────────────
        try {
            $sections = DB::table('page_sections')
                ->where('aktif', true)
                ->get(['id', 'tipe', 'judul', 'konfigurasi']);

            foreach ($sections as $section) {
                $cfg = is_string($section->konfigurasi)
                    ? (json_decode($section->konfigurasi, true) ?? [])
                    : ($section->konfigurasi ?? []);

                // 'teks' blocks — konten is rich HTML
                if ($section->tipe === 'teks' && ! empty($cfg['konten'])) {
                    $items[] = [
                        'type'  => 'section-teks',
                        'label' => "Section teks #{$section->id}: " . ($section->judul ?: '(no title)'),
                        'html'  => $cfg['konten'],
                    ];
                }

                // 'hero' blocks — heading + sub + konten (plain text, wrap in HTML)
                if ($section->tipe === 'hero') {
                    $heroText = collect([
                        $cfg['heading']     ?? null,
                        $cfg['sub']         ?? null,
                        $cfg['sub_heading'] ?? null,
                        $cfg['konten']      ?? null,
                    ])->filter()->map(fn($t) => "<p>{$t}</p>")->implode("\n");

                    if ($heroText) {
                        $items[] = [
                            'type'  => 'section-hero',
                            'label' => "Hero #{$section->id}: " . ($section->judul ?: ($cfg['heading'] ?? '(no title)')),
                            'html'  => $heroText,
                        ];
                    }
                }

                // 'ai_teks' blocks — may have 'prompt' or 'konten'
                if ($section->tipe === 'ai_teks' && ! empty($cfg['konten'])) {
                    $items[] = [
                        'type'  => 'section-ai-teks',
                        'label' => "AI Teks #{$section->id}",
                        'html'  => $cfg['konten'],
                    ];
                }

                // Statistik items (label field)
                if ($section->tipe === 'statistik' && ! empty($cfg['items'])) {
                    $statHtml = collect($cfg['items'])
                        ->map(fn($item) => '<p>' . ($item['label'] ?? '') . '</p>')
                        ->filter(fn($p) => strip_tags($p) !== '')
                        ->implode("\n");
                    if ($statHtml) {
                        $items[] = [
                            'type'  => 'section-statistik',
                            'label' => "Statistik #{$section->id}",
                            'html'  => $statHtml,
                        ];
                    }
                }
            }
        } catch (Throwable $e) {}

        // ── Pengumuman (HTML) ─────────────────────────────────────────────
        try {
            if (DB::getSchemaBuilder()->hasTable('pengumuman')) {
                $announcements = DB::table('pengumuman')
                    ->where('aktif', true)
                    ->get(['id', 'judul', 'isi']);

                foreach ($announcements as $ann) {
                    if (! empty($ann->isi) && mb_strlen(strip_tags($ann->isi)) > 20) {
                        $items[] = [
                            'type'  => 'pengumuman',
                            'label' => "Pengumuman #{$ann->id}: " . mb_strimwidth($ann->judul, 0, 40, '…'),
                            'html'  => $ann->isi,
                        ];
                    }
                }
            }
        } catch (Throwable $e) {}

        // ── Testimoni Alumni (HTML) ─────────────────────────────────────────
        try {
            if (DB::getSchemaBuilder()->hasTable('testimoni_alumni')) {
                $testimonis = DB::table('testimoni_alumni')
                    ->where('status', 'published')
                    ->get(['id', 'nama', 'testimoni']);

                foreach ($testimonis as $t) {
                    if (! empty($t->testimoni) && mb_strlen(strip_tags($t->testimoni)) > 20) {
                        $items[] = [
                            'type'  => 'testimoni_alumni',
                            'label' => "Testimoni #{$t->id} ({$t->nama})",
                            'html'  => "<p>{$t->testimoni}</p>",
                        ];
                    }
                }
            }
        } catch (Throwable $e) {}

        // ── Mahasiswa Berprestasi (HTML/Text) ─────────────────────────────────
        try {
            if (DB::getSchemaBuilder()->hasTable('mahasiswa_berprestasi')) {
                $outstanding = DB::table('mahasiswa_berprestasi')
                    ->where('aktif', true)
                    ->get(['id', 'periode_label', 'catatan_admin']);

                foreach ($outstanding as $o) {
                    if (! empty($o->catatan_admin) && mb_strlen(strip_tags($o->catatan_admin)) > 20) {
                        $items[] = [
                            'type'  => 'mahasiswa_berprestasi',
                            'label' => "Catatan Admin Outstanding #{$o->id} ({$o->periode_label})",
                            'html'  => "<p>{$o->catatan_admin}</p>",
                        ];
                    }
                }
            }
        } catch (Throwable $e) {}

        // ── Layanan Bisnis (HTML/Text) ───────────────────────────────────────
        try {
            if (DB::getSchemaBuilder()->hasTable('layanan_bisnis')) {
                $rows = DB::table('layanan_bisnis')->where('aktif', true)->get(['id', 'nama', 'deskripsi']);
                foreach ($rows as $r) {
                    if (! empty($r->deskripsi) && mb_strlen(strip_tags($r->deskripsi)) > 20) {
                        $items[] = [
                            'type'  => 'layanan_bisnis',
                            'label' => "Layanan Bisnis #{$r->id}: {$r->nama}",
                            'html'  => "<p>{$r->deskripsi}</p>",
                        ];
                    }
                }
            }
        } catch (Throwable $e) {}

        // ── Layanan Lab (HTML/Text) ──────────────────────────────────────────
        try {
            if (DB::getSchemaBuilder()->hasTable('layanan_lab')) {
                $rows = DB::table('layanan_lab')->where('aktif', true)->get(['id', 'nama', 'deskripsi', 'syarat']);
                foreach ($rows as $r) {
                    if (! empty($r->deskripsi) && mb_strlen(strip_tags($r->deskripsi)) > 20) {
                        $items[] = [
                            'type'  => 'layanan_lab_deskripsi',
                            'label' => "Layanan Lab #{$r->id} Deskripsi: {$r->nama}",
                            'html'  => "<p>{$r->deskripsi}</p>",
                        ];
                    }
                    if (! empty($r->syarat) && mb_strlen(strip_tags($r->syarat)) > 20) {
                        $items[] = [
                            'type'  => 'layanan_lab_syarat',
                            'label' => "Layanan Lab #{$r->id} Syarat: {$r->nama}",
                            'html'  => "<p>{$r->syarat}</p>",
                        ];
                    }
                }
            }
        } catch (Throwable $e) {}

        // ── Fasilitas Asrama (HTML/Text) ──────────────────────────────────────
        try {
            if (DB::getSchemaBuilder()->hasTable('fasilitas_asrama')) {
                $rows = DB::table('fasilitas_asrama')->where('aktif', true)->get(['id', 'nama', 'keterangan']);
                foreach ($rows as $r) {
                    if (! empty($r->keterangan) && mb_strlen(strip_tags($r->keterangan)) > 20) {
                        $items[] = [
                            'type'  => 'fasilitas_asrama',
                            'label' => "Fasilitas Asrama #{$r->id}: {$r->nama}",
                            'html'  => "<p>{$r->keterangan}</p>",
                        ];
                    }
                }
            }
        } catch (Throwable $e) {}

        // ── Koleksi Perpustakaan (HTML/Text) ───────────────────────────────────
        try {
            if (DB::getSchemaBuilder()->hasTable('koleksi_perpus')) {
                $rows = DB::table('koleksi_perpus')->where('aktif', true)->get(['id', 'judul', 'deskripsi']);
                foreach ($rows as $r) {
                    if (! empty($r->deskripsi) && mb_strlen(strip_tags($r->deskripsi)) > 20) {
                        $items[] = [
                            'type'  => 'koleksi_perpus',
                            'label' => "Koleksi Perpus #{$r->id}: {$r->judul}",
                            'html'  => "<p>{$r->deskripsi}</p>",
                        ];
                    }
                }
            }
        } catch (Throwable $e) {}

        // ── Publikasi (HTML/Text) ─────────────────────────────────────────────
        try {
            if (DB::getSchemaBuilder()->hasTable('publikasi')) {
                $rows = DB::table('publikasi')->where('aktif', true)->get(['id', 'judul', 'abstrak']);
                foreach ($rows as $r) {
                    if (! empty($r->abstrak) && mb_strlen(strip_tags($r->abstrak)) > 20) {
                        $items[] = [
                            'type'  => 'publikasi',
                            'label' => "Publikasi #{$r->id}: {$r->judul}",
                            'html'  => "<p>{$r->abstrak}</p>",
                        ];
                    }
                }
            }
        } catch (Throwable $e) {}

        // ── Dokumen Mutu (HTML/Text) ───────────────────────────────────────────
        try {
            if (DB::getSchemaBuilder()->hasTable('dokumen_mutu')) {
                $rows = DB::table('dokumen_mutu')->where('aktif', true)->get(['id', 'judul', 'keterangan']);
                foreach ($rows as $r) {
                    if (! empty($r->keterangan) && mb_strlen(strip_tags($r->keterangan)) > 20) {
                        $items[] = [
                            'type'  => 'dokumen_mutu',
                            'label' => "Dokumen Mutu #{$r->id}: {$r->judul}",
                            'html'  => "<p>{$r->keterangan}</p>",
                        ];
                    }
                }
            }
        } catch (Throwable $e) {}

        // ── Layanan TI (HTML/Text) ────────────────────────────────────────────
        try {
            if (DB::getSchemaBuilder()->hasTable('layanan_ti')) {
                $rows = DB::table('layanan_ti')->where('aktif', true)->get(['id', 'nama', 'deskripsi', 'prosedur']);
                foreach ($rows as $r) {
                    if (! empty($r->deskripsi) && mb_strlen(strip_tags($r->deskripsi)) > 20) {
                        $items[] = [
                            'type'  => 'layanan_ti_deskripsi',
                            'label' => "Layanan TI #{$r->id} Deskripsi: {$r->nama}",
                            'html'  => "<p>{$r->deskripsi}</p>",
                        ];
                    }
                    if (! empty($r->prosedur) && mb_strlen(strip_tags($r->prosedur)) > 20) {
                        $items[] = [
                            'type'  => 'layanan_ti_prosedur',
                            'label' => "Layanan TI #{$r->id} Prosedur: {$r->nama}",
                            'html'  => "<p>{$r->prosedur}</p>",
                        ];
                    }
                }
            }
        } catch (Throwable $e) {}

        // ── Pelatihan (HTML/Text) ─────────────────────────────────────────────
        try {
            if (DB::getSchemaBuilder()->hasTable('pelatihan')) {
                $rows = DB::table('pelatihan')->where('aktif', true)->get(['id', 'judul', 'deskripsi', 'hasil']);
                foreach ($rows as $r) {
                    if (! empty($r->deskripsi) && mb_strlen(strip_tags($r->deskripsi)) > 20) {
                        $items[] = [
                            'type'  => 'pelatihan_deskripsi',
                            'label' => "Pelatihan #{$r->id} Deskripsi: {$r->judul}",
                            'html'  => "<p>{$r->deskripsi}</p>",
                        ];
                    }
                    if (! empty($r->hasil) && mb_strlen(strip_tags($r->hasil)) > 20) {
                        $items[] = [
                            'type'  => 'pelatihan_hasil',
                            'label' => "Pelatihan #{$r->id} Hasil: {$r->judul}",
                            'html'  => "<p>{$r->hasil}</p>",
                        ];
                    }
                }
            }
        } catch (Throwable $e) {}

        return $items;
    }

    // =========================================================================
    // PHASE 1 HELPERS
    // =========================================================================

    private function collectStrings(): array
    {
        $strings = [];

        $this->collectModel($strings, 'unit_pages', ['judul', 'sub_judul'], ['aktif' => 'Y']);
        $this->collectPageSectionLabels($strings);
        $this->collectModel($strings, 'berita', ['judul', 'ringkasan', 'meta_description'], []);
        $this->collectModel($strings, 'halaman_statis', ['judul', 'menu_label'], []);

        if (DB::getSchemaBuilder()->hasTable('nav_menus')) {
            $this->collectModel($strings, 'nav_menus', ['label'], []);
        }
        if (DB::getSchemaBuilder()->hasTable('jurusan')) {
            $this->collectModel($strings, 'jurusan', ['nama', 'deskripsi_singkat'], []);
        }
        if (DB::getSchemaBuilder()->hasTable('agenda')) {
            $this->collectModel($strings, 'agenda', ['judul', 'deskripsi'], []);
        }
        if (DB::getSchemaBuilder()->hasTable('program_studi')) {
            $this->collectModel($strings, 'program_studi', ['nama', 'deskripsi'], []);
        }
        if (DB::getSchemaBuilder()->hasTable('pengumuman')) {
            $this->collectModel($strings, 'pengumuman', ['judul'], ['aktif' => true]);
        }
        if (DB::getSchemaBuilder()->hasTable('mahasiswa_berprestasi')) {
            $this->collectModel($strings, 'mahasiswa_berprestasi', ['periode_label', 'catatan_admin'], ['aktif' => true]);
        }
        if (DB::getSchemaBuilder()->hasTable('testimoni_alumni')) {
            $this->collectModel($strings, 'testimoni_alumni', ['nama', 'instansi', 'jabatan', 'testimoni'], ['status' => 'published']);
        }
        if (DB::getSchemaBuilder()->hasTable('layanan_bisnis')) {
            $this->collectModel($strings, 'layanan_bisnis', ['nama', 'deskripsi', 'satuan_harga'], ['aktif' => true]);
        }
        if (DB::getSchemaBuilder()->hasTable('layanan_lab')) {
            $this->collectModel($strings, 'layanan_lab', ['nama', 'deskripsi', 'satuan', 'kategori'], ['aktif' => true]);
        }
        if (DB::getSchemaBuilder()->hasTable('fasilitas_asrama')) {
            $this->collectModel($strings, 'fasilitas_asrama', ['nama', 'fasilitas', 'keterangan'], ['aktif' => true]);
        }
        if (DB::getSchemaBuilder()->hasTable('koleksi_perpus')) {
            $this->collectModel($strings, 'koleksi_perpus', ['judul', 'penulis', 'penerbit', 'kategori', 'deskripsi'], ['aktif' => true]);
        }
        if (DB::getSchemaBuilder()->hasTable('publikasi')) {
            $this->collectModel($strings, 'publikasi', ['judul', 'penulis', 'institusi', 'jenis', 'abstrak'], ['aktif' => true]);
        }
        if (DB::getSchemaBuilder()->hasTable('dokumen_mutu')) {
            $this->collectModel($strings, 'dokumen_mutu', ['judul', 'jenis', 'keterangan'], ['aktif' => true]);
        }
        if (DB::getSchemaBuilder()->hasTable('layanan_ti')) {
            $this->collectModel($strings, 'layanan_ti', ['nama', 'kategori', 'deskripsi', 'prosedur', 'kontak', 'estimasi_waktu'], ['aktif' => true]);
        }
        if (DB::getSchemaBuilder()->hasTable('pelatihan')) {
            $this->collectModel($strings, 'pelatihan', ['judul', 'jenis', 'penyelenggara', 'lokasi', 'deskripsi', 'peserta_target', 'hasil'], ['aktif' => true]);
        }

        $strings = array_merge($strings, $this->staticStrings());

        return array_values(array_unique(array_filter(
            array_map('trim', $strings),
            fn($s) => $s !== '' && mb_strlen($s) >= 2 && mb_strlen($s) <= 500
        )));
    }

    private function collectModel(array &$strings, string $table, array $columns, array $where): void
    {
        try {
            $query = DB::table($table);
            foreach ($where as $col => $val) {
                $query->where($col, $val);
            }
            foreach ($query->get($columns) as $row) {
                foreach ($columns as $col) {
                    $val = $row->$col ?? null;
                    if (is_string($val) && trim($val) !== '' && ! preg_match('/<[a-z]/', $val)) {
                        $strings[] = trim($val);
                    }
                }
            }
        } catch (Throwable) {}
    }

    private function collectPageSectionLabels(array &$strings): void
    {
        try {
            foreach (DB::table('page_sections')->where('aktif', true)->get(['judul', 'konfigurasi']) as $sec) {
                if (is_string($sec->judul) && trim($sec->judul) !== '') {
                    $strings[] = trim($sec->judul);
                }
                if (is_string($sec->konfigurasi)) {
                    $this->extractJsonStrings($strings, json_decode($sec->konfigurasi, true) ?? []);
                }
            }
        } catch (Throwable) {}
    }

    private function extractJsonStrings(array &$strings, mixed $data, int $depth = 0): void
    {
        if ($depth > 6) {
            return;
        }

        if (is_string($data)) {
            $t = trim($data);
            if ($t === '' || mb_strlen($t) < 3 || mb_strlen($t) > 500
                || str_starts_with($t, 'http') || str_starts_with($t, '/')
                || str_starts_with($t, '#') || str_starts_with($t, '{')
                || str_starts_with($t, '[') || is_numeric($t)
                || preg_match('/^[\d\+\-\.%\s]+$/', $t)
                || preg_match('/^[a-z_\-]+$/', $t)
                || str_contains($t, "\n") || str_contains($t, '<')
            ) {
                return;
            }
            $strings[] = $t;
            return;
        }

        if (is_array($data)) {
            $skip = ['tipe','urutan','aktif','id','warna','color','icon','ikon','foto','gambar','url','href','src','width','height','target','class','style','bg_color','bg_image','overlay_opacity'];
            foreach ($data as $k => $v) {
                if (in_array($k, $skip, true)) {
                    continue;
                }
                $this->extractJsonStrings($strings, $v, $depth + 1);
            }
        }
    }

    private function staticStrings(): array
    {
        return [
            'Beranda','Tentang','Layanan','Berita','Kontak','Galeri','Profil',
            'Program Studi','Daftar Online','Login Admin','Portal Mahasiswa',
            'Cari menu, jurusan, unit, HIMA...','Bahasa','Pengumuman',
            'Pengaduan / Kritik / Saran / Keluhan',
            'Sampaikan masukan Anda kepada unit atau program studi terkait. Setiap laporan akan ditindaklanjuti.',
            'Kirim Sekarang','Tautan Cepat','Menu Utama',
            'Formulir Pengaduan / Kritik / Saran / Keluhan',
            'Jenis Pesan','Pengaduan','Kritik','Saran','Keluhan',
            'Ditujukan kepada','Nama Lengkap','Nomor Telepon','Isi Pesan',
            'Kirim sebagai Anonim','Kirim Pengaduan / Saran',
            'Baca selengkapnya','Lihat semua','Kembali','Simpan','Batal',
            'Tidak ada data','Memuat...','Berhasil','Gagal',
            'Visi & Misi','Struktur Organisasi','Akreditasi','Program Kerja',
            'Kegiatan Terbaru','Kontak Unit','Dokumen','Jadwal',
            'Agenda & Kalender','Pojok Alumni','Mahasiswa Berprestasi',
            'Publikasi dan Penelitian','Kerjasama','Alumni',
        ];
    }

    private function translateChunk(array $texts, string $locale): void
    {
        $body = implode("\n", array_map(
            fn($text, $i) => '<p data-idx="' . $i . '">' . e($text) . '</p>',
            $texts,
            array_keys($texts)
        ));
        $html = "<!DOCTYPE html><html lang=\"id\"><head><meta charset=\"utf-8\"><title>sync</title></head><body>{$body}</body></html>";
        $this->translator->translateHtml($html, $locale);
    }

    private function warmGlossaryToDb(string $locale): void
    {
        $keys = [
            'Beranda','Tentang','Layanan','Berita','Kontak','Profil',
            'Program Studi','Daftar Online','Login Admin','Portal Mahasiswa',
            'Hubungi Kami','Informasi Kontak','Alamat','Telepon',
            'Jam Operasional','Kirim Pesan','Nama Lengkap','Topik',
            'Subjek','Pesan','Bahasa','Visi & Misi','Akreditasi',
            'Pengaduan','Kritik','Saran','Keluhan','Kembali',
            'Simpan','Batal','Cari','Tambah','Hapus','Edit',
            'Tidak ada data','Memuat...','Berhasil','Gagal',
            'Baca selengkapnya','Lihat semua','Galeri','Sejarah',
        ];

        $this->info('  Warming glossary entries into DB…');
        $bar = $this->output->createProgressBar(count($keys));
        $bar->start();
        foreach ($keys as $key) {
            try {
                $html = "<!DOCTYPE html><html lang=\"id\"><head><meta charset=\"utf-8\"></head><body><p>{$key}</p></body></html>";
                $this->translator->translateHtml($html, $locale);
            } catch (Throwable) {}
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
    }
}
