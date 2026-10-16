<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DokumenMutu;
use Illuminate\Http\Request;

class DokumenMutuController extends Controller
{
    public function index(Request $request)
    {
        $q = DokumenMutu::orderByDesc('tanggal_terbit')->orderBy('judul');
        if ($request->filled('jenis')) $q->where('jenis', $request->jenis);
        $items = $q->get();
        return view('admin.dokumen-mutu.index', compact('items'));
    }

    public function create()
    {
        return view('admin.dokumen-mutu.form', ['item' => new DokumenMutu()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul'          => 'required|string|max:300',
            'jenis'          => 'required|in:kebijakan,standar,prosedur,formulir,akreditasi,lap_audit,lainnya',
            'nomor_dok'      => 'nullable|string|max:50',
            'tanggal_terbit' => 'nullable|date',
            'berlaku_sampai' => 'nullable|date|after_or_equal:tanggal_terbit',
            'versi'          => 'nullable|string|max:20',
            'file_url'       => 'nullable|url|max:500',
            'keterangan'     => 'nullable|string',
            'aktif'          => 'boolean',
        ]);

        $data['aktif'] = $request->boolean('aktif');
        DokumenMutu::create($data);

        return redirect()->route('admin.dokumen-mutu.index')
            ->with('success', 'Dokumen berhasil ditambahkan.');
    }

    public function edit(DokumenMutu $dokumenMutu)
    {
        return view('admin.dokumen-mutu.form', ['item' => $dokumenMutu]);
    }

    public function update(Request $request, DokumenMutu $dokumenMutu)
    {
        $data = $request->validate([
            'judul'          => 'required|string|max:300',
            'jenis'          => 'required|in:kebijakan,standar,prosedur,formulir,akreditasi,lap_audit,lainnya',
            'nomor_dok'      => 'nullable|string|max:50',
            'tanggal_terbit' => 'nullable|date',
            'berlaku_sampai' => 'nullable|date|after_or_equal:tanggal_terbit',
            'versi'          => 'nullable|string|max:20',
            'file_url'       => 'nullable|url|max:500',
            'keterangan'     => 'nullable|string',
            'aktif'          => 'boolean',
        ]);

        $data['aktif'] = $request->boolean('aktif');
        $dokumenMutu->update($data);

        return redirect()->route('admin.dokumen-mutu.index')
            ->with('success', 'Dokumen berhasil diperbarui.');
    }

    public function destroy(DokumenMutu $dokumenMutu)
    {
        $dokumenMutu->delete();
        return back()->with('success', 'Dokumen dihapus.');
    }
}
