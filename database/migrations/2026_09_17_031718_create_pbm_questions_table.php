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
        Schema::create('pbm_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pbm_quiz_id')->constrained('pbm_quizzes')->cascadeOnDelete();
            $table->text('question_text');
            $table->string('question_type')->default('multiple_choice');
            $table->integer('points')->default(1);
            $table->integer('order')->default(0);
            
            // Item Analysis Data (Updated automatically)
            $table->decimal('difficulty_index', 5, 4)->nullable();
            $table->decimal('discrimination_index', 5, 4)->nullable();
            $table->string('analysis_status')->nullable(); // accepted, revise, rejected
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbm_questions');
    }
};
