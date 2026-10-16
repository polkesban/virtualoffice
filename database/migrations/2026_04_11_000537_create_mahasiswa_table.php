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
        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->string('nim', 20)->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('no_hp', 20)->nullable();
            $table->string('foto')->nullable();           // path ke storage/app/public/
            $table->foreignId('program_studi_id')->constrained('program_studi')->cascadeOnDelete();
            $table->enum('angkatan', array_map('strval', range(2015, 2030)))->default(date('Y'));
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mahasiswa');
    }
};
