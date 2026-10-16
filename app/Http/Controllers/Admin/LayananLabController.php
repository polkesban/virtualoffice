<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LayananLab;
use Illuminate\Http\Request;

class LayananLabController extends Controller
{
    public function index(Request $request)
    {
        $q = LayananLab::orderBy('urutan')->orderBy('nama');

        if ($request->filled('kategori')) {
            $q->where('kategori', $request->kategori);
        }

        $layanan = $q->get();

        return view('admin.layanan-lab.index', compact('layanan'));
    }

    public function create()
    {
        $layanan = new LayananLab();
        return view('admin.layanan-lab.form', compact('layanan'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama'             => 'required|string|max:255',
            'deskripsi'        => 'nullable|string',
            'tarif'            => 'nullable|numeric|min:0',
            'satuan'           => 'nullable|string|max:50',
            'kategori'         => 'required|string|max:50',
            'lama_pengerjaan'  => 'nullable|string|max:100',
            'syarat'           => 'nullable|string',
            'urutan'           => 'nullable|integer|min:0|max:127',
            'aktif'            => 'boolean',
        ]);

        $data['aktif'] = $request->boolean('aktif');

        LayananLab::create($data);

        return redirect()->route('admin.layanan-lab.index')
            ->with('success', 'Layanan lab berhasil ditambahkan.');
    }

    public function edit(LayananLab $layananLab)
    {
        return view('admin.layanan-lab.form', ['layanan' => $layananLab]);
    }

    public function update(Request $request, LayananLab $layananLab)
    {
        $data = $request->validate([
            'nama'             => 'required|string|max:255',
            'deskripsi'        => 'nullable|string',
            'tarif'            => 'nullable|numeric|min:0',
            'satuan'           => 'nullable|string|max:50',
            'kategori'         => 'required|string|max:50',
            'lama_pengerjaan'  => 'nullable|string|max:100',
            'syarat'           => 'nullable|string',
            'urutan'           => 'nullable|integer|min:0|max:127',
            'aktif'            => 'boolean',
        ]);

        $data['aktif'] = $request->boolean('aktif');

        $layananLab->update($data);

        return redirect()->route('admin.layanan-lab.index')
            ->with('success', 'Layanan lab berhasil diperbarui.');
    }

    public function destroy(LayananLab $layananLab)
    {
        $layananLab->delete();
        return back()->with('success', 'Layanan dihapus.');
    }

    public function toggleAktif(LayananLab $layananLab)
    {
        $layananLab->update(['aktif' => !$layananLab->aktif]);
        return back()->with('success', 'Status diperbarui.');
    }
}
