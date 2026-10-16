<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite' || ! Schema::hasTable('ai_interactions')) {
            return;
        }

        DB::statement("ALTER TABLE ai_interactions MODIFY COLUMN interaction_type ENUM('chatbot','search','content_summary','recommendation','faq','translation','concierge') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite' || ! Schema::hasTable('ai_interactions')) {
            return;
        }

        DB::statement("ALTER TABLE ai_interactions MODIFY COLUMN interaction_type ENUM('chatbot','search','content_summary','recommendation','faq','translation') NOT NULL");
    }
};
