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
        Schema::create('pbm_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pbm_question_id')->constrained('pbm_questions')->cascadeOnDelete();
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            
            // Item Analysis Data
            $table->decimal('selection_frequency', 5, 4)->nullable(); // % of students who chose this
            $table->boolean('is_functioning_distractor')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbm_question_options');
    }
};
