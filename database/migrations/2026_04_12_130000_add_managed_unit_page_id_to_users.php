<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('unit_pages') || Schema::hasColumn('users', 'managed_unit_page_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('managed_unit_page_id')
                ->nullable()
                ->after('program_studi_id')
                ->constrained('unit_pages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'managed_unit_page_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['managed_unit_page_id']);
            $table->dropColumn('managed_unit_page_id');
        });
    }
};