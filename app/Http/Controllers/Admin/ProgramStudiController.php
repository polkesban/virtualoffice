<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramStudi;
use App\Models\Jurusan;
use App\Models\UnitPage;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProgramStudiController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $q = ProgramStudi::with('jurusan')->orderBy('urutan');

        // Kaprodi hanya bisa lihat prodi miliknya
        if ($user->isKaprodi() && $user->program_studi_id) {
            $q->where('id', $user->program_studi_id);
        }

        // Kajur, Sekjur, Admin Jurusan hanya bisa lihat prodi di jurusannya
        if ($user->isJurusanAdmin() && $user->jurusan_id) {
            $q->where('jurusan_id', $user->jurusan_id);
        }

        if ($request->filled('jenjang')) {
            $q->where('jenjang', $request->jenjang);
        }

        $prodiList = $q->get();
        return view('admin.program-studi.index', compact('prodiList'));
    }

    public function create()
    {
        $jurusan = Jurusan::orderBy('nama')->get();
        return view('admin.program-studi.form', [
            'prodi' => new ProgramStudi(),
            'jurusan' => $jurusan,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'jurusan_id'       => 'nullable|exists:jurusan,id',
            'nama'             => 'required|string|max:255',
            'singkatan'        => 'nullable|string|max:20',
            'jenjang'          => 'required|in:D3,D4,Profesi,S1,S2',
            'icon_emoji'       => 'nullable|string|max:10',
            'deskripsi_singkat'=> 'nullable|string|max:500',
            'deskripsi'        => 'nullable|string',
            'akreditasi'       => 'nullable|string|max:30',
            'durasi_semester'  => 'nullable|integer|min:2|max:12',
            'kuota'            => 'nullable|integer|min:0',
            'gelar'            => 'nullable|string|max:50',
            'gambar'           => 'nullable|image|max:3072',
            'aktif'            => 'boolean',
            'urutan'           => 'integer|min:0',
        ]);

        $data['aktif']  = $request->boolean('aktif', true);
        $data['slug']   = Str::slug($data['nama']);
        $data['akreditasi'] = $data['akreditasi'] ?? 'B';
        $data['singkatan'] = $data['singkatan'] ?? '-';
        $data['durasi_semester'] = $data['durasi_semester'] ?? 8;
        $data['kuota'] = $data['kuota'] ?? 50;

        if ($request->hasFile('gambar')) {
            $data['gambar'] = MediaStorage::storeUploadedFile($request->file('gambar'), 'program-studi');
        }

        ProgramStudi::create($data);

        return redirect()->route('admin.program-studi.index')
            ->with('success', 'Program studi berhasil disimpan.');
    }

    public function edit(ProgramStudi $programStudi)
    {
        $this->authorizeProgramStudiAccess($programStudi);

        $jurusan = Jurusan::orderBy('nama')->get();
        return view('admin.program-studi.form', ['prodi' => $programStudi, 'jurusan' => $jurusan]);
    }

    public function update(Request $request, ProgramStudi $programStudi)
    {
        $this->authorizeProgramStudiAccess($programStudi);

        $data = $request->validate([
            'jurusan_id'       => 'nullable|exists:jurusan,id',
            'nama'             => 'required|string|max:255',
            'singkatan'        => 'nullable|string|max:20',
            'jenjang'          => 'required|in:D3,D4,Profesi,S1,S2',
            'icon_emoji'       => 'nullable|string|max:10',
            'deskripsi_singkat'=> 'nullable|string|max:500',
            'deskripsi'        => 'nullable|string',
            'akreditasi'       => 'nullable|string|max:30',
            'durasi_semester'  => 'nullable|integer|min:2|max:12',
            'kuota'            => 'nullable|integer|min:0',
            'gelar'            => 'nullable|string|max:50',
            'gambar'           => 'nullable|image|max:3072',
            'aktif'            => 'boolean',
            'urutan'           => 'integer|min:0',
        ]);

        $data['aktif'] = $request->boolean('aktif', true);
        
        $data['akreditasi'] = $data['akreditasi'] ?? 'B';
        $data['singkatan'] = $data['singkatan'] ?? '-';
        $data['durasi_semester'] = $data['durasi_semester'] ?? 8;
        $data['kuota'] = $data['kuota'] ?? 50;

        if ($request->hasFile('gambar')) {
            MediaStorage::delete($programStudi->gambar);
            $data['gambar'] = MediaStorage::storeUploadedFile($request->file('gambar'), 'program-studi');
        }

        $programStudi->update($data);

        return redirect()->route('admin.program-studi.index')
            ->with('success', 'Program studi berhasil diperbarui.');
    }

    public function manageContent(ProgramStudi $programStudi)
    {
        $this->authorizeProgramStudiAccess($programStudi);

        return redirect()->route('admin.halaman-unit.manage', $this->resolveOrCreateUnitPage($programStudi));
    }

    public function destroy(ProgramStudi $programStudi)
    {
        MediaStorage::delete($programStudi->gambar);
        $programStudi->delete();

        return back()->with('success', 'Program studi berhasil dihapus.');
    }

    private function authorizeProgramStudiAccess(ProgramStudi $programStudi): void
    {
        $user = Auth::user();

        if ($user->isKaprodi() && $user->program_studi_id !== $programStudi->id) {
            abort(403, 'Anda hanya dapat mengedit program studi Anda sendiri.');
        }

        if ($user->isJurusanAdmin() && $user->jurusan_id) {
            if ($programStudi->jurusan_id !== $user->jurusan_id) {
                abort(403, 'Anda hanya dapat mengelola program studi dalam jurusan Anda.');
            }
        }
    }

    private function resolveOrCreateUnitPage(ProgramStudi $programStudi): UnitPage
    {
        if ($programStudi->unitPage) {
            return $programStudi->unitPage;
        }

        $page = UnitPage::query()->firstOrCreate(
            ['role' => 'kaprodi_' . $programStudi->id],
            [
                'slug' => 'prodi-' . Str::slug($programStudi->slug ?: $programStudi->nama),
                'judul' => $programStudi->nama,
                'aktif' => true,
                'warna_tema' => '#1d4ed8',
            ]
        );

        if ((int) $programStudi->unit_page_id !== (int) $page->id) {
            $programStudi->update(['unit_page_id' => $page->id]);
        }

        return $page;
    }
}
