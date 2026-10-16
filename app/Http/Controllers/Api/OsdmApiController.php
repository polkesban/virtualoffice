<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Osdm\OsdmPortalConfig;
use App\Models\Osdm\OsdmEmployee;
use App\Models\Osdm\OsdmWfaSubmission;
use App\Models\Osdm\OsdmKebugaranSubmission;
use App\Models\Osdm\OsdmClickLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OsdmApiController extends Controller
{
    // ─── Portal Config ────────────────────────────────────────────────────

    /**
     * GET /api/osdm/portal/live
     * Return the published (live) portal data.
     */
    public function getPortalLive(): JsonResponse
    {
        $config = OsdmPortalConfig::where('config_key', 'live')->first();

        if (!$config) {
            return response()->json([
                'menus' => [],
                'profile' => null,
                'lastPublishedAt' => null,
            ]);
        }

        return response()->json([
            'menus' => json_decode($config->menus, true) ?: [],
            'profile' => json_decode($config->profile, true),
            'lastPublishedAt' => $config->published_at?->toISOString(),
        ]);
    }

    /**
     * POST /api/osdm/portal/publish
     * Publish menus + profile to live.
     */
    public function publishPortal(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'menus' => 'required|array',
            'profile' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $now = now();

        // Update or create live config
        OsdmPortalConfig::updateOrCreate(
            ['config_key' => 'live'],
            [
                'menus' => json_encode($request->input('menus'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'profile' => json_encode($request->input('profile'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'published_at' => $now,
            ]
        );

        // Also sync to draft
        OsdmPortalConfig::updateOrCreate(
            ['config_key' => 'draft'],
            [
                'menus' => json_encode($request->input('menus'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'profile' => json_encode($request->input('profile'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]
        );

        return response()->json([
            'success' => true,
            'timestamp' => $now->toISOString(),
        ]);
    }

    /**
     * GET /api/osdm/portal/draft
     */
    public function getPortalDraft(): JsonResponse
    {
        $config = OsdmPortalConfig::where('config_key', 'draft')->first();

        if (!$config) {
            $config = OsdmPortalConfig::where('config_key', 'live')->first();
        }

        if (!$config) {
            return response()->json(['menus' => [], 'profile' => null]);
        }

        return response()->json([
            'menus' => json_decode($config->menus, true) ?: [],
            'profile' => json_decode($config->profile, true),
        ]);
    }

    /**
     * POST /api/osdm/portal/draft
     */
    public function savePortalDraft(Request $request): JsonResponse
    {
        OsdmPortalConfig::updateOrCreate(
            ['config_key' => 'draft'],
            [
                'menus' => json_encode($request->input('menus', []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'profile' => json_encode($request->input('profile', []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]
        );

        return response()->json(['success' => true]);
    }

    // ─── Security / PIN ───────────────────────────────────────────────────

    /**
     * GET /api/osdm/security/pin
     */
    public function getAdminPin(): JsonResponse
    {
        $config = OsdmPortalConfig::where('config_key', 'security')->first();
        $wfaConfig = OsdmPortalConfig::where('config_key', 'security_wfa')->first();
        $kebugaranConfig = OsdmPortalConfig::where('config_key', 'security_kebugaran')->first();
        $meetingConfig = OsdmPortalConfig::where('config_key', 'security_meeting')->first();
        $monitoringAllConfig = OsdmPortalConfig::where('config_key', 'security_monitoring_all')->first();

        return response()->json([
            'pin' => $config->admin_pin ?? 'admin123',
            'wfa_pin' => $wfaConfig->admin_pin ?? 'wfa123',
            'kebugaran_pin' => $kebugaranConfig->admin_pin ?? 'fit123',
            'meeting_pin' => $meetingConfig->admin_pin ?? 'rapat123',
            'monitoring_all_pin' => $monitoringAllConfig->admin_pin ?? 'pantau123',
        ]);
    }

    /**
     * POST /api/osdm/security/pin
     */
    public function saveAdminPin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pin' => 'required|string|min:4',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        OsdmPortalConfig::updateOrCreate(
            ['config_key' => 'security'],
            ['admin_pin' => trim($request->input('pin'))]
        );

        return response()->json(['success' => true]);
    }

    /**
     * POST /api/osdm/security/wfa-pin
     */
    public function saveWfaAdminPin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pin' => 'required|string|min:4',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        OsdmPortalConfig::updateOrCreate(
            ['config_key' => 'security_wfa'],
            ['admin_pin' => trim($request->input('pin'))]
        );

        return response()->json(['success' => true]);
    }

    /**
     * POST /api/osdm/security/kebugaran-pin
     */
    public function saveKebugaranAdminPin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pin' => 'required|string|min:4',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        OsdmPortalConfig::updateOrCreate(
            ['config_key' => 'security_kebugaran'],
            ['admin_pin' => trim($request->input('pin'))]
        );

        return response()->json(['success' => true]);
    }

    /**
     * POST /api/osdm/security/meeting-pin
     */
    public function saveMeetingAdminPin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pin' => 'required|string|min:4',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        OsdmPortalConfig::updateOrCreate(
            ['config_key' => 'security_meeting'],
            ['admin_pin' => trim($request->input('pin'))]
        );

        return response()->json(['success' => true]);
    }

    /**
     * POST /api/osdm/security/monitoring-all-pin
     */
    public function saveMonitoringAllAdminPin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pin' => 'required|string|min:4',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        OsdmPortalConfig::updateOrCreate(
            ['config_key' => 'security_monitoring_all'],
            ['admin_pin' => trim($request->input('pin'))]
        );

        return response()->json(['success' => true]);
    }

    // ─── Employees ────────────────────────────────────────────────────────

    /**
     * Helper to map employee model to frontend format.
     */
    private function formatEmployee(OsdmEmployee $emp): array
    {
        $cleanJabatan = $emp->jabatan ? trim(preg_replace('/\s*\((?:JFT|JFU)\)\s*$/i', '', $emp->jabatan)) : '';

        return [
            'nip' => $emp->nip,
            'name' => $emp->name,
            'unitKerja' => $emp->unit_kerja,
            'jabatan' => $cleanJabatan,
            'jenisJabatan' => $emp->jenis_jabatan,
            'jabatanPengelola' => $emp->jabatan_pengelola,
            'email' => $emp->email,
            'nomorWa' => $emp->nomor_wa,
            'tanggalLahir' => $emp->tanggal_lahir?->format('Y-m-d'),
            'nik' => $emp->nik,
            'status' => $emp->status ?? 'Aktif',
            'jenisKepegawaian' => $emp->jenis_kepegawaian ?? 'ASN',
        ];
    }

    /**
     * GET /api/osdm/employees
     */
    public function getEmployees(Request $request): JsonResponse
    {
        $query = OsdmEmployee::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('name')->get();

        return response()->json($employees->map(fn ($emp) => $this->formatEmployee($emp)));
    }

    /**
     * POST /api/osdm/employees
     */
    public function createEmployee(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nip' => 'required|string|min:3|max:50',
            'name' => 'required|string|max:255',
            'unitKerja' => 'nullable|string|max:255',
            'unit_kerja' => 'nullable|string|max:255',
            'jabatan' => 'required|string|max:255',
            'jenisJabatan' => 'nullable|string|max:50',
            'jenis_jabatan' => 'nullable|string|max:50',
            'jabatanPengelola' => 'nullable|string|max:255',
            'jabatan_pengelola' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'nomorWa' => 'nullable|string|max:50',
            'nomor_wa' => 'nullable|string|max:50',
            'tanggalLahir' => 'nullable|date',
            'tanggal_lahir' => 'nullable|date',
            'nik' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:Aktif,Tugas Belajar,Cuti Melahirkan,Cuti Besar,Pensiun',
            'jenisKepegawaian' => 'nullable|string|in:ASN,Non ASN',
            'jenis_kepegawaian' => 'nullable|string|in:ASN,Non ASN',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $cleanNip = preg_replace('/[\s.\-]/', '', (string) $request->input('nip'));
        if (empty($cleanNip)) {
            return response()->json(['success' => false, 'error' => 'Format NIP tidak valid.'], 422);
        }

        // Duplicate check (case-insensitive & whitespace-stripped)
        $exists = OsdmEmployee::whereRaw("REPLACE(REPLACE(REPLACE(LOWER(nip), ' ', ''), '.', ''), '-', '') = ?", [strtolower($cleanNip)])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'error' => "Pegawai dengan NIP {$request->input('nip')} sudah terdaftar di database.",
            ], 409);
        }

        $unitKerja = $request->input('unitKerja', $request->input('unit_kerja', 'Direktorat'));
        $nomorWa = $request->input('nomorWa', $request->input('nomor_wa'));
        $tanggalLahir = $request->input('tanggalLahir', $request->input('tanggal_lahir'));
        $status = $request->input('status', 'Aktif') ?: 'Aktif';
        $jenisJabatan = $request->input('jenisJabatan', $request->input('jenis_jabatan'));
        $jabatanPengelola = $request->input('jabatanPengelola', $request->input('jabatan_pengelola'));
        $jenisKepegawaian = $request->input('jenisKepegawaian', $request->input('jenis_kepegawaian', 'ASN')) ?: 'ASN';

        $employee = OsdmEmployee::create([
            'nip' => $cleanNip,
            'name' => trim((string) $request->input('name')),
            'unit_kerja' => trim((string) $unitKerja),
            'jabatan' => trim((string) $request->input('jabatan')),
            'jenis_jabatan' => $jenisJabatan ? trim((string) $jenisJabatan) : null,
            'jabatan_pengelola' => $jabatanPengelola ? trim((string) $jabatanPengelola) : null,
            'email' => $request->input('email') ? trim((string) $request->input('email')) : null,
            'nomor_wa' => $nomorWa ? trim((string) $nomorWa) : null,
            'tanggal_lahir' => $tanggalLahir ?: null,
            'nik' => $request->input('nik') ? trim((string) $request->input('nik')) : null,
            'status' => $status,
            'jenis_kepegawaian' => $jenisKepegawaian,
        ]);

        return response()->json([
            'success' => true,
            'employee' => $this->formatEmployee($employee),
            'message' => "Pegawai {$employee->name} berhasil ditambahkan ke database MySQL.",
        ], 201);
    }

    /**
     * PUT /api/osdm/employees/{nip}
     */
    public function updateEmployee(Request $request, string $nip): JsonResponse
    {
        $cleanNip = preg_replace('/[\s.\-]/', '', $nip);
        $employee = OsdmEmployee::whereRaw("REPLACE(REPLACE(REPLACE(LOWER(nip), ' ', ''), '.', ''), '-', '') = ?", [strtolower($cleanNip)])
            ->first();

        if (!$employee) {
            return response()->json(['success' => false, 'error' => 'Data pegawai tidak ditemukan.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'unitKerja' => 'nullable|string|max:255',
            'unit_kerja' => 'nullable|string|max:255',
            'jabatan' => 'sometimes|required|string|max:255',
            'jenisJabatan' => 'nullable|string|max:50',
            'jenis_jabatan' => 'nullable|string|max:50',
            'jabatanPengelola' => 'nullable|string|max:255',
            'jabatan_pengelola' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'nomorWa' => 'nullable|string|max:50',
            'nomor_wa' => 'nullable|string|max:50',
            'tanggalLahir' => 'nullable|date',
            'tanggal_lahir' => 'nullable|date',
            'nik' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:Aktif,Tugas Belajar,Cuti Melahirkan,Cuti Besar,Pensiun',
            'jenisKepegawaian' => 'nullable|string|in:ASN,Non ASN',
            'jenis_kepegawaian' => 'nullable|string|in:ASN,Non ASN',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        if ($request->has('name')) {
            $employee->name = trim((string) $request->input('name'));
        }
        if ($request->has('unitKerja') || $request->has('unit_kerja')) {
            $employee->unit_kerja = trim((string) $request->input('unitKerja', $request->input('unit_kerja')));
        }
        if ($request->has('jabatan')) {
            $employee->jabatan = trim((string) $request->input('jabatan'));
        }
        if ($request->has('jenisJabatan') || $request->has('jenis_jabatan')) {
            $jj = $request->input('jenisJabatan', $request->input('jenis_jabatan'));
            $employee->jenis_jabatan = $jj ? trim((string) $jj) : null;
        }
        if ($request->has('jabatanPengelola') || $request->has('jabatan_pengelola')) {
            $jp = $request->input('jabatanPengelola', $request->input('jabatan_pengelola'));
            $employee->jabatan_pengelola = $jp ? trim((string) $jp) : null;
        }
        if ($request->has('email')) {
            $employee->email = $request->input('email') ? trim((string) $request->input('email')) : null;
        }
        if ($request->has('nomorWa') || $request->has('nomor_wa')) {
            $nomorWa = $request->input('nomorWa', $request->input('nomor_wa'));
            $employee->nomor_wa = $nomorWa ? trim((string) $nomorWa) : null;
        }
        if ($request->has('tanggalLahir') || $request->has('tanggal_lahir')) {
            $tgl = $request->input('tanggalLahir', $request->input('tanggal_lahir'));
            $employee->tanggal_lahir = $tgl ?: null;
        }
        if ($request->has('nik')) {
            $employee->nik = $request->input('nik') ? trim((string) $request->input('nik')) : null;
        }
        if ($request->has('status')) {
            $employee->status = $request->input('status') ?: 'Aktif';
        }
        if ($request->has('jenisKepegawaian') || $request->has('jenis_kepegawaian')) {
            $jk = $request->input('jenisKepegawaian', $request->input('jenis_kepegawaian'));
            $employee->jenis_kepegawaian = $jk ? trim((string) $jk) : 'ASN';
        }

        $employee->save();

        return response()->json([
            'success' => true,
            'employee' => $this->formatEmployee($employee),
            'message' => "Data pegawai {$employee->name} berhasil diperbarui di database MySQL.",
        ]);
    }

    /**
     * DELETE /api/osdm/employees/{nip}
     */
    public function deleteEmployee(string $nip): JsonResponse
    {
        $cleanNip = preg_replace('/[\s.\-]/', '', $nip);
        $employee = OsdmEmployee::whereRaw("REPLACE(REPLACE(REPLACE(LOWER(nip), ' ', ''), '.', ''), '-', '') = ?", [strtolower($cleanNip)])
            ->first();

        if (!$employee) {
            return response()->json(['success' => false, 'error' => 'Data pegawai tidak ditemukan atau sudah dihapus.'], 404);
        }

        $name = $employee->name;
        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => "Pegawai {$name} ({$nip}) berhasil dihapus dari database MySQL.",
        ]);
    }

    // ─── WFA Submissions ──────────────────────────────────────────────────

    /**
     * GET /api/osdm/wfa
     */
    public function getWfaSubmissions(Request $request): JsonResponse
    {
        $query = OsdmWfaSubmission::query();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->has('nip')) {
            $cleanNip = preg_replace('/[\s.\-]/', '', $request->input('nip'));
            $query->whereRaw("REPLACE(REPLACE(REPLACE(nip, ' ', ''), '.', ''), '-', '') = ?", [$cleanNip]);
        }

        $subs = $query->orderByDesc('created_at')->get();

        return response()->json($subs->map(function ($s) {
            $unitKerja = $s->unit_kerja;
            if (empty($unitKerja)) {
                $cleanNip = preg_replace('/[\s.\-]/', '', (string)$s->nip);
                $emp = OsdmEmployee::whereRaw("REPLACE(REPLACE(REPLACE(nip, ' ', ''), '.', ''), '-', '') = ?", [$cleanNip])->first();
                if (!$emp && !empty($s->employee_name)) {
                    $cleanName = trim(preg_replace('/^(dr|drg|prof|ir|apt)\.?\s+/i', '', (string)$s->employee_name));
                    $emp = OsdmEmployee::where('name', 'like', "%{$cleanName}%")->first();
                }
                if ($emp && !empty($emp->unit_kerja)) {
                    $unitKerja = $emp->unit_kerja;
                    try {
                        $s->update(['unit_kerja' => $unitKerja]);
                    } catch (\Throwable $e) {}
                }
            }

            return [
                'id' => (string) $s->id,
                'nip' => $s->nip,
                'employeeName' => $s->employee_name,
                'unitKerja' => $unitKerja ?: 'Direktorat',
                'jabatan' => $s->jabatan,
                'nomorWa' => $s->nomor_wa,
                'tanggalWfa' => $s->tanggal_wfa->format('Y-m-d'),
                'namaKegiatan' => $s->nama_kegiatan,
                'lokasiKegiatan' => $s->lokasi_kegiatan,
                'lokasiLahanBimbingan' => $s->lokasi_lahan_bimbingan,
                'statusWfa' => $s->status_wfa,
                'linkSuratTugas' => $s->link_surat_tugas,
                'status' => $s->status,
                'catatanPengelola' => $s->catatan_pengelola,
                'createdAt' => $s->created_at->toISOString(),
                'validatedAt' => $s->validated_at?->toISOString(),
                'validatedBy' => $s->validated_by,
            ];
        }));
    }

    /**
     * POST /api/osdm/wfa
     */
    public function createWfaSubmission(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nip' => 'required|string',
            'employeeName' => 'required|string',
            'tanggalWfa' => 'required|date',
            'namaKegiatan' => 'required|string',
            'lokasiKegiatan' => 'required|string',
            'lokasiLahanBimbingan' => 'required|string',
            'statusWfa' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $cleanNip = preg_replace('/[\s.\-]/', '', $request->input('nip'));

        // Check employee status: only active employees are allowed to submit WFA
        $employee = OsdmEmployee::whereRaw("REPLACE(REPLACE(REPLACE(nip, ' ', ''), '.', ''), '-', '') = ?", [$cleanNip])->first();
        if ($employee && $employee->status && $employee->status !== 'Aktif') {
            return response()->json([
                'success' => false,
                'error' => "Pengajuan WFA Dibatasi : Pegawai atas nama {$employee->name} saat ini sedang berstatus {$employee->status}. tidak diperkenankan mengajukan permohonan WFA Bimbingan.",
            ], 422);
        }

        // Duplicate check
        $exists = OsdmWfaSubmission::whereRaw("REPLACE(REPLACE(REPLACE(nip, ' ', ''), '.', ''), '-', '') = ?", [$cleanNip])
            ->where('tanggal_wfa', $request->input('tanggalWfa'))
            ->where('status', '!=', 'Ditolak')
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'error' => "Pengajuan ditolak: Pegawai dengan NIP {$request->input('nip')} sudah memiliki jadwal pengajuan WFA pada tanggal {$request->input('tanggalWfa')}.",
            ], 409);
        }

        $unitKerja = trim((string) $request->input('unitKerja', ''));
        if (empty($unitKerja) && $employee && !empty($employee->unit_kerja)) {
            $unitKerja = $employee->unit_kerja;
        }
        if (empty($unitKerja) && !empty($request->input('employeeName'))) {
            $cleanName = trim(preg_replace('/^(dr|drg|prof|ir|apt)\.?\s+/i', '', (string)$request->input('employeeName')));
            $emp = OsdmEmployee::where('name', 'like', "%{$cleanName}%")->first();
            if ($emp && !empty($emp->unit_kerja)) {
                $unitKerja = $emp->unit_kerja;
            }
        }

        $sub = OsdmWfaSubmission::create([
            'nip' => $request->input('nip'),
            'employee_name' => $request->input('employeeName'),
            'unit_kerja' => $unitKerja ?: 'Direktorat',
            'jabatan' => $request->input('jabatan'),
            'nomor_wa' => $request->input('nomorWa'),
            'tanggal_wfa' => $request->input('tanggalWfa'),
            'nama_kegiatan' => $request->input('namaKegiatan'),
            'lokasi_kegiatan' => $request->input('lokasiKegiatan'),
            'lokasi_lahan_bimbingan' => $request->input('lokasiLahanBimbingan'),
            'status_wfa' => $request->input('statusWfa'),
            'link_surat_tugas' => $request->input('linkSuratTugas', ''),
            'status' => 'Menunggu Validasi',
        ]);

        return response()->json([
            'success' => true,
            'submission' => [
                'id' => (string) $sub->id,
                'nip' => $sub->nip,
                'employeeName' => $sub->employee_name,
                'unitKerja' => $sub->unit_kerja,
                'jabatan' => $sub->jabatan,
                'nomorWa' => $sub->nomor_wa,
                'tanggalWfa' => $sub->tanggal_wfa->format('Y-m-d'),
                'namaKegiatan' => $sub->nama_kegiatan,
                'lokasiKegiatan' => $sub->lokasi_kegiatan,
                'lokasiLahanBimbingan' => $sub->lokasi_lahan_bimbingan,
                'statusWfa' => $sub->status_wfa,
                'linkSuratTugas' => $sub->link_surat_tugas,
                'status' => $sub->status,
                'catatanPengelola' => $sub->catatan_pengelola,
                'createdAt' => $sub->created_at->toISOString(),
                'validatedAt' => null,
                'validatedBy' => null,
            ],
        ]);
    }

    /**
     * PUT /api/osdm/wfa/{id}/status
     */
    public function updateWfaStatus(Request $request, int $id): JsonResponse
    {
        $sub = OsdmWfaSubmission::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Menunggu Validasi,Valid,Ditolak',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $sub->status = $request->input('status');
        $sub->catatan_pengelola = $request->input('catatanPengelola', $sub->catatan_pengelola);

        if (in_array($request->input('status'), ['Valid', 'Ditolak'])) {
            $sub->validated_at = now();
            $sub->validated_by = $request->input('validatedBy', 'Tim OSDM Poltekkes');
        } elseif ($request->input('status') === 'Menunggu Validasi') {
            $sub->validated_at = null;
            $sub->validated_by = null;
        }

        $sub->save();

        return response()->json(['success' => true]);
    }

    /**
     * PUT /api/osdm/wfa/{id}/link
     */
    public function updateWfaLinkSuratTugas(Request $request, int $id): JsonResponse
    {
        $sub = OsdmWfaSubmission::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'linkSuratTugas' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $link = trim((string) $request->input('linkSuratTugas', ''));
        if (!empty($link) && !preg_match('#^https?://#i', $link)) {
            $link = 'https://' . $link;
        }

        $sub->link_surat_tugas = $link;
        $sub->save();

        return response()->json([
            'success' => true,
            'linkSuratTugas' => $sub->link_surat_tugas,
        ]);
    }

    /**
     * DELETE /api/osdm/wfa/{id}
     */
    public function deleteWfaSubmission(int $id): JsonResponse
    {
        OsdmWfaSubmission::where('id', $id)->delete();

        return response()->json(['success' => true]);
    }

    // ─── Kebugaran Submissions ────────────────────────────────────────────

    /**
     * GET /api/osdm/kebugaran
     */
    public function getKebugaranSubmissions(): JsonResponse
    {
        $submissions = OsdmKebugaranSubmission::orderByDesc('created_at')->limit(1000)->get();

        return response()->json($submissions->map(function ($sub) {
            return [
                'id' => (string) $sub->id,
                'tanggalPeriksa' => $sub->tanggal_periksa->format('Y-m-d'),
                'periode' => $sub->periode,
                'nip' => $sub->nip,
                'namaPegawai' => $sub->nama_pegawai,
                'tanggalLahir' => $sub->tanggal_lahir,
                'unitKerja' => $sub->unit_kerja,
                'nik' => $sub->nik,
                'tensiSistolik' => (int) $sub->tensi_sistolik,
                'tensiDiastolik' => (int) $sub->tensi_diastolik,
                'beratBadan' => (float) $sub->berat_badan,
                'tinggiBadan' => (float) $sub->tinggi_badan,
                'lingkarPinggang' => (float) $sub->lingkar_pinggang,
                'tipeGulaDarah' => $sub->tipe_gula_darah,
                'gulaDarah' => (int) $sub->gula_darah,
                'kolesterol' => (int) $sub->kolesterol,
                'nomorWa' => $sub->nomor_wa,
                'fasyankes' => $sub->fasyankes,
                'catatan' => $sub->catatan,
                'createdAt' => $sub->created_at->toISOString(),
            ];
        }));
    }

    /**
     * POST /api/osdm/kebugaran
     */
    public function createKebugaranSubmission(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tanggalPeriksa' => 'required|date',
            'periode' => 'required|string',
            'nip' => 'required|string',
            'namaPegawai' => 'required|string',
            'unitKerja' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        // Validate that NIP is provided and exists in master employee database
        $cleanNip = preg_replace('/[\s.\-]/', '', (string) $request->input('nip'));
        if (empty($cleanNip)) {
            return response()->json(['success' => false, 'error' => 'Format NIP tidak valid.'], 422);
        }

        $employee = OsdmEmployee::whereRaw("REPLACE(REPLACE(REPLACE(LOWER(nip), ' ', ''), '.', ''), '-', '') = ?", [strtolower($cleanNip)])
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'error' => "Pengisian Ditolak: Nomor NIP '{$request->input('nip')}' tidak terdaftar dalam Master Data Pegawai Poltekkes Bandung. Pastikan Anda memasukkan nomor NIP yang valid.",
            ], 422);
        }

        // Duplicate check
        $exists = OsdmKebugaranSubmission::whereRaw("REPLACE(REPLACE(REPLACE(nip, ' ', ''), '.', ''), '-', '') = ?", [$cleanNip])
            ->whereRaw("LOWER(periode) = ?", [strtolower($request->input('periode'))])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'error' => "Data Ditolak: Pegawai dengan NIP {$employee->nip} ({$employee->name}) sudah terdaftar mengisi formulir data kebugaran untuk {$request->input('periode')}.",
            ], 409);
        }

        $sub = OsdmKebugaranSubmission::create([
            'tanggal_periksa' => $request->input('tanggalPeriksa'),
            'periode' => $request->input('periode'),
            'nip' => $employee->nip,
            'nama_pegawai' => $employee->name,
            'tanggal_lahir' => $request->input('tanggalLahir') ?: ($employee->tanggal_lahir?->format('Y-m-d') ?: null),
            'unit_kerja' => $employee->unit_kerja,
            'nik' => $request->input('nik') ?: ($employee->nik ?: null),
            'tensi_sistolik' => $request->input('tensiSistolik', 120),
            'tensi_diastolik' => $request->input('tensiDiastolik', 80),
            'berat_badan' => $request->input('beratBadan', 60),
            'tinggi_badan' => $request->input('tinggiBadan', 160),
            'lingkar_pinggang' => $request->input('lingkarPinggang', 75),
            'tipe_gula_darah' => $request->input('tipeGulaDarah', 'GDS'),
            'gula_darah' => $request->input('gulaDarah', 100),
            'kolesterol' => $request->input('kolesterol', 180),
            'nomor_wa' => $request->input('nomorWa'),
            'fasyankes' => $request->input('fasyankes'),
            'catatan' => $request->input('catatan'),
        ]);

        return response()->json([
            'success' => true,
            'submission' => [
                'id' => (string) $sub->id,
                'tanggalPeriksa' => $sub->tanggal_periksa->format('Y-m-d'),
                'periode' => $sub->periode,
                'nip' => $sub->nip,
                'namaPegawai' => $sub->nama_pegawai,
                'tanggalLahir' => $sub->tanggal_lahir,
                'unitKerja' => $sub->unit_kerja,
                'nik' => $sub->nik,
                'tensiSistolik' => (int) $sub->tensi_sistolik,
                'tensiDiastolik' => (int) $sub->tensi_diastolik,
                'beratBadan' => (float) $sub->berat_badan,
                'tinggiBadan' => (float) $sub->tinggi_badan,
                'lingkarPinggang' => (float) $sub->lingkar_pinggang,
                'tipeGulaDarah' => $sub->tipe_gula_darah,
                'gulaDarah' => (int) $sub->gula_darah,
                'kolesterol' => (int) $sub->kolesterol,
                'nomorWa' => $sub->nomor_wa,
                'fasyankes' => $sub->fasyankes,
                'catatan' => $sub->catatan,
                'createdAt' => $sub->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * DELETE /api/osdm/kebugaran/{id}
     */
    public function deleteKebugaranSubmission(int $id): JsonResponse
    {
        OsdmKebugaranSubmission::where('id', $id)->delete();

        return response()->json(['success' => true]);
    }

    // ─── Click Logs ───────────────────────────────────────────────────────

    /**
     * Helper to keep clickCount in portal configs in sync with osdm_click_logs table
     */
    private function syncMenuClickCounts(): void
    {
        $counts = OsdmClickLog::selectRaw('menu_id, count(*) as total')
            ->whereNotNull('menu_id')
            ->where('menu_id', '!=', '')
            ->groupBy('menu_id')
            ->pluck('total', 'menu_id');

        $titleCounts = OsdmClickLog::selectRaw('menu_title, count(*) as total')
            ->whereNotNull('menu_title')
            ->where('menu_title', '!=', '')
            ->groupBy('menu_title')
            ->pluck('total', 'menu_title');

        foreach (['draft', 'live'] as $key) {
            $config = OsdmPortalConfig::where('config_key', $key)->first();
            if ($config && $config->menus) {
                $menus = json_decode($config->menus, true);
                if (is_array($menus)) {
                    $changed = false;
                    foreach ($menus as &$m) {
                        $c = (int) ($counts[$m['id']] ?? $titleCounts[$m['title']] ?? 0);
                        if (($m['clickCount'] ?? 0) !== $c) {
                            $m['clickCount'] = $c;
                            $changed = true;
                        }
                    }
                    if ($changed) {
                        $config->menus = json_encode($menus, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $config->save();
                    }
                }
            }
        }
    }

    /**
     * GET /api/osdm/click-logs
     */
    public function getClickLogs(): JsonResponse
    {
        $logs = OsdmClickLog::orderByDesc('created_at')->limit(150)->get();

        $menuCounts = OsdmClickLog::selectRaw('menu_id, count(*) as total')
            ->whereNotNull('menu_id')
            ->where('menu_id', '!=', '')
            ->groupBy('menu_id')
            ->pluck('total', 'menu_id');

        $menuTitleCounts = OsdmClickLog::selectRaw('menu_title, count(*) as total')
            ->whereNotNull('menu_title')
            ->where('menu_title', '!=', '')
            ->groupBy('menu_title')
            ->pluck('total', 'menu_title');

        $deviceCounts = OsdmClickLog::selectRaw('device, count(*) as total')
            ->whereNotNull('device')
            ->where('device', '!=', '')
            ->groupBy('device')
            ->pluck('total', 'device');

        $totalClicks = OsdmClickLog::count();

        $formattedLogs = $logs->map(function ($log) {
            return [
                'id' => (string) $log->id,
                'menuId' => $log->menu_id,
                'menuTitle' => $log->menu_title,
                'category' => $log->category,
                'timestamp' => $log->created_at->toISOString(),
                'device' => $log->device,
                'browser' => $log->browser,
                'referrer' => $log->referrer,
            ];
        });

        return response()->json([
            'logs' => $formattedLogs,
            'stats' => [
                'totalClicks' => $totalClicks,
                'menuCounts' => $menuCounts,
                'menuTitleCounts' => $menuTitleCounts,
                'deviceCounts' => [
                    'mobile' => (int) ($deviceCounts['Mobile'] ?? 0),
                    'desktop' => (int) ($deviceCounts['Desktop'] ?? 0),
                    'tablet' => (int) ($deviceCounts['Tablet'] ?? 0),
                ],
            ],
        ]);
    }

    /**
     * POST /api/osdm/click-logs
     */
    public function createClickLog(Request $request): JsonResponse
    {
        OsdmClickLog::create([
            'menu_id' => $request->input('menuId', ''),
            'menu_title' => $request->input('menuTitle', ''),
            'category' => $request->input('category'),
            'device' => $request->input('device', 'Mobile'),
            'browser' => $request->input('browser', 'Browser'),
            'referrer' => $request->input('referrer', 'Direct / QR'),
        ]);

        $this->syncMenuClickCounts();

        return response()->json(['success' => true]);
    }

    /**
     * DELETE /api/osdm/click-logs
     */
    public function deleteClickLogs(): JsonResponse
    {
        OsdmClickLog::truncate();

        // Also reset clickCount on menus in portal configs
        foreach (['draft', 'live'] as $key) {
            $config = OsdmPortalConfig::where('config_key', $key)->first();
            if ($config && $config->menus) {
                $menus = json_decode($config->menus, true);
                if (is_array($menus)) {
                    foreach ($menus as &$m) {
                        $m['clickCount'] = 0;
                    }
                    $config->menus = json_encode($menus, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $config->save();
                }
            }
        }

        return response()->json(['success' => true]);
    }

    // ─── Meeting Session & Attendees ──────────────────────────────────────

    /**
     * GET /api/osdm/meeting/sessions
     */
    public function getMeetingSessions(): JsonResponse
    {
        $config = OsdmPortalConfig::where('config_key', 'meeting_sessions')->first();
        if ($config && $config->menus) {
            $sessions = json_decode($config->menus, true);
            if (is_array($sessions) && count($sessions) > 0) {
                return response()->json($sessions);
            }
        }

        // Fallback to single meeting_session if meeting_sessions is not yet populated
        $single = OsdmPortalConfig::where('config_key', 'meeting_session')->first();
        if ($single && $single->menus) {
            $sess = json_decode($single->menus, true);
            if ($sess && is_array($sess)) {
                return response()->json([$sess]);
            }
        }

        return response()->json([]);
    }

    /**
     * GET /api/osdm/meeting/session
     */
    public function getMeetingSession(): JsonResponse
    {
        $config = OsdmPortalConfig::where('config_key', 'meeting_session')->first();
        if ($config && $config->menus) {
            return response()->json(json_decode($config->menus, true));
        }

        // Check if there are sessions in meeting_sessions
        $sessionsConfig = OsdmPortalConfig::where('config_key', 'meeting_sessions')->first();
        if ($sessionsConfig && $sessionsConfig->menus) {
            $sessions = json_decode($sessionsConfig->menus, true);
            if (is_array($sessions) && count($sessions) > 0) {
                return response()->json($sessions[0]);
            }
        }

        return response()->json(null);
    }

    /**
     * POST /api/osdm/meeting/session or /api/osdm/meeting/sessions
     */
    public function saveMeetingSession(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Handle case where array of sessions is passed directly or wrapped in 'sessions'
        if (isset($payload['sessions']) && is_array($payload['sessions'])) {
            $sessions = $payload['sessions'];
            OsdmPortalConfig::updateOrCreate(
                ['config_key' => 'meeting_sessions'],
                [
                    'menus' => json_encode($sessions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'published_at' => now(),
                ]
            );
            if (!empty($sessions[0])) {
                OsdmPortalConfig::updateOrCreate(
                    ['config_key' => 'meeting_session'],
                    [
                        'menus' => json_encode($sessions[0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'published_at' => now(),
                    ]
                );
            }
            return response()->json(['success' => true, 'sessions' => $sessions]);
        }

        // Single session object
        $sessionData = $payload;
        if (empty($sessionData['id'])) {
            $sessionData['id'] = 'meet-' . round(microtime(true) * 1000);
        }

        // Load existing sessions
        $sessionsConfig = OsdmPortalConfig::where('config_key', 'meeting_sessions')->first();
        $sessions = ($sessionsConfig && $sessionsConfig->menus) ? json_decode($sessionsConfig->menus, true) : [];
        if (!is_array($sessions)) {
            $sessions = [];
        }

        // If sessions empty, also check legacy meeting_session
        if (empty($sessions)) {
            $legacy = OsdmPortalConfig::where('config_key', 'meeting_session')->first();
            if ($legacy && $legacy->menus) {
                $legacySess = json_decode($legacy->menus, true);
                if ($legacySess && is_array($legacySess) && !empty($legacySess['id'])) {
                    $sessions[] = $legacySess;
                }
            }
        }

        // Upsert by ID
        $found = false;
        foreach ($sessions as $idx => $s) {
            if (($s['id'] ?? '') === $sessionData['id']) {
                $sessions[$idx] = array_merge($s, $sessionData);
                $found = true;
                break;
            }
        }
        if (!$found) {
            array_unshift($sessions, $sessionData);
        }

        // Save back meeting_sessions
        OsdmPortalConfig::updateOrCreate(
            ['config_key' => 'meeting_sessions'],
            [
                'menus' => json_encode($sessions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'published_at' => now(),
            ]
        );

        // Also save current active session in meeting_session
        OsdmPortalConfig::updateOrCreate(
            ['config_key' => 'meeting_session'],
            [
                'menus' => json_encode($sessionData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'published_at' => now(),
            ]
        );

        // Auto-save agenda to meeting_agendas
        if (!empty($sessionData['agenda'])) {
            $agendaName = trim($sessionData['agenda']);
            $agendasConfig = OsdmPortalConfig::where('config_key', 'meeting_agendas')->first();
            $agendas = $agendasConfig && $agendasConfig->menus ? json_decode($agendasConfig->menus, true) : [];
            if (!is_array($agendas)) $agendas = [];

            $exists = false;
            foreach ($agendas as $a) {
                if (strcasecmp(trim($a), $agendaName) === 0) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists && strlen($agendaName) > 2) {
                array_unshift($agendas, $agendaName);
                OsdmPortalConfig::updateOrCreate(
                    ['config_key' => 'meeting_agendas'],
                    ['menus' => json_encode($agendas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
                );
            }
        }

        return response()->json(['success' => true, 'session' => $sessionData, 'sessions' => $sessions]);
    }

    /**
     * DELETE /api/osdm/meeting/sessions/{id}
     */
    public function deleteMeetingSession(string $id): JsonResponse
    {
        $sessionsConfig = OsdmPortalConfig::where('config_key', 'meeting_sessions')->first();
        if ($sessionsConfig && $sessionsConfig->menus) {
            $sessions = json_decode($sessionsConfig->menus, true);
            if (is_array($sessions)) {
                $filtered = array_values(array_filter($sessions, fn($s) => ($s['id'] ?? '') !== $id));
                $sessionsConfig->menus = json_encode($filtered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $sessionsConfig->save();

                if (count($filtered) > 0) {
                    OsdmPortalConfig::updateOrCreate(
                        ['config_key' => 'meeting_session'],
                        ['menus' => json_encode($filtered[0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
                    );
                }
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * GET /api/osdm/meeting/attendees
     */
    public function getMeetingAttendees(): JsonResponse
    {
        $config = OsdmPortalConfig::where('config_key', 'meeting_attendees')->first();
        if (!$config || !$config->menus) {
            return response()->json([]);
        }

        return response()->json(json_decode($config->menus, true) ?: []);
    }

    /**
     * POST /api/osdm/meeting/attendees
     */
    public function saveMeetingAttendee(Request $request): JsonResponse
    {
        $newAttendee = $request->all();
        if (empty($newAttendee['id'])) {
            $newAttendee['id'] = 'att-' . round(microtime(true) * 1000);
        }
        if (empty($newAttendee['timestamp'])) {
            $newAttendee['timestamp'] = now()->toISOString();
        }

        // Cek apakah sesi rapat tujuan berstatus ditutup (isActive === false)
        if (!empty($newAttendee['meetingId'])) {
            $sessConfig = OsdmPortalConfig::where('config_key', 'meeting_sessions')->first();
            if ($sessConfig && $sessConfig->menus) {
                $sessions = json_decode($sessConfig->menus, true);
                if (is_array($sessions)) {
                    foreach ($sessions as $s) {
                        if (isset($s['id']) && $s['id'] === $newAttendee['meetingId'] && isset($s['isActive']) && $s['isActive'] === false) {
                            return response()->json(['success' => false, 'error' => 'Presensi rapat untuk sesi ini telah resmi ditutup oleh panitia.'], 403);
                        }
                    }
                }
            }
        }

        $config = OsdmPortalConfig::where('config_key', 'meeting_attendees')->first();
        $attendees = $config && $config->menus ? json_decode($config->menus, true) : [];
        if (!is_array($attendees)) $attendees = [];

        // Prepend new attendee
        array_unshift($attendees, $newAttendee);

        OsdmPortalConfig::updateOrCreate(
            ['config_key' => 'meeting_attendees'],
            ['menus' => json_encode($attendees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );

        // Also save attendee agendas to meeting_agendas if present
        if (!empty($newAttendee['agendas']) && is_array($newAttendee['agendas'])) {
            $agendasConfig = OsdmPortalConfig::where('config_key', 'meeting_agendas')->first();
            $agendas = $agendasConfig && $agendasConfig->menus ? json_decode($agendasConfig->menus, true) : [];
            if (!is_array($agendas)) $agendas = [];

            $changed = false;
            foreach ($newAttendee['agendas'] as $ag) {
                $agClean = trim($ag);
                $found = false;
                foreach ($agendas as $item) {
                    if (strcasecmp(trim($item), $agClean) === 0) {
                        $found = true;
                        break;
                    }
                }
                if (!$found && strlen($agClean) > 2) {
                    array_unshift($agendas, $agClean);
                    $changed = true;
                }
            }
            if ($changed) {
                OsdmPortalConfig::updateOrCreate(
                    ['config_key' => 'meeting_agendas'],
                    ['menus' => json_encode($agendas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
                );
            }
        }

        return response()->json(['success' => true, 'attendee' => $newAttendee]);
    }

    /**
     * DELETE /api/osdm/meeting/attendees/{id}
     */
    public function deleteMeetingAttendee(string $id): JsonResponse
    {
        $config = OsdmPortalConfig::where('config_key', 'meeting_attendees')->first();
        if ($config && $config->menus) {
            $attendees = json_decode($config->menus, true);
            if (is_array($attendees)) {
                $filtered = array_values(array_filter($attendees, fn($a) => ($a['id'] ?? '') !== $id));
                $config->menus = json_encode($filtered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $config->save();
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * GET /api/osdm/meeting/agendas
     */
    public function getMeetingAgendas(): JsonResponse
    {
        $config = OsdmPortalConfig::where('config_key', 'meeting_agendas')->first();
        if (!$config || !$config->menus) {
            return response()->json([]);
        }

        return response()->json(json_decode($config->menus, true) ?: []);
    }

    /**
     * POST /api/osdm/meeting/agendas
     */
    public function saveMeetingAgenda(Request $request): JsonResponse
    {
        $agendaName = trim($request->input('agenda', ''));
        if (strlen($agendaName) < 2) {
            return response()->json(['success' => false, 'error' => 'Agenda invalid'], 422);
        }

        $config = OsdmPortalConfig::where('config_key', 'meeting_agendas')->first();
        $agendas = $config && $config->menus ? json_decode($config->menus, true) : [];
        if (!is_array($agendas)) $agendas = [];

        $exists = false;
        foreach ($agendas as $a) {
            if (strcasecmp(trim($a), $agendaName) === 0) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            array_unshift($agendas, $agendaName);
            OsdmPortalConfig::updateOrCreate(
                ['config_key' => 'meeting_agendas'],
                ['menus' => json_encode($agendas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
            );
        }

        return response()->json(['success' => true, 'agendas' => $agendas]);
    }

    /**
     * DELETE /api/osdm/meeting/agendas
     */
    public function deleteMeetingAgenda(Request $request): JsonResponse
    {
        $agendaName = trim($request->input('agenda', ''));
        if (!$agendaName) {
            return response()->json(['success' => false, 'error' => 'Agenda invalid'], 422);
        }

        // 1. Hapus dari meeting_agendas
        $config = OsdmPortalConfig::where('config_key', 'meeting_agendas')->first();
        if ($config && $config->menus) {
            $agendas = json_decode($config->menus, true);
            if (is_array($agendas)) {
                $filtered = array_values(array_filter($agendas, fn($a) => strcasecmp(trim($a), $agendaName) !== 0));
                $config->menus = json_encode($filtered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $config->save();
            }
        }

        // 2. Cascade delete seluruh peserta (attendees) yang terdaftar pada agenda ini
        $attConfig = OsdmPortalConfig::where('config_key', 'meeting_attendees')->first();
        if ($attConfig && $attConfig->menus) {
            $attendees = json_decode($attConfig->menus, true);
            if (is_array($attendees)) {
                $filteredAttendees = array_values(array_filter($attendees, function ($att) use ($agendaName) {
                    if (!empty($att['agendas']) && is_array($att['agendas'])) {
                        foreach ($att['agendas'] as $ag) {
                            if (strcasecmp(trim($ag), $agendaName) === 0) {
                                return false; // Hapus peserta ini
                            }
                        }
                    }
                    if (!empty($att['agenda']) && strcasecmp(trim($att['agenda']), $agendaName) === 0) {
                        return false; // Hapus peserta ini
                    }
                    return true;
                }));
                $attConfig->menus = json_encode($filteredAttendees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $attConfig->save();
            }
        }

        // 3. Bersihkan dari meeting_session jika agenda yang sedang aktif sama dengan yang dihapus
        $sessConfig = OsdmPortalConfig::where('config_key', 'meeting_session')->first();
        if ($sessConfig && $sessConfig->menus) {
            $sess = json_decode($sessConfig->menus, true);
            if (is_array($sess)) {
                $modified = false;
                if (!empty($sess['agenda']) && strcasecmp(trim($sess['agenda']), $agendaName) === 0) {
                    $sess['agenda'] = '';
                    $modified = true;
                }
                if (!empty($sess['agendas']) && is_array($sess['agendas'])) {
                    $sess['agendas'] = array_values(array_filter($sess['agendas'], fn($a) => strcasecmp(trim($a), $agendaName) !== 0));
                    $modified = true;
                }
                if ($modified) {
                    $sessConfig->menus = json_encode($sess, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $sessConfig->save();
                }
            }
        }

        // 4. Bersihkan dari meeting_sessions (plural) agar seluruh sesi tersinkronisasi
        $sessionsConfig = OsdmPortalConfig::where('config_key', 'meeting_sessions')->first();
        if ($sessionsConfig && $sessionsConfig->menus) {
            $sessions = json_decode($sessionsConfig->menus, true);
            if (is_array($sessions)) {
                $sessModified = false;
                foreach ($sessions as &$s) {
                    if (!empty($s['agenda']) && strcasecmp(trim($s['agenda']), $agendaName) === 0) {
                        $s['agenda'] = '';
                        $sessModified = true;
                    }
                    if (!empty($s['agendas']) && is_array($s['agendas'])) {
                        $s['agendas'] = array_values(array_filter($s['agendas'], fn($a) => strcasecmp(trim($a), $agendaName) !== 0));
                        $sessModified = true;
                    }
                }
                unset($s);
                if ($sessModified) {
                    $sessionsConfig->menus = json_encode($sessions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $sessionsConfig->save();
                }
            }
        }

        return response()->json(['success' => true]);
    }

    // ─── Pegawai Custom Forms & Survei Dinamis ──────────────────────────

    /**
     * GET /api/osdm/pegawai/forms
     */
    public function getPegawaiForms(): JsonResponse
    {
        $config = OsdmPortalConfig::where('config_key', 'pegawai_forms')->first();
        if (!$config || !$config->menus) {
            return response()->json([]);
        }

        $forms = json_decode($config->menus, true);
        return response()->json(is_array($forms) ? $forms : []);
    }

    /**
     * POST /api/osdm/pegawai/forms
     */
    public function savePegawaiForm(Request $request): JsonResponse
    {
        $data = $request->all();
        $config = OsdmPortalConfig::where('config_key', 'pegawai_forms')->first();
        $forms = ($config && $config->menus) ? json_decode($config->menus, true) : [];
        if (!is_array($forms)) {
            $forms = [];
        }

        // Check if payload contains { forms: [...] }
        if (isset($data['forms']) && is_array($data['forms'])) {
            $forms = $data['forms'];
        } elseif (!empty($data['id'])) {
            $found = false;
            foreach ($forms as $idx => $f) {
                if (($f['id'] ?? '') === $data['id']) {
                    $forms[$idx] = array_merge($f, $data);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                array_unshift($forms, $data);
            }
        } else {
            return response()->json(['success' => false, 'error' => 'Data formulir tidak valid.'], 422);
        }

        OsdmPortalConfig::updateOrCreate(
            ['config_key' => 'pegawai_forms'],
            [
                'menus' => json_encode($forms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'published_at' => now(),
            ]
        );

        return response()->json(['success' => true, 'forms' => $forms]);
    }

    /**
     * DELETE /api/osdm/pegawai/forms/{id}
     */
    public function deletePegawaiForm(string $id): JsonResponse
    {
        $config = OsdmPortalConfig::where('config_key', 'pegawai_forms')->first();
        if ($config && $config->menus) {
            $forms = json_decode($config->menus, true);
            if (is_array($forms)) {
                $filtered = array_values(array_filter($forms, fn($f) => ($f['id'] ?? '') !== $id));
                $config->menus = json_encode($filtered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $config->save();
            }
        }

        // Clean up associated responses
        $respConfig = OsdmPortalConfig::where('config_key', 'pegawai_form_responses')->first();
        if ($respConfig && $respConfig->menus) {
            $responses = json_decode($respConfig->menus, true);
            if (is_array($responses)) {
                $filteredResponses = array_values(array_filter($responses, fn($r) => ($r['formId'] ?? '') !== $id));
                $respConfig->menus = json_encode($filteredResponses, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $respConfig->save();
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * GET /api/osdm/pegawai/form-responses
     */
    public function getPegawaiFormResponses(Request $request): JsonResponse
    {
        $config = OsdmPortalConfig::where('config_key', 'pegawai_form_responses')->first();
        if (!$config || !$config->menus) {
            return response()->json([]);
        }

        $responses = json_decode($config->menus, true);
        if (!is_array($responses)) {
            $responses = [];
        }

        $formId = $request->query('formId');
        if ($formId) {
            $responses = array_values(array_filter($responses, fn($r) => ($r['formId'] ?? '') === $formId));
        }

        return response()->json($responses);
    }

    /**
     * POST /api/osdm/pegawai/form-responses
     */
    public function savePegawaiFormResponse(Request $request): JsonResponse
    {
        $newResponse = $request->all();
        if (empty($newResponse['nip']) || empty($newResponse['formId'])) {
            return response()->json(['success' => false, 'error' => 'NIP dan ID Formulir wajib diisi.'], 422);
        }

        $rawWa = (string)($newResponse['nomorWa'] ?? '');
        $cleanWa = preg_replace('/[\s.\-_()]/', '', $rawWa);
        $digitsWa = preg_replace('/[^0-9]/', '', $cleanWa);
        if (empty($cleanWa) || strlen($digitsWa) < 10) {
            return response()->json([
                'success' => false,
                'error' => 'Nomor WhatsApp aktif wajib diisi dan lengkap (minimal 10-13 digit angka, contoh: 081234567890).'
            ], 422);
        }

        if (empty($newResponse['id'])) {
            $newResponse['id'] = 'resp-' . round(microtime(true) * 1000) . '-' . substr(md5(uniqid()), 0, 4);
        }
        if (empty($newResponse['submittedAt'])) {
            $newResponse['submittedAt'] = now()->toISOString();
        }

        // Enforce target kepegawaian (ASN vs Non ASN) if specified by form
        $formConfig = OsdmPortalConfig::where('config_key', 'pegawai_forms')->first();
        $forms = ($formConfig && $formConfig->menus) ? json_decode($formConfig->menus, true) : [];
        $targetForm = null;
        if (is_array($forms)) {
            foreach ($forms as $f) {
                if (($f['id'] ?? '') === $newResponse['formId']) {
                    $targetForm = $f;
                    break;
                }
            }
        }

        if ($targetForm && !empty($targetForm['targetKepegawaian']) && $targetForm['targetKepegawaian'] !== 'ALL') {
            $cleanNip = preg_replace('/[\s.\-]/', '', (string) $newResponse['nip']);
            $emp = OsdmEmployee::whereRaw("REPLACE(REPLACE(REPLACE(nip, ' ', ''), '.', ''), '-', '') = ?", [$cleanNip])->first();
            $empType = ($emp && !empty($emp->jenis_kepegawaian)) ? $emp->jenis_kepegawaian : 'ASN';

            if ($targetForm['targetKepegawaian'] === 'ASN' && $empType !== 'ASN') {
                return response()->json([
                    'success' => false,
                    'error' => 'Formulir ini dikhususkan untuk Pegawai ASN. Anda terdaftar sebagai Pegawai Non-ASN sehingga tidak dapat mengisi formulir ini.'
                ], 403);
            }
            if ($targetForm['targetKepegawaian'] === 'Non ASN' && $empType !== 'Non ASN') {
                return response()->json([
                    'success' => false,
                    'error' => 'Formulir ini dikhususkan untuk Pegawai Non-ASN. Anda terdaftar sebagai Pegawai ASN sehingga tidak dapat mengisi formulir ini.'
                ], 403);
            }
        }

        return DB::transaction(function () use ($newResponse) {
            $config = OsdmPortalConfig::where('config_key', 'pegawai_form_responses')->lockForUpdate()->first();
            $responses = ($config && $config->menus) ? json_decode($config->menus, true) : [];
            if (!is_array($responses)) {
                $responses = [];
            }

            // Clean NIP comparison to update if employee already submitted for this same form
            $cleanNip = preg_replace('/[\s.\-]/', '', (string) $newResponse['nip']);
            $existingIdx = -1;
            foreach ($responses as $idx => $r) {
                $rCleanNip = preg_replace('/[\s.\-]/', '', (string) ($r['nip'] ?? ''));
                if ($rCleanNip === $cleanNip && ($r['formId'] ?? '') === $newResponse['formId']) {
                    $existingIdx = $idx;
                    break;
                }
            }

            if ($existingIdx >= 0) {
                // Update existing response
                $newResponse['id'] = $responses[$existingIdx]['id'];
                $responses[$existingIdx] = array_merge($responses[$existingIdx], $newResponse);
            } else {
                // Add new response
                array_unshift($responses, $newResponse);
            }

            OsdmPortalConfig::updateOrCreate(
                ['config_key' => 'pegawai_form_responses'],
                [
                    'menus' => json_encode($responses, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'published_at' => now(),
                ]
            );

            return response()->json(['success' => true, 'response' => $newResponse]);
        });
    }

    /**
     * DELETE /api/osdm/pegawai/form-responses/{id}
     */
    public function deletePegawaiFormResponse(string $id): JsonResponse
    {
        $config = OsdmPortalConfig::where('config_key', 'pegawai_form_responses')->first();
        if ($config && $config->menus) {
            $responses = json_decode($config->menus, true);
            if (is_array($responses)) {
                $filtered = array_values(array_filter($responses, fn($r) => ($r['id'] ?? '') !== $id));
                $config->menus = json_encode($filtered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $config->save();
            }
        }

        return response()->json(['success' => true]);
    }
}


