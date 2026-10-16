<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $menuKey = 'pengaduan';

    public function up(): void
    {
        if (Schema::hasTable('admin_nav_config') && ! DB::table('admin_nav_config')->where('menu_key', $this->menuKey)->exists()) {
            DB::table('admin_nav_config')->insert([
                'menu_key' => $this->menuKey,
                'custom_label' => null,
                'custom_icon' => null,
                'urutan' => 185,
                'globally_hidden' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admin_nav_config')) {
            DB::table('admin_nav_config')->where('menu_key', $this->menuKey)->delete();
        }
    }
};
