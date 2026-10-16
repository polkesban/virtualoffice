<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $now = now();

        DB::table('roles')->updateOrInsert(
            ['key' => 'admin_jurusan'],
            [
                'label'      => 'Admin Jurusan',
                'menus'      => json_encode(['halaman-unit', 'program-studi', 'berita', 'pengumuman', 'agenda', 'eviden-akreditasi']),
                'is_system'  => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('roles')) {
            DB::table('roles')->where('key', 'admin_jurusan')->delete();
        }
    }
};
