<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LayananTI;
use Illuminate\Http\Request;

class LayananTIController extends Controller
{
    public function index(Request $request)
    {
        $q = LayananTI::orderBy('urutan')->orderBy('nama');
        if ($request->filled('kategori')) $q->where('kategori', $request->kategori);
        $items = $q->get();
        return view('admin.layanan-ti.index', compact('items'));
    }

    public function create()
    {
        return view('admin.layanan-ti.form', ['item' => new LayananTI()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama'           => 'required|string|max:200',
            'kategori'       => 'required|in:jaringan,hardware,software,akun,email,lainnya',
            'deskripsi'      => 'nullable|string',
            'prosedur'       => 'nullable|string',
            'kontak'         => 'nullable|string|max:100',
            'estimasi_waktu' => 'nullable|string|max:100',
            'aktif'          => 'boolean',
            'urutan'         => 'nullable|integer|min:0|max:127',
        ]);

        $data['aktif'] = $request->boolean('aktif');
        LayananTI::create($data);

        return redirect()->route('admin.layanan-ti.index')
            ->with('success', 'Layanan TI berhasil ditambahkan.');
    }

    public function edit(LayananTI $layananTI)
    {
        return view('admin.layanan-ti.form', ['item' => $layananTI]);
    }

    public function update(Request $request, LayananTI $layananTI)
    {
        $data = $request->validate([
            'nama'           => 'required|string|max:200',
            'kategori'       => 'required|in:jaringan,hardware,software,akun,email,lainnya',
            'deskripsi'      => 'nullable|string',
            'prosedur'       => 'nullable|string',
            'kontak'         => 'nullable|string|max:100',
            'estimasi_waktu' => 'nullable|string|max:100',
            'aktif'          => 'boolean',
            'urutan'         => 'nullable|integer|min:0|max:127',
        ]);

        $data['aktif'] = $request->boolean('aktif');
        $layananTI->update($data);

        return redirect()->route('admin.layanan-ti.index')
            ->with('success', 'Layanan TI berhasil diperbarui.');
    }

    public function destroy(LayananTI $layananTI)
    {
        $layananTI->delete();
        return back()->with('success', 'Layanan TI dihapus.');
    }
}
