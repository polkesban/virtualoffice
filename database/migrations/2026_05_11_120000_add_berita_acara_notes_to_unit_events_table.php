<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_events', function (Blueprint $table) {
            $table->text('berita_acara_catatan')->nullable()->after('allow_direct_attendance');
        });
    }

    public function down(): void
    {
        Schema::table('unit_events', function (Blueprint $table) {
            $table->dropColumn('berita_acara_catatan');
        });
    }
};
