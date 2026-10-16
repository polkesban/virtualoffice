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
        Schema::table('wfh_reports', function (Blueprint $table) {
            $table->index('user_id');
            $table->dropUnique(['user_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wfh_reports', function (Blueprint $table) {
            $table->unique(['user_id', 'tanggal']);
            $table->dropIndex(['user_id']);
        });
    }
};
