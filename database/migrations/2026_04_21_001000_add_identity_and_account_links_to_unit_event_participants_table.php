<?php

use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->string('identity_number', 50)->nullable()->after('email')->index();
            $table->foreignId('linked_user_id')->nullable()->after('sertifikat_uploaded_at')->constrained('users')->nullOnDelete();
            $table->foreignId('linked_mahasiswa_id')->nullable()->after('linked_user_id')->constrained('mahasiswa')->nullOnDelete();
        });

        $participants = DB::table('unit_event_participants')
            ->select('id', 'email', 'identity_number')
            ->orderBy('id')
            ->get();

        foreach ($participants as $participant) {
            $normalizedIdentity = Str::upper((string) preg_replace('/[^A-Za-z0-9]+/', '', trim((string) $participant->identity_number)));
            $normalizedEmail = Str::lower(trim((string) $participant->email));

            $linkedUserId = null;
            $linkedMahasiswaId = null;

            if ($normalizedIdentity !== '') {
                $mahasiswaId = Mahasiswa::query()
                    ->whereIn('nim', array_unique([$normalizedIdentity, trim((string) $participant->identity_number)]))
                    ->value('id');

                if ($mahasiswaId) {
                    $linkedMahasiswaId = $mahasiswaId;
                }
            }

            if (! $linkedMahasiswaId && $normalizedEmail !== '') {
                $userIds = User::query()
                    ->where('email', $normalizedEmail)
                    ->where('is_admin', true)
                    ->limit(2)
                    ->pluck('id');
                $mahasiswaIds = Mahasiswa::query()
                    ->where('email', $normalizedEmail)
                    ->limit(2)
                    ->pluck('id');

                if ($userIds->count() === 1 && $mahasiswaIds->isEmpty()) {
                    $linkedUserId = $userIds->first();
                } elseif ($mahasiswaIds->count() === 1 && $userIds->isEmpty()) {
                    $linkedMahasiswaId = $mahasiswaIds->first();
                }
            }

            if (! $linkedUserId && ! $linkedMahasiswaId) {
                continue;
            }

            DB::table('unit_event_participants')
                ->where('id', $participant->id)
                ->update([
                    'linked_user_id' => $linkedUserId,
                    'linked_mahasiswa_id' => $linkedMahasiswaId,
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('unit_event_participants')) {
            return;
        }

        Schema::table('unit_event_participants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('linked_mahasiswa_id');
            $table->dropConstrainedForeignId('linked_user_id');
            $table->dropColumn('identity_number');
        });
    }
};