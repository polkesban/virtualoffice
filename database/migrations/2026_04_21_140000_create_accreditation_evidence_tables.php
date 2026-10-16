<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasDokumenMutuTable = Schema::hasTable('dokumen_mutu');

        Schema::create('accreditation_folders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('accreditation_evidences', function (Blueprint $table) use ($hasDokumenMutuTable) {
            $table->id();
            $table->foreignId('folder_id')->constrained('accreditation_folders')->cascadeOnDelete();
            $dokumenMutuColumn = $table->foreignId('dokumen_mutu_id')->nullable();

            if ($hasDokumenMutuTable) {
                $dokumenMutuColumn->constrained('dokumen_mutu')->nullOnDelete();
            }
            $table->string('title', 300);
            $table->string('subcriterion_code', 50)->nullable()->index();
            $table->string('pic_name', 180)->nullable()->index();
            $table->string('verification_status', 30)->default('draft')->index();
            $table->text('summary')->nullable();
            $table->longText('notes')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->date('review_due_at')->nullable()->index();
            $table->timestamp('verified_at')->nullable();
            $table->string('external_url', 1000)->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();

        DB::table('accreditation_folders')->insert([
            [
                'code' => '1',
                'title' => 'Visi, Misi, Tujuan, dan Strategi',
                'description' => 'Eviden VMTS, renstra, renop, sosialisasi, dan keterlibatan pemangku kepentingan.',
                'sort_order' => 10,
                'is_default' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '2',
                'title' => 'Kurikulum',
                'description' => 'CPL, struktur kurikulum, evaluasi kurikulum, dan bukti keterlibatan stakeholder.',
                'sort_order' => 20,
                'is_default' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '3',
                'title' => 'Penilaian',
                'description' => 'Kebijakan penilaian, blueprint asesmen, rubrik, moderasi, dan evaluasi sistem penilaian.',
                'sort_order' => 30,
                'is_default' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '4',
                'title' => 'Mahasiswa',
                'description' => 'Seleksi mahasiswa baru, dukungan mahasiswa, survei kepuasan, dan keselamatan mahasiswa.',
                'sort_order' => 40,
                'is_default' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '5',
                'title' => 'SDM, Penelitian, dan Pengabdian',
                'description' => 'Profil dosen, tendik, pengembangan profesional, penelitian, dan pengabdian kepada masyarakat.',
                'sort_order' => 50,
                'is_default' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '6',
                'title' => 'Sarana, Prasarana, dan Keuangan',
                'description' => 'Fasilitas pembelajaran, wahana praktik, sumber informasi, dan ringkasan kecukupan pendanaan.',
                'sort_order' => 60,
                'is_default' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '7',
                'title' => 'Penjaminan Mutu',
                'description' => 'SPMI, AMI, tindak lanjut, monitoring, dan pengendalian mutu.',
                'sort_order' => 70,
                'is_default' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '8',
                'title' => 'Tata Kelola dan Administrasi',
                'description' => 'Struktur organisasi, SOP, SK, notulen, administrasi akademik, dan kepatuhan tata kelola.',
                'sort_order' => 80,
                'is_default' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('accreditation_evidences');
        Schema::dropIfExists('accreditation_folders');
    }
};