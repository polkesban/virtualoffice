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
        Schema::create('pbm_quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pbm_quiz_attempt_id')->constrained('pbm_quiz_attempts')->cascadeOnDelete();
            $table->foreignId('pbm_question_id')->constrained('pbm_questions')->cascadeOnDelete();
            $table->foreignId('pbm_question_option_id')->nullable()->constrained('pbm_question_options')->nullOnDelete();
            $table->text('essay_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_awarded', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbm_quiz_answers');
    }
};
