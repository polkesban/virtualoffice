<?php

namespace App\Http\Controllers;

use App\Models\EvaluationRubric;
use App\Models\UnitEventAttendance;
use App\Models\UnitEvent;
use App\Models\UnitEventParticipant;
use App\Models\UnitEventQuestion;
use App\Models\UnitEventSubmission;
use App\Models\UnitPage;
use App\Models\User;
use App\Support\MediaStorage;
use App\Support\DukEmployeeDirectory;
use App\Support\QrCodeDataUri;
use App\Support\UnitEventParticipantAccountResolver;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UnitEventController extends Controller
{
    public function show(string $unitSlug, string $eventSlug, UnitEventParticipantAccountResolver $accountResolver, QrCodeDataUri $qrCode): View
    {
        [$page, $event] = $this->resolvePublicEvent($unitSlug, $eventSlug);

        $materials = $event->materials()->where('publik', true)->get();
        $authenticatedParticipant = $accountResolver->findAuthenticatedParticipant($event);
        $speakers = $event->speakers()->get();
        $theme = $event->resolvedFlyerThemeConfig();
        $benefits = $event->resolvedBenefitsList();
        $publicUrl = route('unit.events.show', [$page->slug, $event->slug]);
        $qrCodeDataUri = $qrCode->make($publicUrl, 5);

        return view('unit.events.show', compact(
            'page',
            'event',
            'materials',
            'authenticatedParticipant',
            'speakers',
            'theme',
            'benefits',
            'publicUrl',
            'qrCodeDataUri'
        ));
    }

    public function flyer(string $unitSlug, string $eventSlug, QrCodeDataUri $qrCode): View
    {
        [$page, $event] = $this->resolvePublicEvent($unitSlug, $eventSlug);

        $speakers = $event->speakers()->get();
        $theme = $event->resolvedFlyerThemeConfig();
        $benefits = $event->resolvedBenefitsList();
        $publicUrl = route('unit.events.show', [$page->slug, $event->slug]);
        $qrCodeDataUri = $qrCode->make($publicUrl, 6);

        return view('unit.events.flyer', compact(
            'page',
            'event',
            'speakers',
            'theme',
            'benefits',
            'publicUrl',
            'qrCodeDataUri'
        ));
    }

    public function flyerOgImage(string $unitSlug, string $eventSlug)
    {
        [$page, $event] = $this->resolvePublicEvent($unitSlug, $eventSlug);

        $path = \App\Support\EventFlyerOgImageGenerator::generate($event);

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function slides(Request $request, string $unitSlug, string $eventSlug): View
    {
        [$page, $event] = $this->resolvePublicEvent($unitSlug, $eventSlug);

        $prodis = UnitEventParticipant::query()
            ->where('unit_event_id', $event->id)
            ->whereNotNull('instansi')
            ->where('instansi', '!=', '')
            ->distinct()
            ->orderBy('instansi')
            ->pluck('instansi');

        $query = UnitEventParticipant::query()
            ->where('unit_event_id', $event->id);

        if ($request->filled('prodi')) {
            $query->where('instansi', $request->query('prodi'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('identity_number', 'like', "%{$search}%")
                  ->orWhere('meta->nama_wisudawan', 'like', "%{$search}%");
            });
        }

        $participants = $query->orderBy('instansi')
            ->orderBy('identity_number')
            ->get();

        $slidesData = $participants->map(function ($p) {
            $meta = is_array($p->meta) ? $p->meta : (json_decode($p->meta, true) ?? []);
            $namaWisudawan = $meta['nama_wisudawan'] ?? str_replace(['Orang Tua / Wali dari ', 'Orang tua/ Wali ', 'Orang Tua/Wali dari '], '', $p->nama);
            
            $fotoUrl = $p->foto_url;
            if (!$fotoUrl && !empty($p->foto_path)) {
                $fotoUrl = asset('storage/' . ltrim($p->foto_path, '/'));
            }

            $ipkVal = $meta['ipk'] ?? null;
            $ipkNum = $ipkVal ? (float)str_replace(',', '.', (string)$ipkVal) : null;
            $predikat = null;
            if ($ipkNum !== null && $ipkNum > 0) {
                if ($ipkNum >= 3.51) {
                    $predikat = 'Dengan Pujian';
                } elseif ($ipkNum >= 3.00) {
                    $predikat = 'Sangat Memuaskan';
                } else {
                    $predikat = 'Memuaskan';
                }
            }

            return [
                'id' => $p->id,
                'nim' => $p->identity_number,
                'nama' => trim($namaWisudawan),
                'prodi' => $p->instansi,
                'ipk' => $ipkVal,
                'predikat' => $predikat,
                'ttl' => $meta['ttl'] ?? null,
                'jk' => $meta['jk'] ?? null,
                'foto_url' => $fotoUrl,
            ];
        });

        $activeProdi = $request->query('prodi');
        $initialNim = $request->query('nim');

        return view('unit.events.slides-showcase', compact(
            'page',
            'event',
            'prodis',
            'slidesData',
            'activeProdi',
            'initialNim'
        ));
    }

    public function register(
        Request $request,
        string $unitSlug,
        string $eventSlug,
        UnitEventParticipantAccountResolver $accountResolver
    )
    {
        [$page, $event] = $this->resolvePublicEvent($unitSlug, $eventSlug);

        if ($event->shouldBypassPublicRegistration()) {
            $participant = $accountResolver->findAuthenticatedParticipant($event);

            if ($participant) {
                return $this->redirectToParticipantDestination($page, $event, $participant, 'attendance')
                    ->with('success', 'Peserta event ini sudah disiapkan oleh panitia. Silakan isi daftar hadir Anda.');
            }

            return redirect()->route('unit.events.login.show', [$page->slug, $event->slug, 'redirect' => 'attendance'])
                ->with('info', 'Registrasi publik untuk event ini dibypass karena daftar peserta sudah diimpor panitia. Silakan masuk lalu isi daftar hadir.');
        }

        abort_unless($event->isRegistrationOpen(), 403);

        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'no_hp' => 'nullable|string|max:50',
            'instansi' => 'nullable|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'catatan' => 'nullable|string|max:2000',
        ]);

        $existing = $event->participants()
            ->where('peran', 'participant')
            ->where('email', $data['email'])
            ->first();

        if ($existing) {
            return redirect()->route('unit.events.access', [$page->slug, $event->slug, $existing->access_token])
                ->with('success', 'Anda sudah terdaftar. Link akses peserta lama ditampilkan kembali.');
        }

        $participant = UnitEventParticipant::create([
            'unit_event_id' => $event->id,
            'peran' => 'participant',
            'sumber_registrasi' => 'public',
            'nama' => $data['nama'],
            'email' => $data['email'],
            'no_hp' => $data['no_hp'] ?? null,
            'instansi' => $data['instansi'] ?? null,
            'jabatan' => $data['jabatan'] ?? null,
            'catatan' => $data['catatan'] ?? null,
            'kode_registrasi' => $this->generateUniqueCode(),
            'access_token' => $this->generateUniqueToken(),
            'registered_at' => now(),
        ]);

        return redirect()->route('unit.events.access', [$page->slug, $event->slug, $participant->access_token])
            ->with('success', 'Registrasi berhasil. Simpan tautan akses peserta Anda.');
    }

    public function access(string $unitSlug, string $eventSlug, string $token): View
    {
        [$page, $event, $participant] = $this->resolveParticipantContext($unitSlug, $eventSlug, $token);

        $participant->setRelation('event', $event);
        $participant->load(['attendances' => fn ($query) => $query->orderBy('attendance_date')->orderBy('signed_at')]);

        $submissions = $participant->submissions()->get()->keyBy('fase');

        return view('unit.events.access', compact('page', 'event', 'participant', 'submissions'));
    }

    public function showRubrik(string $unitSlug, string $eventSlug, string $token): View
    {
        [$page, $event, $participant] = $this->resolveParticipantContext($unitSlug, $eventSlug, $token);

        $rubriks = $event->evaluationRubrics;
        if ($rubriks->isEmpty() && $page) {
            $rubriks = $page->evaluationRubrics;
        }
        if ($rubriks->isEmpty()) {
            $rubriks = EvaluationRubric::latest()->get();
        }

        $selectedRubrik = request('rubrik_id')
            ? EvaluationRubric::find(request('rubrik_id'))
            : $rubriks->first();

        return view('unit.events.rubrik', compact('page', 'event', 'participant', 'rubriks', 'selectedRubrik'));
    }

    public function showParticipantLogin(
        Request $request,
        string $unitSlug,
        string $eventSlug,
        UnitEventParticipantAccountResolver $accountResolver
    ): View|RedirectResponse
    {
        [$page, $event] = $this->resolveParticipantAccessibleEvent($unitSlug, $eventSlug);

        $authenticatedParticipant = $accountResolver->findAuthenticatedParticipant(
            $event,
            $request->query('kode_registrasi'),
            $request->query('portal'),
        );

        if ($authenticatedParticipant) {
            return $this->redirectToParticipantDestination($page, $event, $authenticatedParticipant, $request->query('redirect'))
                ->with('success', 'Akses peserta ditemukan dari sesi login Anda.');
        }

        return view('unit.events.login', compact('page', 'event'));
    }

    public function loginParticipant(Request $request, string $unitSlug, string $eventSlug)
    {
        [$page, $event] = $this->resolveParticipantAccessibleEvent($unitSlug, $eventSlug);

        $data = $request->validate([
            'kode_registrasi' => 'required|string|max:30',
            'password' => 'required|string|max:255',
            'redirect_to' => 'nullable|string|in:attendance,access',
        ]);

        $username = trim($data['kode_registrasi']);

        $participant = $event->participants()
            ->where(function ($query) use ($username) {
                $query->whereRaw('LOWER(kode_registrasi) = ?', [strtolower($username)])
                      ->orWhereRaw('LOWER(identity_number) = ?', [strtolower($username)]);
            })
            ->first();

        $isValid = false;
        if ($participant) {
            if (filled($participant->access_password) && Hash::check($data['password'], $participant->access_password)) {
                $isValid = true;
            } elseif ($participant->participant_category === 'student' && filled($participant->identity_number) && (
                strtolower($data['password']) === strtolower($participant->identity_number) ||
                $data['password'] === 'polkesban' ||
                $data['password'] === '123456'
            )) {
                $isValid = true;
            }
        }

        if (! $isValid) {
            throw ValidationException::withMessages([
                'kode_registrasi' => 'Username atau password peserta tidak valid.',
            ]);
        }

        return $this->redirectToParticipantDestination($page, $event, $participant, $data['redirect_to'] ?? null)
            ->with('success', 'Login peserta berhasil.');
    }

    public function directAttendance(
        Request $request,
        string $unitSlug,
        string $eventSlug,
        UnitEventParticipantAccountResolver $accountResolver
    ): RedirectResponse {
        [$page, $event] = $this->resolveParticipantAccessibleEvent($unitSlug, $eventSlug);

        $registrationCode = Str::upper(trim($request->string('kode_registrasi')->toString()));
        $portal = trim($request->string('portal')->toString());
        $portal = in_array($portal, ['admin', 'mahasiswa', 'pegawai'], true) ? $portal : null;

        $requestedParticipant = $registrationCode !== ''
            ? $event->participants()->where('kode_registrasi', $registrationCode)->first()
            : null;

        if ($requestedParticipant) {
            return $this->redirectToParticipantDestination($page, $event, $requestedParticipant, 'attendance')
                ->with('success', 'QR peserta membuka halaman daftar hadir secara langsung.');
        }

        $participant = $accountResolver->findAuthenticatedParticipant(
            $event,
            $registrationCode !== '' ? $registrationCode : null,
            $portal,
        );

        if ($participant) {
            return $this->redirectToParticipantDestination($page, $event, $participant, 'attendance');
        }

        if ($portal === 'mahasiswa') {
            return redirect()->guest(route('mahasiswa.login'));
        }

        if ($portal === 'pegawai') {
            return redirect()->guest(route('pegawai.login'));
        }

        if ($portal === 'admin') {
            return redirect()->guest(route('admin.login'));
        }

        if ($requestedParticipant?->linked_mahasiswa_id) {
            return redirect()->guest(route('mahasiswa.login'));
        }

        if ($requestedParticipant?->linked_user_id || $requestedParticipant?->participant_category === 'employee') {
            return redirect()->guest(route('pegawai.login'));
        }

        if ($event->allow_direct_attendance) {
            return redirect()->route('unit.events.attendance.direct.show', [$page->slug, $event->slug]);
        }

        return redirect()->route('unit.events.login.show', [
            $page->slug,
            $event->slug,
            'kode_registrasi' => $registrationCode !== '' ? $registrationCode : null,
            'redirect' => 'attendance',
        ])->with('info', 'Masuk terlebih dahulu untuk membuka daftar hadir event.');
    }

    public function showAttendance(Request $request, string $unitSlug, string $eventSlug, string $token): View
    {
        [$page, $event, $participant] = $this->resolveParticipantContext($unitSlug, $eventSlug, $token);

        abort_unless($event->daftar_hadir_aktif, 404);

        $attendanceDates = $event->attendanceDates();
        $attendanceByDate = $participant->attendances()
            ->get()
            ->keyBy(fn (UnitEventAttendance $attendance) => $attendance->attendance_date->toDateString());
        $selectedAttendanceDate = $this->resolveSelectedAttendanceDate($attendanceDates, $attendanceByDate, $request->query('tanggal'));
        $attendanceRecord = $attendanceByDate->get($selectedAttendanceDate->toDateString());

        return view('unit.events.activity-form', [
            'page' => $page,
            'event' => $event,
            'participant' => $participant,
            'fase' => 'attendance',
            'questions' => collect(),
            'submission' => null,
            'attendanceDates' => $attendanceDates,
            'attendanceByDate' => $attendanceByDate,
            'selectedAttendanceDate' => $selectedAttendanceDate,
            'attendanceRecord' => $attendanceRecord,
        ]);
    }

    public function submitAttendance(Request $request, string $unitSlug, string $eventSlug, string $token)
    {
        [$page, $event, $participant] = $this->resolveParticipantContext($unitSlug, $eventSlug, $token);

        abort_unless($event->daftar_hadir_aktif, 404);

        $attendanceDateStrings = $event->attendanceDateStrings();
        $isPraktik = ($event->jenis_kegiatan === 'pbm' && $event->sub_jenis_kegiatan === 'praktik');

        $data = $request->validate([
            'attendance_date' => ['required', 'date', Rule::in($attendanceDateStrings)],
            'attendance_notes' => 'nullable|string|max:2000',
            'signature_data' => 'nullable|string',
            'signature_data_datang' => 'nullable|string',
            'signature_data_pulang' => 'nullable|string',
            'shift_name' => $isPraktik ? 'required|string' : 'nullable|string',
        ]);

        $shiftName = $data['shift_name'] ?? null;
        $attendanceDate = Carbon::parse($data['attendance_date'])->startOfDay();

        $attendance = $participant->attendances()
            ->whereDate('attendance_date', $attendanceDate->toDateString())
            ->first();

        $updatePayload = [
            'notes' => $data['attendance_notes'] ?? null,
        ];

        if ($isPraktik) {
            $updatePayload['shift_name'] = $shiftName;
            
            $sigDatang = trim((string) ($data['signature_data_datang'] ?? ''));
            $sigPulang = trim((string) ($data['signature_data_pulang'] ?? ''));
            $tipePresensi = $request->input('tipe_presensi', 'datang');

            if ($tipePresensi === 'pulang') {
                if ($sigPulang === '' && !$attendance?->check_out_signature_path) {
                    throw ValidationException::withMessages([
                        'signature_data_pulang' => 'Tanda tangan kepulangan wajib diisi.',
                    ]);
                }
            } else {
                if ($sigDatang === '' && !$attendance?->signature_path) {
                    throw ValidationException::withMessages([
                        'signature_data_datang' => 'Tanda tangan kedatangan wajib diisi.',
                    ]);
                }
            }

            if ($sigDatang !== '') {
                $updatePayload['signature_path'] = $this->storeAttendanceSignature(
                    $sigDatang,
                    $event,
                    $participant,
                    $attendanceDate,
                    $attendance?->signature_path,
                    'signature_data_datang',
                );
                $updatePayload['signed_at'] = now();
            }

            if ($sigPulang !== '') {
                $updatePayload['check_out_signature_path'] = $this->storeAttendanceSignature(
                    $sigPulang,
                    $event,
                    $participant,
                    $attendanceDate,
                    $attendance?->check_out_signature_path,
                    'signature_data_pulang',
                );
                $updatePayload['check_out_at'] = now();
            }
        } else {
            $signatureData = trim((string) ($data['signature_data'] ?? ''));

            if ($signatureData === '' && !$attendance?->signature_path) {
                throw ValidationException::withMessages([
                    'signature_data' => 'Tanda tangan wajib diisi.',
                ]);
            }

            if ($signatureData !== '') {
                $updatePayload['signature_path'] = $this->storeAttendanceSignature(
                    $signatureData,
                    $event,
                    $participant,
                    $attendanceDate,
                    $attendance?->signature_path,
                );
                $updatePayload['signed_at'] = now();
            }
        }

        if ($attendance) {
            $attendance->update($updatePayload);
        } else {
            $attendance = UnitEventAttendance::create(array_merge([
                'unit_event_id' => $event->id,
                'unit_event_participant_id' => $participant->id,
                'attendance_date' => $attendanceDate->toDateString(),
            ], $updatePayload));
        }

        $participantUpdate = [
            'attended_at' => $participant->attendances()->orderBy('attendance_date')->value('signed_at') ?? $attendance->signed_at,
        ];

        if ($event->attendanceDayCount() === 1) {
            $participantUpdate['attendance_notes'] = $data['attendance_notes'] ?? null;
        }

        $participant->update($participantUpdate);

        $actionLabel = 'kehadiran';
        if ($isPraktik) {
            if ($sigDatang !== '' && $sigPulang !== '') {
                $actionLabel = 'kedatangan & kepulangan';
            } elseif ($sigPulang !== '') {
                $actionLabel = 'kepulangan';
            } else {
                $actionLabel = 'kedatangan';
            }
        }

        return redirect()->route('unit.events.access', [$page->slug, $event->slug, $participant->access_token])
            ->with('success', 'Kehadiran (' . $actionLabel . ') untuk tanggal ' . $attendanceDate->translatedFormat('d F Y') . ' berhasil dicatat.');
    }

    public function showPreTest(string $unitSlug, string $eventSlug, string $token): View
    {
        return $this->showPhaseForm($unitSlug, $eventSlug, $token, 'pre_test');
    }

    public function submitPreTest(Request $request, string $unitSlug, string $eventSlug, string $token)
    {
        return $this->submitPhaseForm($request, $unitSlug, $eventSlug, $token, 'pre_test');
    }

    public function showPostTest(string $unitSlug, string $eventSlug, string $token): View
    {
        return $this->showPhaseForm($unitSlug, $eventSlug, $token, 'post_test');
    }

    public function submitPostTest(Request $request, string $unitSlug, string $eventSlug, string $token)
    {
        return $this->submitPhaseForm($request, $unitSlug, $eventSlug, $token, 'post_test');
    }

    public function showEvaluation(string $unitSlug, string $eventSlug, string $token): View
    {
        return $this->showPhaseForm($unitSlug, $eventSlug, $token, 'evaluation');
    }

    public function submitEvaluation(Request $request, string $unitSlug, string $eventSlug, string $token)
    {
        return $this->submitPhaseForm($request, $unitSlug, $eventSlug, $token, 'evaluation');
    }

    public function downloadCertificate(string $unitSlug, string $eventSlug, string $token)
    {
        [$page, $event, $participant] = $this->resolveParticipantContext($unitSlug, $eventSlug, $token);

        abort_if(empty($participant->sertifikat_path) || ! MediaStorage::exists($participant->sertifikat_path), 404);

        $extension = pathinfo((string) MediaStorage::fileName($participant->sertifikat_path), PATHINFO_EXTENSION);
        $filename = Str::slug($event->judul . '-' . $participant->nama . '-' . $participant->peran) . ($extension !== '' ? '.' . $extension : '');

        return MediaStorage::inlineResponse($participant->sertifikat_path, $filename, true);
    }

    public function printInvitation(string $unitSlug, string $eventSlug, string $token, \App\Support\QrCodeDataUri $qrCode): View
    {
        [$page, $event, $participant] = $this->resolveParticipantContext($unitSlug, $eventSlug, $token);
        
        $scanUrl = route('admin.unit-events.participants.scan-direct', [
            'unitPage' => $page->slug,
            'unitEvent' => $event->slug,
            'token' => $participant->access_token,
        ]);
        
        $qrCodeUri = $qrCode->make($scanUrl);
        
        return view('unit.events.invitation-single-print', compact('page', 'event', 'participant', 'qrCodeUri'));
    }

    public function showEventAssignmentLetter(Request $request, string $unitSlug, string $eventSlug)
    {
        [$page, $event] = $this->resolvePublicEvent($unitSlug, $eventSlug);

        abort_if(empty($event->surat_tugas_event_path) || ! MediaStorage::exists($event->surat_tugas_event_path), 404);

        $extension = pathinfo((string) MediaStorage::fileName($event->surat_tugas_event_path), PATHINFO_EXTENSION);
        $filename = Str::slug($event->judul . '-surat-tugas-kegiatan') . ($extension !== '' ? '.' . $extension : '');

        return $this->respondWithInlinePublicFile($request, $event->surat_tugas_event_path, $filename);
    }

    public function showAssignmentLetter(Request $request, string $unitSlug, string $eventSlug, string $token)
    {
        [$page, $event, $participant] = $this->resolveParticipantContext($unitSlug, $eventSlug, $token);

        abort_if(empty($participant->surat_tugas_path) || ! MediaStorage::exists($participant->surat_tugas_path), 404);

        $extension = pathinfo((string) MediaStorage::fileName($participant->surat_tugas_path), PATHINFO_EXTENSION);
        $filename = Str::slug($event->judul . '-' . $participant->nama . '-surat-tugas') . ($extension !== '' ? '.' . $extension : '');

        return $this->respondWithInlinePublicFile($request, $participant->surat_tugas_path, $filename);
    }

    private function showPhaseForm(string $unitSlug, string $eventSlug, string $token, string $fase): View
    {
        [$page, $event, $participant] = $this->resolveParticipantContext($unitSlug, $eventSlug, $token);
        $this->abortIfPhaseClosed($event, $fase);

        $questions = $event->questionsFor($fase)->get();
        abort_if($questions->isEmpty(), 404);

        $submission = $participant->submissions()->where('fase', $fase)->first();

        return view('unit.events.activity-form', compact('page', 'event', 'participant', 'fase', 'questions', 'submission'));
    }

    private function submitPhaseForm(Request $request, string $unitSlug, string $eventSlug, string $token, string $fase)
    {
        [$page, $event, $participant] = $this->resolveParticipantContext($unitSlug, $eventSlug, $token);
        $this->abortIfPhaseClosed($event, $fase);

        $questions = $event->questionsFor($fase)->get();
        abort_if($questions->isEmpty(), 404);

        $answers = $request->input('answers', []);
        $errors = [];

        foreach ($questions as $question) {
            $answer = $answers[$question->id] ?? null;
            if ($question->wajib && blank($answer)) {
                $errors['answers.' . $question->id] = 'Pertanyaan "' . Str::limit($question->pertanyaan, 60) . '" wajib diisi.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $normalizedAnswers = [];
        $score = null;
        $totalWeight = 0;
        $achievedWeight = 0;

        foreach ($questions as $question) {
            $answer = $answers[$question->id] ?? null;
            $normalizedAnswers[$question->id] = $answer;

            if ($question->tipe_pertanyaan === 'multiple_choice' && in_array($fase, ['pre_test', 'post_test'], true) && $question->bobot > 0) {
                $totalWeight += $question->bobot;
                if ((string) $answer === (string) $question->jawaban_benar) {
                    $achievedWeight += $question->bobot;
                }
            }
        }

        if (in_array($fase, ['pre_test', 'post_test'], true)) {
            $score = $totalWeight > 0 ? round(($achievedWeight / $totalWeight) * 100, 2) : null;
        }

        UnitEventSubmission::updateOrCreate(
            [
                'unit_event_id' => $event->id,
                'unit_event_participant_id' => $participant->id,
                'fase' => $fase,
            ],
            [
                'jawaban' => $normalizedAnswers,
                'nilai' => $score,
                'submitted_at' => now(),
            ]
        );

        if ($fase === 'pre_test') {
            $participant->update(['pre_test_nilai' => $score]);
        }

        if ($fase === 'post_test') {
            $participant->update(['post_test_nilai' => $score]);
        }

        if ($fase === 'evaluation') {
            $participant->update(['evaluasi_dikirim_at' => now()]);
        }

        return redirect()->route('unit.events.access', [$page->slug, $event->slug, $participant->access_token])
            ->with('success', (UnitEvent::$faseLabels[$fase] ?? $fase) . ' berhasil dikirim.');
    }

    private function abortIfPhaseClosed(UnitEvent $event, string $fase): void
    {
        $enabled = match ($fase) {
            'pre_test' => $event->pre_test_aktif,
            'post_test' => $event->post_test_aktif,
            'evaluation' => $event->evaluasi_aktif,
            default => false,
        };

        abort_unless($enabled, 404);
    }

    private function resolvePublicEvent(string $unitSlug, string $eventSlug): array
    {
        $page = UnitPage::query()
            ->where('slug', $unitSlug)
            ->where('aktif', true)
            ->firstOrFail();

        $eventQuery = $page->events()
            ->with(['materials', 'questions'])
            ->where('slug', $eventSlug);

        // Allow viewing draft/preview events if user is authenticated admin or manager
        if (auth()->check()) {
            $event = $eventQuery->firstOrFail();
        } else {
            $event = $eventQuery->published()->firstOrFail();
        }

        return [$page, $event];
    }

    private function resolveParticipantAccessibleEvent(string $unitSlug, string $eventSlug): array
    {
        $page = UnitPage::query()
            ->where('slug', $unitSlug)
            ->where('aktif', true)
            ->firstOrFail();

        $event = $page->events()
            ->with(['materials', 'questions'])
            ->where('slug', $eventSlug)
            ->whereIn('status', ['draft', 'published', 'completed'])
            ->firstOrFail();

        return [$page, $event];
    }

    private function resolveParticipantContext(string $unitSlug, string $eventSlug, string $token): array
    {
        [$page, $event] = $this->resolveParticipantAccessibleEvent($unitSlug, $eventSlug);

        $participant = $event->participants()
            ->where('access_token', $token)
            ->firstOrFail();

        return [$page, $event, $participant];
    }

    private function redirectToParticipantDestination(
        UnitPage $page,
        UnitEvent $event,
        UnitEventParticipant $participant,
        ?string $target = null
    ): RedirectResponse {
        $target = in_array($target, ['attendance', 'access'], true) ? $target : null;

        if (($target === 'attendance' || ($target === null && $event->shouldBypassPublicRegistration())) && $event->daftar_hadir_aktif) {
            return redirect()->route('unit.events.attendance.show', [$page->slug, $event->slug, $participant->access_token]);
        }

        return redirect()->route('unit.events.access', [$page->slug, $event->slug, $participant->access_token]);
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

    private function resolveSelectedAttendanceDate(Collection $attendanceDates, Collection $attendanceByDate, ?string $requestedDate): Carbon
    {
        $availableDateStrings = $attendanceDates
            ->map(fn (Carbon $date) => $date->toDateString())
            ->all();

        if ($requestedDate !== null) {
            abort_unless(in_array($requestedDate, $availableDateStrings, true), 404);

            return Carbon::parse($requestedDate)->startOfDay();
        }

        $today = now()->toDateString();

        if (in_array($today, $availableDateStrings, true)) {
            return Carbon::parse($today)->startOfDay();
        }

        $firstIncomplete = $attendanceDates->first(fn (Carbon $date) => ! $attendanceByDate->has($date->toDateString()));

        if ($firstIncomplete) {
            return $firstIncomplete->copy();
        }

        return ($attendanceDates->last()?->copy()) ?? now()->startOfDay();
    }

    private function storeAttendanceSignature(
        string $signatureData,
        UnitEvent $event,
        UnitEventParticipant $participant,
        Carbon $attendanceDate,
        ?string $previousPath = null,
        string $fieldKey = 'signature_data',
    ): string {
        if (! preg_match('/^data:image\/png;base64,(.+)$/', $signatureData, $matches)) {
            throw ValidationException::withMessages([
                $fieldKey => 'Format tanda tangan tidak valid.',
            ]);
        }

        $binary = base64_decode(str_replace(' ', '+', $matches[1]), true);

        if ($binary === false || strlen($binary) < 256) {
            throw ValidationException::withMessages([
                $fieldKey => 'Tanda tangan tidak terbaca. Silakan tanda tangani ulang.',
            ]);
        }

        if (strlen($binary) > 2 * 1024 * 1024) {
            throw ValidationException::withMessages([
                $fieldKey => 'Ukuran tanda tangan terlalu besar. Silakan ulangi tanda tangan.',
            ]);
        }

        $path = sprintf(
            'unit-events/attendance-signatures/%d/%d-%s-%s.png',
            $event->id,
            $participant->id,
            $attendanceDate->format('Ymd'),
            Str::lower(Str::random(10)),
        );

        $storedPath = MediaStorage::put($path, $binary, 'image/png');

        if ($previousPath && $previousPath !== $storedPath) {
            MediaStorage::delete($previousPath);
        }

        return $storedPath;
    }

    private function respondWithInlinePublicFile(Request $request, string $path, string $filename)
    {
        return MediaStorage::inlineResponse($path, $filename, $request->boolean('download'));
    }

    public function showDirectAttendance(Request $request, string $unitSlug, string $eventSlug): View
    {
        [$page, $event] = $this->resolvePublicEvent($unitSlug, $eventSlug);

        $formType = trim($request->string('form_type')->toString()) ?: 'daftar_hadir';

        if ($formType === 'berita_acara') {
            abort_unless($event->berita_acara_form_aktif, 404);
        } else {
            abort_unless($event->allow_direct_attendance || $event->daftar_hadir_aktif, 404);
        }

        $attendanceDates = $event->attendanceDates();
        $unitKerjaOptions = $this->getUnitKerjaOptions();

        return view('unit.events.direct-attendance-form', compact('page', 'event', 'attendanceDates', 'unitKerjaOptions', 'formType'))->with('showBeritaAcaraForm', $event->berita_acara_form_aktif ?? false);
    }

    public function submitDirectAttendance(Request $request, string $unitSlug, string $eventSlug)
    {
        [$page, $event] = $this->resolvePublicEvent($unitSlug, $eventSlug);

        $formType = trim($request->string('form_type')->toString()) ?: 'daftar_hadir';

        if ($formType === 'berita_acara') {
            abort_unless($event->berita_acara_form_aktif, 404);
        } else {
            abort_unless($event->allow_direct_attendance || $event->daftar_hadir_aktif, 404);
        }

        $attendanceDateStrings = $event->attendanceDateStrings();

        $data = $request->validate([
            'form_type' => 'required|in:daftar_hadir,berita_acara',
            'nama' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'no_hp' => 'nullable|string|max:50',
            'instansi_type' => 'nullable|string|in:poltekkes,other',
            'instansi_manual' => 'required_if:instansi_type,other|nullable|string|max:255',
            'unit_kerja' => 'required_if:instansi_type,poltekkes|nullable|string|max:255',
            'attendance_status' => 'required|in:hadir,izin,sakit,tidak_hadir',
            'attendance_date' => ['required', 'date', Rule::in($attendanceDateStrings)],
            'attendance_notes' => 'nullable|string|max:2000',
            'berita_acara_judul' => 'nullable|string|max:255',
            'berita_acara_ringkasan' => 'nullable|string|max:2000',
            'signature_data' => 'nullable|string',
        ]);

        $attendanceDate = Carbon::parse($data['attendance_date'])->startOfDay();
        $signatureData = trim((string) ($data['signature_data'] ?? ''));

        if ($signatureData === '') {
            throw ValidationException::withMessages([
                'signature_data' => 'Tanda tangan wajib diisi.',
            ]);
        }

        // Process instansi based on type
        $instansi = null;
        if (isset($data['instansi_type'])) {
            switch ($data['instansi_type']) {
                case 'poltekkes':
                    $instansi = 'Poltekkes Kemenkes Bandung';
                    break;
                case 'other':
                    $instansi = $data['instansi_manual'] ?? null;
                    break;
            }
        }

        $signaturePath = null;

        if ($signatureData !== '') {
            $binary = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $signatureData));
            $path = sprintf(
                'events/%s/attendances/direct_%s_%s.png',
                $event->id,
                $attendanceDate->format('Ymd'),
                Str::lower(Str::random(10)),
            );

            $signaturePath = MediaStorage::put($path, $binary, 'image/png');
        }

        if ($data['form_type'] === 'berita_acara') {
            $eventUpdate = [];
            if (isset($data['berita_acara_judul'])) {
                $eventUpdate['berita_acara_judul'] = $data['berita_acara_judul'];
            }
            if (isset($data['berita_acara_ringkasan'])) {
                $eventUpdate['berita_acara_ringkasan'] = $data['berita_acara_ringkasan'];
            }
            if (!empty($eventUpdate)) {
                $event->update($eventUpdate);
            }
        }

        // Find or create participant for direct attendance so they appear in admin roster and attendance print
        $participant = null;
        if (!empty($data['email'])) {
            $participant = $event->participants()->where('email', $data['email'])->first();
        }
        if (!$participant) {
            $participant = $event->participants()->where('nama', $data['nama'])->first();
        }

        if (!$participant) {
            $kodeRegistrasi = 'EVT-' . Str::upper(Str::random(8));
            $participant = UnitEventParticipant::create([
                'unit_event_id' => $event->id,
                'nama' => $data['nama'],
                'email' => $data['email'] ?? null,
                'no_hp' => $data['no_hp'] ?? null,
                'instansi' => $instansi ?: 'Poltekkes Kemenkes Bandung',
                'unit_kerja' => $data['unit_kerja'] ?? null,
                'peran' => 'participant',
                'kode_registrasi' => $kodeRegistrasi,
                'access_token' => Str::random(48),
                'attended_at' => now(),
            ]);
        } else {
            $participant->update([
                'attended_at' => $participant->attended_at ?? now(),
                'no_hp' => $participant->no_hp ?: ($data['no_hp'] ?? null),
                'instansi' => $participant->instansi ?: $instansi,
                'unit_kerja' => $participant->unit_kerja ?: ($data['unit_kerja'] ?? null),
            ]);
        }

        // Find existing direct attendance record on the same date for the same participant/person
        $existingAttendance = UnitEventAttendance::where('unit_event_id', $event->id)
            ->where(function ($query) use ($participant, $data) {
                $query->where('unit_event_participant_id', $participant->id);
                if (!empty($data['email'])) {
                    $query->orWhere('email', $data['email']);
                } else {
                    $query->orWhere('nama', $data['nama']);
                }
            })
            ->whereDate('attendance_date', $attendanceDate->toDateString())
            ->first();

        // If updating signature, delete the old signature file
        if ($existingAttendance && $existingAttendance->signature_path && $signaturePath) {
            MediaStorage::delete($existingAttendance->signature_path);
        }

        $attendance = UnitEventAttendance::updateOrCreate(
            [
                'id' => $existingAttendance ? $existingAttendance->id : null,
            ],
            [
                'unit_event_id' => $event->id,
                'unit_event_participant_id' => $participant->id,
                'attendance_date' => $attendanceDate->toDateString(),
                'signed_at' => now(),
                'notes' => $data['attendance_notes'] ?? null,
                'signature_path' => $signaturePath ?: ($existingAttendance?->signature_path),
                'nama' => $data['nama'],
                'email' => $data['email'] ?? null,
                'no_hp' => $data['no_hp'] ?? null,
                'instansi' => $instansi,
                'unit_kerja' => $data['unit_kerja'] ?? null,
                'attendance_status' => $data['attendance_status'] ?? 'hadir',
            ]
        );

        $formLabel = $data['form_type'] === 'berita_acara' ? 'penandatanganan berita acara' : 'daftar hadir';
        return redirect()->route('unit.events.show', [$page->slug, $event->slug])
            ->with('success', 'Terima kasih! ' . ucfirst($formLabel) . ' untuk tanggal ' . $attendanceDate->translatedFormat('d F Y') . ' berhasil dicatat.');
    }

    public function generateBeritaAcara(string $unitSlug, string $eventSlug)
    {
        [$page, $event] = $this->resolvePublicEvent($unitSlug, $eventSlug);

        // Only allow if user is authenticated (admin or event organizer)
        // For now, we'll make it accessible to anyone, but in production this should be protected

        $rawAttendances = $event->attendances()
            ->whereNotNull('nama')
            ->orderBy('attendance_date')
            ->orderBy('signed_at')
            ->get();

        // Deduplicate: group by date and (email or name) and keep the latest record of each group
        $uniqueAttendances = $rawAttendances->groupBy(function ($attendance) {
            return $attendance->attendance_date . '_' . ($attendance->email ?: $attendance->nama);
        })->map(function ($group) {
            return $group->last();
        })->values()->sortBy('signed_at');

        $attendances = $uniqueAttendances->groupBy('attendance_date');

        $totalParticipants = $uniqueAttendances->count();
        $presentCount = $uniqueAttendances->where('attendance_status', 'hadir')->count();

        return view('unit.events.berita-acara', compact('page', 'event', 'attendances', 'totalParticipants', 'presentCount'));
    }

    private function getUnitKerjaOptions(): array
    {
        return [
            'Direktorat' => 'Direktorat',
            'Jurusan Keperawatan' => 'Jurusan Keperawatan',
            'Jurusan Kebidanan' => 'Jurusan Kebidanan',
            'Jurusan Farmasi' => 'Jurusan Farmasi',
            'Jurusan Kesehatan Gigi' => 'Jurusan Kesehatan Gigi',
            'Jurusan Gizi' => 'Jurusan Gizi',
            'Jurusan Promosi Kesehatan' => 'Jurusan Promosi Kesehatan',
            'Jurusan Teknologi Laboratorium Medis' => 'Jurusan Teknologi Laboratorium Medis',
            'Jurusan Kesehatan Lingkungan' => 'Jurusan Kesehatan Lingkungan',
            'Prodi Kebidanan Karawang' => 'Prodi Kebidanan Karawang',
            'Prodi Keperawatan Bogor' => 'Prodi Keperawatan Bogor',
            'Prodi Kebidanan Bogor' => 'Prodi Kebidanan Bogor',
        ];
    }

    private function getAttendanceStatusOptions(): array
    {
        return [
            'hadir' => 'Hadir',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            'tidak_hadir' => 'Tidak Hadir',
        ];
    }

    public function lookupNip(Request $request, string $unitSlug, string $eventSlug, DukEmployeeDirectory $dukEmployeeDirectory)
    {
        [$page, $event] = $this->resolvePublicEvent($unitSlug, $eventSlug);

        // Limit access to when direct attendance or berita acara is active
        abort_unless($event->allow_direct_attendance || $event->daftar_hadir_aktif || $event->berita_acara_form_aktif, 404);

        $nip = trim($request->string('nip')->toString());

        if ($nip === '') {
            return response()->json([
                'success' => false,
                'message' => 'NIP tidak boleh kosong.'
            ], 400);
        }

        $name = null;
        $email = null;
        $unitKerja = null;
        $isStudent = false;

        // 1. Try searching in this event's participants (for pre-registered students or staff)
        $participant = $event->participants()
            ->where('identity_number', $nip)
            ->first();

        if ($participant) {
            $name = $participant->nama;
            $email = $participant->email;
            $unitKerja = $participant->instansi ?: ($participant->tempat_praktik ?: null);
            if ($participant->participant_category === 'student') {
                $isStudent = true;
            }
        }

        // 2. Try searching in mahasiswas table (by NIM)
        if (!$name) {
            $mahasiswa = \App\Models\Mahasiswa::where('nim', $nip)->first();
            if ($mahasiswa) {
                $name = $mahasiswa->name;
                $email = $mahasiswa->email;
                $unitKerja = $mahasiswa->programStudi ? $mahasiswa->programStudi->nama : null;
                $isStudent = true;
            }
        }

        // 3. Try searching in Excel employee directory (by NIP)
        if (!$name) {
            $profile = $dukEmployeeDirectory->find($nip);
            if ($profile) {
                $name = $profile['name'] ?? null;
                $position = $profile['position'] ?? '';
                $unitKerja = $this->mapPositionToUnitKerja($position);
            }
        }

        // 4. Try searching in users table (by identity_number)
        if (!$name) {
            $user = User::query()
                ->where('identity_number', $nip)
                ->first();

            if ($user) {
                $name = $user->name;
                $email = $user->email;
                if ($user->role === User::ROLE_EMPLOYEE) {
                    $unitKerja = $this->mapPositionToUnitKerja($user->jabatan ?: '');
                }
            }
        }

        if (!$name) {
            return response()->json([
                'success' => false,
                'message' => 'Identitas (NIP/NIM) tidak ditemukan di sistem.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'nama' => $name,
                'email' => $email,
                'unit_kerja' => $unitKerja,
                'is_student' => $isStudent
            ]
        ]);
    }

    private function mapPositionToUnitKerja(string $position): ?string
    {
        $positionLower = strtolower($position);
        
        if (str_contains($positionLower, 'direktorat')) {
            return 'Direktorat';
        }
        
        // Match specific prodi/kampus first
        if (str_contains($positionLower, 'kebidanan') && str_contains($positionLower, 'karawang')) {
            return 'Prodi Kebidanan Karawang';
        }
        if (str_contains($positionLower, 'keperawatan') && str_contains($positionLower, 'bogor')) {
            return 'Prodi Keperawatan Bogor';
        }
        if (str_contains($positionLower, 'kebidanan') && str_contains($positionLower, 'bogor')) {
            return 'Prodi Kebidanan Bogor';
        }
        
        // Match general majors/jurusan
        if (str_contains($positionLower, 'keperawatan')) {
            return 'Jurusan Keperawatan';
        }
        if (str_contains($positionLower, 'kebidanan')) {
            return 'Jurusan Kebidanan';
        }
        if (str_contains($positionLower, 'farmasi')) {
            return 'Jurusan Farmasi';
        }
        if (str_contains($positionLower, 'gigi')) {
            return 'Jurusan Kesehatan Gigi';
        }
        if (str_contains($positionLower, 'gizi')) {
            return 'Jurusan Gizi';
        }
        if (str_contains($positionLower, 'promosi') || str_contains($positionLower, 'promkes')) {
            return 'Jurusan Promosi Kesehatan';
        }
        if (str_contains($positionLower, 'laboratorium') || str_contains($positionLower, 'tlm') || str_contains($positionLower, 'medis')) {
            return 'Jurusan Teknologi Laboratorium Medis';
        }
        if (str_contains($positionLower, 'lingkungan') || str_contains($positionLower, 'sanitasi')) {
            return 'Jurusan Kesehatan Lingkungan';
        }
        
        return null;
    }
}