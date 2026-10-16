<?php

namespace App\Support;

use App\Models\Mahasiswa;
use App\Models\UnitEvent;
use App\Models\UnitEventParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UnitEventParticipantAccountResolver
{
    public function normalizeParticipantCategory(?string $value): ?string
    {
        $normalized = (string) Str::of((string) $value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_');

        return match ($normalized) {
            '', null => null,
            'pegawai', 'pegawai_polkesban', 'employee', 'staff', 'internal', 'dosen', 'tendik' => 'employee',
            'mahasiswa', 'student', 'student_polkesban' => 'student',
            'umum', 'public', 'non_pegawai', 'non_pegawai_polkesban', 'non_pegawai_umum', 'eksternal', 'external' => 'public',
            default => null,
        };
    }

    public function normalizeIdentityNumber(?string $value): ?string
    {
        $normalized = Str::upper((string) preg_replace('/[^A-Za-z0-9]+/', '', trim((string) $value)));

        return $normalized !== '' ? $normalized : null;
    }

    public function normalizeEmail(?string $value): ?string
    {
        $normalized = Str::lower(trim((string) $value));

        return $normalized !== '' ? $normalized : null;
    }

    public function resolveParticipantCategory(?string $category, array $linkAttributes = []): string
    {
        $normalizedCategory = $this->normalizeParticipantCategory($category);

        if ($normalizedCategory) {
            return $normalizedCategory;
        }

        if (filled($linkAttributes['linked_mahasiswa_id'] ?? null)) {
            return 'student';
        }

        if (filled($linkAttributes['linked_user_id'] ?? null)) {
            return 'employee';
        }

        return 'public';
    }

    public function resolveLinkAttributes(?string $identityNumber, ?string $email): array
    {
        $normalizedIdentityNumber = $this->normalizeIdentityNumber($identityNumber);
        $normalizedEmail = $this->normalizeEmail($email);

        if ($normalizedIdentityNumber) {
            $users = User::query()
                ->where('identity_number', $normalizedIdentityNumber)
                ->limit(2)
                ->get();

            $mahasiswa = Mahasiswa::query()
                ->whereIn('nim', array_unique([$normalizedIdentityNumber, trim((string) $identityNumber)]))
                ->limit(2)
                ->get();

            if ($users->count() === 1 && $mahasiswa->isEmpty()) {
                return [
                    'linked_user_id' => $users->first()->id,
                    'linked_mahasiswa_id' => null,
                ];
            }

            if ($mahasiswa->count() === 1 && $users->isEmpty()) {
                return [
                    'linked_user_id' => null,
                    'linked_mahasiswa_id' => $mahasiswa->first()->id,
                ];
            }
        }

        if (! $normalizedEmail) {
            return [
                'linked_user_id' => null,
                'linked_mahasiswa_id' => null,
            ];
        }

        $users = User::query()
            ->where('email', $normalizedEmail)
            ->limit(2)
            ->get();

        $mahasiswa = Mahasiswa::query()
            ->where('email', $normalizedEmail)
            ->limit(2)
            ->get();

        if ($users->count() === 1 && $mahasiswa->isEmpty()) {
            return [
                'linked_user_id' => $users->first()->id,
                'linked_mahasiswa_id' => null,
            ];
        }

        if ($mahasiswa->count() === 1 && $users->isEmpty()) {
            return [
                'linked_user_id' => null,
                'linked_mahasiswa_id' => $mahasiswa->first()->id,
            ];
        }

        return [
            'linked_user_id' => null,
            'linked_mahasiswa_id' => null,
        ];
    }

    public function findAuthenticatedParticipant(UnitEvent $event, ?string $registrationCode = null, ?string $portal = null): ?UnitEventParticipant
    {
        return match ($portal) {
            'mahasiswa' => $this->findForMahasiswa($event, $registrationCode),
            'pegawai', 'admin' => $this->findForUser($event, $registrationCode),
            default => $this->findForMahasiswa($event, $registrationCode)
                ?? $this->findForUser($event, $registrationCode),
        };
    }

    private function findForMahasiswa(UnitEvent $event, ?string $registrationCode = null): ?UnitEventParticipant
    {
        $mahasiswa = Auth::guard('mahasiswa')->user();

        if (! $mahasiswa instanceof Mahasiswa) {
            return null;
        }

        $nim = $this->normalizeIdentityNumber($mahasiswa->nim);
        $email = $this->normalizeEmail($mahasiswa->email);

        return $this->findMatchingParticipant(
            $event,
            $registrationCode,
            [
                'linked_user_id' => null,
                'linked_mahasiswa_id' => $mahasiswa->id,
            ],
            fn (UnitEventParticipant $participant): bool => (
                ($nim && $this->normalizeIdentityNumber($participant->identity_number) === $nim)
                || ($email && $this->normalizeEmail($participant->email) === $email)
            ),
        );
    }

    private function findForUser(UnitEvent $event, ?string $registrationCode = null): ?UnitEventParticipant
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        $identityNumber = $this->normalizeIdentityNumber($user->identity_number);
        $email = $this->normalizeEmail($user->email);

        if (! $identityNumber && ! $email) {
            return null;
        }

        return $this->findMatchingParticipant(
            $event,
            $registrationCode,
            [
                'linked_user_id' => $user->id,
                'linked_mahasiswa_id' => null,
            ],
            fn (UnitEventParticipant $participant): bool => (
                ($identityNumber && $this->normalizeIdentityNumber($participant->identity_number) === $identityNumber)
                || ($email && $this->normalizeEmail($participant->email) === $email)
            ),
        );
    }

    private function findMatchingParticipant(
        UnitEvent $event,
        ?string $registrationCode,
        array $linkAttributes,
        callable $matcher
    ): ?UnitEventParticipant {
        $normalizedCode = $registrationCode ? Str::upper(trim($registrationCode)) : null;

        $participants = $event->participants()
            ->with(['linkedUser', 'linkedMahasiswa'])
            ->when($normalizedCode, fn ($query) => $query->where('kode_registrasi', $normalizedCode))
            ->get();

        $linkedParticipant = $participants->first(function (UnitEventParticipant $participant) use ($linkAttributes): bool {
            return (int) ($participant->linked_user_id ?? 0) === (int) ($linkAttributes['linked_user_id'] ?? 0)
                && (int) ($participant->linked_mahasiswa_id ?? 0) === (int) ($linkAttributes['linked_mahasiswa_id'] ?? 0);
        });

        if ($linkedParticipant) {
            return $linkedParticipant;
        }

        $matches = $participants->filter($matcher)->values();

        if ($matches->count() !== 1) {
            return null;
        }

        $participant = $matches->first();
        $this->persistLinks($participant, $linkAttributes);

        return $participant;
    }

    private function persistLinks(UnitEventParticipant $participant, array $linkAttributes): void
    {
        $attributes = [
            'linked_user_id' => $linkAttributes['linked_user_id'] ?? null,
            'linked_mahasiswa_id' => $linkAttributes['linked_mahasiswa_id'] ?? null,
        ];

        $participantCategory = $participant->participant_category;

        if (filled($attributes['linked_mahasiswa_id'])) {
            $participantCategory = 'student';
        } elseif (filled($attributes['linked_user_id']) && blank($participantCategory)) {
            $participantCategory = 'employee';
        }

        if ((int) ($participant->linked_user_id ?? 0) === (int) ($attributes['linked_user_id'] ?? 0)
            && (int) ($participant->linked_mahasiswa_id ?? 0) === (int) ($attributes['linked_mahasiswa_id'] ?? 0)
            && (string) ($participant->participant_category ?? '') === (string) ($participantCategory ?? '')) {
            return;
        }

        $participant->forceFill([
            ...$attributes,
            'participant_category' => $participantCategory ?: 'public',
        ])->saveQuietly();
    }
}