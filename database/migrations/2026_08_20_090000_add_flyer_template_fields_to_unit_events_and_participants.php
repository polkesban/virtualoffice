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
        if (Schema::hasTable('unit_events')) {
            Schema::table('unit_events', function (Blueprint $table) {
                if (! Schema::hasColumn('unit_events', 'direktur_nama')) {
                    $table->string('direktur_nama')->nullable()->after('penandatangan_nip');
                }
                if (! Schema::hasColumn('unit_events', 'direktur_foto_path')) {
                    $table->string('direktur_foto_path')->nullable()->after('direktur_nama');
                }
                if (! Schema::hasColumn('unit_events', 'flyer_theme')) {
                    $table->string('flyer_theme', 50)->default('modern_teal')->after('direktur_foto_path');
                }
                if (! Schema::hasColumn('unit_events', 'flyer_tagline')) {
                    $table->string('flyer_tagline')->nullable()->after('flyer_theme');
                }
                if (! Schema::hasColumn('unit_events', 'flyer_benefits')) {
                    $table->text('flyer_benefits')->nullable()->after('flyer_tagline');
                }
            });
        }

        if (Schema::hasTable('unit_event_participants')) {
            Schema::table('unit_event_participants', function (Blueprint $table) {
                if (! Schema::hasColumn('unit_event_participants', 'foto_path')) {
                    $table->string('foto_path')->nullable()->after('catatan');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('unit_events')) {
            Schema::table('unit_events', function (Blueprint $table) {
                $table->dropColumn([
                    'direktur_nama',
                    'direktur_foto_path',
                    'flyer_theme',
                    'flyer_tagline',
                    'flyer_benefits',
                ]);
            });
        }

        if (Schema::hasTable('unit_event_participants')) {
            Schema::table('unit_event_participants', function (Blueprint $table) {
                $table->dropColumn(['foto_path']);
            });
        }
    }
};
