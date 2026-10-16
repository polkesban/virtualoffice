<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesUnitPageAccess;
use App\Http\Controllers\Controller;
use App\Models\UnitEventAttendance;
use App\Models\UnitEvent;
use App\Models\UnitEventParticipant;
use App\Models\UnitPage;
use App\Models\User;
use App\Support\DukEmployeeDirectory;
use App\Support\QrCodeDataUri;
use App\Support\UnitEventEmployeeRoster;
use App\Support\UnitEventParticipantAccountResolver;
use App\Support\UnitEventParticipantSpreadsheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UnitEventParticipantController extends Controller
{
    use AuthorizesUnitPageAccess;

    private const DEFAULT_EMPLOYEE_INSTITUTION = 'Poltekkes Kemenkes Bandung';
    private const LEGACY_WRONG_EMPLOYEE_INSTITUTION = 'Poltekkes Kemenkes Banjarmasin';

    public function index(Request $request, UnitPage $unitPage, UnitEvent $unitEvent, UnitEventEmployeeRoster $employeeRoster): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $prodiId = $request->input('program_studi_id');
        $participantsQuery = $unitEvent->participants()
            ->with([
                'attendances' => fn ($query) => $query->orderBy('attendance_date')->orderBy('signed_at'),
                'linkedUser',
                'linkedMahasiswa.programStudi',
            ]);

        if (filled($prodiId)) {
            $prodiToInstansiMap = [
                1  => ['STr Keperawatan', 'D4 Keperawatan'],
                2  => ['STr Kebidanan', 'D4 Kebidanan'],
                3  => ['STr Gizi', 'D4 Gizi'],
                5  => ['STr Sanitasi Lingkungan', 'D4 Kesehatan Lingkungan', 'D4 Sanitasi Lingkungan'],
                6  => ['DIII Teknologi Laboratorium Medis', 'D3 Analis Kesehatan', 'Analis Kesehatan', 'DIII Analis Kesehatan'],
                10 => ['STr Teknologi Laboratorium Medis', 'Sarjana Terapan TLM', 'D4 TLM'],
                11 => ['STr Terapi Gigi', 'Terapi Gigi'],
                12 => ['DIII Keperawatan (Kampus Bandung)', 'Keperawatan Bandung', 'DIII Keperawatan Bandung'],
                13 => ['DIII Keperawatan (Kampus Bogor)', 'Keperawatan Bogor', 'DIII Keperawatan Bogor'],
                14 => ['DIII Kebidanan (Kampus Bandung)', 'Kebidanan Bandung', 'DIII Kebidanan Bandung'],
                15 => ['DIII Kebidanan (Kampus Karawang)', 'Kebidanan Karawang', 'DIII Kebidanan Karawang'],
                16 => ['DIII Kebidanan (Kampus Bogor)', 'Kebidanan Bogor', 'DIII Kebidanan Bogor'],
                17 => ['DIII Kesehatan Gigi', 'Kesehatan Gigi', 'DIII Terapi Gigi'],
                18 => ['DIII Sanitasi', 'Sanitasi & Kesehatan Lingkungan', 'Sanitasi'],
                19 => ['Profesi Ners', 'Ners'],
                20 => ['Profesi Bidan'],
                21 => ['Profesi Dietisien'],
                22 => ['DIII Gizi', 'Gizi'],
            ];

            $terms = $prodiToInstansiMap[$prodiId] ?? [];

            $participantsQuery->where(function ($query) use ($prodiId, $terms) {
                $query->whereHas('linkedMahasiswa', function ($q) use ($prodiId) {
                    $q->where('program_studi_id', $prodiId);
                })
                ->orWhereIn('identity_number', function ($q) use ($prodiId) {
                    $q->select('nim')
                        ->from('mahasiswa')
                        ->where('program_studi_id', $prodiId);
                });

                if (!empty($terms)) {
                    $query->orWhere(function ($q) use ($terms) {
                        foreach ($terms as $term) {
                            $q->orWhere('instansi', 'like', '%' . $term . '%');
                        }
                    });
                }
            });
        }

        $participants = $participantsQuery->get();
        $attendanceDates = $unitEvent->attendanceDates();
        $employeeQuery = trim($request->string('pegawai_q')->toString());
        $employeeSearchResults = $employeeRoster->searchEmployees($employeeQuery);
        $assignmentLetterPreview = ($request->boolean('read_assignment_letter') || filled($unitEvent->surat_tugas_event_path))
            ? $employeeRoster->previewEventAssignmentLetter($unitEvent)
            : null;

        $prodiList = \App\Models\ProgramStudi::orderBy('nama')->get();

        $wisudaProdis = [];
        $wisudaJurusanCounts = [];
        $jurusanDefinitions = [
            'keperawatan' => [
                'label' => 'Jurusan Keperawatan',
                'terms' => ['Keperawatan', 'Ners'],
            ],
            'kebidanan' => [
                'label' => 'Jurusan Kebidanan',
                'terms' => ['Kebidanan', 'Bidan'],
            ],
            'gizi' => [
                'label' => 'Jurusan Gizi',
                'terms' => ['Gizi', 'Dietisien'],
            ],
            'sanitasi' => [
                'label' => 'Jurusan Sanitasi / Kesling',
                'terms' => ['Sanitasi'],
            ],
            'tlm' => [
                'label' => 'Jurusan Teknologi Lab Medis',
                'terms' => ['Teknologi Laboratorium Medis', 'TLM'],
            ],
            'kesehatan-gigi' => [
                'label' => 'Jurusan Kesehatan Gigi',
                'terms' => ['Kesehatan Gigi', 'Terapi Gigi'],
            ],
            'farmasi' => [
                'label' => 'Jurusan Farmasi',
                'terms' => ['Farmasi'],
            ],
            'promkes' => [
                'label' => 'Jurusan Promosi Kesehatan',
                'terms' => ['Promosi Kesehatan'],
            ],
        ];

        if ($unitEvent->jenis_kegiatan === 'wisuda') {
            $wisudaProdis = $unitEvent->participants()
                ->where('peran', 'participant')
                ->whereNotNull('instansi')
                ->selectRaw('instansi, count(*) as count')
                ->groupBy('instansi')
                ->orderBy('instansi')
                ->pluck('count', 'instansi')
                ->toArray();

            foreach ($jurusanDefinitions as $key => $def) {
                $jCount = 0;
                foreach ($wisudaProdis as $prodiName => $count) {
                    foreach ($def['terms'] as $term) {
                        if (stripos($prodiName, $term) !== false) {
                            $jCount += $count;
                            break;
                        }
                    }
                }
                $wisudaJurusanCounts[$key] = $jCount;
            }
        }

        return view('admin.unit-events.participants.index', compact(
            'unitPage',
            'unitEvent',
            'participants',
            'attendanceDates',
            'employeeQuery',
            'employeeSearchResults',
            'assignmentLetterPreview',
            'prodiList',
            'wisudaProdis',
            'wisudaJurusanCounts',
            'jurusanDefinitions',
        ));
    }

    public function attachEmployees(
        Request $request,
        UnitPage $unitPage,
        UnitEvent $unitEvent,
        UnitEventParticipantAccountResolver $accountResolver,
        DukEmployeeDirectory $dukEmployeeDirectory
    ): RedirectResponse {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $data = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_EMPLOYEE)),
            ],
            'peran' => 'required|string|in:' . implode(',', array_keys(UnitEventParticipant::$peranLabels)),
            'source' => 'nullable|string|max:50',
        ]);

        $employees = User::query()
            ->where('role', User::ROLE_EMPLOYEE)
            ->whereIn('id', $data['employee_ids'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'identity_number', 'role']);

        if ($employees->isEmpty()) {
            return back()->withErrors([
                'employee_ids' => 'Tidak ada pegawai valid yang dipilih.',
            ]);
        }

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $source = trim((string) ($data['source'] ?? 'manual_search')) ?: 'manual_search';

        DB::transaction(function () use ($employees, $unitEvent, $accountResolver, $dukEmployeeDirectory, $data, $source, &$createdCount, &$updatedCount, &$skippedCount): void {
            foreach ($employees as $employee) {
                if ($data['peran'] === 'committee' && ! $employee->is_admin) {
                    $employee->forceFill(['is_admin' => true])->save();
                }

                $dukProfile = $dukEmployeeDirectory->find($employee->identity_number);
                $existingParticipant = $this->findExistingEmployeeParticipant($unitEvent, $employee, $accountResolver);

                if (! $existingParticipant) {
                    $participant = UnitEventParticipant::create($this->buildEmployeeParticipantPayload($employee, $unitEvent, $data['peran'], $source, $accountResolver, $dukProfile));
                    $this->ensureParticipantCredentials($participant);
                    $createdCount++;
                    continue;
                }

                $updates = $this->buildEmployeeParticipantUpdates($existingParticipant, $employee, $data['peran'], $source, $accountResolver, $dukProfile);

                if ($updates === []) {
                    $skippedCount++;
                    continue;
                }

                $existingParticipant->fill($updates);
                $existingParticipant->save();
                $this->ensureParticipantCredentials($existingParticipant);
                $updatedCount++;
            }
        });

        $message = 'Sinkronisasi pegawai ke event selesai.';

        if ($createdCount > 0) {
            $message .= ' ' . $createdCount . ' pegawai ditambahkan.';
        }

        if ($updatedCount > 0) {
            $message .= ' ' . $updatedCount . ' peserta lama diperbarui atau dilink ke akun pegawai.';
        }

        if ($skippedCount > 0) {
            $message .= ' ' . $skippedCount . ' data sudah sesuai sehingga dilewati.';
        }

        return back()->with('success', $message);
    }

    public function attendancePrint(Request $request, UnitPage $unitPage, UnitEvent $unitEvent): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $attendanceDates = $unitEvent->attendanceDates();
        $selectedDate = trim($request->string('tanggal')->toString());
        $format = trim($request->string('format')->toString());

        if ($selectedDate !== '') {
            abort_unless(in_array($selectedDate, $unitEvent->attendanceDateStrings(), true), 404);
            $printDates = $attendanceDates
                ->filter(fn ($date) => $date->toDateString() === $selectedDate)
                ->values();
        } else {
            $printDates = $attendanceDates;
            $selectedDate = null;
        }

        $participants = $unitEvent->participants()
            ->with(['attendances' => fn ($query) => $query->orderBy('attendance_date')->orderBy('signed_at')])
            ->get();

        $viewName = ($format === 'lembur' || (empty($format) && $unitEvent->jenis_kegiatan === 'kerja_lembur'))
            ? 'admin.unit-events.participants.attendance-print-lembur' 
            : 'admin.unit-events.participants.attendance-print';

        return view($viewName, compact(
            'unitPage',
            'unitEvent',
            'participants',
            'attendanceDates',
            'printDates',
            'selectedDate',
        ));
    }

    public function credentialsPrint(UnitPage $unitPage, UnitEvent $unitEvent, QrCodeDataUri $qrCode): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $participants = $unitEvent->participants()
            ->with(['linkedUser', 'linkedMahasiswa'])
            ->where(function ($query) {
                $query->whereNotNull('access_password')
                    ->orWhereNotNull('linked_user_id')
                    ->orWhereNotNull('linked_mahasiswa_id');
            })
            ->orderBy('peran')
            ->orderBy('nama')
            ->get();

        $loginUrl = route('unit.events.login.show', [$unitPage->slug, $unitEvent->slug]);
        $credentialRows = $this->buildCredentialRows($participants, $unitPage, $unitEvent, $qrCode);

        return view('admin.unit-events.participants.credentials-print', compact(
            'unitPage',
            'unitEvent',
            'participants',
            'credentialRows',
            'loginUrl',
        ));
    }

    public function credentialsCardsPrint(UnitPage $unitPage, UnitEvent $unitEvent, QrCodeDataUri $qrCode): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $participants = $unitEvent->participants()
            ->with(['linkedUser', 'linkedMahasiswa'])
            ->where(function ($query) {
                $query->whereNotNull('access_password')
                    ->orWhereNotNull('linked_user_id')
                    ->orWhereNotNull('linked_mahasiswa_id');
            })
            ->orderBy('peran')
            ->orderBy('nama')
            ->get();

        $loginUrl = route('unit.events.login.show', [$unitPage->slug, $unitEvent->slug]);
        $credentialRows = $this->buildCredentialRows($participants, $unitPage, $unitEvent, $qrCode);

        return view('admin.unit-events.participants.credentials-cards-print', compact(
            'unitPage',
            'unitEvent',
            'participants',
            'credentialRows',
            'loginUrl',
        ));
    }

    public function downloadImportTemplate(
        UnitPage $unitPage,
        UnitEvent $unitEvent,
        UnitEventParticipantSpreadsheet $spreadsheet
    ): BinaryFileResponse {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $path = $spreadsheet->createTemplateFile($unitEvent->judul);
        $filename = 'template-impor-peserta-' . (Str::slug($unitEvent->judul) ?: 'event') . '.xlsx';

        return response()->download(
            $path,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    public function exportExcel(
        Request $request,
        UnitPage $unitPage,
        UnitEvent $unitEvent,
        UnitEventParticipantSpreadsheet $spreadsheet
    ): BinaryFileResponse {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $prodiId = $request->input('program_studi_id');
        $isPraktik = ($unitEvent->jenis_kegiatan === 'pbm' && ($unitEvent->sub_jenis_kegiatan === 'praktik' || stripos($unitEvent->judul, 'praktik') !== false || stripos($unitEvent->judul, 'magang') !== false));

        $participantsQuery = $unitEvent->participants()
            ->with(['linkedMahasiswa.programStudi', 'attendances']);

        if (filled($prodiId)) {
            $prodiToInstansiMap = [
                1  => ['STr Keperawatan', 'D4 Keperawatan'],
                2  => ['STr Kebidanan', 'D4 Kebidanan'],
                3  => ['STr Gizi', 'D4 Gizi'],
                5  => ['STr Sanitasi Lingkungan', 'D4 Kesehatan Lingkungan', 'D4 Sanitasi Lingkungan'],
                6  => ['DIII Teknologi Laboratorium Medis', 'D3 Analis Kesehatan', 'Analis Kesehatan', 'DIII Analis Kesehatan'],
                10 => ['STr Teknologi Laboratorium Medis', 'Sarjana Terapan TLM', 'D4 TLM'],
                11 => ['STr Terapi Gigi', 'Terapi Gigi'],
                12 => ['DIII Keperawatan (Kampus Bandung)', 'Keperawatan Bandung', 'DIII Keperawatan Bandung'],
                13 => ['DIII Keperawatan (Kampus Bogor)', 'Keperawatan Bogor', 'DIII Keperawatan Bogor'],
                14 => ['DIII Kebidanan (Kampus Bandung)', 'Kebidanan Bandung', 'DIII Kebidanan Bandung'],
                15 => ['DIII Kebidanan (Kampus Karawang)', 'Kebidanan Karawang', 'DIII Kebidanan Karawang'],
                16 => ['DIII Kebidanan (Kampus Bogor)', 'Kebidanan Bogor', 'DIII Kebidanan Bogor'],
                17 => ['DIII Kesehatan Gigi', 'Kesehatan Gigi', 'DIII Terapi Gigi'],
                18 => ['DIII Sanitasi', 'Sanitasi & Kesehatan Lingkungan', 'Sanitasi'],
                19 => ['Profesi Ners', 'Ners'],
                20 => ['Profesi Bidan'],
                21 => ['Profesi Dietisien'],
                22 => ['DIII Gizi', 'Gizi'],
            ];

            $terms = $prodiToInstansiMap[$prodiId] ?? [];

            $participantsQuery->where(function ($query) use ($prodiId, $terms) {
                $query->whereHas('linkedMahasiswa', function ($q) use ($prodiId) {
                    $q->where('program_studi_id', $prodiId);
                })
                ->orWhereIn('identity_number', function ($q) use ($prodiId) {
                    $q->select('nim')
                        ->from('mahasiswa')
                        ->where('program_studi_id', $prodiId);
                });

                if (!empty($terms)) {
                    $query->orWhere(function ($q) use ($terms) {
                        foreach ($terms as $term) {
                            $q->orWhere('instansi', 'like', '%' . $term . '%');
                        }
                    });
                }
            });
        }

        $participants = $participantsQuery->get();
        $unitEvent->load('page');

        $isGabungan = ($unitEvent->jenis_kegiatan === 'pbm' && $unitEvent->sub_jenis_kegiatan === 'gabungan');

        if ($isGabungan) {
            $path = $spreadsheet->exportGabunganRecap($unitEvent, $participants);
            $filename = 'rekap-kehadiran-gabungan-' . (Str::slug($unitEvent->judul) ?: 'event') . '.xlsx';
        } elseif ($isPraktik) {
            $path = $spreadsheet->exportPraktikRecap($unitEvent, $participants);
            $filename = 'rekap-kehadiran-praktik-' . (Str::slug($unitEvent->judul) ?: 'event') . '.xlsx';
        } else {
            $path = $spreadsheet->createExportFile($unitEvent, $participants);
            $filename = 'daftar-undangan-' . (Str::slug($unitEvent->judul) ?: 'event') . '.xlsx';
        }

        return response()->download(
            $path,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    public function import(
        Request $request,
        UnitPage $unitPage,
        UnitEvent $unitEvent,
        UnitEventParticipantSpreadsheet $spreadsheet,
        UnitEventParticipantAccountResolver $accountResolver,
        DukEmployeeDirectory $dukEmployeeDirectory
    ) {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $data = $request->validate([
            'participants_file' => 'required|file|mimes:xlsx,xls,csv,txt,pdf|max:10240',
        ]);

        $file = $request->file('participants_file');
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'pdf') {
            return $this->splitPdfForAjax($request, $unitPage, $unitEvent, $file);
        }

        $rows = $spreadsheet->readImportRows($data['participants_file']);

        if ($rows === []) {
            return back()->withErrors([
                'participants_file' => 'Tidak ada baris peserta yang bisa diimpor dari file tersebut.',
            ]);
        }

        $knownSignatures = [];
        $knownNims = [];
        $knownNames = [];

        foreach ($unitEvent->participants()->get() as $participant) {
            $knownSignatures[$this->participantSignature([
                'peran' => $participant->peran,
                'nama' => $participant->nama,
                'email' => $participant->email,
                'identity_number' => $participant->identity_number,
                'no_hp' => $participant->no_hp,
                'instansi' => $participant->instansi,
                'jabatan' => $participant->jabatan,
            ])] = true;

            if ($participant->identity_number) {
                $knownNims[$participant->identity_number] = true;
            }

            $cleanName = $participant->nama;
            if (Str::startsWith($cleanName, 'Orang Tua / Wali dari ')) {
                $cleanName = str_replace('Orang Tua / Wali dari ', '', $cleanName);
            } elseif (Str::startsWith($cleanName, 'Orang tua/ Wali ')) {
                $cleanName = str_replace('Orang tua/ Wali ', '', $cleanName);
            }
            $knownNames[Str::lower(trim($cleanName))] = true;

            if (!empty($participant->meta['nama_wisudawan'])) {
                $knownNames[Str::lower(trim($participant->meta['nama_wisudawan']))] = true;
            }
        }

        $importedCount = 0;
        $skippedCount = 0;
        $linkedAccountCount = 0;

        DB::transaction(function () use ($rows, $unitEvent, $accountResolver, $dukEmployeeDirectory, &$knownSignatures, &$knownNims, &$knownNames, &$importedCount, &$skippedCount, &$linkedAccountCount): void {
            foreach ($rows as $row) {
                $signature = $this->participantSignature($row);

                if (isset($knownSignatures[$signature])) {
                    $skippedCount++;
                    continue;
                }

                $linkAttributes = $accountResolver->resolveLinkAttributes($row['identity_number'] ?? null, $row['email'] ?? null);
                $participantCategory = $accountResolver->resolveParticipantCategory($row['participant_category'] ?? null, $linkAttributes);

                $identityNum = $row['identity_number'] !== '' ? $row['identity_number'] : null;
                $rowName = trim($row['nama']);

                $isStudent = false;
                if (($row['peran'] ?? 'participant') === 'participant') {
                    $isStudent = ($participantCategory === 'student')
                        || ($identityNum && preg_match('/^P\d+/i', $identityNum))
                        || (isset($row['participant_category']) && str_contains(strtolower($row['participant_category']), 'mahasiswa'))
                        || (isset($row['instansi']) && preg_match('/^(DIII|D3|DIV|D4|STr|Profesi)/i', trim($row['instansi'])));
                }

                if ($isStudent) {
                    if ($identityNum && isset($knownNims[$identityNum])) {
                        $skippedCount++;
                        continue;
                    }
                    if (isset($knownNames[Str::lower($rowName)])) {
                        $skippedCount++;
                        continue;
                    }
                }

                $dukProfile = $participantCategory === 'employee'
                    ? $dukEmployeeDirectory->find($row['identity_number'] ?? null)
                    : null;

                $meta = [];
                $nama = $rowName;
                if ($isStudent && $unitEvent->jenis_kegiatan === 'wisuda') {
                    $meta['nama_wisudawan'] = $rowName;
                    if (!Str::startsWith($rowName, 'Orang Tua / Wali dari') && !Str::startsWith($rowName, 'Orang tua/ Wali')) {
                        $nama = 'Orang Tua / Wali dari ' . $rowName;
                    }

                    if ($identityNum) {
                        $knownNims[$identityNum] = true;
                    }
                    $knownNames[Str::lower($rowName)] = true;
                }

                $participant = UnitEventParticipant::create([
                    'unit_event_id' => $unitEvent->id,
                    'peran' => $row['peran'],
                    'participant_category' => $participantCategory,
                    'sumber_registrasi' => 'admin_import',
                    'nama' => $nama,
                    'email' => $row['email'] !== '' ? $row['email'] : null,
                    'identity_number' => $identityNum,
                    'no_hp' => $row['no_hp'] !== '' ? $row['no_hp'] : null,
                    'instansi' => $row['instansi'] !== ''
                        ? $row['instansi']
                        : ($participantCategory === 'employee'
                            ? $this->normalizedEmployeeInstitution(null, $dukProfile['institution'] ?? null)
                            : null),
                    'tempat_praktik' => $row['tempat_praktik'] !== '' ? $row['tempat_praktik'] : null,
                    'nama_pembimbing' => $row['nama_pembimbing'] !== '' ? $row['nama_pembimbing'] : null,
                    'jabatan' => $row['jabatan'] !== ''
                        ? $row['jabatan']
                        : ($participantCategory === 'employee'
                            ? $this->resolvedEmployeePosition(null, $dukProfile['position'] ?? null)
                            : null),
                    'catatan' => $row['catatan'] !== '' ? $row['catatan'] : null,
                    'kode_registrasi' => $this->generateUniqueCode(),
                    'access_token' => $this->generateUniqueToken(),
                    'registered_at' => now(),
                    'meta' => !empty($meta) ? $meta : null,
                    ...$linkAttributes,
                ]);

                $this->ensureParticipantCredentials($participant);

                if ($participant->usesLinkedApplicationAccess()) {
                    $linkedAccountCount++;
                }

                $knownSignatures[$signature] = true;
                $importedCount++;
            }
        });

        $message = 'Import peserta selesai. ' . $importedCount . ' peserta ditambahkan. Username menggunakan kode registrasi dan password sementara siap dicetak.';

        if ($linkedAccountCount > 0) {
            $message .= ' ' . $linkedAccountCount . ' peserta terhubung ke akun aplikasi yang sudah ada dan dapat langsung membuka daftar hadir saat login.';
        }

        if ($skippedCount > 0) {
            $message .= ' ' . $skippedCount . ' baris identik dilewati.';
        }

        return redirect()->route('admin.unit-events.participants.index', [$unitPage, $unitEvent])
            ->with('success', $message);
    }

    private function splitPdfForAjax(Request $request, UnitPage $unitPage, UnitEvent $unitEvent, \Illuminate\Http\UploadedFile $file): \Illuminate\Http\JsonResponse
    {
        $tempDirName = 'temp-pdf-import-' . time();
        $tempDir = storage_path('app/public/' . $tempDirName);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $pdfPath = $file->storeAs('temp', 'import-' . time() . '.pdf');
        $absolutePdfPath = Storage::path($pdfPath);

        $outputPrefix = $tempDir . '/page';
        $cmd = "pdftoppm -png -r 150 " . escapeshellarg($absolutePdfPath) . " " . escapeshellarg($outputPrefix) . " 2>&1";
        $output = [];
        $resultCode = -1;
        exec($cmd, $output, $resultCode);

        \Log::info("pdftoppm command executed: " . $cmd);
        \Log::info("pdftoppm exit code: " . $resultCode);
        \Log::info("pdftoppm output: " . implode("\n", $output));

        $images = glob($tempDir . '/page-*.png');
        @unlink($absolutePdfPath);

        if (empty($images)) {
            @rmdir($tempDir);
            return response()->json([
                'success' => false, 
                'message' => 'Gagal mengonversi file PDF ke gambar. Command: ' . $cmd . ' | Exit code: ' . $resultCode . ' | Output: ' . implode('\n', $output)
            ], 400);
        }

        natsort($images);
        
        $relativePaths = array_map(function($img) use ($tempDirName) {
            return 'public/' . $tempDirName . '/' . basename($img);
        }, $images);

        return response()->json([
            'success' => true,
            'type' => 'pdf',
            'pages' => array_values($relativePaths),
            'temp_dir_name' => $tempDirName,
        ]);
    }

    public function importPdfPage(Request $request, UnitPage $unitPage, UnitEvent $unitEvent): \Illuminate\Http\JsonResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $imagePath = $request->input('image_path');
        if (blank($imagePath)) {
            return response()->json(['success' => false, 'message' => 'Path gambar kosong.'], 400);
        }

        $absolutePath = storage_path('app/' . $imagePath);
        if (!is_file($absolutePath)) {
            return response()->json(['success' => false, 'message' => 'File gambar tidak ditemukan.'], 404);
        }

        $aiClient = new \App\Services\AI\AiClient();
        $prompt = "Ini adalah daftar calon wisudawan/wisudawati untuk wisuda Poltekkes Bandung. " .
                  "Tolong ekstrak semua data dari tabel yang ada di gambar ini ke dalam format JSON. " .
                  "JSON harus berupa array of objects dengan kunci: 'nama_wisudawan', 'nim', dan 'nomor_kursi'. " .
                  "Jika ada kolom nomor kursi atau baris, isi 'nomor_kursi' dengannya. Jika tidak ada, biarkan null. " .
                  "Kembalikan HANYA block kode JSON yang valid, tanpa teks penjelasan sebelum atau sesudahnya. " .
                  "Format: [ { \"nama_wisudawan\": \"Nama\", \"nim\": \"NIM\", \"nomor_kursi\": \"Kursi\" } ]";

        $graduates = [];
        $maxRetries = 3;
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            $attempt++;
            try {
                $resultText = $aiClient->vision($absolutePath, $prompt, 0.1, 4096);
                
                $jsonText = '';
                if (preg_match('/\[\s*\{.*\}\s*\]/s', $resultText, $matches)) {
                    $jsonText = $matches[0];
                } else {
                    $cleanText = preg_replace('/^```(?:json)?/i', '', $resultText);
                    $cleanText = preg_replace('/```$/', '', $cleanText);
                    $jsonText = trim($cleanText);
                }

                $pageGraduates = json_decode($jsonText, true);
                if (is_array($pageGraduates) && !empty($pageGraduates)) {
                    $graduates = $pageGraduates;
                    break;
                }
            } catch (\Throwable $e) {
                $lastException = $e;
                \Log::warning("Vision parse attempt {$attempt} failed for " . basename($absolutePath) . ": " . $e->getMessage());
                if ($attempt < $maxRetries) {
                    sleep(3);
                }
            }
        }

        @unlink($absolutePath);

        if (empty($graduates)) {
            if ($lastException) {
                $errorMsg = $lastException->getMessage();
                \Log::error("Failed to parse PDF page after {$maxRetries} attempts: " . $errorMsg);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membaca halaman PDF setelah beberapa kali percobaan. Error: ' . $errorMsg
                ], 500);
            }
            
            return response()->json(['success' => true, 'count' => 0, 'message' => 'Tidak ada data wisudawan terdeteksi di halaman ini.']);
        }

        $importedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use ($graduates, $unitEvent, &$importedCount, &$skippedCount) {
            foreach ($graduates as $grad) {
                $namaWisudawan = trim($grad['nama_wisudawan'] ?? '');
                $nim = trim($grad['nim'] ?? '');
                $nomorKursi = trim($grad['nomor_kursi'] ?? '');

                if (empty($namaWisudawan)) {
                    continue;
                }

                $invitationName = "Orang Tua / Wali dari " . $namaWisudawan;

                $exists = $unitEvent->participants()
                    ->where(function ($query) use ($nim, $namaWisudawan) {
                        if ($nim) {
                            $query->where('identity_number', $nim);
                        }
                        $query->orWhere('meta->nama_wisudawan', $namaWisudawan);
                    })
                    ->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                $participant = UnitEventParticipant::create([
                    'unit_event_id' => $unitEvent->id,
                    'peran' => 'participant',
                    'participant_category' => 'public',
                    'sumber_registrasi' => 'admin_import',
                    'nama' => $invitationName,
                    'identity_number' => $nim ?: null,
                    'kode_registrasi' => $this->generateUniqueCode(),
                    'access_token' => $this->generateUniqueToken(),
                    'registered_at' => now(),
                    'meta' => [
                        'nama_wisudawan' => $namaWisudawan,
                        'nomor_kursi' => $nomorKursi ?: null,
                    ]
                ]);

                $this->ensureParticipantCredentials($participant);
                $importedCount++;
            }
        });

        $parentDir = dirname($absolutePath);
        if (is_dir($parentDir)) {
            $files = array_diff(scandir($parentDir), ['.', '..']);
            if (empty($files)) {
                @rmdir($parentDir);
            }
        }

        return response()->json([
            'success' => true,
            'count' => $importedCount,
            'skipped' => $skippedCount,
        ]);
    }

    public function create(UnitPage $unitPage, UnitEvent $unitEvent, DukEmployeeDirectory $dukEmployeeDirectory): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $participant = new UnitEventParticipant([
            'peran' => 'participant',
            'participant_category' => 'public',
            'registered_at' => now(),
        ]);

        $employees = $dukEmployeeDirectory->all();

        return view('admin.unit-events.participants.form', compact('unitPage', 'unitEvent', 'participant', 'employees'));
    }

    public function store(Request $request, UnitPage $unitPage, UnitEvent $unitEvent, UnitEventParticipantAccountResolver $accountResolver): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $data = $this->validatedData($request, null, $accountResolver);
        $data['unit_event_id'] = $unitEvent->id;
        $data['sumber_registrasi'] = 'admin';
        $data['kode_registrasi'] = $this->generateUniqueCode();
        $data['access_token'] = $this->generateUniqueToken();
        $data['registered_at'] = $data['registered_at'] ?? now();

        if ($request->hasFile('foto')) {
            $data['foto_path'] = \App\Support\MediaStorage::storeUploadedFile($request->file('foto'), 'unit-events/speakers');
        }

        if ($request->hasFile('sertifikat_file')) {
            $data['sertifikat_path'] = \App\Support\MediaStorage::storeUploadedFile($request->file('sertifikat_file'), 'unit-events/certificates');
            $data['sertifikat_uploaded_at'] = now();
        }

        if ($request->hasFile('surat_tugas_file')) {
            $data['surat_tugas_path'] = \App\Support\MediaStorage::storeUploadedFile($request->file('surat_tugas_file'), 'unit-events/assignment-letters');
            $data['surat_tugas_uploaded_at'] = now();
        }

        $participant = UnitEventParticipant::create($data);
        $this->ensureParticipantCredentials($participant);
        $this->syncManualAttendance($unitEvent, $participant, $request);

        return redirect()->route('admin.unit-events.participants.index', [$unitPage, $unitEvent])
            ->with('success', $participant->hasAccessCredentials()
                ? 'Peserta / narasumber / panitia berhasil ditambahkan. Kode registrasi sekaligus password sementara adalah ' . $participant->kode_registrasi . '.'
                : 'Peserta / narasumber / panitia berhasil ditambahkan. Username menggunakan kode registrasi dan password sementara sudah dibuat.');
    }

    public function edit(UnitPage $unitPage, UnitEvent $unitEvent, UnitEventParticipant $participant, DukEmployeeDirectory $dukEmployeeDirectory): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);
        abort_if((int) $participant->unit_event_id !== (int) $unitEvent->id, 404);

        $employees = $dukEmployeeDirectory->all();

        return view('admin.unit-events.participants.form', compact('unitPage', 'unitEvent', 'participant', 'employees'));
    }

    public function update(Request $request, UnitPage $unitPage, UnitEvent $unitEvent, UnitEventParticipant $participant, UnitEventParticipantAccountResolver $accountResolver): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);
        abort_if((int) $participant->unit_event_id !== (int) $unitEvent->id, 404);

        $data = $this->validatedData($request, $participant, $accountResolver);

        if ($request->boolean('hapus_foto') && $participant->foto_path) {
            \App\Support\MediaStorage::delete($participant->foto_path);
            $data['foto_path'] = null;
        }

        if ($request->hasFile('foto')) {
            \App\Support\MediaStorage::delete($participant->foto_path);
            $data['foto_path'] = \App\Support\MediaStorage::storeUploadedFile($request->file('foto'), 'unit-events/speakers');
        }

        if ($request->boolean('hapus_sertifikat') && $participant->sertifikat_path) {
            \App\Support\MediaStorage::delete($participant->sertifikat_path);
            $data['sertifikat_path'] = null;
            $data['sertifikat_uploaded_at'] = null;
        }

        if ($request->boolean('hapus_surat_tugas') && $participant->surat_tugas_path) {
            \App\Support\MediaStorage::delete($participant->surat_tugas_path);
            $data['surat_tugas_path'] = null;
            $data['surat_tugas_uploaded_at'] = null;
        }

        if ($request->hasFile('sertifikat_file')) {
            \App\Support\MediaStorage::delete($participant->sertifikat_path);
            $data['sertifikat_path'] = \App\Support\MediaStorage::storeUploadedFile($request->file('sertifikat_file'), 'unit-events/certificates');
            $data['sertifikat_uploaded_at'] = now();
        }

        if ($request->hasFile('surat_tugas_file')) {
            \App\Support\MediaStorage::delete($participant->surat_tugas_path);
            $data['surat_tugas_path'] = \App\Support\MediaStorage::storeUploadedFile($request->file('surat_tugas_file'), 'unit-events/assignment-letters');
            $data['surat_tugas_uploaded_at'] = now();
        }

        $participant->update($data);
        $this->syncManualAttendance($unitEvent, $participant, $request);

        return redirect()->route('admin.unit-events.participants.index', [$unitPage, $unitEvent])
            ->with('success', 'Data peserta berhasil diperbarui.');
    }

    public function destroy(UnitPage $unitPage, UnitEvent $unitEvent, UnitEventParticipant $participant): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);
        abort_if((int) $participant->unit_event_id !== (int) $unitEvent->id, 404);

        \App\Support\MediaStorage::delete($participant->foto_path);
        \App\Support\MediaStorage::delete($participant->sertifikat_path);
        \App\Support\MediaStorage::delete($participant->surat_tugas_path);

        foreach ($participant->attendances as $attendance) {
            \App\Support\MediaStorage::delete($attendance->signature_path);
        }

        $participant->delete();

        return redirect()->route('admin.unit-events.participants.index', [$unitPage, $unitEvent])
            ->with('success', 'Data peserta berhasil dihapus.');
    }

    public function destroyBulk(Request $request, UnitPage $unitPage, UnitEvent $unitEvent): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $ids = $request->input('ids');
        if (empty($ids) || !is_array($ids)) {
            return back()->withErrors(['ids' => 'Tidak ada peserta yang dipilih untuk dihapus.']);
        }

        $participants = $unitEvent->participants()->whereIn('id', $ids)->get();

        if ($participants->isEmpty()) {
            return back()->withErrors(['ids' => 'Peserta yang dipilih tidak ditemukan untuk event ini.']);
        }

        $deletedCount = 0;
        DB::transaction(function () use ($participants, &$deletedCount) {
            foreach ($participants as $participant) {
                \App\Support\MediaStorage::delete($participant->foto_path);
                \App\Support\MediaStorage::delete($participant->sertifikat_path);
                \App\Support\MediaStorage::delete($participant->surat_tugas_path);

                foreach ($participant->attendances as $attendance) {
                    \App\Support\MediaStorage::delete($attendance->signature_path);
                }

                $participant->delete();
                $deletedCount++;
            }
        });

        return redirect()->route('admin.unit-events.participants.index', [$unitPage, $unitEvent])
            ->with('success', "Berhasil menghapus {$deletedCount} data peserta.");
    }

    private function validatedData(
        Request $request,
        ?UnitEventParticipant $participant = null,
        ?UnitEventParticipantAccountResolver $accountResolver = null
    ): array
    {
        $extraValidation = [];
        if ($request->has('nama_wisudawan') || $request->has('nomor_kursi')) {
            $extraValidation = [
                'nama_wisudawan' => 'nullable|string|max:255',
                'nomor_kursi' => 'nullable|string|max:50',
            ];
        }

        $data = $request->validate([
            'peran' => 'required|string|in:' . implode(',', array_keys(UnitEventParticipant::$peranLabels)),
            'participant_category' => 'nullable|string|in:' . implode(',', array_keys(UnitEventParticipant::$participantCategoryLabels)),
            'nama' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'identity_number' => 'nullable|string|max:50',
            'no_hp' => 'nullable|string|max:50',
            'instansi' => 'nullable|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'catatan' => 'nullable|string|max:4000',
            'foto' => 'nullable|image|max:4096',
            'hapus_foto' => 'boolean',
            'registered_at' => 'nullable|date',
            'attended' => 'boolean',
            'attendance_notes' => 'nullable|string|max:2000',
            'sertifikat_file' => 'nullable|file|max:10240',
            'surat_tugas_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'hapus_sertifikat' => 'boolean',
            'hapus_surat_tugas' => 'boolean',
            ...$extraValidation,
        ]);

        if ($request->boolean('attended')) {
            $data['attended_at'] = $participant?->attended_at ?? now();
        } else {
            $data['attended_at'] = null;
        }

        if ($accountResolver) {
            $data['identity_number'] = $accountResolver->normalizeIdentityNumber($data['identity_number'] ?? null);
            $data = [
                ...$data,
                ...$accountResolver->resolveLinkAttributes($data['identity_number'] ?? null, $data['email'] ?? null),
            ];
            $data['participant_category'] = $accountResolver->resolveParticipantCategory($data['participant_category'] ?? null, $data);
        }

        $meta = $participant ? ($participant->meta ?? []) : [];
        if ($request->has('nama_wisudawan') || $request->has('nomor_kursi')) {
            $meta['nama_wisudawan'] = $request->input('nama_wisudawan');
            $meta['nomor_kursi'] = $request->input('nomor_kursi');
            $data['meta'] = $meta;
        }

        return $data;
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'EVT-' . Str::upper(Str::random(8));
        } while (UnitEventParticipant::query()->where('kode_registrasi', $code)->exists());

        return $code;
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(48);
        } while (UnitEventParticipant::query()->where('access_token', $token)->exists());

        return $token;
    }

    private function findExistingEmployeeParticipant(
        UnitEvent $unitEvent,
        User $employee,
        UnitEventParticipantAccountResolver $accountResolver
    ): ?UnitEventParticipant {
        $identityNumber = $accountResolver->normalizeIdentityNumber($employee->identity_number);
        $email = $accountResolver->normalizeEmail($employee->email);

        return $unitEvent->participants()
            ->where(function ($query) use ($employee, $identityNumber, $email): void {
                $query->where('linked_user_id', $employee->id);

                if ($identityNumber) {
                    $query->orWhere('identity_number', $identityNumber);
                }

                if ($email) {
                    $query->orWhere('email', $email);
                }
            })
            ->orderByDesc('linked_user_id')
            ->first();
    }

    private function buildEmployeeParticipantPayload(
        User $employee,
        UnitEvent $unitEvent,
        string $peran,
        string $source,
        UnitEventParticipantAccountResolver $accountResolver,
        ?array $dukProfile = null
    ): array {
        $identityNumber = $accountResolver->normalizeIdentityNumber($employee->identity_number);
        $email = $accountResolver->normalizeEmail($employee->email);

        return [
            'unit_event_id' => $unitEvent->id,
            'peran' => $peran,
            'participant_category' => 'employee',
            'sumber_registrasi' => $source,
            'nama' => $employee->name,
            'email' => $email,
            'identity_number' => $identityNumber,
            'instansi' => $this->normalizedEmployeeInstitution(null, $dukProfile['institution'] ?? null),
            'jabatan' => $this->resolvedEmployeePosition(null, $dukProfile['position'] ?? null),
            'kode_registrasi' => $this->generateUniqueCode(),
            'access_token' => $this->generateUniqueToken(),
            'registered_at' => now(),
            'linked_user_id' => $employee->id,
            'linked_mahasiswa_id' => null,
            'meta' => [
                'employee_roster_source' => $source,
                'employee_roster_synced_at' => now()->toIso8601String(),
                'employee_directory_source_file' => DukEmployeeDirectory::DEFAULT_DUK_FILE,
            ],
        ];
    }

    private function buildEmployeeParticipantUpdates(
        UnitEventParticipant $participant,
        User $employee,
        string $peran,
        string $source,
        UnitEventParticipantAccountResolver $accountResolver,
        ?array $dukProfile = null
    ): array {
        $identityNumber = $accountResolver->normalizeIdentityNumber($employee->identity_number);
        $email = $accountResolver->normalizeEmail($employee->email);
        $meta = is_array($participant->meta) ? $participant->meta : [];
        $meta['employee_roster_source'] = $source;
        $meta['employee_roster_synced_at'] = now()->toIso8601String();
        $meta['employee_directory_source_file'] = DukEmployeeDirectory::DEFAULT_DUK_FILE;

        $candidateValues = [
            'peran' => $peran,
            'participant_category' => 'employee',
            'nama' => $employee->name,
            'email' => $email,
            'identity_number' => $identityNumber,
            'linked_user_id' => $employee->id,
            'linked_mahasiswa_id' => null,
            'instansi' => $this->normalizedEmployeeInstitution($participant->instansi, $dukProfile['institution'] ?? null),
            'jabatan' => $this->resolvedEmployeePosition($participant->jabatan, $dukProfile['position'] ?? null),
            'meta' => $meta,
        ];

        if (blank($participant->sumber_registrasi)) {
            $candidateValues['sumber_registrasi'] = $source;
        }

        if (blank($participant->kode_registrasi)) {
            $candidateValues['kode_registrasi'] = $this->generateUniqueCode();
        }

        if (blank($participant->access_token)) {
            $candidateValues['access_token'] = $this->generateUniqueToken();
        }

        if (blank($participant->registered_at)) {
            $candidateValues['registered_at'] = now();
        }

        $updates = [];

        foreach ($candidateValues as $field => $value) {
            if ($field === 'meta') {
                if (($participant->meta ?? []) !== $value) {
                    $updates[$field] = $value;
                }

                continue;
            }

            if ($participant->{$field} !== $value) {
                $updates[$field] = $value;
            }
        }

        return $updates;
    }

    private function normalizedEmployeeInstitution(?string $currentValue, ?string $directoryValue = null): string
    {
        $institution = trim((string) ($directoryValue ?: $currentValue));

        if ($institution === '' || $institution === self::LEGACY_WRONG_EMPLOYEE_INSTITUTION) {
            return self::DEFAULT_EMPLOYEE_INSTITUTION;
        }

        return $institution;
    }

    private function resolvedEmployeePosition(?string $currentValue, ?string $directoryValue = null): ?string
    {
        $position = trim((string) $directoryValue);

        if ($position !== '') {
            return $position;
        }

        $existingValue = trim((string) $currentValue);

        return $existingValue !== '' ? $existingValue : null;
    }

    private function ensureParticipantCredentials(UnitEventParticipant $participant): void
    {
        if ($participant->usesLinkedApplicationAccess() && ! $participant->hasAccessCredentials()) {
            return;
        }

        if ($participant->hasAccessCredentials() && filled($participant->access_password_encrypted)) {
            return;
        }

        $temporaryPassword = $this->generateTemporaryParticipantPassword();

        $participant->forceFill([
            'access_password' => Hash::make($temporaryPassword),
            'access_password_encrypted' => Crypt::encryptString($temporaryPassword),
            'credentials_generated_at' => now(),
        ])->saveQuietly();
    }

    private function generateTemporaryParticipantPassword(): string
    {
        return Str::upper(Str::random(4)) . random_int(1000, 9999);
    }

    private function syncManualAttendance(UnitEvent $unitEvent, UnitEventParticipant $participant, Request $request): void
    {
        $firstAttendanceDate = $unitEvent->attendanceDates()->first();

        if (! $firstAttendanceDate) {
            return;
        }

        if (! $request->boolean('attended')) {
            if (! $participant->attendances()->whereNotNull('signature_path')->exists()) {
                $participant->attendances()->delete();
            }
        } else {
            $attendance = $participant->attendances()
                ->whereDate('attendance_date', $firstAttendanceDate->toDateString())
                ->first();

            if (! $attendance) {
                UnitEventAttendance::create([
                    'unit_event_id' => $unitEvent->id,
                    'unit_event_participant_id' => $participant->id,
                    'attendance_date' => $firstAttendanceDate->toDateString(),
                    'signed_at' => $participant->attended_at ?? now(),
                    'notes' => $request->input('attendance_notes'),
                ]);
            } elseif (! $attendance->signature_path) {
                $attendance->update([
                    'signed_at' => $participant->attended_at ?? $attendance->signed_at ?? now(),
                    'notes' => $request->input('attendance_notes'),
                ]);
            }
        }

        $firstSignedAt = $participant->attendances()->orderBy('attendance_date')->value('signed_at');

        if ($firstSignedAt) {
            $participant->forceFill(['attended_at' => $firstSignedAt])->saveQuietly();
        } elseif (! $request->boolean('attended')) {
            $participant->forceFill(['attended_at' => null])->saveQuietly();
        }
    }

    private function participantSignature(array $payload): string
    {
        $phone = preg_replace('/\D+/', '', (string) ($payload['no_hp'] ?? ''));

        return implode('|', [
            Str::lower(trim((string) ($payload['peran'] ?? 'participant'))),
            Str::lower(trim((string) ($payload['nama'] ?? ''))),
            Str::lower(trim((string) ($payload['email'] ?? ''))),
            Str::lower(trim((string) ($payload['identity_number'] ?? ''))),
            $phone,
            Str::lower(trim((string) ($payload['instansi'] ?? ''))),
            Str::lower(trim((string) ($payload['jabatan'] ?? ''))),
        ]);
    }

    private function buildCredentialRows(Collection $participants, UnitPage $unitPage, UnitEvent $unitEvent, QrCodeDataUri $qrCode): Collection
    {
        return $participants->map(function (UnitEventParticipant $participant) use ($unitPage, $unitEvent, $qrCode) {
            $participantAccessUrl = route('unit.events.attendance.show', [
                $unitPage->slug,
                $unitEvent->slug,
                $participant->access_token,
            ]);

            $usesLinkedAccess = $participant->usesLinkedApplicationAccess();
            $accessLabel = $usesLinkedAccess
                ? ($participant->linked_access_identifier ?: ($participant->formatted_identity_number ?: $participant->email ?: 'Gunakan akun aplikasi yang sudah terdaftar'))
                : $participant->participant_login_username;
            $secretLabel = $usesLinkedAccess
                ? 'Gunakan password akun aplikasi Anda'
                : ($participant->temporary_access_password ?: 'Manual / tidak tersimpan');
            $instructionLines = $usesLinkedAccess
                ? [
                    '1. Scan QR untuk langsung membuka daftar hadir event.',
                    '2. Pilih hari kehadiran lalu isi tanda tangan dan submit.',
                    '3. Jika QR tidak dapat dipakai, masuk manual ke ' . ($participant->linked_access_portal_label ?: 'akun aplikasi') . ' menggunakan akun Anda.',
                ]
                : [
                    '1. Scan QR untuk langsung membuka daftar hadir event.',
                    '2. Pilih hari kehadiran lalu isi tanda tangan dan submit.',
                    '3. Jika QR tidak dapat dipakai, gunakan username dan password sementara di samping.',
                ];

            return [
                'participant' => $participant,
                'login_url' => $participantAccessUrl,
                'qr_code_data_uri' => $qrCode->make($participantAccessUrl),
                'category_label' => $participant->participant_category_label,
                'access_mode_label' => $usesLinkedAccess ? ($participant->linked_access_portal_label ?: 'Akun aplikasi') : 'Kredensial Peserta',
                'access_label' => $accessLabel,
                'secret_label' => $secretLabel,
                'instruction_lines' => $instructionLines,
            ];
        });
    }

    public function scanner(UnitPage $unitPage, UnitEvent $unitEvent): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);
        return view('admin.unit-events.participants.scanner', compact('unitPage', 'unitEvent'));
    }

    public function processScan(Request $request, UnitPage $unitPage, UnitEvent $unitEvent): \Illuminate\Http\JsonResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $scannedCode = trim((string) $request->input('code'));

        if (blank($scannedCode)) {
            return response()->json(['success' => false, 'message' => 'Kode scan kosong.'], 400);
        }

        if (filter_var($scannedCode, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($scannedCode);
            if (isset($parsedUrl['path'])) {
                $pathParts = explode('/', trim($parsedUrl['path'], '/'));
                foreach ($pathParts as $part) {
                    if (strlen($part) === 48) {
                        $scannedCode = $part;
                        break;
                    }
                }
            }
        }

        $participant = $unitEvent->participants()
            ->where(function ($query) use ($scannedCode) {
                $query->where('kode_registrasi', $scannedCode)
                    ->orWhere('access_token', $scannedCode);
            })
            ->first();

        if (! $participant) {
            return response()->json(['success' => false, 'message' => 'Peserta/Undangan tidak ditemukan untuk event ini.'], 404);
        }

        $todayDate = now()->toDateString();
        $eventDates = $unitEvent->attendanceDateStrings();
        $attendanceDate = in_array($todayDate, $eventDates, true) 
            ? $todayDate 
            : ($eventDates[0] ?? $todayDate);

        $attendance = $participant->attendances()
            ->whereDate('attendance_date', $attendanceDate)
            ->first();

        if (! $attendance) {
            UnitEventAttendance::create([
                'unit_event_id' => $unitEvent->id,
                'unit_event_participant_id' => $participant->id,
                'attendance_date' => $attendanceDate,
                'signed_at' => now(),
                'attendance_status' => 'hadir',
                'notes' => 'Hadir via QR Scanner Panitia',
            ]);
            
            $participant->forceFill(['attended_at' => now()])->saveQuietly();
            $status = 'baru';
            $message = "Kehadiran {$participant->nama} berhasil dicatat!";
        } else {
            $status = 'sudah_hadir';
            $message = "{$participant->nama} sudah tercatat hadir sebelumnya.";
        }

        return response()->json([
            'success' => true,
            'status' => $status,
            'message' => $message,
            'data' => [
                'nama' => $participant->nama,
                'peran_label' => $participant->peran_label,
                'kode_registrasi' => $participant->kode_registrasi,
                'nomor_kursi' => $participant->meta['nomor_kursi'] ?? '—',
                'nama_wisudawan' => $participant->meta['nama_wisudawan'] ?? '—',
                'attended_at' => now()->translatedFormat('H:i:s'),
            ]
        ]);
    }

    public function scanDirect(UnitPage $unitPage, UnitEvent $unitEvent, string $token): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $participant = $unitEvent->participants()
            ->where('access_token', $token)
            ->firstOrFail();

        $todayDate = now()->toDateString();
        $eventDates = $unitEvent->attendanceDateStrings();
        $attendanceDate = in_array($todayDate, $eventDates, true) 
            ? $todayDate 
            : ($eventDates[0] ?? $todayDate);

        $attendance = $participant->attendances()
            ->whereDate('attendance_date', $attendanceDate)
            ->first();

        $alreadyAttended = true;

        if (! $attendance) {
            UnitEventAttendance::create([
                'unit_event_id' => $unitEvent->id,
                'unit_event_participant_id' => $participant->id,
                'attendance_date' => $attendanceDate,
                'signed_at' => now(),
                'attendance_status' => 'hadir',
                'notes' => 'Hadir via Scan Direct Kamera HP',
            ]);
            
            $participant->forceFill(['attended_at' => now()])->saveQuietly();
            $alreadyAttended = false;
        }

        return view('admin.unit-events.participants.scan-direct-result', compact(
            'unitPage',
            'unitEvent',
            'participant',
            'alreadyAttended',
            'attendanceDate'
        ));
    }

    public function invitationPrint(Request $request, UnitPage $unitPage, UnitEvent $unitEvent, QrCodeDataUri $qrCode): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $selectedProdi = trim((string) $request->input('prodi'));
        $selectedJurusan = trim((string) $request->input('jurusan'));

        $jurusanDefinitions = [
            'keperawatan' => [
                'label' => 'Jurusan Keperawatan',
                'terms' => ['Keperawatan', 'Ners'],
            ],
            'kebidanan' => [
                'label' => 'Jurusan Kebidanan',
                'terms' => ['Kebidanan', 'Bidan'],
            ],
            'gizi' => [
                'label' => 'Jurusan Gizi',
                'terms' => ['Gizi', 'Dietisien'],
            ],
            'sanitasi' => [
                'label' => 'Jurusan Sanitasi / Kesling',
                'terms' => ['Sanitasi'],
            ],
            'tlm' => [
                'label' => 'Jurusan Teknologi Lab Medis',
                'terms' => ['Teknologi Laboratorium Medis', 'TLM'],
            ],
            'kesehatan-gigi' => [
                'label' => 'Jurusan Kesehatan Gigi',
                'terms' => ['Kesehatan Gigi', 'Terapi Gigi'],
            ],
            'farmasi' => [
                'label' => 'Jurusan Farmasi',
                'terms' => ['Farmasi'],
            ],
            'promkes' => [
                'label' => 'Jurusan Promosi Kesehatan',
                'terms' => ['Promosi Kesehatan'],
            ],
        ];

        // Group available prodis and participant counts
        $availableProdis = $unitEvent->participants()
            ->where('peran', 'participant')
            ->whereNotNull('instansi')
            ->selectRaw('instansi, count(*) as count')
            ->groupBy('instansi')
            ->orderBy('instansi')
            ->pluck('count', 'instansi')
            ->toArray();

        // Calculate count for each jurusan
        $jurusanCounts = [];
        foreach ($jurusanDefinitions as $key => $def) {
            $jCount = 0;
            foreach ($availableProdis as $prodiName => $count) {
                foreach ($def['terms'] as $term) {
                    if (stripos($prodiName, $term) !== false) {
                        $jCount += $count;
                        break;
                    }
                }
            }
            $jurusanCounts[$key] = $jCount;
        }

        $totalAllUndangan = array_sum($availableProdis);

        // Filter query
        $query = $unitEvent->participants()
            ->where('peran', 'participant');

        if ($selectedProdi !== '') {
            $query->where('instansi', $selectedProdi);
        } elseif ($selectedJurusan !== '' && isset($jurusanDefinitions[$selectedJurusan])) {
            $terms = $jurusanDefinitions[$selectedJurusan]['terms'];
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->orWhere('instansi', 'like', '%' . $term . '%');
                }
            });
        }

        $participants = $query->orderBy('instansi')->orderBy('nama')->get();

        $invitationRows = $participants->map(function (UnitEventParticipant $participant) use ($unitPage, $unitEvent, $qrCode) {
            $scanUrl = route('admin.unit-events.participants.scan-direct', [
                'unitPage' => $unitPage->slug,
                'unitEvent' => $unitEvent->slug,
                'token' => $participant->access_token,
            ]);

            return [
                'participant' => $participant,
                'qr_code_data_uri' => $qrCode->make($scanUrl),
                'nama_wisudawan' => $participant->meta['nama_wisudawan'] ?? null,
                'nomor_kursi' => $participant->meta['nomor_kursi'] ?? null,
            ];
        });

        $filterTitle = 'Semua Program Studi';
        if ($selectedProdi !== '') {
            $filterTitle = 'Prodi ' . $selectedProdi;
        } elseif ($selectedJurusan !== '' && isset($jurusanDefinitions[$selectedJurusan])) {
            $filterTitle = $jurusanDefinitions[$selectedJurusan]['label'];
        }

        return view('admin.unit-events.participants.invitation-print', compact(
            'unitPage',
            'unitEvent',
            'invitationRows',
            'availableProdis',
            'jurusanDefinitions',
            'jurusanCounts',
            'selectedProdi',
            'selectedJurusan',
            'filterTitle',
            'totalAllUndangan'
        ));
    }
}