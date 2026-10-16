<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unit_events')) {
            return;
        }

        Schema::table('unit_events', function (Blueprint $table) {
            $table->string('surat_tugas_event_path')->nullable()->after('flyer_path');
            $table->dateTime('surat_tugas_event_uploaded_at')->nullable()->after('surat_tugas_event_path');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('unit_events')) {
            return;
        }

        Schema::table('unit_events', function (Blueprint $table) {
            $table->dropColumn([
                'surat_tugas_event_uploaded_at',
                'surat_tugas_event_path',
            ]);
        });
    }
};