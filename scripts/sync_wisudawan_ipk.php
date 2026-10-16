<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\UnitEventParticipant;
use Illuminate\Support\Facades\DB;

$excelFile = __DIR__ . '/../Data Wisudawan September 2026.xlsx';
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($excelFile);
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($excelFile);
$sheet = $spreadsheet->getActiveSheet();
$highestRow = $sheet->getHighestRow();

echo "Updating participants with IPK and details from Excel (Total rows: $highestRow)...\n";

$updated = 0;
$participants = UnitEventParticipant::where('unit_event_id', 70)->get();
$byNim = [];
$byName = [];

foreach ($participants as $p) {
    if ($p->identity_number) {
        $byNim[strtoupper(trim($p->identity_number))] = $p;
    }
    $cleanName = str_replace(['Orang Tua / Wali dari ', 'Orang tua/ Wali '], '', $p->nama);
    $byName[strtolower(trim($cleanName))] = $p;
}

DB::transaction(function () use ($sheet, $highestRow, &$byNim, &$byName, &$updated) {
    for ($row = 2; $row <= $highestRow; $row++) {
        $nim  = strtoupper(trim($sheet->getCell('C'.$row)->getValue() ?? ''));
        $nama = trim($sheet->getCell('D'.$row)->getValue() ?? '');
        $ipk  = trim($sheet->getCell('G'.$row)->getValue() ?? '');
        $ttl  = trim($sheet->getCell('F'.$row)->getValue() ?? '');
        $jk   = trim($sheet->getCell('E'.$row)->getValue() ?? '');

        if ($nama === '' && $nim === '') continue;

        $p = null;
        if ($nim !== '' && isset($byNim[$nim])) {
            $p = $byNim[$nim];
        } elseif (isset($byName[strtolower($nama)])) {
            $p = $byName[strtolower($nama)];
        }

        if ($p) {
            $meta = $p->meta ?? [];
            $meta['nama_wisudawan'] = $nama;
            $meta['ipk'] = $ipk ?: ($meta['ipk'] ?? null);
            $meta['ttl'] = $ttl ?: ($meta['ttl'] ?? null);
            $meta['jk'] = $jk ?: ($meta['jk'] ?? null);

            $p->meta = $meta;
            $p->saveQuietly();
            $updated++;
        }
    }
});

echo "Successfully updated $updated participants with IPK and details!\n";
