<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pbm_materials', function (Blueprint $table) {
            if (!Schema::hasColumn('pbm_materials', 'type')) {
                $table->string('type', 50)->default('document')->after('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pbm_materials', function (Blueprint $table) {
            if (Schema::hasColumn('pbm_materials', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
