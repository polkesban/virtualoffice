<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unit_pages')) {
            return;
        }

        Schema::create('unit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_page_id')->constrained('unit_pages')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->text('ringkasan')->nullable();
            $table->longText('deskripsi')->nullable();
            $table->string('jenis_kegiatan', 40)->default('webinar');
            $table->string('metode', 20)->default('online');
            $table->string('status', 20)->default('draft')->index();
            $table->string('flyer_path')->nullable();
            $table->string('lokasi')->nullable();
            $table->text('alamat_detail')->nullable();
            $table->text('maps_embed')->nullable();
            $table->string('zoom_link')->nullable();
            $table->string('whatsapp_group_link')->nullable();
            $table->string('narahubung_nama')->nullable();
            $table->string('narahubung_whatsapp')->nullable();
            $table->dateTime('mulai_at');
            $table->dateTime('selesai_at')->nullable();
            $table->unsignedInteger('kapasitas')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('registrasi_aktif')->default(true);
            $table->dateTime('registrasi_mulai_at')->nullable();
            $table->dateTime('registrasi_selesai_at')->nullable();
            $table->boolean('daftar_hadir_aktif')->default(true);
            $table->boolean('pre_test_aktif')->default(false);
            $table->boolean('post_test_aktif')->default(false);
            $table->boolean('evaluasi_aktif')->default(false);
            $table->boolean('sertifikat_peserta_aktif')->default(false);
            $table->boolean('sertifikat_narasumber_aktif')->default(false);
            $table->boolean('sertifikat_panitia_aktif')->default(false);
            $table->dateTime('published_at')->nullable();
            $table->timestamps();

            $table->index(['unit_page_id', 'status']);
            $table->index(['unit_page_id', 'mulai_at']);
        });

        Schema::create('unit_event_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_event_id')->constrained('unit_events')->cascadeOnDelete();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('tipe', 20)->default('file');
            $table->string('file_path')->nullable();
            $table->text('link_url')->nullable();
            $table->text('embed_url')->nullable();
            $table->boolean('downloadable')->default(true);
            $table->boolean('publik')->default(true);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['unit_event_id', 'urutan']);
        });

        Schema::create('unit_event_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_event_id')->constrained('unit_events')->cascadeOnDelete();
            $table->string('fase', 20)->index();
            $table->string('tipe_pertanyaan', 30)->default('multiple_choice');
            $table->text('pertanyaan');
            $table->json('opsi')->nullable();
            $table->text('jawaban_benar')->nullable();
            $table->boolean('wajib')->default(true);
            $table->unsignedSmallInteger('bobot')->default(1);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['unit_event_id', 'fase', 'urutan']);
        });

        Schema::create('unit_event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_event_id')->constrained('unit_events')->cascadeOnDelete();
            $table->string('peran', 20)->default('participant')->index();
            $table->string('sumber_registrasi', 20)->default('public');
            $table->string('nama');
            $table->string('email')->nullable()->index();
            $table->string('no_hp')->nullable();
            $table->string('instansi')->nullable();
            $table->string('jabatan')->nullable();
            $table->text('catatan')->nullable();
            $table->string('kode_registrasi', 30)->unique();
            $table->string('access_token', 80)->unique();
            $table->dateTime('registered_at')->nullable();
            $table->dateTime('attended_at')->nullable();
            $table->text('attendance_notes')->nullable();
            $table->decimal('pre_test_nilai', 5, 2)->nullable();
            $table->decimal('post_test_nilai', 5, 2)->nullable();
            $table->dateTime('evaluasi_dikirim_at')->nullable();
            $table->string('sertifikat_path')->nullable();
            $table->dateTime('sertifikat_uploaded_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['unit_event_id', 'peran']);
        });

        Schema::create('unit_event_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_event_id')->constrained('unit_events')->cascadeOnDelete();
            $table->foreignId('unit_event_participant_id')->constrained('unit_event_participants')->cascadeOnDelete();
            $table->string('fase', 20)->index();
            $table->json('jawaban');
            $table->decimal('nilai', 5, 2)->nullable();
            $table->dateTime('submitted_at');
            $table->timestamps();

            $table->unique(['unit_event_participant_id', 'fase'], 'unit_event_submission_unique_phase');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_event_submissions');
        Schema::dropIfExists('unit_event_participants');
        Schema::dropIfExists('unit_event_questions');
        Schema::dropIfExists('unit_event_materials');
        Schema::dropIfExists('unit_events');
    }
};