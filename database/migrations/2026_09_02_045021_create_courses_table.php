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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Kode Mata Kuliah
            $table->string('name'); // Nama Mata Kuliah
            $table->integer('sks')->nullable(); // SKS
            $table->string('semester')->nullable(); // Semester
            $table->foreignId('program_studi_id')->nullable()->constrained('program_studi')->nullOnDelete(); // Prodi yang memiliki MK
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
