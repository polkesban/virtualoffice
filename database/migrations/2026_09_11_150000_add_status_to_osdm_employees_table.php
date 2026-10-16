<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('osdm_employees', 'status')) {
            Schema::table('osdm_employees', function (Blueprint $table) {
                $table->string('status', 50)->default('Aktif')->after('jabatan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('osdm_employees', 'status')) {
            Schema::table('osdm_employees', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
