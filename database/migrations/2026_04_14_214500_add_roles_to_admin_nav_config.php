<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_nav_config')) {
            return;
        }

        if (DB::table('admin_nav_config')->where('menu_key', 'roles')->exists()) {
            return;
        }

        DB::table('admin_nav_config')->insert([
            'menu_key' => 'roles',
            'custom_label' => null,
            'custom_icon' => null,
            'urutan' => 15,
            'globally_hidden' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_nav_config')) {
            return;
        }

        DB::table('admin_nav_config')->where('menu_key', 'roles')->delete();
    }
};