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
        Schema::table('clinical_sessions', function (Blueprint $table) {
            $table->json('shifts')->nullable()->after('description');
            $table->json('lokasi_praktik')->nullable()->after('shifts');
        });

        Schema::table('clinical_attendances', function (Blueprint $table) {
            $table->string('shift_name')->nullable()->after('status');
            $table->string('lokasi_praktik')->nullable()->after('shift_name');
            $table->string('check_in_signature_path')->nullable()->after('check_in_lng');
            $table->string('check_out_signature_path')->nullable()->after('check_out_lng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pbm_virtual', function (Blueprint $table) {
            //
        });
    }
};
