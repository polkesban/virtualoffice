<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPenandatanganSignaturePathToUnitEventsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('unit_events', function (Blueprint $table) {
            $table->string('penandatangan_signature_path', 1000)->nullable()->after('penandatangan_nip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_events', function (Blueprint $table) {
            $table->dropColumn('penandatangan_signature_path');
        });
    }
};
