<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nav_menus', function (Blueprint $table) {
            $table->id();
            $table->string('label', 100);
            $table->string('href', 300)->nullable()->comment('null = pure dropdown group');
            $table->string('icon', 20)->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('nav_menus')->nullOnDelete();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->string('target', 10)->default('_self');
            $table->timestamps();
        });

        // Seed initial public navbar data
        $now = now();

        // Top-level items
        $beranda    = DB::table('nav_menus')->insertGetId(['label'=>'Beranda',      'href'=>'/',              'urutan'=>1,  'aktif'=>1, 'target'=>'_self', 'created_at'=>$now, 'updated_at'=>$now]);
        $berita     = DB::table('nav_menus')->insertGetId(['label'=>'Berita',       'href'=>'/berita',        'urutan'=>2,  'aktif'=>1, 'target'=>'_self', 'created_at'=>$now, 'updated_at'=>$now]);
        $pengumuman = DB::table('nav_menus')->insertGetId(['label'=>'Pengumuman',   'href'=>'/pengumuman',    'urutan'=>3,  'aktif'=>1, 'target'=>'_self', 'created_at'=>$now, 'updated_at'=>$now]);
        $profil     = DB::table('nav_menus')->insertGetId(['label'=>'Profil',       'href'=>null,             'urutan'=>4,  'aktif'=>1, 'target'=>'_self', 'created_at'=>$now, 'updated_at'=>$now]);
        $prodi      = DB::table('nav_menus')->insertGetId(['label'=>'Program Studi','href'=>'/program-studi', 'urutan'=>5,  'aktif'=>1, 'target'=>'_self', 'created_at'=>$now, 'updated_at'=>$now]);
        $kontak     = DB::table('nav_menus')->insertGetId(['label'=>'Kontak',       'href'=>'/kontak',        'urutan'=>6,  'aktif'=>1, 'target'=>'_self', 'created_at'=>$now, 'updated_at'=>$now]);

        // Sub-items under Profil
        $subs = [
            ['label'=>'Sejarah',             'href'=>'/profil/sejarah',    'urutan'=>1],
            ['label'=>'Visi & Misi',         'href'=>'/profil/visi-misi',  'urutan'=>2],
            ['label'=>'Struktur Organisasi', 'href'=>'/profil/struktur',   'urutan'=>3],
            ['label'=>'Akreditasi',          'href'=>'/profil/akreditasi', 'urutan'=>4],
        ];
        foreach ($subs as $sub) {
            DB::table('nav_menus')->insert(array_merge($sub, [
                'parent_id'=>$profil, 'aktif'=>1, 'target'=>'_self',
                'created_at'=>$now, 'updated_at'=>$now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nav_menus');
    }
};
