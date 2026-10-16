<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_events', function (Blueprint $table) {
            $table->string('berita_acara_judul')->nullable()->after('berita_acara_catatan');
            $table->text('berita_acara_ringkasan')->nullable()->after('berita_acara_judul');
        });
    }

    public function down(): void
    {
        Schema::table('unit_events', function (Blueprint $table) {
            $table->dropColumn(['berita_acara_judul', 'berita_acara_ringkasan']);
        });
    }
};
