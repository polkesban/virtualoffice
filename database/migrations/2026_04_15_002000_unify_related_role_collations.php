<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('roles')) {
            DB::statement("ALTER TABLE `roles` MODIFY `key` VARCHAR(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL");
        }

        if (Schema::hasTable('unit_pages')) {
            DB::statement("ALTER TABLE `unit_pages` MODIFY `role` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('roles')) {
            DB::statement("ALTER TABLE `roles` MODIFY `key` VARCHAR(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL");
        }

        if (Schema::hasTable('unit_pages')) {
            DB::statement("ALTER TABLE `unit_pages` MODIFY `role` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL");
        }
    }
};