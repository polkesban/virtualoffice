<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_nav_config', function (Blueprint $table) {
            $table->id();
            $table->string('menu_key', 80)->unique()->comment('Matches menu key in $allNav');
            $table->string('custom_label', 100)->nullable()->comment('null = use hardcoded default');
            $table->string('custom_icon', 20)->nullable()->comment('null = use hardcoded default');
            $table->unsignedSmallInteger('urutan')->default(999);
            $table->boolean('globally_hidden')->default(false)->comment('Hide for all non-super-admin users');
            $table->timestamps();
        });

        // Seed with current hardcoded order
        $items = [
            ['menu_key'=>'users',               'custom_label'=>null, 'custom_icon'=>null, 'urutan'=>10],
            ['menu_key'=>'api-keys',            'custom_label'=>null, 'custom_icon'=>null, 'urutan'=>20],
            ['menu_key'=>'halaman-statis-mgr',  'custom_label'=>null, 'custom_icon'=>null, 'urutan'=>25],
            ['menu_key'=>'nav-menus',           'custom_label'=>'Kelola Navigasi',  'custom_icon'=>'🧭', 'urutan'=>26],
            ['menu_key'=>'content-engine',      'custom_label'=>null, 'custom_icon'=>null, 'urutan'=>30],
            ['menu_key'=>'berita',              'custom_label'=>null, 'custom_icon'=>null, 'urutan'=>40],
            ['menu_key'=>'pengumuman',          'custom_label'=>null, 'custom_icon'=>null, 'urutan'=>50],
            ['menu_key'=>'galeri',              'custom_label'=>null, 'custom_icon'=>null, 'urutan'=>60],
            ['menu_key'=>'agenda',              'custom_label'=>null, 'custom_icon'=>null, 'urutan'=>70],
            ['menu_key'=>'program-studi',       'custom_label'=>null, 'custom_icon'=>null, 'urutan'=>80],
            ['menu_key'=>'berprestasi',         'custom_label'=>null, 'custom_icon'=>null, 'urutan'=>90],
            ['menu_key'=>'layanan-bisnis',      'custom_label'=>null, 'custom_icon'=>null, 'urutan'=>100],
            ['menu_key'=>'layanan-lab',         'custom_label'=>null, 'custom_icon'=>null, 'urutan'=>110],
            ['menu_key'=>'fasilitas-asrama',    'custom_label'=>null, 'custom_icon'=>null, 'urutan'=>120],
            ['menu_key'=>'koleksi-perpus',      'custom_label'=>null, 'custom_icon'=>null, 'urutan'=>130],
            ['menu_key'=>'publikasi',           'custom_label'=>null, 'custom_icon'=>null, 'urutan'=>140],
            ['menu_key'=>'dokumen-mutu',        'custom_label'=>null, 'custom_icon'=>null, 'urutan'=>150],
            ['menu_key'=>'layanan-ti',          'custom_label'=>null, 'custom_icon'=>null, 'urutan'=>160],
            ['menu_key'=>'pelatihan',           'custom_label'=>null, 'custom_icon'=>null, 'urutan'=>170],
            ['menu_key'=>'halaman-unit',        'custom_label'=>null, 'custom_icon'=>null, 'urutan'=>180],
        ];
        $now = now();
        foreach ($items as $item) {
            DB::table('admin_nav_config')->insert(array_merge($item, [
                'globally_hidden'=>0, 'created_at'=>$now, 'updated_at'=>$now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_nav_config');
    }
};
