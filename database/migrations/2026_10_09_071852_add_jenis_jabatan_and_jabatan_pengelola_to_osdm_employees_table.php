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
        Schema::table('osdm_employees', function (Blueprint $table) {
            $table->string('jenis_jabatan', 50)->nullable()->after('jabatan');
            $table->string('jabatan_pengelola', 255)->nullable()->after('jenis_jabatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('osdm_employees', function (Blueprint $table) {
            $table->dropColumn(['jenis_jabatan', 'jabatan_pengelola']);
        });
    }
};
