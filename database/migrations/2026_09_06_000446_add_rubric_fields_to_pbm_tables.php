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
            $table->foreignId('evaluation_rubric_id')->nullable()->after('description')->constrained('evaluation_rubrics')->nullOnDelete();
        });

        Schema::table('clinical_attendances', function (Blueprint $table) {
            $table->json('rubric_scores')->nullable()->after('status');
            $table->decimal('final_grade', 5, 2)->nullable()->after('rubric_scores');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinical_attendances', function (Blueprint $table) {
            $table->dropColumn(['rubric_scores', 'final_grade']);
        });

        Schema::table('clinical_sessions', function (Blueprint $table) {
            $table->dropForeign(['evaluation_rubric_id']);
            $table->dropColumn('evaluation_rubric_id');
        });
    }
};
