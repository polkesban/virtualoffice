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

        DB::transaction(function () {
            DB::table('unit_pages')
                ->where('role', 'wadir_2')
                ->where('slug', 'unit-humas-kerjasama')
                ->update([
                    'role' => 'unit_humas_kerjasama',
                    'judul' => 'Unit Humas dan Kerjasama',
                    'sub_judul' => 'Informasi publik, media, humas, dan kerja sama institusi Poltekkes Bandung.',
                    'warna_tema' => '#0891b2',
                    'updated_at' => now(),
                ]);

            if (! DB::table('unit_pages')->where('role', 'wadir_2')->exists()) {
                DB::table('unit_pages')->insert([
                    'role' => 'wadir_2',
                    'slug' => 'bidang-administrasi-umum',
                    'judul' => 'Bagian Keuangan',
                    'sub_judul' => 'Informasi layanan, tata kelola, dan capaian kinerja Bagian Keuangan Poltekkes Bandung.',
                    'cover_foto' => null,
                    'warna_tema' => '#0f766e',
                    'aktif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('unit_pages')) {
            return;
        }

        DB::transaction(function () {
            DB::table('unit_pages')
                ->where('role', 'unit_humas_kerjasama')
                ->where('slug', 'unit-humas-kerjasama')
                ->update([
                    'role' => 'wadir_2',
                    'judul' => 'Unit Humas dan Kerjasama',
                    'sub_judul' => 'Informasi publik, media, humas, dan kerja sama institusi Poltekkes Bandung.',
                    'warna_tema' => '#0891b2',
                    'updated_at' => now(),
                ]);

            DB::table('unit_pages')
                ->where('role', 'wadir_2')
                ->where('slug', 'bidang-administrasi-umum')
                ->delete();
        });
    }
};