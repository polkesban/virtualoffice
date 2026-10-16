<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('unit_events', function (Blueprint $table) {
            $table->string('koordinator_nama')->nullable()->after('penandatangan_nip');
            $table->string('koordinator_nip')->nullable()->after('koordinator_nama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_events', function (Blueprint $table) {
            $table->dropColumn(['koordinator_nama', 'koordinator_nip']);
        });
    }
};
