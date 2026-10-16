<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KoleksiPerpus;
use App\Support\MediaStorage;
use Illuminate\Http\Request;

class KoleksiPerpusController extends Controller
{
    public function index(Request $request)
    {
        $q = KoleksiPerpus::orderBy('judul');
        if ($request->filled('kategori')) $q->where('kategori', $request->kategori);
        if ($request->filled('q')) {
            $q->where(fn($s) => $s->where('judul', 'like', '%'.$request->q.'%')
                ->orWhere('penulis', 'like', '%'.$request->q.'%')
                ->orWhere('isbn', 'like', '%'.$request->q.'%'));
        }
        $items = $q->get();
        return view('admin.koleksi-perpus.index', compact('items'));
    }

    public function create()
    {
        return view('admin.koleksi-perpus.form', ['item' => new KoleksiPerpus()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul'           => 'required|string|max:300',
            'penulis'         => 'required|string|max:300',
            'penerbit'        => 'nullable|string|max:200',
            'tahun_terbit'    => 'nullable|integer|min:1900|max:2099',
            'isbn'            => 'nullable|string|max:30',
            'kategori'        => 'required|string|max:50',
            'stok_total'      => 'nullable|integer|min:0',
            'stok_tersedia'   => 'nullable|integer|min:0',
            'lokasi_rak'      => 'nullable|string|max:50',
            'cover'           => 'nullable|image|max:2048',
            'deskripsi'       => 'nullable|string',
            'link_digital'    => 'nullable|url|max:500',
            'aktif'           => 'boolean',
        ]);

        $data['aktif'] = $request->boolean('aktif');
        if ($request->hasFile('cover')) {
            $data['cover'] = MediaStorage::storeUploadedFile($request->file('cover'), 'perpus');
        }

        KoleksiPerpus::create($data);

        return redirect()->route('admin.koleksi-perpus.index')
            ->with('success', 'Koleksi berhasil ditambahkan.');
    }

    public function edit(KoleksiPerpus $koleksiPerpus)
    {
        return view('admin.koleksi-perpus.form', ['item' => $koleksiPerpus]);
    }

    public function update(Request $request, KoleksiPerpus $koleksiPerpus)
    {
        $data = $request->validate([
            'judul'           => 'required|string|max:300',
            'penulis'         => 'required|string|max:300',
            'penerbit'        => 'nullable|string|max:200',
            'tahun_terbit'    => 'nullable|integer|min:1900|max:2099',
            'isbn'            => 'nullable|string|max:30',
            'kategori'        => 'required|string|max:50',
            'stok_total'      => 'nullable|integer|min:0',
            'stok_tersedia'   => 'nullable|integer|min:0',
            'lokasi_rak'      => 'nullable|string|max:50',
            'cover'           => 'nullable|image|max:2048',
            'deskripsi'       => 'nullable|string',
            'link_digital'    => 'nullable|url|max:500',
            'aktif'           => 'boolean',
        ]);

        $data['aktif'] = $request->boolean('aktif');
        if ($request->hasFile('cover')) {
            MediaStorage::delete($koleksiPerpus->cover);
            $data['cover'] = MediaStorage::storeUploadedFile($request->file('cover'), 'perpus');
        }

        $koleksiPerpus->update($data);

        return redirect()->route('admin.koleksi-perpus.index')
            ->with('success', 'Koleksi berhasil diperbarui.');
    }

    public function destroy(KoleksiPerpus $koleksiPerpus)
    {
        MediaStorage::delete($koleksiPerpus->cover);
        $koleksiPerpus->delete();
        return back()->with('success', 'Koleksi dihapus.');
    }
}
