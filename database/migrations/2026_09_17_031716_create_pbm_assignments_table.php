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
        Schema::create('pbm_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_session_id')->constrained('clinical_sessions')->cascadeOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->boolean('is_practical')->default(false); // If true, might require CI validation
            $table->string('attachment_path')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbm_assignments');
    }
};
