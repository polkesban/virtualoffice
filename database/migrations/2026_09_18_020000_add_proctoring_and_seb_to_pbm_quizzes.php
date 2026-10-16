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
        Schema::table('pbm_quizzes', function (Blueprint $table) {
            $table->boolean('enable_proctoring')->default(false)->after('show_results');
            $table->boolean('require_webcam')->default(false)->after('enable_proctoring');
            $table->boolean('enable_browser_lockdown')->default(false)->after('require_webcam');
            $table->unsignedInteger('max_violations')->default(3)->after('enable_browser_lockdown');
            $table->boolean('require_seb')->default(false)->after('max_violations');
            $table->string('seb_quit_password')->nullable()->after('require_seb');
        });

        Schema::table('pbm_quiz_attempts', function (Blueprint $table) {
            $table->unsignedInteger('violation_count')->default(0)->after('score');
            $table->boolean('is_locked')->default(false)->after('violation_count');
            $table->string('lock_reason')->nullable()->after('is_locked');
        });

        Schema::create('pbm_quiz_proctoring_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pbm_quiz_attempt_id')->constrained('pbm_quiz_attempts')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('snapshot_path')->nullable();
            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbm_quiz_proctoring_logs');

        Schema::table('pbm_quiz_attempts', function (Blueprint $table) {
            $table->dropColumn(['violation_count', 'is_locked', 'lock_reason']);
        });

        Schema::table('pbm_quizzes', function (Blueprint $table) {
            $table->dropColumn([
                'enable_proctoring',
                'require_webcam',
                'enable_browser_lockdown',
                'max_violations',
                'require_seb',
                'seb_quit_password',
            ]);
        });
    }
};
