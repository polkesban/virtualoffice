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
        if (!Schema::hasColumn('osdm_employees', 'jenis_kepegawaian')) {
            Schema::table('osdm_employees', function (Blueprint $table) {
                $table->string('jenis_kepegawaian', 20)->default('ASN')->after('status');
            });
        }

        // Set all existing employees to ASN first
        DB::table('osdm_employees')->whereNull('jenis_kepegawaian')->orWhere('jenis_kepegawaian', '')->update([
            'jenis_kepegawaian' => 'ASN',
        ]);

        // Set the 6 specific employees to Non ASN
        $nonAsnNips = [
            '19690211201301L027', // Jejen
            '19711006201301L026', // Iwan Gunawan
            '19720417201301L055', // Subur
            '19961020202301P162', // Maryam Salsabila
            '19980902202206P161', // Femmy Anissa Ayuningtias
            '19861210202308P166', // Ns.Mutiara Syagitta, MSN
        ];

        DB::table('osdm_employees')
            ->whereIn('nip', $nonAsnNips)
            ->update(['jenis_kepegawaian' => 'Non ASN']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('osdm_employees', 'jenis_kepegawaian')) {
            Schema::table('osdm_employees', function (Blueprint $table) {
                $table->dropColumn('jenis_kepegawaian');
            });
        }
    }
};
