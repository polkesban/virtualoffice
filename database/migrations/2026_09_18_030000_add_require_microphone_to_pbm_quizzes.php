<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pbm_quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('pbm_quizzes', 'require_microphone')) {
                $table->boolean('require_microphone')->default(false)->after('require_webcam');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pbm_quizzes', function (Blueprint $table) {
            if (Schema::hasColumn('pbm_quizzes', 'require_microphone')) {
                $table->dropColumn('require_microphone');
            }
        });
    }
};
