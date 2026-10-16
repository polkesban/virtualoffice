<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Galeri;
use App\Support\MediaStorage;
use Illuminate\Http\Request;

class GaleriController extends Controller
{
    public function index(Request $request)
    {
        $galeri = Galeri::orderBy('urutan')->orderByDesc('id')->get();
        return view('admin.galeri.index', compact('galeri'));
    }

    public function create()
    {
        return view('admin.galeri.form', ['galeri' => new Galeri()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul'    => 'required|string|max:255',
            'file'     => 'required|image|max:4096',
            'alt'      => 'nullable|string|max:255',
            'kategori' => 'required|in:umum,kegiatan,akademik,kampus,wisuda',
            'tampil'   => 'boolean',
            'urutan'   => 'integer|min:0|max:255',
        ]);

        $path = MediaStorage::storeUploadedFile($request->file('file'), 'galeri');

        Galeri::create([
            'judul'    => $request->judul,
            'file'     => $path,
            'alt'      => $request->alt ?? $request->judul,
            'kategori' => $request->kategori,
            'tampil'   => $request->boolean('tampil', true),
            'urutan'   => $request->input('urutan', 0),
        ]);

        return redirect()->route('admin.galeri.index')
            ->with('success', 'Foto berhasil diunggah.');
    }

    public function edit(Galeri $galeri)
    {
        return view('admin.galeri.form', compact('galeri'));
    }

    public function update(Request $request, Galeri $galeri)
    {
        $request->validate([
            'judul'    => 'required|string|max:255',
            'file'     => 'nullable|image|max:4096',
            'alt'      => 'nullable|string|max:255',
            'kategori' => 'required|in:umum,kegiatan,akademik,kampus,wisuda',
            'tampil'   => 'boolean',
            'urutan'   => 'integer|min:0|max:255',
        ]);

        $data = $request->only(['judul', 'alt', 'kategori', 'urutan']);
        $data['tampil'] = $request->boolean('tampil', true);

        if ($request->hasFile('file')) {
            MediaStorage::delete($galeri->file, 'galeri');
            $data['file'] = MediaStorage::storeUploadedFile($request->file('file'), 'galeri');
        }

        $galeri->update($data);

        return redirect()->route('admin.galeri.index')
            ->with('success', 'Foto berhasil diperbarui.');
    }

    public function destroy(Galeri $galeri)
    {
        MediaStorage::delete($galeri->file, 'galeri');
        $galeri->delete();

        return back()->with('success', 'Foto berhasil dihapus.');
    }

    public function toggleTampil(Galeri $galeri)
    {
        $galeri->update(['tampil' => !$galeri->tampil]);
        return back()->with('success', 'Status berhasil diubah.');
    }
}
