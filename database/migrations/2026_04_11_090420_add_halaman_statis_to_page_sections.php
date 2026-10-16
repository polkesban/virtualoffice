<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Allow sections to belong to halaman_statis (not just unit_pages)
        if (Schema::hasTable('page_sections')) {
            Schema::table('page_sections', function (Blueprint $table) {
                $table->unsignedBigInteger('halaman_statis_id')->nullable()->after('unit_page_id');
                $table->foreign('halaman_statis_id')->references('id')->on('halaman_statis')->nullOnDelete();
                $table->unsignedBigInteger('unit_page_id')->nullable()->change();
            });

            // Polling votes
            Schema::create('page_section_votes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('section_id')->constrained('page_sections')->cascadeOnDelete();
                $table->string('session_key', 64)->index()->comment('SHA256 of session+IP');
                $table->unsignedTinyInteger('pilihan')->comment('0-based option index');
                $table->timestamp('created_at')->useCurrent();
            });

            // Form submissions
            Schema::create('page_section_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('section_id')->constrained('page_sections')->cascadeOnDelete();
                $table->json('data');
                $table->string('ip', 45)->nullable();
                $table->timestamps();
            });
        }

        // Add nav-admin to admin_nav_config if not already present
        if (Schema::hasTable('admin_nav_config') && !DB::table('admin_nav_config')->where('menu_key','nav-admin')->exists()) {
            DB::table('admin_nav_config')->insert([
                'menu_key'       => 'nav-admin',
                'custom_label'   => 'Konfigurasi Sidebar',
                'custom_icon'    => '⚙️',
                'urutan'         => 27,
                'globally_hidden'=> 0,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    public function down(): void {
        Schema::dropIfExists('page_section_submissions');
        Schema::dropIfExists('page_section_votes');

        if (Schema::hasTable('page_sections') && Schema::hasColumn('page_sections', 'halaman_statis_id')) {
            Schema::table('page_sections', function (Blueprint $table) {
                $table->dropForeign(['halaman_statis_id']);
                $table->dropColumn('halaman_statis_id');
            });
        }
    }
};
