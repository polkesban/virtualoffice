<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PengumumanController extends Controller
{
    public function index(Request $request)
    {
        $q = Pengumuman::with('penulis')->latest();

        if ($request->filled('prioritas')) {
            $q->where('prioritas', $request->prioritas);
        }
        if ($request->filled('aktif')) {
            $q->where('aktif', $request->aktif);
        }
        if ($request->filled('cari')) {
            $q->where('judul', 'like', '%' . $request->cari . '%');
        }

        $pengumuman = $q->get();

        return view('admin.pengumuman.index', compact('pengumuman'));
    }

    public function create()
    {
        return view('admin.pengumuman.form', ['pengumuman' => new Pengumuman()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul'           => 'required|string|max:255',
            'isi'             => 'required|string',
            'prioritas'       => 'required|in:normal,penting,urgent',
            'aktif'           => 'boolean',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'file_lampiran'   => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $data['user_id'] = Auth::id();
        $data['aktif']   = $request->boolean('aktif');
        $data['slug']    = Str::slug($data['judul']) . '-' . Str::random(5);

        if ($request->hasFile('file_lampiran')) {
            $data['file_lampiran'] = MediaStorage::storeUploadedFile($request->file('file_lampiran'), 'pengumuman');
        }

        Pengumuman::create($data);

        return redirect()->route('admin.pengumuman.index')
            ->with('success', 'Pengumuman berhasil disimpan.');
    }

    private function authorizeOwner(Pengumuman $pengumuman): void
    {
        if (!Auth::user()->isSuperAdmin() && $pengumuman->user_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah pengumuman ini.');
        }
    }

    public function edit(Pengumuman $pengumuman)
    {
        $this->authorizeOwner($pengumuman);
        return view('admin.pengumuman.form', compact('pengumuman'));
    }

    public function update(Request $request, Pengumuman $pengumuman)
    {
        $this->authorizeOwner($pengumuman);
        $data = $request->validate([
            'judul'           => 'required|string|max:255',
            'isi'             => 'required|string',
            'prioritas'       => 'required|in:normal,penting,urgent',
            'aktif'           => 'boolean',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'file_lampiran'   => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $data['aktif'] = $request->boolean('aktif');

        if ($request->hasFile('file_lampiran')) {
            MediaStorage::delete($pengumuman->file_lampiran);
            $data['file_lampiran'] = MediaStorage::storeUploadedFile($request->file('file_lampiran'), 'pengumuman');
        }

        $pengumuman->update($data);

        return redirect()->route('admin.pengumuman.index')
            ->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Pengumuman $pengumuman)
    {
        $this->authorizeOwner($pengumuman);
        MediaStorage::delete($pengumuman->file_lampiran);
        $pengumuman->delete();

        return back()->with('success', 'Pengumuman berhasil dihapus.');
    }

    public function toggleAktif(Pengumuman $pengumuman)
    {
        $this->authorizeOwner($pengumuman);
        $pengumuman->update(['aktif' => !$pengumuman->aktif]);
        return back()->with('success', 'Status berhasil diubah.');
    }
}
