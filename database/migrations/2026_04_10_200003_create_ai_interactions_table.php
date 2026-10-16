<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel ai_interactions: Log anonim interaksi pengguna dengan fitur AI
 *
 * Prinsip desain:
 * - TIDAK menyimpan identitas pengguna (GDPR/UU PDP)
 * - Hanya session_hash (SHA-256) untuk menghitung sesi unik tanpa identifikasi
 * - Digunakan untuk analitik: fitur apa yang paling berguna, kecepatan respons,
 *   tingkat kepuasan pengguna
 * - Tabel ini append-only: tidak ada UPDATE, hanya INSERT + soft-read
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_interactions', function (Blueprint $table) {
            // ── Primary ───────────────────────────────────────────────────────
            $table->id();

            // ── Anonymized Session ────────────────────────────────────────────
            // SHA-256(session_id + daily_salt) — tidak bisa di-reverse ke user asli
            $table->string('session_hash', 64)->index();

            // ── Konteks Halaman ────────────────────────────────────────────────
            $table->string('page_path', 255)->nullable();
                  // Path saja, bukan full URL (hindari menyimpan query param sensitif)
                  // Contoh: "/berita/kader-hebat-ibu-hamil"
            $table->string('referrer_path', 255)->nullable();

            // ── Tipe Interaksi ─────────────────────────────────────────────────
            $table->enum('interaction_type', [
                'chatbot',          // Percakapan chatbot
                'search',           // Pencarian dengan AI
                'content_summary',  // Rangkuman konten otomatis
                'recommendation',   // Rekomendasi artikel/prodi
                'faq',              // Jawaban FAQ otomatis
                'translation',      // Terjemahan konten
            ])->index();

            // ── Input ─────────────────────────────────────────────────────────
            $table->text('prompt_preview')->nullable();
                  // 200 karakter pertama saja (bukan full prompt untuk privasi)
            $table->unsignedSmallInteger('input_tokens')->default(0);
            $table->string('detected_language', 10)->nullable(); // id / en / ...

            // ── Output / Model ────────────────────────────────────────────────
            $table->string('model_used', 80)->nullable();
                  // Contoh: "gpt-4o-mini", "gemini-1.5-flash", "local-ollama"
            $table->unsignedSmallInteger('output_tokens')->default(0);
            $table->unsignedSmallInteger('response_time_ms')->nullable();
                  // Latensi dari kirim request sampai terima respons

            // ── Kualitas & Feedback ────────────────────────────────────────────
            $table->boolean('was_helpful')->nullable();
                  // NULL = belum dinilai, true = thumbs up, false = thumbs down
            $table->unsignedTinyInteger('rating')->nullable();
                  // 1–5 bintang (opsional, disi pengguna)
            $table->boolean('was_fallback')->default(false);
                  // true = AI tidak bisa jawab, diarahkan ke admin/halaman lain

            // ── Biaya (jika menggunakan API berbayar) ─────────────────────────
            $table->unsignedInteger('cost_microcents')->default(0);
                  // Biaya dalam 1/100 sen ($0.001 = 100 microcents)
                  // Memungkinkan tracking budget AI tanpa floating point

            // ── Device (anonymized) ───────────────────────────────────────────
            $table->enum('device_type', ['desktop', 'mobile', 'tablet', 'bot', 'unknown'])
                  ->default('unknown');
            $table->string('browser_family', 30)->nullable(); // Chrome, Firefox, Safari

            // ── Timestamp ─────────────────────────────────────────────────────
            // Hanya created_at — tabel ini append-only, tidak pernah di-update
            $table->timestamp('created_at')->useCurrent()->index();
            // Tidak ada updated_at untuk menghemat storage

            // ── Index untuk analitik ──────────────────────────────────────────

            // Dashboard harian: berapa interaksi per hari per tipe
            $table->index(['interaction_type', 'created_at'], 'idx_ai_type_date');

            // Analisis model: perbandingan performa antar model AI
            $table->index(['model_used', 'created_at'], 'idx_ai_model_date');

            // Filter feedback: berapa persen helpful per periode
            $table->index(['was_helpful', 'created_at'], 'idx_ai_helpful_date');

            // Biaya bulanan: SUM(cost_microcents)
            $table->index('cost_microcents', 'idx_ai_cost');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_interactions');
    }
};
