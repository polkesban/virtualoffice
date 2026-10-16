<?php

namespace Database\Seeders;

use App\Models\UnitEvent;
use App\Models\UnitEventParticipant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MagangEssensialSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = base_path('MAGANG ESSENSIAL_SMT V_2026 2027.xlsx');
        if (!file_exists($filePath)) {
            $this->command->error("File MAGANG ESSENSIAL_SMT V_2026 2027.xlsx not found at $filePath");
            return;
        }

        $existingEventIds = UnitEvent::where('sub_jenis_kegiatan', 'gabungan')->pluck('id');
        if ($existingEventIds->isNotEmpty()) {
            DB::table('unit_event_attendances')->whereIn('unit_event_id', $existingEventIds)->delete();
            DB::table('unit_event_participants')->whereIn('unit_event_id', $existingEventIds)->delete();
            UnitEvent::whereIn('id', $existingEventIds)->delete();
        }

        $spreadsheet = IOFactory::load($filePath);
        
        // Find default creator
        $creator = User::where('email', 'admin@polkesban.ac.id')->first()
            ?: User::where('role', 'admin')->first()
            ?: User::where('role', 'kaprodi')->first()
            ?: User::first();

        if (!$creator) {
            $this->command->error("No user found in the database to act as event creator.");
            return;
        }

        // Find program studi D3 Kebidanan Bandung (ID 14)
        $prodi = \App\Models\ProgramStudi::find(14)
            ?: \App\Models\ProgramStudi::where('nama', 'like', '%Kebidanan Bandung%')->where('jenjang', 'D3')->first();

        $unitPage = null;
        if ($prodi) {
            $unitPage = DB::table('unit_pages')->where('role', 'kaprodi_' . $prodi->id)->first()
                ?: DB::table('unit_pages')->where('id', $prodi->unit_page_id)->first();
        }

        if (!$unitPage) {
            $unitPage = DB::table('unit_pages')->where('slug', 'prodi-kebidanan-bandung')->first()
                ?: DB::table('unit_pages')->where('slug', 'prodi-kebidanan')->first()
                ?: DB::table('unit_pages')->first();
        }

        if (!$unitPage) {
            $this->command->error("No unit page found in the database.");
            return;
        }

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            
            $judul = trim((string) $sheet->getCell('C1')->getValue());
            $waktuRange = trim((string) $sheet->getCell('C2')->getValue());
            $semesterRaw = trim((string) $sheet->getCell('C4')->getValue());
            $tahunAkademik = trim((string) $sheet->getCell('C6')->getValue());

            if (!$judul) {
                continue;
            }

            // Parse semester (e.g. "V (Lima " -> "V")
            $semester = 'V';
            if (preg_match('/^([IVXLCDM]+)/i', $semesterRaw, $semMatches)) {
                $semester = Str::upper($semMatches[1]);
            }

            // Clean Tahun Akademik (e.g. "2026/2027 ")
            $tahunAkademik = trim(str_replace(' ', '', $tahunAkademik));

            // Parse Date Range
            [$startDate, $endDate] = $this->parseDateRange($waktuRange);

            // Create UnitEvent
            $unitEvent = UnitEvent::create([
                'unit_page_id' => $unitPage->id,
                'created_by' => $creator->id,
                'judul' => $judul,
                'slug' => Str::slug($judul) . '-' . Str::random(4),
                'ringkasan' => 'Kegiatan PBM Matakuliah Gabungan: ' . $judul,
                'deskripsi' => 'Daftar hadir untuk matakuliah gabungan ' . $judul,
                'jenis_kegiatan' => 'pbm',
                'sub_jenis_kegiatan' => 'gabungan',
                'metode' => 'offline',
                'status' => 'published',
                'mulai_at' => $startDate . ' 07:00:00',
                'selesai_at' => $endDate . ' 23:59:00',
                'tahun_akademik' => $tahunAkademik ?: '2026/2027',
                'tingkat' => 'III',
                'semester' => $semester,
                'kapasitas' => 100,
                'registrasi_aktif' => 1,
                'daftar_hadir_aktif' => 1,
                'allow_direct_attendance' => 1,
            ]);

            // Now parse students and pembimbings from rows 10 to 50
            $students = [];
            $pembimbings = []; // unique pembimbing names keyed by location
            $locations = [];
            $kaprodiName = '';
            $kaprodiNip = '';
            $koordinatorName = '';
            $koordinatorNip = '';

            $highestRow = $sheet->getHighestRow();
            for ($r = 10; $r <= $highestRow; $r++) {
                $noVal = $sheet->getCell('A' . $r)->getValue();
                $nameVal = trim((string) $sheet->getCell('B' . $r)->getValue());
                $nimVal = trim((string) $sheet->getCell('C' . $r)->getValue());
                $tempatVal = trim((string) $sheet->getCell('D' . $r)->getValue());
                $p1Val = trim((string) $sheet->getCell('E' . $r)->getValue());
                $p2Val = trim((string) $sheet->getCell('F' . $r)->getValue());

                // If it's a student row (No is numeric)
                if (is_numeric($noVal) && $nameVal !== '') {
                    $students[] = [
                        'nama' => $nameVal,
                        'nim' => $nimVal,
                        'tempat_praktik' => $tempatVal,
                        'pembimbing_1' => $p1Val,
                        'pembimbing_2' => $p2Val,
                    ];

                    if ($tempatVal !== '') {
                        $locations[$tempatVal] = true;
                        if ($p1Val !== '') {
                            $pembimbings[$tempatVal][$p1Val] = 'pembimbing_institusi';
                        }
                        if ($p2Val !== '') {
                            $pembimbings[$tempatVal][$p2Val] = 'preseptor';
                        }
                    }
                }

                // If it's Koordinator/Ketua Program Studi signature info
                if (str_contains(strtolower($nameVal), 'koordinator')) {
                    $koordinatorName = trim((string) $sheet->getCell('C' . $r)->getValue());
                    $koordinatorNip = trim((string) $sheet->getCell('D' . $r)->getValue());
                }
                if (str_contains(strtolower($nameVal), 'ketua program')) {
                    $kaprodiName = trim((string) $sheet->getCell('C' . $r)->getValue());
                    $kaprodiNip = trim((string) $sheet->getCell('D' . $r)->getValue());
                }
            }

            // Update penandatangan, shifts and lokasi_praktik on event
            $locsArray = array_keys($locations);
            $unitEvent->update([
                'shifts' => [
                    ['name' => 'Pagi', 'start' => '07:00', 'end' => '14:00'],
                    ['name' => 'Siang', 'start' => '14:00', 'end' => '21:00'],
                    ['name' => 'Malam', 'start' => '21:00', 'end' => '07:00'],
                    ['name' => 'Libur', 'start' => '00:00', 'end' => '00:00'],
                ],
                'lokasi_praktik' => $locsArray,
                'penandatangan_jabatan' => 'Ketua Program Studi',
                'penandatangan_nama' => $kaprodiName ?: 'Sri Mulyati, SST., M. Keb',
                'penandatangan_nip' => $kaprodiNip ?: '198105092005012000',
            ]);

            // Insert students
            foreach ($students as $student) {
                $pembimbingStr = $student['pembimbing_1'];
                if ($student['pembimbing_2'] !== '') {
                    $pembimbingStr .= ' / ' . $student['pembimbing_2'];
                }

                $this->createParticipant($unitEvent->id, [
                    'peran' => 'participant',
                    'participant_category' => 'student',
                    'nama' => $student['nama'],
                    'identity_number' => $student['nim'],
                    'tempat_praktik' => $student['tempat_praktik'],
                    'nama_pembimbing' => $pembimbingStr,
                    'catatan' => 'Peserta mahasiswa untuk ' . $sheetName,
                ]);
            }

            // Insert unique pembimbings
            foreach ($pembimbings as $loc => $pList) {
                foreach ($pList as $pName => $peran) {
                    $this->createParticipant($unitEvent->id, [
                        'peran' => $peran, // 'speaker' or 'committee'
                        'participant_category' => 'employee',
                        'nama' => $pName,
                        'identity_number' => null,
                        'tempat_praktik' => $loc,
                        'nama_pembimbing' => null,
                        'catatan' => 'Pembimbing untuk lokasi ' . $loc,
                    ]);
                }
            }

            // Insert Koordinator Praktik as a speaker/committee participant if not exists
            if ($koordinatorName !== '') {
                $this->createParticipant($unitEvent->id, [
                    'peran' => 'speaker',
                    'participant_category' => 'employee',
                    'nama' => $koordinatorName,
                    'identity_number' => $koordinatorNip,
                    'tempat_praktik' => 'Semua Lokasi',
                    'nama_pembimbing' => null,
                    'catatan' => 'Koordinator Praktik',
                ]);
            }
        }
    }

    private function createParticipant(int $eventId, array $data): void
    {
        $temporaryPassword = Str::upper(Str::random(4)) . random_int(1000, 9999);

        // Access mapping/linking
        $linkedUser = User::where('name', $data['nama'])->first();

        UnitEventParticipant::create([
            'unit_event_id' => $eventId,
            'peran' => $data['peran'],
            'participant_category' => $data['participant_category'],
            'sumber_registrasi' => 'admin_import',
            'nama' => $data['nama'],
            'identity_number' => $data['identity_number'],
            'tempat_praktik' => $data['tempat_praktik'],
            'nama_pembimbing' => $data['nama_pembimbing'],
            'catatan' => $data['catatan'],
            'kode_registrasi' => 'EVT-' . Str::upper(Str::random(8)),
            'access_token' => Str::random(48),
            'access_password' => Hash::make($temporaryPassword),
            'access_password_encrypted' => Crypt::encryptString($temporaryPassword),
            'credentials_generated_at' => now(),
            'registered_at' => now(),
            'linked_user_id' => $linkedUser?->id,
        ]);
    }

    private function parseDateRange(string $waktuRange): array
    {
        $waktuRange = trim($waktuRange);
        $parts = explode('s.d', $waktuRange);
        if (count($parts) < 2) {
            $parts = explode('sd', $waktuRange);
        }
        
        $startStr = trim($parts[0] ?? '');
        $endStr = trim($parts[1] ?? '');
        
        $monthMap = [
            'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04', 'mei' => '05', 'juni' => '06',
            'juli' => '07', 'agustus' => '08', 'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12',
            'okt' => '10'
        ];
        
        // Parse end date
        preg_match('/(\d+)\s+([a-zA-Z]+)\s+(\d{4})/', $endStr, $endMatches);
        if ($endMatches) {
            $endDay = str_pad($endMatches[1], 2, '0', STR_PAD_LEFT);
            $endMonthName = strtolower($endMatches[2]);
            $endMonth = $monthMap[$endMonthName] ?? '08';
            $endYear = $endMatches[3];
            $endDate = "$endYear-$endMonth-$endDay";
        } else {
            $endDate = "2026-10-25";
            $endYear = "2026";
        }
        
        // Parse start date
        preg_match('/(\d+)\s+([a-zA-Z]+)(?:\s+(\d{4}))?/', $startStr, $startMatches);
        if ($startMatches) {
            $startDay = str_pad($startMatches[1], 2, '0', STR_PAD_LEFT);
            $startMonthName = strtolower($startMatches[2]);
            $startMonth = $monthMap[$startMonthName] ?? '07';
            $startYear = $startMatches[3] ?? $endYear;
            $startDate = "$startYear-$startMonth-$startDay";
        } else {
            $startDate = "2026-07-13";
        }
        
        return [$startDate, $endDate];
    }
}
