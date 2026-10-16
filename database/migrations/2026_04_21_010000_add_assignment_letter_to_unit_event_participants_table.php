<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unit_event_participants')) {
            return;
        }

        Schema::table('unit_event_participants', function (Blueprint $table) {
            $table->string('surat_tugas_path')->nullable()->after('sertifikat_uploaded_at');
            $table->dateTime('surat_tugas_uploaded_at')->nullable()->after('surat_tugas_path');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('unit_event_participants')) {
            return;
        }

        Schema::table('unit_event_participants', function (Blueprint $table) {
            $table->dropColumn([
                'surat_tugas_uploaded_at',
                'surat_tugas_path',
            ]);
        });
    }
};