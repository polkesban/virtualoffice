<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unit_event_participants')) {
            return;
        }

        Schema::table('unit_event_participants', function (Blueprint $table) {
            $table->string('access_password')->nullable()->after('access_token');
            $table->text('access_password_encrypted')->nullable()->after('access_password');
            $table->dateTime('credentials_generated_at')->nullable()->after('access_password_encrypted');
        });

        $participants = DB::table('unit_event_participants')
            ->whereIn('sumber_registrasi', ['admin', 'admin_import'])
            ->whereNull('access_password')
            ->orderBy('id')
            ->get(['id']);

        foreach ($participants as $participant) {
            $temporaryPassword = $this->generateTemporaryPassword();

            DB::table('unit_event_participants')
                ->where('id', $participant->id)
                ->update([
                    'access_password' => Hash::make($temporaryPassword),
                    'access_password_encrypted' => Crypt::encryptString($temporaryPassword),
                    'credentials_generated_at' => now(),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('unit_event_participants')) {
            return;
        }

        Schema::table('unit_event_participants', function (Blueprint $table) {
            $table->dropColumn([
                'access_password',
                'access_password_encrypted',
                'credentials_generated_at',
            ]);
        });
    }

    private function generateTemporaryPassword(): string
    {
        return Str::upper(Str::random(4)) . random_int(1000, 9999);
    }
};