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
        Schema::table('pbm_questions', function (Blueprint $table) {
            $table->text('vignette')->nullable()->after('pbm_quiz_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pbm_questions', function (Blueprint $table) {
            $table->dropColumn('vignette');
        });
    }
};
