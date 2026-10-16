<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_rubrics', function (Blueprint $table): void {
            $table->string('jenis_rubrik', 50)->default('analytic')->after('kategori'); // analytic, checklist, clinical, group_matrix
            $table->string('rumus_penilaian', 50)->default('weighted_percentage')->after('jenis_rubrik'); // weighted_percentage, score_times_weight, raw_sum
            $table->decimal('skala_max', 5, 2)->default(4.00)->change();
        });
    }

    public function down(): void
    {
        Schema::table('evaluation_rubrics', function (Blueprint $table): void {
            $table->dropColumn(['jenis_rubrik', 'rumus_penilaian']);
            $table->unsignedTinyInteger('skala_max')->default(4)->change();
        });
    }
};
