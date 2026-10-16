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
        Schema::table('pbm_quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('pbm_quizzes', 'mic_sensitivity')) {
                $table->string('mic_sensitivity', 20)->default('medium')->after('require_microphone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pbm_quizzes', function (Blueprint $table) {
            if (Schema::hasColumn('pbm_quizzes', 'mic_sensitivity')) {
                $table->dropColumn('mic_sensitivity');
            }
        });
    }
};
