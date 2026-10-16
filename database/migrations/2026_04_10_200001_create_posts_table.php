<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel posts: konten serba guna (artikel, pengumuman, halaman statis)
 * dengan kolom SEO lengkap dan full-text search index.
 *
 * Berbeda dari tabel `berita` yang sederhana, tabel ini digunakan untuk
 * konten hasil migrasi dari poltekkesbandung.ac.id dan konten baru
 * yang memerlukan kontrol SEO penuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        $supportsFullText = Schema::getConnection()->getDriverName() !== 'sqlite';

        Schema::create('posts', function (Blueprint $table) use ($supportsFullText) {
            // ── Primary ───────────────────────────────────────────────────────
            $table->id();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            // ── Konten utama ──────────────────────────────────────────────────
            $table->string('title');
            $table->string('slug')->unique();           // URL-friendly, dijamin unik
            $table->text('excerpt')->nullable();        // Ringkasan 160–300 karakter
            $table->longText('content');                // Markdown / HTML
            $table->enum('content_format', ['markdown', 'html'])->default('markdown');

            // ── Media ──────────────────────────────────────────────────────────
            $table->string('featured_image')->nullable();
            $table->string('featured_image_alt', 255)->nullable();
            $table->string('featured_image_caption')->nullable();

            // ── Taksonomi ─────────────────────────────────────────────────────
            $table->enum('type', [
                'article',       // berita / artikel
                'announcement',  // pengumuman
                'event',         // kegiatan / acara
                'page',          // halaman statis (visi-misi, sejarah, dll)
            ])->default('article')->index();

            $table->json('tags')->nullable();           // ["#berita","#akademik"]
            $table->string('category', 80)->nullable(); // Kategori bebas

            // ── Status & Publikasi ────────────────────────────────────────────
            $table->enum('status', [
                'draft',
                'published',
                'scheduled',
                'archived',
            ])->default('draft');

            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable(); // Untuk penjadwalan otomatis

            // ── Statistik ────────────────────────────────────────────────────
            $table->unsignedInteger('views')->default(0);
            $table->unsignedTinyInteger('reading_time_minutes')->default(0);
                  // Dihitung otomatis: ceil(word_count / 200)
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('allow_comments')->default(false);

            // ── SEO ──────────────────────────────────────────────────────────
            $table->string('meta_title', 70)->nullable();       // <title> override
            $table->string('meta_description', 160)->nullable();
            $table->string('meta_keywords', 255)->nullable();
            $table->string('og_title', 95)->nullable();         // Open Graph
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('canonical_url')->nullable();        // Menghindari duplikat
            $table->enum('robots', [
                'index,follow',
                'noindex,follow',
                'index,nofollow',
                'noindex,nofollow',
            ])->default('index,follow');

            // ── Migrasi metadata ─────────────────────────────────────────────
            $table->string('source_url')->nullable(); // URL asal dari situs lama
            $table->string('source_author', 100)->nullable();

            $table->timestamps();
            $table->softDeletes(); // Hapus aman — data tidak hilang permanen

            // ── Index untuk performa query ────────────────────────────────────

            // Query listing: WHERE status = 'published' ORDER BY published_at DESC
            $table->index(['status', 'published_at'], 'idx_posts_status_published');

            // Filtering berdasarkan type + status (halaman berita vs pengumuman)
            $table->index(['type', 'status'], 'idx_posts_type_status');

            // Query featured posts
            $table->index(['is_featured', 'status', 'published_at'], 'idx_posts_featured');

            // Full-text search untuk pencarian judul dan isi konten
            // MariaDB FULLTEXT index — digunakan dengan MATCH(...) AGAINST(...)
            if ($supportsFullText) {
                $table->fullText(['title', 'excerpt', 'content'], 'ft_posts_search');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
