<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\MahasiswaObeEvidence;
use App\Models\MahasiswaObePortofolio;
use App\Support\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MahasiswaObePortofolioController extends Controller
{
    private function mahasiswa()
    {
        return Auth::guard('mahasiswa')->user();
    }

    public function index(): View
    {
        $mahasiswa = $this->mahasiswa();
        $list = $mahasiswa->obePortofolios()
            ->withCount('evidences')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('mahasiswa.obe.index', compact('mahasiswa', 'list'));
    }

    public function create(): View
    {
        $mahasiswa = $this->mahasiswa();
        $mahasiswa->load('programStudi');

        // Preset data awal sesuai template OBE Gizi / Kesehatan Poltekkes Bandung
        $defaultCpl = [
            ['kode' => 'CPL 1', 'deskripsi' => 'Mampu melaksanakan pelayanan asuhan gizi pada daur kehidupan dengan keunggulan pada penyakit diabetes mellitus sesuai standar profesi dan sikap bertanggungjawab dan taat hukum.'],
            ['kode' => 'CPL 2', 'deskripsi' => 'Mampu melaksanakan program gizi masyarakat dengan keunggulan pengendalian faktor risiko penyakit diabetes mellitus, sesuai dengan standar profesi, dilandasi kerjasama lintas program dan lintas sektor.'],
            ['kode' => 'CPL 3', 'deskripsi' => 'Mampu melaksanakan penyelenggaraan makanan institusi dengan keunggulan pada penyakit diabetes mellitus sesuai standar profesi, dan menunjukkan sikap tanggung jawab.'],
            ['kode' => 'CPL 4', 'deskripsi' => 'Mampu melaksanakan promosi gizi untuk pengendalian faktor risiko penyakit diabetes mellitus sesuai standar profesi dengan menunjukkan sikap menghargai keanekaragaman budaya.'],
            ['kode' => 'CPL 5', 'deskripsi' => 'Mampu menerapkan literasi IPTEKS gizi dalam pengendalian faktor risiko masalah gizi dengan keunggulan pada penyakit diabetes mellitus sesuai standar profesi secara profesional.'],
        ];

        $defaultCpmk = [
            ['kode' => 'CPMK 1', 'bobot' => 50, 'deskripsi' => 'Mahasiswa mampu mengaplikasikan asuhan gizi terstandar pada klien.'],
            ['kode' => 'CPMK 2', 'bobot' => 50, 'deskripsi' => 'Mahasiswa mampu merancang dan mengevaluasi program promosi kesehatan.'],
        ];

        $defaultSubCpmk = [
            'Sub CPMK 1.1: Memahami mekanisme intervensi gizi',
            'Sub CPMK 1.2: Melakukan pengkajian status gizi (nutritional assessment)',
            'Sub CPMK 2.1: Merancang media promosi kesehatan gizi (leaflet/poster)',
            'Sub CPMK 2.2: Melakukan pendokumentasian logbook harian',
        ];

        $defaultMateri = [
            'Asuhan Gizi Terstandar (PAGT)',
            'Pengkajian Status Gizi Pasien',
            'Perencanaan Diet & Konseling Gizi',
            'Penyelenggaraan Makanan Institusi',
            'Promosi Gizi & Pembuatan Media KIE',
        ];

        $defaultBobotPenilaian = [
            ['komponen' => 'Aktivitas Partisipatif', 'persentase' => 50],
            ['komponen' => 'Tugas', 'persentase' => 10],
            ['komponen' => 'Kuis', 'persentase' => 10],
            ['komponen' => 'ETS (Evaluasi Tengah Semester)', 'persentase' => 15],
            ['komponen' => 'EAS (Evaluasi Akhir Semester)', 'persentase' => 15],
        ];

        return view('mahasiswa.obe.form', [
            'mahasiswa' => $mahasiswa,
            'obe' => null,
            'defaultCpl' => $defaultCpl,
            'defaultCpmk' => $defaultCpmk,
            'defaultSubCpmk' => $defaultSubCpmk,
            'defaultMateri' => $defaultMateri,
            'defaultBobotPenilaian' => $defaultBobotPenilaian,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $mahasiswa = $this->mahasiswa();

        $validated = $request->validate([
            'tahun_akademik' => 'required|string|max:20',
            'semester' => 'required|integer|min:1|max:14',
            'kelas' => 'nullable|string|max:50',
            'dosen_pa' => 'nullable|string|max:150',
            'lahan_praktik' => 'nullable|string|max:200',
            'pembimbing_lahan' => 'nullable|string|max:150',
            'ketua_jurusan' => 'nullable|string|max:150',
            'nip_ketua_jurusan' => 'nullable|string|max:50',
            'ketua_prodi' => 'nullable|string|max:150',
            'nip_ketua_prodi' => 'nullable|string|max:50',
            'kode_mk' => 'required|string|max:50',
            'nama_mk' => 'required|string|max:200',
            'sks' => 'required|integer|min:1|max:10',
            'deskripsi_mk' => 'nullable|string',
            'metode_pembelajaran' => 'nullable|string',
            'metode_penilaian' => 'nullable|string',
            'nilai_angka' => 'nullable|numeric|min:0|max:100',
            'nilai_mutu' => 'nullable|numeric|min:0|max:4',
            'nilai_lambang' => 'nullable|string|max:5',
            'kelebihan_mahasiswa' => 'nullable|string',
            'aspek_perlu_ditingkatkan' => 'nullable|string',
            'rekomendasi_pembimbing' => 'nullable|string',
        ]);

        $validated['cpl_list'] = $request->input('cpl_list', []);
        $validated['cpmk_list'] = $request->input('cpmk_list', []);
        $validated['sub_cpmk_list'] = array_filter($request->input('sub_cpmk_list', []));
        $validated['materi_pembelajaran'] = array_filter($request->input('materi_pembelajaran', []));
        $validated['bobot_penilaian'] = $request->input('bobot_penilaian', []);

        $obe = $mahasiswa->obePortofolios()->create($validated);

        $defaultEvidences = [
            'Laporan Studi Kasus',
            'Log Book Harian / Praktik',
            'Leaflet / Media Edukasi',
            'Laporan Presentasi Kelompok',
            'Dokumentasi Foto / Video Kegiatan',
        ];

        foreach ($defaultEvidences as $dokumen) {
            $obe->evidences()->create([
                'nama_dokumen' => $dokumen,
                'is_ada' => false,
            ]);
        }

        return redirect()->route('mahasiswa.obe.edit', $obe->id)
            ->with('success', 'Buku Portofolio OBE berhasil dibuat! Silakan upload evidence/bukti dokumen jika ada.');
    }

    public function edit(MahasiswaObePortofolio $obe): View|RedirectResponse
    {
        if ($obe->mahasiswa_id !== $this->mahasiswa()->id) {
            abort(403);
        }

        $mahasiswa = $this->mahasiswa();
        $mahasiswa->load('programStudi');
        $obe->load('evidences');

        return view('mahasiswa.obe.form', [
            'mahasiswa' => $mahasiswa,
            'obe' => $obe,
            'defaultCpl' => $obe->cpl_list ?? [],
            'defaultCpmk' => $obe->cpmk_list ?? [],
            'defaultSubCpmk' => $obe->sub_cpmk_list ?? [],
            'defaultMateri' => $obe->materi_pembelajaran ?? [],
            'defaultBobotPenilaian' => $obe->bobot_penilaian ?? [],
        ]);
    }

    public function update(Request $request, MahasiswaObePortofolio $obe): RedirectResponse
    {
        if ($obe->mahasiswa_id !== $this->mahasiswa()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'tahun_akademik' => 'required|string|max:20',
            'semester' => 'required|integer|min:1|max:14',
            'kelas' => 'nullable|string|max:50',
            'dosen_pa' => 'nullable|string|max:150',
            'lahan_praktik' => 'nullable|string|max:200',
            'pembimbing_lahan' => 'nullable|string|max:150',
            'ketua_jurusan' => 'nullable|string|max:150',
            'nip_ketua_jurusan' => 'nullable|string|max:50',
            'ketua_prodi' => 'nullable|string|max:150',
            'nip_ketua_prodi' => 'nullable|string|max:50',
            'kode_mk' => 'required|string|max:50',
            'nama_mk' => 'required|string|max:200',
            'sks' => 'required|integer|min:1|max:10',
            'deskripsi_mk' => 'nullable|string',
            'metode_pembelajaran' => 'nullable|string',
            'metode_penilaian' => 'nullable|string',
            'nilai_angka' => 'nullable|numeric|min:0|max:100',
            'nilai_mutu' => 'nullable|numeric|min:0|max:4',
            'nilai_lambang' => 'nullable|string|max:5',
            'kelebihan_mahasiswa' => 'nullable|string',
            'aspek_perlu_ditingkatkan' => 'nullable|string',
            'rekomendasi_pembimbing' => 'nullable|string',
        ]);

        $validated['cpl_list'] = $request->input('cpl_list', []);
        $validated['cpmk_list'] = $request->input('cpmk_list', []);
        $validated['sub_cpmk_list'] = array_filter($request->input('sub_cpmk_list', []));
        $validated['materi_pembelajaran'] = array_filter($request->input('materi_pembelajaran', []));
        $validated['bobot_penilaian'] = $request->input('bobot_penilaian', []);

        $obe->update($validated);

        if ($request->has('evidences_status')) {
            foreach ($request->input('evidences_status') as $evidenceId => $isAda) {
                MahasiswaObeEvidence::where('id', $evidenceId)
                    ->where('obe_portofolio_id', $obe->id)
                    ->update(['is_ada' => (bool)$isAda]);
            }
        }

        return redirect()->route('mahasiswa.obe.edit', $obe->id)
            ->with('success', 'Buku Portofolio OBE berhasil diperbarui.');
    }

    public function uploadEvidence(Request $request, MahasiswaObePortofolio $obe): RedirectResponse
    {
        if ($obe->mahasiswa_id !== $this->mahasiswa()->id) {
            abort(403);
        }

        $request->validate([
            'nama_dokumen' => 'required|string|max:200',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240',
            'catatan' => 'nullable|string',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = MediaStorage::storeUploadedFile($request->file('file'), 'obe-evidence/' . $obe->id);
        }

        $obe->evidences()->create([
            'nama_dokumen' => $request->nama_dokumen,
            'is_ada' => true,
            'file_path' => $filePath,
            'catatan' => $request->catatan,
        ]);

        return back()->with('success', 'Dokumen evidence berhasil ditambahkan.');
    }

    public function deleteEvidence(MahasiswaObePortofolio $obe, MahasiswaObeEvidence $evidence): RedirectResponse
    {
        if ($obe->mahasiswa_id !== $this->mahasiswa()->id || $evidence->obe_portofolio_id !== $obe->id) {
            abort(403);
        }

        if ($evidence->file_path) {
            MediaStorage::delete($evidence->file_path);
        }

        $evidence->delete();

        return back()->with('success', 'Dokumen evidence berhasil dihapus.');
    }

    public function print(MahasiswaObePortofolio $obe): View
    {
        if ($obe->mahasiswa_id !== $this->mahasiswa()->id) {
            abort(403);
        }

        $mahasiswa = $this->mahasiswa();
        $mahasiswa->load('programStudi');
        $obe->load('evidences');

        return view('mahasiswa.obe.print', compact('mahasiswa', 'obe'));
    }

    public function destroy(MahasiswaObePortofolio $obe): RedirectResponse
    {
        if ($obe->mahasiswa_id !== $this->mahasiswa()->id) {
            abort(403);
        }

        foreach ($obe->evidences as $evidence) {
            if ($evidence->file_path) {
                MediaStorage::delete($evidence->file_path);
            }
        }
        $obe->delete();

        return redirect()->route('mahasiswa.obe.index')->with('success', 'Buku Portofolio OBE dihapus.');
    }
}
