<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::create('roles', function (Blueprint $table) use ($isSqlite) {
            $table->id();
            $keyColumn = $table->string('key', 80);

            if (! $isSqlite) {
                $keyColumn->collation('utf8mb4_unicode_ci');
            }

            $keyColumn->unique();
            $table->string('label', 180);
            $table->json('menus')->nullable();
            $table->boolean('is_system')->default(false)->index();
            $table->timestamps();
        });

        $now = now();

        DB::table('roles')->insert([
            ['key' => 'super_admin', 'label' => 'Super Admin', 'menus' => json_encode(['*']), 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'wadir_1', 'label' => 'Wakil Direktur I (Akademik)', 'menus' => json_encode(['halaman-unit', 'berita', 'pengumuman', 'program-studi', 'agenda']), 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'wadir_2', 'label' => 'Wakil Direktur II (Administrasi Umum)', 'menus' => json_encode(['halaman-unit']), 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'wadir_3', 'label' => 'Wakil Direktur III (Kemahasiswaan)', 'menus' => json_encode(['halaman-unit', 'berita', 'pengumuman', 'berprestasi', 'agenda']), 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'pusat_mutu', 'label' => 'Pusat Penjaminan Mutu', 'menus' => json_encode(['halaman-unit', 'dokumen-mutu', 'halaman-statis:akreditasi', 'halaman-statis:visi_misi']), 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'pusat_litmas', 'label' => 'Pusat Penelitian & Pengabmas', 'menus' => json_encode(['halaman-unit', 'publikasi', 'berita', 'pengumuman', 'agenda', 'halaman-statis:penelitian']), 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'pusat_p3', 'label' => 'Pusat Pengembangan Pendidikan', 'menus' => json_encode(['halaman-unit', 'pelatihan', 'berita', 'pengumuman', 'halaman-statis:kurikulum']), 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'kaprodi', 'label' => 'Kepala Program Studi', 'menus' => json_encode(['halaman-unit', 'program-studi', 'berita', 'pengumuman']), 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'unit_ti', 'label' => 'Unit Teknologi & Informasi', 'menus' => json_encode(['halaman-unit', 'layanan-ti', 'halaman-statis:sejarah']), 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'unit_humas_kerjasama', 'label' => 'Unit Humas dan Kerjasama', 'menus' => json_encode(['halaman-unit', 'berita', 'pengumuman', 'galeri', 'agenda', 'halaman-statis:kerjasama', 'halaman-statis:akreditasi']), 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'unit_perpus', 'label' => 'Unit Perpustakaan Terpadu', 'menus' => json_encode(['halaman-unit', 'koleksi-perpus', 'halaman-statis:perpustakaan', 'berita']), 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'unit_lab', 'label' => 'Unit Laboratorium Terpadu', 'menus' => json_encode(['halaman-unit', 'layanan-lab', 'berita', 'halaman-statis:laboratorium']), 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'unit_bahasa', 'label' => 'Unit Bahasa', 'menus' => json_encode(['halaman-unit', 'berita', 'pengumuman', 'agenda']), 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'unit_bisnis', 'label' => 'Unit Bisnis', 'menus' => json_encode(['halaman-unit', 'layanan-bisnis', 'galeri', 'berita', 'halaman-statis:kerjasama']), 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'unit_asrama', 'label' => 'Unit Asrama', 'menus' => json_encode(['halaman-unit', 'fasilitas-asrama', 'berita', 'halaman-statis:asrama']), 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'spi', 'label' => 'Satuan Pengawas Internal', 'menus' => json_encode(['halaman-unit']), 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'page_admin', 'label' => 'Admin Halaman Publik', 'menus' => json_encode(['halaman-unit']), 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};