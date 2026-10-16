<?php

require __DIR__ . '/../vendor/autoload.php';

$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile(__DIR__ . '/../Data Wisudawan September 2026.xlsx');
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load(__DIR__ . '/../Data Wisudawan September 2026.xlsx');
$sheet = $spreadsheet->getActiveSheet();

$prodiValues = [];
$dataCount = 0;
$emptyNameCount = 0;

for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
    $prodi = trim($sheet->getCell('A'.$row)->getValue() ?? '');
    $nama = trim($sheet->getCell('D'.$row)->getValue() ?? '');
    $nim = trim($sheet->getCell('C'.$row)->getValue() ?? '');
    
    if ($nama === '' && $nim === '') {
        $emptyNameCount++;
        continue;
    }
    
    $dataCount++;
    if ($prodi !== '') {
        if (!isset($prodiValues[$prodi])) {
            $prodiValues[$prodi] = 0;
        }
        $prodiValues[$prodi]++;
    }
}

echo "Total rows with data: $dataCount\n";
echo "Empty rows skipped: $emptyNameCount\n\n";
echo "Program Studi values:\n";
ksort($prodiValues);
foreach ($prodiValues as $p => $count) {
    echo "  $p: $count rows\n";
}
