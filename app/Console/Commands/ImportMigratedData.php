<?php

namespace App\Console\Commands;

use App\Models\Berita;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportMigratedData extends Command
{
    protected $signature   = 'migrate:import
                                {--only= : Bagian yang diimport: berita, pengumuman, jurusan}
                                {--dry-run : Tampilkan preview tanpa menyimpan ke database}';

    protected $description = 'Import data hasil scraping dari poltekkesbandung.ac.id ke database';

    private string $jsonDir;
    private ?int   $adminUserId;

    public function handle(): int
    {
        $this->jsonDir      = storage_path('app/public/migrated/json');
        $this->adminUserId  = User::where('email', 'admin@polkesban.ac.id')->value('id');

        if (! $this->adminUserId) {
            $this->error('Admin user tidak ditemukan. Jalankan terlebih dahulu: php artisan db:seed');
            return self::FAILURE;
        }

        $only = $this->option('only');

        if (! $only || $only === 'berita') {
            $this->importBerita('berita', 'berita');
        }

        if (! $only || $only === 'pengumuman') {
            $this->importBerita('pengumuman', 'pengumuman');
        }

        $this->info('Import selesai.');

        return self::SUCCESS;
    }

    private function importBerita(string $jsonFile, string $kategori): void
    {
        $path = "{$this->jsonDir}/{$jsonFile}.json";

        if (! file_exists($path)) {
            $this->warn("File tidak ditemukan: {$path}");
            return;
        }

        $items = json_decode(file_get_contents($path), true);

        if (empty($items)) {
            $this->warn("Tidak ada data di {$jsonFile}.json");
            return;
        }

        $this->info("Mengimport {$kategori}: " . count($items) . ' item...');
        $bar = $this->output->createProgressBar(count($items));
        $bar->start();

        $imported = 0;
        $skipped  = 0;

        foreach ($items as $item) {
            $judul = $item['judul'] ?? '';
            $slug  = $item['slug'] ?? Str::slug($judul);

            if (empty($judul)) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Hindari duplikasi berdasarkan slug
            if (Berita::where('slug', $slug)->exists()) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Buat ringkasan dari 160 karakter pertama konten
            $plainText = strip_tags(
                preg_replace('/#{1,6}\s+/', '', $item['konten_markdown'] ?? '')
            );
            $ringkasan = Str::limit(trim($plainText), 160);

            $gambarSampul = null;
            if (! empty($item['featured_image_local'])) {
                // Path sudah relatif ke public storage, e.g. migrated/images/xxx.jpg
                $gambarSampul = $item['featured_image_local'];
            }

            $data = [
                'user_id'          => $this->adminUserId,
                'judul'            => $judul,
                'slug'             => $slug,
                'ringkasan'        => $ringkasan ?: null,
                'isi'              => $item['konten_markdown'] ?? '',
                'gambar_sampul'    => $gambarSampul,
                'gambar_sampul_alt'=> $judul,
                'kategori'         => $kategori,
                'status'           => 'published',
                'published_at'     => $item['published_at'] ?? now()->toDateString(),
                'meta_description' => Str::limit($ringkasan, 155),
            ];

            if ($this->option('dry-run')) {
                $this->newLine();
                $this->line("  [DRY-RUN] {$judul}");
            } else {
                Berita::create($data);
                $imported++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  ✓ Imported: {$imported}  Skipped (duplikat/kosong): {$skipped}");
    }
}
