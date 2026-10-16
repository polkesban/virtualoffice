<?php

namespace App\Http\Controllers\Pbm;

use App\Http\Controllers\Controller;
use App\Models\ClinicalClass;
use App\Models\ClinicalClassMember;
use App\Models\ClinicalSession;
use App\Models\ClinicalAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        // Check if user is assigned as CI in any class
        $isCiMember = ClinicalClassMember::where('user_id', $user->id)->where('role', 'ci')->exists();
        $isStaffOrAdmin = $user->isSuperAdmin() || in_array($user->role, [
            'ci', 'dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum', 'admin'
        ]);

        if (!$isCiMember && !$isStaffOrAdmin) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses Dashboard Clinical Instructor.');
        }

        $query = ClinicalAttendance::whereNull('ci_validated_at')
            ->with(['session.clinicalClass', 'mahasiswa'])
            ->latest();

        // Scope attendance data based on role
        if ($user->isSuperAdmin() || in_array($user->role, ['kaprodi', 'admin_jurusan', 'kajur', 'admin'])) {
            // SuperAdmin & pengelola dapat memantau seluruh presensi yang menunggu validasi CI
        } elseif ($user->role === 'ci' || $isCiMember) {
            // CI melihat presensi untuk kelas di mana mereka ditugaskan sebagai CI
            $query->whereHas('session.clinicalClass.members', function($q) use ($user) {
                $q->where('user_id', $user->id)->where('role', 'ci');
            });
        } elseif (in_array($user->role, ['dosen', 'pj_kurikulum'])) {
            // Dosen melihat presensi untuk kelas yang mereka buat atau mereka ajar
            $query->whereHas('session.clinicalClass', function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereHas('members', function($mq) use ($user) {
                      $mq->where('user_id', $user->id);
                  });
            });
        }

        $attendances = $query->get();

        return view('pbm.attendances.ci_dashboard', compact('attendances'));
    }

    public function store(Request $request, ClinicalSession $session)
    {
        // Direct scanning by Dosen/CI for a student
        $validated = $request->validate([
            'mahasiswa_id' => 'required|exists:mahasiswa,id',
            'status' => 'required|in:hadir,izin,sakit,alpha',
            'notes' => 'nullable|string'
        ]);

        $attendance = ClinicalAttendance::firstOrNew([
            'clinical_session_id' => $session->id,
            'mahasiswa_id' => $validated['mahasiswa_id']
        ]);

        $attendance->status = $validated['status'];
        $attendance->notes = $validated['notes'];
        
        if ($validated['status'] == 'hadir') {
            $attendance->check_in_time = $attendance->check_in_time ?? Carbon::now();
        }

        // If Dosen overrides/creates it, automatically validate
        if (in_array(Auth::user()->role, ['dosen', 'admin']) || Auth::user()->isSuperAdmin()) {
            $attendance->dosen_override_at = Carbon::now();
            $attendance->dosen_override_by = Auth::id();
        }

        $attendance->save();

        return redirect()->back()->with('success', 'Presensi berhasil disimpan.');
    }

    public function update(Request $request, ClinicalSession $session, ClinicalAttendance $attendance)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        // For CI or authorized staff to validate
        if ($request->has('validate_ci')) {
            $isCiMember = ClinicalClassMember::where('user_id', $user->id)->where('role', 'ci')->exists();
            $canValidate = $user->isSuperAdmin() || in_array($user->role, ['ci', 'dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum', 'admin']) || $isCiMember;

            if (!$canValidate) {
                abort(403, 'Anda tidak memiliki izin untuk memvalidasi presensi.');
            }

            $attendance->update([
                'ci_validated_at' => Carbon::now(),
                'ci_validated_by' => $user->id,
            ]);
            return redirect()->back()->with('success', 'Presensi berhasil divalidasi.');
        }
        
        // For Dosen/Admin to Override
        if ($request->has('override_dosen') && ($user->isSuperAdmin() || in_array($user->role, ['dosen', 'admin', 'kaprodi', 'admin_jurusan', 'kajur']))) {
            $validated = $request->validate([
                'status' => 'required|in:hadir,izin,sakit,alpha',
            ]);
            
            $attendance->update([
                'status' => $validated['status'],
                'dosen_override_at' => Carbon::now(),
                'dosen_override_by' => $user->id,
            ]);
            return redirect()->back()->with('success', 'Presensi berhasil dioverride.');
        }

        // Manual grading (direct score without rubric)
        if ($request->has('manual_grade') && ($user->isSuperAdmin() || in_array($user->role, ['dosen', 'admin', 'ci', 'kaprodi', 'admin_jurusan', 'kajur']))) {
            $validated = $request->validate([
                'final_grade' => 'required|numeric|min:0|max:100',
            ]);
            
            $attendance->update([
                'final_grade' => $validated['final_grade'],
            ]);
            return redirect()->back()->with('success', 'Nilai berhasil disimpan.');
        }

        // Grading with Rubric
        if ($request->has('rubric_scores') && ($user->isSuperAdmin() || in_array($user->role, ['dosen', 'admin', 'ci', 'kaprodi', 'admin_jurusan', 'kajur']))) {
            $scores = $request->input('rubric_scores');
            $session->load('evaluationRubric');
            
            if ($session->evaluationRubric) {
                $calculation = $session->evaluationRubric->calculateFinalScore($scores);
                $attendance->update([
                    'rubric_scores' => $scores,
                    'final_grade' => $calculation['final_percentage'],
                ]);
                return redirect()->back()->with('success', 'Nilai rubrik berhasil disimpan.');
            }
        }

        return redirect()->back()->with('error', 'Aksi tidak valid.');
    }

    public function export(ClinicalClass $class, ClinicalSession $session)
    {
        $session->load('attendances.mahasiswa');

        $filename = 'kehadiran_' . str_replace(' ', '_', $session->title) . '_' . $session->start_time->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($session, $class) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header info
            fputcsv($file, ['Kelas', $class->name]);
            fputcsv($file, ['Sesi', $session->title]);
            fputcsv($file, ['Tanggal', $session->start_time->format('d M Y')]);
            fputcsv($file, ['Waktu', $session->start_time->format('H:i') . ' - ' . $session->end_time->format('H:i')]);
            fputcsv($file, []);

            // Column headers
            fputcsv($file, ['No', 'NIM', 'Nama Mahasiswa', 'Shift', 'Lokasi Praktik', 'Status', 'Check-In', 'Check-Out', 'Validasi CI', 'Nilai']);

            $no = 1;
            foreach ($session->attendances as $att) {
                fputcsv($file, [
                    $no++,
                    $att->mahasiswa->nim ?? '-',
                    $att->mahasiswa->name ?? 'Mahasiswa',
                    $att->shift_name ?? '-',
                    $att->lokasi_praktik ?? '-',
                    ucfirst($att->status),
                    $att->check_in_time ? $att->check_in_time->format('H:i') : '-',
                    $att->check_out_time ? $att->check_out_time->format('H:i') : '-',
                    $att->ci_validated_at ? 'Ya' : 'Belum',
                    $att->final_grade ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function scanQr(ClinicalSession $session)
    {
        // For Mahasiswa: when they scan QR, they land here.
        if (Auth::user()->role !== 'mahasiswa' && !(Auth::user() instanceof \App\Models\Mahasiswa)) {
            return redirect()->route('home')->with('error', 'Hanya mahasiswa yang dapat melakukan presensi mandiri.');
        }

        return view('pbm.attendances.scan', compact('session'));
    }

    public function processScan(Request $request, ClinicalSession $session)
    {
        // For Mahasiswa processing their scan
        if (Auth::user()->role !== 'mahasiswa' && !(Auth::user() instanceof \App\Models\Mahasiswa)) {
            abort(403);
        }

        $mahasiswaId = Auth::user()->mahasiswa_id; // assuming relation exists, or find by nim
        if (!$mahasiswaId) {
            $mahasiswa = \App\Models\Mahasiswa::where('nim', Auth::user()->username)->first();
            if ($mahasiswa) {
                $mahasiswaId = $mahasiswa->id;
            } else {
                return back()->with('error', 'Profil mahasiswa tidak ditemukan.');
            }
        }

        $tipePresensi = $request->input('tipe_presensi', 'datang');
        $shiftName = $request->input('shift_name');
        $lokasiPraktik = $request->input('lokasi_praktik');
        $signatureData = $request->input('signature_data');

        if (!empty($session->shifts) && !$shiftName) {
            return back()->with('error', 'Silakan pilih Shift Praktik.');
        }

        if (!empty($session->lokasi_praktik) && !$lokasiPraktik) {
            return back()->with('error', 'Silakan pilih Lokasi Praktik.');
        }

        // Check if already attended
        $existing = ClinicalAttendance::where('clinical_session_id', $session->id)
            ->where('mahasiswa_id', $mahasiswaId)
            ->first();
            
        if ($existing && $existing->check_in_time && $existing->check_out_time) {
            return redirect()->route('pbm.classes.show', $session->clinical_class_id)->with('success', 'Anda sudah menyelesaikan presensi sesi ini.');
        }

        $lat = $request->input('lat');
        $lng = $request->input('lng');

        // GPS Validation if required
        if ($session->require_gps) {
            if (!$lat || !$lng) {
                return back()->with('error', 'Lokasi GPS tidak terdeteksi. Pastikan GPS aktif dan izinkan browser mengakses lokasi.');
            }
            
            if ($session->location_lat && $session->location_lng) {
                // Calculate distance in meters using Haversine formula
                $earthRadius = 6371000;
                $latTo = deg2rad($lat);
                $lngTo = deg2rad($lng);
                $latFrom = deg2rad($session->location_lat);
                $lngFrom = deg2rad($session->location_lng);
                
                $latDelta = $latTo - $latFrom;
                $lngDelta = $lngTo - $lngFrom;
                
                $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                    cos($latFrom) * cos($latTo) * pow(sin($lngDelta / 2), 2)));
                    
                $distance = $angle * $earthRadius;
                $allowedRadius = $session->location_radius ?? 100;
                
                if ($distance > $allowedRadius) {
                    return back()->with('error', 'Anda berada di luar radius lokasi praktik (' . round($distance) . 'm). Radius maksimal adalah ' . $allowedRadius . 'm.');
                }
            }
        }

        if (!$existing) {
            $existing = new ClinicalAttendance();
            $existing->clinical_session_id = $session->id;
            $existing->mahasiswa_id = $mahasiswaId;
            $existing->status = 'hadir';
        }

        if ($shiftName) $existing->shift_name = $shiftName;
        if ($lokasiPraktik) $existing->lokasi_praktik = $lokasiPraktik;

        // Process Signature
        $signaturePath = null;
        if ($signatureData && preg_match('/^data:image\/(\w+);base64,/', $signatureData, $type)) {
            $data = substr($signatureData, strpos($signatureData, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                $data = base64_decode($data);
                if ($data !== false) {
                    $fileName = 'signatures/pbm/' . $session->id . '/' . $mahasiswaId . '_' . time() . '.' . $type;
                    \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $data);
                    $signaturePath = 'storage/' . $fileName;
                }
            }
        }

        if ($tipePresensi === 'datang') {
            if ($existing->check_in_time) {
                return back()->with('error', 'Anda sudah melakukan Absen Masuk.');
            }
            $existing->check_in_time = \Carbon\Carbon::now();
            $existing->check_in_lat = $lat;
            $existing->check_in_lng = $lng;
            if ($signaturePath) $existing->check_in_signature_path = $signaturePath;
            $msg = 'Presensi Masuk berhasil dicatat.';
        } else {
            if (!$existing->check_in_time) {
                return back()->with('error', 'Anda belum melakukan Absen Masuk. Silakan Absen Masuk terlebih dahulu.');
            }
            if ($existing->check_out_time) {
                return back()->with('error', 'Anda sudah melakukan Absen Keluar.');
            }
            $existing->check_out_time = \Carbon\Carbon::now();
            $existing->check_out_lat = $lat;
            $existing->check_out_lng = $lng;
            if ($signaturePath) $existing->check_out_signature_path = $signaturePath;
            $msg = 'Presensi Keluar berhasil dicatat.';
        }
        
        $existing->save();

        return redirect()->route('pbm.classes.show', $session->clinical_class_id)->with('success', $msg);
    }
}
