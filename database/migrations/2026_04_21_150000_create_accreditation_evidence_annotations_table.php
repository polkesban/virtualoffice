<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accreditation_evidence_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accreditation_evidence_id')->constrained('accreditation_evidences')->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('excerpt')->nullable();
            $table->longText('note')->nullable();
            $table->string('status', 30)->default('open')->index();
            $table->boolean('is_pinned')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['accreditation_evidence_id', 'is_pinned'], 'accred_evidence_annotations_pin_idx');
            $table->index(['accreditation_evidence_id', 'status'], 'accred_evidence_annotations_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accreditation_evidence_annotations');
    }
};