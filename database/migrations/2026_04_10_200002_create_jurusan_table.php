<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel jurusan: data program studi yang kaya (dari scraping + pengisian manual)
 *
 * Tabel ini melengkapi tabel `program_studi` yang berisi data ringkas untuk
 * tampilan kartu di homepage. Tabel `jurusan` menyimpan profil lengkap:
 * visi-misi, prospek kerja, kurikulum, dan data akreditasi detail.
 *
 * Relasi: jurusan.program_studi_id → program_studi.id (FK nullable)
 */
return new class extends Migration
{
    public function up(): void
    {
            $supportsFullText = Schema::getConnection()->getDriverName() !== 'sqlite';

      Schema::create('jurusan', function (Blueprint $table) use ($supportsFullText) {
            // ── Primary ───────────────────────────────────────────────────────
            $table->id();

            // Link ke tabel program_studi yang sudah ada (opsional)
            $table->foreignId('program_studi_id')
                  ->nullable()
                  ->constrained('program_studi')
                  ->nullOnDelete();

            // ── Identitas ─────────────────────────────────────────────────────
            $table->string('nama');                     // Teknologi Laboratorium Medis
            $table->string('nama_inggris')->nullable(); // Medical Laboratory Technology
            $table->string('singkatan', 20)->nullable();// TLM
            $table->string('slug')->unique();
            $table->enum('jenjang', ['D3', 'D4', 'S1', 'Profesi'])->index();
            $table->string('gelar', 50)->nullable();    // A.Md.AK, S.Tr.Kes
            $table->string('akreditasi', 20)->default('Unggul')->index();
                  // Unggul / Baik Sekali / Baik / A / B / C

            // ── Deskripsi umum ────────────────────────────────────────────────
            $table->text('deskripsi_singkat')->nullable();    // Untuk kartu (150 kata)
            $table->longText('deskripsi')->nullable();        // Profil lengkap (Markdown)
            $table->string('gambar')->nullable();             // Foto/ilustrasi utama
            $table->string('gambar_sampul')->nullable();      // Hero image halaman prodi
            $table->string('sumber_url')->nullable();         // URL dari situs lama

            // ── Visi & Misi ───────────────────────────────────────────────────
            $table->text('visi')->nullable();
                  // Contoh: "Menjadi program studi unggulan di bidang..."
            $table->json('misi')->nullable();
                  // Contoh: ["Menyelenggarakan pendidikan...", "Melaksanakan penelitian..."]
            $table->text('tujuan')->nullable();
            $table->json('sasaran')->nullable();              // Target capaian strategis

            // ── Kurikulum ─────────────────────────────────────────────────────
            $table->unsignedSmallInteger('durasi_semester')->default(6);
            $table->unsignedSmallInteger('total_sks')->nullable();
            $table->json('mata_kuliah_unggulan')->nullable();
                  // ["Hematologi Klinik", "Imunologi", "Parasitologi"]
            $table->longText('kurikulum_deskripsi')->nullable(); // Narasi kurikulum

            // ── Prospek Kerja ─────────────────────────────────────────────────
            $table->json('prospek_kerja')->nullable();
                  // ["Rumah sakit","Laboratorium klinik","BPOM","KKP"]
            $table->json('kompetensi_lulusan')->nullable();
                  // Daftar kompetensi yang dikuasai lulusan

            // ── Fasilitas & SDM ───────────────────────────────────────────────
            $table->json('fasilitas')->nullable();
                  // ["Lab Mikrobiologi","Lab Kimia Klinik","Lab Imunoserologi"]
            $table->unsignedSmallInteger('jumlah_dosen')->nullable();
            $table->unsignedSmallInteger('jumlah_mahasiswa_aktif')->nullable();

            // ── Akreditasi & Legalitas ────────────────────────────────────────
            $table->string('no_sk_akreditasi', 100)->nullable();
            $table->date('tgl_akreditasi')->nullable();
            $table->date('tgl_kadaluarsa_akreditasi')->nullable();

            // ── Penerimaan Mahasiswa ──────────────────────────────────────────
            $table->unsignedSmallInteger('kuota_per_tahun')->default(60);
            $table->unsignedSmallInteger('biaya_sks')->nullable(); // Biaya per SKS

            // ── Kontak & Media Sosial ─────────────────────────────────────────
            $table->string('email_prodi')->nullable();
            $table->string('website_prodi')->nullable();
            $table->json('sosial_media')->nullable();
                  // {"instagram":"@tlm_polkesban","youtube":"..."}

            // ── Tampilan ──────────────────────────────────────────────────────
            $table->unsignedTinyInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true)->index();
            $table->boolean('tampil_di_beranda')->default(true);

            // ── SEO ──────────────────────────────────────────────────────────
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();

            $table->timestamps();

            // ── Index ─────────────────────────────────────────────────────────

            // Filter listing: jenjang aktif, urutan tampil
            $table->index(['jenjang', 'aktif', 'urutan'], 'idx_jurusan_listing');

            // Filter akreditasi untuk halaman perbandingan/pencarian
            $table->index(['akreditasi', 'aktif'], 'idx_jurusan_akreditasi');

            // Full-text search untuk nama dan deskripsi
                  if ($supportsFullText) {
                        $table->fullText(['nama', 'deskripsi_singkat', 'deskripsi'], 'ft_jurusan_search');
                  }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurusan');
    }
};
