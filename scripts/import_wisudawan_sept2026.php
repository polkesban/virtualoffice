<?php

/**
 * Import Wisudawan September 2026 from Excel file
 * into event ID=70 (Wisuda Periode September 2025/2026)
 * under unit page bidang-kemahasiswaan (ID=3).
 *
 * Each wisudawan becomes a "participant" (peran=participant, participant_category=public)
 * named "Orang Tua / Wali dari <NAMA_WISUDAWAN>",
 * with meta containing nama_wisudawan and the program studi as instansi.
 * Credentials (access_password, access_token, etc.) are generated
 * so QR code invitations can be printed.
 *
 * Usage: php scripts/import_wisudawan_sept2026.php
 */

require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\UnitEvent;
use App\Models\UnitEventParticipant;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// ---- Configuration ----
$eventId  = 70;
$excelFile = __DIR__ . '/../Data Wisudawan September 2026.xlsx';

// ---- Validate prerequisites ----
$event = UnitEvent::findOrFail($eventId);
echo "Event: {$event->judul}\n";
echo "Jenis: {$event->jenis_kegiatan}\n";
echo "Existing participants: {$event->participants()->count()}\n\n";

if ($event->jenis_kegiatan !== 'wisuda') {
    echo "ERROR: Event jenis_kegiatan is not 'wisuda'. Aborting.\n";
    exit(1);
}

// ---- Read Excel file ----
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($excelFile);
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($excelFile);
$sheet = $spreadsheet->getActiveSheet();
$highestRow = $sheet->getHighestRow();

echo "Reading sheet: {$sheet->getTitle()}\n";
echo "Rows: $highestRow\n\n";

// ---- Build known-set for deduplication ----
$knownNims = [];
$knownNames = [];

foreach ($event->participants()->get() as $p) {
    if ($p->identity_number) {
        $knownNims[$p->identity_number] = true;
    }
    $cleanName = $p->nama;
    if (Str::startsWith($cleanName, 'Orang Tua / Wali dari ')) {
        $cleanName = str_replace('Orang Tua / Wali dari ', '', $cleanName);
    }
    $knownNames[Str::lower(trim($cleanName))] = true;

    if (!empty($p->meta['nama_wisudawan'])) {
        $knownNames[Str::lower(trim($p->meta['nama_wisudawan']))] = true;
    }
}

// ---- Helpers ----
function generateUniqueCode(): string
{
    do {
        $code = 'EVT-' . Str::upper(Str::random(8));
    } while (UnitEventParticipant::query()->where('kode_registrasi', $code)->exists());
    return $code;
}

function generateUniqueToken(): string
{
    do {
        $token = Str::random(48);
    } while (UnitEventParticipant::query()->where('access_token', $token)->exists());
    return $token;
}

function generateTemporaryPassword(): string
{
    return Str::upper(Str::random(4)) . random_int(1000, 9999);
}

function ensureCredentials(UnitEventParticipant $participant): void
{
    if ($participant->usesLinkedApplicationAccess() && ! $participant->hasAccessCredentials()) {
        return;
    }
    if ($participant->hasAccessCredentials() && filled($participant->access_password_encrypted)) {
        return;
    }

    $temporaryPassword = generateTemporaryPassword();

    $participant->forceFill([
        'access_password' => Hash::make($temporaryPassword),
        'access_password_encrypted' => Crypt::encryptString($temporaryPassword),
        'credentials_generated_at' => now(),
    ])->saveQuietly();
}

// ---- Map Program Studi from Excel to instansi label ----
$prodiMap = [
    'D3 Farmasi'                => 'DIII Farmasi',
    'D3 Gizi'                   => 'DIII Gizi',
    'D3 Kebidanan'              => 'DIII Kebidanan (Kampus Bandung)',
    'D3 Kebidanan Bogor'        => 'DIII Kebidanan (Kampus Bogor)',
    'D3 Kebidanan Karawang'     => 'DIII Kebidanan (Kampus Karawang)',
    'D3 Keperawatan'            => 'DIII Keperawatan (Kampus Bandung)',
    'D3 Keperawatan Bogor'      => 'DIII Keperawatan (Kampus Bogor)',
    'D3 Kesehatan Gigi'         => 'DIII Kesehatan Gigi',
    'D3 Sanitasi'               => 'DIII Sanitasi',
    'D3 TLM'                    => 'DIII Teknologi Laboratorium Medis',
    'Profesi Bidan'             => 'Profesi Bidan',
    'Profesi Ners'              => 'Profesi Ners',
    'Profesi Dietisien'         => 'Profesi Dietisien',
    'RPL Dietisien'             => 'Profesi Dietisien',
    'RPL STr Gizi dan Dietetika'=> 'STr Gizi',
    'RPL STr Kebidanan'         => 'STr Kebidanan',
    'RPL STr Keperawatan'       => 'STr Keperawatan',
    'RPL STr Kesling'           => 'STr Sanitasi Lingkungan',
    'RPL STr TLM'               => 'STr Teknologi Laboratorium Medis',
    'STr Gizi dan Dietetika'    => 'STr Gizi',
    'STr Kebidanan'             => 'STr Kebidanan',
    'STr Keperawatan'           => 'STr Keperawatan',
    'STr Promkes'               => 'STr Promosi Kesehatan',
    'STr Sanitasi Lingkungan'   => 'STr Sanitasi Lingkungan',
    'STr TLM'                   => 'STr Teknologi Laboratorium Medis',
    'STr Terapi Gigi'           => 'STr Terapi Gigi',
];

// ---- Import ----
$importedCount = 0;
$skippedCount = 0;
$errors = [];
$lastProdi = '';

echo "Starting import...\n";

DB::transaction(function () use (
    $sheet, $highestRow, $event, $prodiMap,
    &$knownNims, &$knownNames,
    &$importedCount, &$skippedCount, &$errors, &$lastProdi
) {
    for ($row = 2; $row <= $highestRow; $row++) {
        $prodi = trim($sheet->getCell('A'.$row)->getValue() ?? '');
        $nim   = trim($sheet->getCell('C'.$row)->getValue() ?? '');
        $nama  = trim($sheet->getCell('D'.$row)->getValue() ?? '');

        // Track current prodi (it can span multiple rows)
        if ($prodi !== '') {
            $lastProdi = $prodi;
        }

        // Skip empty rows
        if ($nama === '' && $nim === '') {
            continue;
        }

        // Use last known prodi if current row is blank
        $currentProdi = $prodi !== '' ? $prodi : $lastProdi;

        // Check for duplicates by NIM
        if ($nim !== '' && isset($knownNims[$nim])) {
            $skippedCount++;
            continue;
        }

        // Check for duplicates by name
        if (isset($knownNames[Str::lower($nama)])) {
            $skippedCount++;
            continue;
        }

        // Map instansi
        $instansi = $prodiMap[$currentProdi] ?? $currentProdi;

        // Build participant name (same as June 2026 event pattern)
        $invitationName = 'Orang Tua / Wali dari ' . $nama;

        // Create participant
        $participant = UnitEventParticipant::create([
            'unit_event_id'       => $event->id,
            'peran'               => 'participant',
            'participant_category'=> 'public',
            'sumber_registrasi'   => 'admin_import',
            'nama'                => $invitationName,
            'identity_number'     => $nim ?: null,
            'instansi'            => $instansi,
            'kode_registrasi'     => generateUniqueCode(),
            'access_token'        => generateUniqueToken(),
            'registered_at'       => now(),
            'meta'                => [
                'nama_wisudawan' => $nama,
            ],
        ]);

        // Generate credentials for QR code
        ensureCredentials($participant);

        // Track for deduplication
        if ($nim !== '') {
            $knownNims[$nim] = true;
        }
        $knownNames[Str::lower($nama)] = true;
        $importedCount++;

        // Progress
        if ($importedCount % 100 === 0) {
            echo "  ...imported $importedCount so far\n";
        }
    }
});

echo "\n=== Import Complete ===\n";
echo "Imported: $importedCount\n";
echo "Skipped (duplicate): $skippedCount\n";
echo "Total participants now: {$event->participants()->count()}\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $err) {
        echo "  - $err\n";
    }
}
