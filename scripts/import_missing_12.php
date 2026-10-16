<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\UnitEvent;
use App\Models\UnitEventParticipant;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$event = UnitEvent::findOrFail(70);
$excelFile = __DIR__ . '/../Data Wisudawan September 2026.xlsx';

$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($excelFile);
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($excelFile);
$sheet = $spreadsheet->getActiveSheet();

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
    $temporaryPassword = generateTemporaryPassword();

    $participant->forceFill([
        'access_password' => Hash::make($temporaryPassword),
        'access_password_encrypted' => Crypt::encryptString($temporaryPassword),
        'credentials_generated_at' => now(),
    ])->saveQuietly();
}

$rowsToAdd = [476, 477, 482, 487, 488, 490, 492, 493, 769, 801, 967, 1171];
$added = 0;

DB::transaction(function () use ($sheet, $rowsToAdd, $prodiMap, $event, &$added) {
    foreach ($rowsToAdd as $row) {
        $prodi = trim($sheet->getCell('A'.$row)->getValue() ?? '');
        $nim   = trim($sheet->getCell('C'.$row)->getValue() ?? '');
        $nama  = trim($sheet->getCell('D'.$row)->getValue() ?? '');

        $instansi = $prodiMap[$prodi] ?? $prodi;
        $invitationName = 'Orang Tua / Wali dari ' . $nama;

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

        ensureCredentials($participant);
        $added++;
        echo "Added row $row: $nama ($nim) - $instansi [ID={$participant->id}]\n";
    }
});

echo "\nTotal added: $added\n";
echo "Total participants in Event 70 now: " . $event->participants()->count() . "\n";
