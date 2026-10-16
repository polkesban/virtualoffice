<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\UnitEvent;
use App\Models\UnitPage;

trait AuthorizesUnitPageAccess
{
    private function authorizeUnitPage(UnitPage $page): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403, 'Sesi telah berakhir atau Anda belum login.');
        }

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($user->managed_unit_page_id && (int) $user->managed_unit_page_id === (int) $page->id) {
            return;
        }

        if ($page->role === 'user_' . $user->id) {
            return;
        }

        if ($user->isKaprodi()) {
            $linkedProdi = $page->programStudi;
            if ($linkedProdi && $linkedProdi->id === $user->program_studi_id) {
                return;
            }

            abort(403, 'Anda tidak memiliki akses ke halaman unit ini.');
        }

        if ($user->isJurusanAdmin()) {
            if ($user->jurusan_id) {
                $linkedProdi = $page->programStudi;
                if ($linkedProdi && $linkedProdi->jurusan_id === $user->jurusan_id) {
                    return;
                }
            }

            abort(403, 'Anda tidak memiliki akses ke halaman unit ini.');
        }

        if ($page->role !== $user->role) {
            abort(403, 'Anda tidak memiliki akses ke halaman unit ini.');
        }
    }

    private function authorizeUnitEvent(UnitPage $page, UnitEvent $unitEvent): void
    {
        $user = auth()->user();

        if ($user && $unitEvent->participants()
            ->where('peran', 'committee')
            ->where(function ($query) use ($user) {
                $query->where('linked_user_id', $user->id);
                if ($user->identity_number) {
                    $query->orWhere('identity_number', $user->identity_number);
                }
                if ($user->email) {
                    $query->orWhere('email', $user->email);
                }
            })
            ->exists()) {
            abort_if((int) $unitEvent->unit_page_id !== (int) $page->id, 404);
            return;
        }

        $this->authorizeUnitPage($page);

        abort_if((int) $unitEvent->unit_page_id !== (int) $page->id, 404);
    }
}