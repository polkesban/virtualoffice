<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\MahasiswaBerprestasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MahasiswaBerprestasiController extends Controller
{
    public function index(): View
    {
        $items = MahasiswaBerprestasi::with(['mahasiswa.programStudi'])
            ->orderBy('aktif', 'desc')
            ->orderBy('periode')
            ->orderBy('urutan')
            ->get();

        return view('admin.berprestasi.index', compact('items'));
    }

    public function create(): View
    {
        $mahasiswaList = Mahasiswa::with('programStudi')->orderBy('name')->get();
        $periodeLabels = MahasiswaBerprestasi::$periodeLabels;
        return view('admin.berprestasi.form', compact('mahasiswaList', 'periodeLabels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        MahasiswaBerprestasi::create($data);
        return redirect()->route('admin.berprestasi.index')
            ->with('success', 'Mahasiswa berprestasi berhasil ditambahkan.');
    }

    public function edit(MahasiswaBerprestasi $berprestasi): View
    {
        $mahasiswaList = Mahasiswa::with('programStudi')->orderBy('name')->get();
        $periodeLabels = MahasiswaBerprestasi::$periodeLabels;
        return view('admin.berprestasi.form', compact('berprestasi', 'mahasiswaList', 'periodeLabels'));
    }

    public function update(Request $request, MahasiswaBerprestasi $berprestasi): RedirectResponse
    {
        $berprestasi->update($this->validated($request));
        return redirect()->route('admin.berprestasi.index')
            ->with('success', 'Data diperbarui.');
    }

    public function destroy(MahasiswaBerprestasi $berprestasi): RedirectResponse
    {
        $berprestasi->delete();
        return back()->with('success', 'Data dihapus.');
    }

    /** Quick-toggle aktif via PATCH */
    public function toggle(MahasiswaBerprestasi $berprestasi): RedirectResponse
    {
        $berprestasi->update(['aktif' => ! $berprestasi->aktif]);
        return back()->with('success', 'Status berhasil diubah.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'mahasiswa_id'  => 'required|exists:mahasiswa,id',
            'periode'       => 'required|in:bulan,semester,tahun',
            'periode_label' => 'required|string|max:60',
            'catatan_admin' => 'nullable|string|max:1000',
            'aktif'         => 'boolean',
            'urutan'        => 'integer|min:0|max:255',
        ]);
    }
}
