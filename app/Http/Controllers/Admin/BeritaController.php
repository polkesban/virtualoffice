<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Berita;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BeritaController extends Controller
{
    public function index(Request $request)
    {
        $q = Berita::with('penulis')->latest('published_at');

        if ($request->filled('kategori')) {
            $q->where('kategori', $request->kategori);
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('cari')) {
            $q->where('judul', 'like', '%' . $request->cari . '%');
        }

        $berita = $q->get();

        return view('admin.berita.index', compact('berita'));
    }

    public function create()
    {
        return view('admin.berita.form', ['berita' => new Berita()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul'            => 'required|string|max:255',
            'ringkasan'        => 'nullable|string|max:500',
            'isi'              => 'required|string',
            'kategori'         => 'required|in:prestasi,pengumuman,kegiatan,akademik,umum',
            'status'           => 'required|in:draft,published',
            'published_at'     => 'nullable|date',
            'gambar_sampul'    => 'nullable|image|max:3072',
            'gambar_sampul_alt'=> 'nullable|string|max:255',
            'tags'             => 'nullable|string',
            'meta_description' => 'nullable|string|max:255',
        ]);

        $data['user_id'] = Auth::id();
        $data['slug'] = Str::slug($data['judul']) . '-' . Str::random(5);
        $data['tags'] = $data['tags'] ? array_map('trim', explode(',', $data['tags'])) : [];

        if ($request->hasFile('gambar_sampul')) {
            $data['gambar_sampul'] = MediaStorage::storeUploadedFile($request->file('gambar_sampul'), 'berita');
        }

        if (empty($data['published_at']) && $data['status'] === 'published') {
            $data['published_at'] = now();
        }

        Berita::create($data);

        return redirect()->route('admin.berita.index')
            ->with('success', 'Berita berhasil disimpan.');
    }

    private function authorizeOwner(Berita $berita): void
    {
        if (!Auth::user()->isSuperAdmin() && $berita->user_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah berita ini.');
        }
    }

    public function edit(Berita $berita)
    {
        $this->authorizeOwner($berita);
        return view('admin.berita.form', compact('berita'));
    }

    public function update(Request $request, Berita $berita)
    {
        $this->authorizeOwner($berita);
        $data = $request->validate([
            'judul'            => 'required|string|max:255',
            'ringkasan'        => 'nullable|string|max:500',
            'isi'              => 'required|string',
            'kategori'         => 'required|in:prestasi,pengumuman,kegiatan,akademik,umum',
            'status'           => 'required|in:draft,published',
            'published_at'     => 'nullable|date',
            'gambar_sampul'    => 'nullable|image|max:3072',
            'gambar_sampul_alt'=> 'nullable|string|max:255',
            'tags'             => 'nullable|string',
            'meta_description' => 'nullable|string|max:255',
        ]);

        $data['tags'] = $data['tags'] ? array_map('trim', explode(',', $data['tags'])) : [];

        if ($request->hasFile('gambar_sampul')) {
            MediaStorage::delete($berita->gambar_sampul);
            $data['gambar_sampul'] = MediaStorage::storeUploadedFile($request->file('gambar_sampul'), 'berita');
        }

        if (empty($data['published_at']) && $data['status'] === 'published' && !$berita->published_at) {
            $data['published_at'] = now();
        }

        $berita->update($data);

        return redirect()->route('admin.berita.index')
            ->with('success', 'Berita berhasil diperbarui.');
    }

    public function destroy(Berita $berita)
    {
        $this->authorizeOwner($berita);
        MediaStorage::delete($berita->gambar_sampul);
        $berita->delete();

        return back()->with('success', 'Berita berhasil dihapus.');
    }
}
