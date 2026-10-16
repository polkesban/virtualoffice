<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pelatihan;
use App\Support\MediaStorage;
use Illuminate\Http\Request;

class PelatihanController extends Controller
{
    public function index(Request $request)
    {
        $q = Pelatihan::orderByDesc('tanggal_mulai');
        if ($request->filled('jenis')) $q->where('jenis', $request->jenis);
        if ($request->filled('tahun')) $q->whereYear('tanggal_mulai', $request->tahun);
        $items = $q->get();
        return view('admin.pelatihan.index', compact('items'));
    }

    public function create()
    {
        return view('admin.pelatihan.form', ['item' => new Pelatihan()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul'           => 'required|string|max:300',
            'jenis'           => 'required|in:pelatihan,workshop,seminar,bimtek,studi_banding,lainnya',
            'penyelenggara'   => 'nullable|string|max:200',
            'tanggal_mulai'   => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
            'lokasi'          => 'nullable|string|max:200',
            'deskripsi'       => 'nullable|string',
            'peserta_target'  => 'nullable|string|max:200',
            'jumlah_peserta'  => 'nullable|integer|min:0',
            'hasil'           => 'nullable|string',
            'foto'            => 'nullable|image|max:3072',
            'aktif'           => 'boolean',
        ]);

        $data['aktif'] = $request->boolean('aktif');
        if ($request->hasFile('foto')) {
            $data['foto'] = MediaStorage::storeUploadedFile($request->file('foto'), 'pelatihan');
        }

        Pelatihan::create($data);

        return redirect()->route('admin.pelatihan.index')
            ->with('success', 'Program pelatihan berhasil ditambahkan.');
    }

    public function edit(Pelatihan $pelatihan)
    {
        return view('admin.pelatihan.form', ['item' => $pelatihan]);
    }

    public function update(Request $request, Pelatihan $pelatihan)
    {
        $data = $request->validate([
            'judul'           => 'required|string|max:300',
            'jenis'           => 'required|in:pelatihan,workshop,seminar,bimtek,studi_banding,lainnya',
            'penyelenggara'   => 'nullable|string|max:200',
            'tanggal_mulai'   => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
            'lokasi'          => 'nullable|string|max:200',
            'deskripsi'       => 'nullable|string',
            'peserta_target'  => 'nullable|string|max:200',
            'jumlah_peserta'  => 'nullable|integer|min:0',
            'hasil'           => 'nullable|string',
            'foto'            => 'nullable|image|max:3072',
            'aktif'           => 'boolean',
        ]);

        $data['aktif'] = $request->boolean('aktif');
        if ($request->hasFile('foto')) {
            MediaStorage::delete($pelatihan->foto);
            $data['foto'] = MediaStorage::storeUploadedFile($request->file('foto'), 'pelatihan');
        }

        $pelatihan->update($data);

        return redirect()->route('admin.pelatihan.index')
            ->with('success', 'Program pelatihan berhasil diperbarui.');
    }

    public function destroy(Pelatihan $pelatihan)
    {
        MediaStorage::delete($pelatihan->foto);
        $pelatihan->delete();
        return back()->with('success', 'Program pelatihan dihapus.');
    }
}
