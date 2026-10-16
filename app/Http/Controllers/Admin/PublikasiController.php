<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Publikasi;
use Illuminate\Http\Request;

class PublikasiController extends Controller
{
    public function index(Request $request)
    {
        $q = Publikasi::orderByDesc('tahun')->orderBy('judul');
        if ($request->filled('jenis'))  $q->where('jenis', $request->jenis);
        if ($request->filled('tahun'))  $q->where('tahun', $request->tahun);
        if ($request->filled('q')) {
            $q->where(fn($s) => $s->where('judul', 'like', '%'.$request->q.'%')
                ->orWhere('penulis', 'like', '%'.$request->q.'%'));
        }
        $items = $q->get();
        return view('admin.publikasi.index', compact('items'));
    }

    public function create()
    {
        return view('admin.publikasi.form', ['item' => new Publikasi()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul'      => 'required|string|max:500',
            'penulis'    => 'required|string|max:500',
            'institusi'  => 'nullable|string|max:200',
            'jenis'      => 'required|in:jurnal,prosiding,skripsi,tesis,disertasi,laporan,buku',
            'tahun'      => 'nullable|integer|min:1990|max:2099',
            'volume'     => 'nullable|string|max:20',
            'nomor'      => 'nullable|string|max:20',
            'halaman'    => 'nullable|string|max:30',
            'doi'        => 'nullable|string|max:200',
            'abstrak'    => 'nullable|string',
            'file_url'   => 'nullable|url|max:500',
            'link_url'   => 'nullable|url|max:500',
            'aktif'      => 'boolean',
        ]);

        $data['aktif'] = $request->boolean('aktif');
        Publikasi::create($data);

        return redirect()->route('admin.publikasi.index')
            ->with('success', 'Publikasi berhasil ditambahkan.');
    }

    public function edit(Publikasi $publikasi)
    {
        return view('admin.publikasi.form', ['item' => $publikasi]);
    }

    public function update(Request $request, Publikasi $publikasi)
    {
        $data = $request->validate([
            'judul'      => 'required|string|max:500',
            'penulis'    => 'required|string|max:500',
            'institusi'  => 'nullable|string|max:200',
            'jenis'      => 'required|in:jurnal,prosiding,skripsi,tesis,disertasi,laporan,buku',
            'tahun'      => 'nullable|integer|min:1990|max:2099',
            'volume'     => 'nullable|string|max:20',
            'nomor'      => 'nullable|string|max:20',
            'halaman'    => 'nullable|string|max:30',
            'doi'        => 'nullable|string|max:200',
            'abstrak'    => 'nullable|string',
            'file_url'   => 'nullable|url|max:500',
            'link_url'   => 'nullable|url|max:500',
            'aktif'      => 'boolean',
        ]);

        $data['aktif'] = $request->boolean('aktif');
        $publikasi->update($data);

        return redirect()->route('admin.publikasi.index')
            ->with('success', 'Publikasi berhasil diperbarui.');
    }

    public function destroy(Publikasi $publikasi)
    {
        $publikasi->delete();
        return back()->with('success', 'Publikasi dihapus.');
    }
}
