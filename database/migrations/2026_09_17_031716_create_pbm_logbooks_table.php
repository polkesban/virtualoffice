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
        Schema::create('pbm_logbooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_class_id')->constrained('clinical_classes')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->date('activity_date');
            $table->string('activity_title');
            $table->text('description')->nullable();
            $table->string('target_competency')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('ci_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('ci_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbm_logbooks');
    }
};
