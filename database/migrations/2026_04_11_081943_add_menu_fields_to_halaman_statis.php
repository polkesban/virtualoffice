<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('halaman_statis', function (Blueprint $table) {
            $table->string('menu_label', 60)->nullable()->after('judul');
            $table->string('menu_icon', 10)->nullable()->default('📄')->after('menu_label');
        });

        $defaults = [
            'visi_misi'    => ['label' => 'Visi & Misi',        'icon' => '📋'],
            'sejarah'      => ['label' => 'Sejarah',            'icon' => '🏛️'],
            'akreditasi'   => ['label' => 'Akreditasi',        'icon' => '✅'],
            'penelitian'   => ['label' => 'Hal. Penelitian',   'icon' => '🔬'],
            'kerjasama'    => ['label' => 'Hal. Kerjasama',    'icon' => '🤝'],
            'perpustakaan' => ['label' => 'Hal. Perpustakaan', 'icon' => '📖'],
            'laboratorium' => ['label' => 'Hal. Laboratorium', 'icon' => '🔭'],
            'asrama'       => ['label' => 'Hal. Asrama',       'icon' => '🏠'],
            'penerimaan'   => ['label' => 'Info Penerimaan',   'icon' => '📝'],
        ];
        foreach ($defaults as $kunci => $meta) {
            DB::table('halaman_statis')
                ->where('kunci', $kunci)
                ->update(['menu_label' => $meta['label'], 'menu_icon' => $meta['icon']]);
        }
    }

    public function down(): void
    {
        Schema::table('halaman_statis', function (Blueprint $table) {
            $table->dropColumn(['menu_label', 'menu_icon']);
        });
    }
};
