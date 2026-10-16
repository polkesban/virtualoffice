<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FasilitasAsrama;
use App\Support\MediaStorage;
use Illuminate\Http\Request;

class FasilitasAsramaController extends Controller
{
    public function index(Request $request)
    {
        $q = FasilitasAsrama::orderBy('urutan')->orderBy('nama');
        if ($request->filled('jenis')) $q->where('jenis', $request->jenis);
        $items = $q->get();
        return view('admin.fasilitas-asrama.index', compact('items'));
    }

    public function create()
    {
        return view('admin.fasilitas-asrama.form', ['item' => new FasilitasAsrama()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama'               => 'required|string|max:100',
            'jenis'              => 'required|in:putra,putri,mix',
            'lantai'             => 'nullable|integer|min:1|max:20',
            'kapasitas'          => 'required|integer|min:1|max:100',
            'terisi'             => 'nullable|integer|min:0',
            'harga_per_bulan'    => 'nullable|numeric|min:0',
            'harga_per_semester' => 'nullable|numeric|min:0',
            'fasilitas'          => 'nullable|string|max:500',
            'keterangan'         => 'nullable|string',
            'foto'               => 'nullable|image|max:2048',
            'aktif'              => 'boolean',
            'urutan'             => 'nullable|integer|min:0|max:127',
        ]);

        $data['aktif'] = $request->boolean('aktif');
        if ($request->hasFile('foto')) {
            $data['foto'] = MediaStorage::storeUploadedFile($request->file('foto'), 'asrama');
        }

        FasilitasAsrama::create($data);

        return redirect()->route('admin.fasilitas-asrama.index')
            ->with('success', 'Fasilitas/kamar berhasil ditambahkan.');
    }

    public function edit(FasilitasAsrama $fasilitasAsrama)
    {
        return view('admin.fasilitas-asrama.form', ['item' => $fasilitasAsrama]);
    }

    public function update(Request $request, FasilitasAsrama $fasilitasAsrama)
    {
        $data = $request->validate([
            'nama'               => 'required|string|max:100',
            'jenis'              => 'required|in:putra,putri,mix',
            'lantai'             => 'nullable|integer|min:1|max:20',
            'kapasitas'          => 'required|integer|min:1|max:100',
            'terisi'             => 'nullable|integer|min:0',
            'harga_per_bulan'    => 'nullable|numeric|min:0',
            'harga_per_semester' => 'nullable|numeric|min:0',
            'fasilitas'          => 'nullable|string|max:500',
            'keterangan'         => 'nullable|string',
            'foto'               => 'nullable|image|max:2048',
            'aktif'              => 'boolean',
            'urutan'             => 'nullable|integer|min:0|max:127',
        ]);

        $data['aktif'] = $request->boolean('aktif');
        if ($request->hasFile('foto')) {
            MediaStorage::delete($fasilitasAsrama->foto);
            $data['foto'] = MediaStorage::storeUploadedFile($request->file('foto'), 'asrama');
        }

        $fasilitasAsrama->update($data);

        return redirect()->route('admin.fasilitas-asrama.index')
            ->with('success', 'Fasilitas/kamar berhasil diperbarui.');
    }

    public function destroy(FasilitasAsrama $fasilitasAsrama)
    {
        MediaStorage::delete($fasilitasAsrama->foto);
        $fasilitasAsrama->delete();
        return back()->with('success', 'Fasilitas/kamar dihapus.');
    }
}
