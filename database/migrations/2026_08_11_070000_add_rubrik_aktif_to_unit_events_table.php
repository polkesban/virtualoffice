<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_events', function (Blueprint $table) {
            if (!Schema::hasColumn('unit_events', 'rubrik_aktif')) {
                $table->boolean('rubrik_aktif')->default(false)->after('evaluasi_aktif');
            }
        });
    }

    public function down(): void
    {
        Schema::table('unit_events', function (Blueprint $table) {
            if (Schema::hasColumn('unit_events', 'rubrik_aktif')) {
                $table->dropColumn('rubrik_aktif');
            }
        });
    }
};
