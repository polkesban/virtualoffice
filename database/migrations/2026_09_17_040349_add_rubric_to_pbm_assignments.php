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
        Schema::table('pbm_assignments', function (Blueprint $table) {
            $table->foreignId('evaluation_rubric_id')->nullable()->constrained('evaluation_rubrics')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pbm_assignments', function (Blueprint $table) {
            $table->dropForeign(['evaluation_rubric_id']);
            $table->dropColumn('evaluation_rubric_id');
        });
    }
};
