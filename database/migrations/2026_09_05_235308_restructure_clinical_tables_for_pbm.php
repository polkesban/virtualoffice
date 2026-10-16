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
        // 1. Drop old unused tables if they exist
        Schema::dropIfExists('clinical_attendances');
        Schema::dropIfExists('clinical_sessions');
        Schema::dropIfExists('clinical_assignments');
        Schema::dropIfExists('clinical_shifts');
        Schema::dropIfExists('clinical_class_members');
        Schema::dropIfExists('clinical_classes');
        
        // 2. Create clinical_classes table
        Schema::create('clinical_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->string('name');
            $table->string('academic_year')->nullable();
            $table->string('semester')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Create clinical_class_members table
        Schema::create('clinical_class_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_class_id')->constrained('clinical_classes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->nullable()->constrained('mahasiswa')->cascadeOnDelete();
            $table->string('role'); // dosen, mahasiswa, ci
            $table->timestamps();
            
            $table->unique(['clinical_class_id', 'user_id', 'role'], 'ccm_user_role_unique');
            $table->unique(['clinical_class_id', 'mahasiswa_id', 'role'], 'ccm_mahasiswa_role_unique');
        });

        // 4. Create clinical_sessions table
        Schema::create('clinical_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_class_id')->constrained('clinical_classes')->cascadeOnDelete();
            $table->string('title');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('location_name')->nullable();
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_lng', 11, 8)->nullable();
            $table->integer('location_radius')->default(100);
            $table->boolean('require_qr')->default(true);
            $table->boolean('require_gps')->default(true);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 5. Create clinical_attendances table
        Schema::create('clinical_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_session_id')->constrained('clinical_sessions')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->dateTime('check_in_time')->nullable();
            $table->decimal('check_in_lat', 10, 8)->nullable();
            $table->decimal('check_in_lng', 11, 8)->nullable();
            $table->dateTime('check_out_time')->nullable();
            $table->decimal('check_out_lat', 10, 8)->nullable();
            $table->decimal('check_out_lng', 11, 8)->nullable();
            $table->string('status')->default('alpha'); // hadir, izin, sakit, alpha
            $table->text('notes')->nullable();
            $table->dateTime('ci_validated_at')->nullable();
            $table->foreignId('ci_validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('dosen_override_at')->nullable();
            $table->foreignId('dosen_override_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->unique(['clinical_session_id', 'mahasiswa_id'], 'session_mahasiswa_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinical_attendances');
        Schema::dropIfExists('clinical_sessions');
        Schema::dropIfExists('clinical_class_members');
        Schema::dropIfExists('clinical_classes');
        
        // Note: not restoring old structure for simplicity of rollback
    }
};
