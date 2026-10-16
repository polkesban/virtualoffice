<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unit_pages')) {
            return;
        }

        DB::table('unit_pages')
            ->where('role', 'wadir_2')
            ->update([
                'slug' => 'unit-humas-kerjasama',
                'judul' => 'Unit Humas dan Kerjasama',
                'sub_judul' => 'Informasi publik, media, humas, dan kerja sama institusi Poltekkes Bandung.',
                'warna_tema' => '#0891b2',
                'updated_at' => now(),
            ]);

        if (! DB::table('unit_pages')->where('role', 'bidang_osdm')->exists()) {
            DB::table('unit_pages')->insert([
                'role' => 'bidang_osdm',
                'slug' => 'bidang-osdm',
                'judul' => 'Bidang OSDM',
                'sub_judul' => 'Halaman publik bidang organisasi dan sumber daya manusia.',
                'cover_foto' => null,
                'warna_tema' => '#0284c7',
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('unit_pages')) {
            return;
        }

        DB::table('unit_pages')
            ->where('role', 'wadir_2')
            ->update([
                'slug' => 'bidang-kerjasama',
                'judul' => 'Bidang Kerjasama & SDM',
                'sub_judul' => null,
                'updated_at' => now(),
            ]);

        DB::table('unit_pages')->where('role', 'bidang_osdm')->delete();
    }
};
