<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use App\Models\UnitPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PengaduanUnitController extends Controller
{
    /**
     * Resolve which unit the authenticated admin manages,
     * and determine the tujuan keys they can see.
     */
    private function resolveUnit(?UnitPage $unitPage = null): array
    {
        $user = Auth::user();
 
        if ($unitPage) {
            // Check ownership
            $ownsPage = false;
            if ($user->isSuperAdmin()) {
                $ownsPage = true;
            } elseif ($user->managed_unit_page_id === $unitPage->id) {
                $ownsPage = true;
            } elseif ($user->role === $unitPage->role) {
                $ownsPage = true;
            } elseif ($user->isKaprodi() && $user->programStudi && $user->programStudi->unit_page_id === $unitPage->id) {
                $ownsPage = true;
            } elseif ($user->isJurusanAdmin() && $unitPage->programStudi && $unitPage->programStudi->jurusan_id === $user->jurusan_id) {
                $ownsPage = true;
            }
 
            abort_unless($ownsPage, 403);
 
            return [
                'page'       => $unitPage,
                'accessKeys' => Pengaduan::unitAccessKeys($unitPage->role),
            ];
        }
 
        // Accessing general index
        if ($user->isSuperAdmin()) {
            return [
                'page'       => null,
                'accessKeys' => collect(Pengaduan::tujuanOptions())->pluck('key')->all(),
            ];
        }
 
        // Unit staff: resolve page
        $page = null;
        if ($user->managed_unit_page_id) {
            $page = UnitPage::find($user->managed_unit_page_id);
        } elseif ($user->isKaprodi() && $user->programStudi) {
            $page = $user->programStudi->unitPage;
        } else {
            $page = UnitPage::where('role', $user->role)->first();
        }
 
        if ($page) {
            return [
                'page'       => $page,
                'accessKeys' => Pengaduan::unitAccessKeys($page->role),
            ];
        }
 
        abort(403);
    }

    /**
     * Rekap / index dashboard per unit.
     */
    public function index(Request $request, ?UnitPage $unitPage = null)
    {
        ['page' => $page, 'accessKeys' => $accessKeys] = $this->resolveUnit($unitPage);

        $query = Pengaduan::whereIn('tujuan_unit', $accessKeys)
            ->orderByDesc('created_at');

        // Filter
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($jenis = $request->query('jenis')) {
            $query->where('jenis', $jenis);
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('subjek', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%")
                  ->orWhere('nomor_tiket', 'like', "%{$search}%");
            });
        }

        $pengaduanList = $query->paginate(20)->withQueryString();

        // Stats
        $stats = [
            'total'    => Pengaduan::whereIn('tujuan_unit', $accessKeys)->count(),
            'masuk'    => Pengaduan::whereIn('tujuan_unit', $accessKeys)->where('status', 'masuk')->count(),
            'diproses' => Pengaduan::whereIn('tujuan_unit', $accessKeys)->where('status', 'diproses')->count(),
            'selesai'  => Pengaduan::whereIn('tujuan_unit', $accessKeys)->where('status', 'selesai')->count(),
            'ditolak'  => Pengaduan::whereIn('tujuan_unit', $accessKeys)->where('status', 'ditolak')->count(),
        ];

        // Per-jenis stats
        $statsJenis = [
            'pengaduan' => Pengaduan::whereIn('tujuan_unit', $accessKeys)->where('jenis', 'pengaduan')->count(),
            'kritik'    => Pengaduan::whereIn('tujuan_unit', $accessKeys)->where('jenis', 'kritik')->count(),
            'saran'     => Pengaduan::whereIn('tujuan_unit', $accessKeys)->where('jenis', 'saran')->count(),
            'keluhan'   => Pengaduan::whereIn('tujuan_unit', $accessKeys)->where('jenis', 'keluhan')->count(),
        ];

        return view('admin.pengaduan.index', compact(
            'page', 'pengaduanList', 'stats', 'statsJenis', 'accessKeys'
        ));
    }

    /**
     * Show detail of a single complaint.
     */
    public function show(Request $request, $first, $second = null)
    {
        if ($second !== null) {
            $unitPage = $first instanceof UnitPage ? $first : UnitPage::findOrFail($first);
            $pengaduan = $second instanceof Pengaduan ? $second : Pengaduan::findOrFail($second);
        } else {
            $unitPage = null;
            $pengaduan = $first instanceof Pengaduan ? $first : Pengaduan::findOrFail($first);
        }

        ['accessKeys' => $accessKeys, 'page' => $page] = $this->resolveUnit($unitPage);
 
        abort_unless(in_array($pengaduan->tujuan_unit, $accessKeys, true), 403);
 
        // Mark as read
        $pengaduan->markDibaca();
 
        return view('admin.pengaduan.show', compact('pengaduan', 'page'));
    }
 
    /**
     * Update status and/or tindak lanjut.
     */
    public function update(Request $request, $first, $second = null)
    {
        if ($second !== null) {
            $unitPage = $first instanceof UnitPage ? $first : UnitPage::findOrFail($first);
            $pengaduan = $second instanceof Pengaduan ? $second : Pengaduan::findOrFail($second);
        } else {
            $unitPage = null;
            $pengaduan = $first instanceof Pengaduan ? $first : Pengaduan::findOrFail($first);
        }

        ['accessKeys' => $accessKeys, 'page' => $page] = $this->resolveUnit($unitPage);
 
        abort_unless(in_array($pengaduan->tujuan_unit, $accessKeys, true), 403);
 
        $validated = $request->validate([
            'status'           => 'required|in:masuk,diproses,selesai,ditolak',
            'tindak_lanjut'    => 'nullable|string|max:5000',
            'catatan_internal' => 'nullable|string|max:5000',
        ]);
 
        $updates = [
            'status'           => $validated['status'],
            'tindak_lanjut'    => $validated['tindak_lanjut'] ?? null,
            'catatan_internal' => $validated['catatan_internal'] ?? null,
            'ditangani_oleh'   => Auth::id(),
            'ditangani_at'     => now(),
        ];
 
        // Timestamp transitions
        if ($validated['status'] === 'diproses' && ! $pengaduan->diproses_at) {
            $updates['diproses_at'] = now();
        }
        if ($validated['status'] === 'selesai' && ! $pengaduan->diselesaikan_at) {
            $updates['diselesaikan_at'] = now();
        }
 
        $pengaduan->update($updates);
 
        $redirectRoute = $unitPage
            ? route('admin.pengaduan.unit.show', [$unitPage, $pengaduan])
            : route('admin.pengaduan.show', $pengaduan);
 
        return redirect($redirectRoute)->with('success', 'Status dan tindak lanjut berhasil diperbarui.');
    }
 
    /**
     * Delete a complaint (super admin only).
     */
    public function destroy($first, $second = null)
    {
        if ($second !== null) {
            $unitPage = $first instanceof UnitPage ? $first : UnitPage::findOrFail($first);
            $pengaduan = $second instanceof Pengaduan ? $second : Pengaduan::findOrFail($second);
        } else {
            $unitPage = null;
            $pengaduan = $first instanceof Pengaduan ? $first : Pengaduan::findOrFail($first);
        }

        abort_unless(Auth::user()->isSuperAdmin(), 403);
 
        $pengaduan->delete();
 
        $redirectRoute = $unitPage
            ? route('admin.pengaduan.unit.index', $unitPage)
            : route('admin.pengaduan.index');
 
        return redirect($redirectRoute)->with('success', 'Pengaduan berhasil dihapus.');
    }
}
