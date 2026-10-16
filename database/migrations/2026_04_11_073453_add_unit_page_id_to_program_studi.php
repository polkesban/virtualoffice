<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('program_studi') || ! Schema::hasTable('unit_pages')) {
            return;
        }

        Schema::table('program_studi', function (Blueprint $table) {
            $table->foreignId('unit_page_id')
                  ->nullable()
                  ->after('jurusan_id')
                  ->constrained('unit_pages')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('program_studi') || ! Schema::hasColumn('program_studi', 'unit_page_id')) {
            return;
        }

        Schema::table('program_studi', function (Blueprint $table) {
            $table->dropForeign(['unit_page_id']);
            $table->dropColumn('unit_page_id');
        });
    }
};
