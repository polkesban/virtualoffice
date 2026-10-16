<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\PortofolioMahasiswa;
use App\Support\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortofolioController extends Controller
{
    private function mahasiswa()
    {
        return Auth::guard('mahasiswa')->user();
    }

    public function index(): View
    {
        $mahasiswa  = $this->mahasiswa();
        $portofolio = $mahasiswa->portofolio()
            ->orderBy('tanggal', 'desc')
            ->get();

        return view('mahasiswa.portofolio.index', compact('mahasiswa', 'portofolio'));
    }

    public function create(): View
    {
        return view('mahasiswa.portofolio.form', [
            'mahasiswa' => $this->mahasiswa(),
            'item'      => null,
            'kategoriLabels' => PortofolioMahasiswa::$kategoriLabels,
            'tingkatLabels'  => PortofolioMahasiswa::$tingkatLabels,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateItem($request);
        $data = $this->handleUpload($request, $data);
        $this->mahasiswa()->portofolio()->create($data);

        return redirect()->route('mahasiswa.portofolio.index')
            ->with('success', 'Item portofolio berhasil ditambahkan.');
    }

    public function edit(PortofolioMahasiswa $portofolio): View|RedirectResponse
    {
        if ($portofolio->mahasiswa_id !== $this->mahasiswa()->id) {
            abort(403);
        }

        return view('mahasiswa.portofolio.form', [
            'mahasiswa'      => $this->mahasiswa(),
            'item'           => $portofolio,
            'kategoriLabels' => PortofolioMahasiswa::$kategoriLabels,
            'tingkatLabels'  => PortofolioMahasiswa::$tingkatLabels,
        ]);
    }

    public function update(Request $request, PortofolioMahasiswa $portofolio): RedirectResponse
    {
        if ($portofolio->mahasiswa_id !== $this->mahasiswa()->id) {
            abort(403);
        }

        $data = $this->validateItem($request);
        $data = $this->handleUpload($request, $data, $portofolio);
        $portofolio->update($data);

        return redirect()->route('mahasiswa.portofolio.index')
            ->with('success', 'Portofolio berhasil diperbarui.');
    }

    public function destroy(PortofolioMahasiswa $portofolio): RedirectResponse
    {
        if ($portofolio->mahasiswa_id !== $this->mahasiswa()->id) {
            abort(403);
        }

        MediaStorage::delete($portofolio->lampiran);
        $portofolio->delete();

        return back()->with('success', 'Item dihapus.');
    }

    /** Profile edit */
    public function editProfile(): View
    {
        $mahasiswa = $this->mahasiswa();
        $prodis    = \App\Models\ProgramStudi::where('aktif', true)
            ->whereIn('jenjang', ['D3', 'D4'])
            ->orderBy('jenjang', 'desc')->orderBy('urutan')->get();

        return view('mahasiswa.profil', compact('mahasiswa', 'prodis'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $mahasiswa = $this->mahasiswa();

        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'email'            => 'required|email|max:150|unique:mahasiswa,email,' . $mahasiswa->id,
            'no_hp'            => 'nullable|string|max:20',
            'program_studi_id' => 'required|exists:program_studi,id',
            'angkatan'         => 'required|integer|min:2015|max:' . (date('Y') + 1),
            'foto'             => 'nullable|image|max:2048',
            'password'         => 'nullable|string|min:8|confirmed',
        ]);

        if ($request->hasFile('foto')) {
            MediaStorage::delete($mahasiswa->foto);
            $data['foto'] = MediaStorage::storeUploadedFile($request->file('foto'), 'foto-mahasiswa');
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $mahasiswa->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    // ── helpers ────────────────────────────────────────────────────────────

    private function validateItem(Request $request): array
    {
        return $request->validate([
            'judul'        => 'required|string|max:200',
            'kategori'     => 'required|in:' . implode(',', array_keys(PortofolioMahasiswa::$kategoriLabels)),
            'deskripsi'    => 'nullable|string|max:2000',
            'institusi'    => 'nullable|string|max:200',
            'tanggal'      => 'required|date',
            'url_eksternal'=> 'nullable|url|max:500',
            'tingkat'      => 'required|in:' . implode(',', array_keys(PortofolioMahasiswa::$tingkatLabels)),
            'tampilkan'    => 'boolean',
        ]);
    }

    private function handleUpload(Request $request, array $data, ?PortofolioMahasiswa $existing = null): array
    {
        if ($request->hasFile('lampiran')) {
            MediaStorage::delete($existing?->lampiran);
            $data['lampiran'] = MediaStorage::storeUploadedFile($request->file('lampiran'), 'portofolio/' . $this->mahasiswa()->id);
        }
        $data['tampilkan'] = $request->boolean('tampilkan', true);
        return $data;
    }

    /** Daftar Kegiatan/PBM mahasiswa */
    public function kegiatan(): View
    {
        $mahasiswa = $this->mahasiswa();
        $nim = $mahasiswa->nim;
        
        $participants = \App\Models\UnitEventParticipant::with(['event.page'])
            ->where(function($query) use ($nim, $mahasiswa) {
                $query->whereRaw('LOWER(identity_number) = ?', [strtolower($nim)])
                      ->orWhere('linked_mahasiswa_id', $mahasiswa->id);
            })
            ->get();

        return view('mahasiswa.kegiatan', compact('mahasiswa', 'participants'));
    }
}
