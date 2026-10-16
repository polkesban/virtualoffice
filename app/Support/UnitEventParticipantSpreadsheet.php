<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class UnitEventParticipantSpreadsheet
{
    private const TEMPLATE_COLUMNS = [
        ['header' => 'Nama', 'key' => 'nama', 'width' => 28],
        ['header' => 'NIP/NIS/NIK', 'key' => 'identity_number', 'width' => 20],
        ['header' => 'Kategori Peserta', 'key' => 'participant_category', 'width' => 24],
        ['header' => 'Unit Kerja', 'key' => 'instansi', 'width' => 34],
        ['header' => 'Tempat Praktik', 'key' => 'tempat_praktik', 'width' => 28],
        ['header' => 'Nama Pembimbing', 'key' => 'nama_pembimbing', 'width' => 28],
        ['header' => 'Jabatan', 'key' => 'jabatan', 'width' => 30],
        ['header' => 'Peran', 'key' => 'peran', 'width' => 18],
        ['header' => 'Email', 'key' => 'email', 'width' => 28],
        ['header' => 'No HP', 'key' => 'no_hp', 'width' => 18],
        ['header' => 'Catatan', 'key' => 'catatan', 'width' => 34],
    ];

    private const SAMPLE_ROWS = [
        ['Yuliyani Dewi Sartika', '199001012024031001', 'Pegawai Poltekkes Bandung', 'Jurusan Kesehatan Lingkungan', 'RSUD Welas Asih', 'Yulia Ulfah SST., M.Keb', 'Admin Prodi', 'Peserta', 'yuliyani@example.test', '081234567890', 'Peserta umum'],
        ['Ulfah Utami, M.Kes', '198807152020122002', 'Pegawai Poltekkes Bandung', 'Jurusan Kesehatan Gigi', 'RSUD Welas Asih', 'Yulia Ulfah SST., M.Keb', 'PJ Adak Jurusan / prodi', 'Narasumber', 'ulfah@example.test', '081298765432', 'Sesi pembukaan'],
        ['Rudiansyah', '2204010007', 'Mahasiswa', 'Prodi PSDKU Keperawatan Bogor', 'RSAU dr. M. Salamun', 'Pembimbing Lahan', 'Admin Prodi', 'Panitia', '', '081277788899', 'Registrasi meja depan'],
        ['Rizki Lestari Kusuma Putri', '2204010011', 'Mahasiswa', 'Jurusan Kesehatan Lingkungan', 'RSAU dr. M. Salamun', 'Pembimbing Lahan', 'Admin Prodi', 'Peserta', '', '', ''],
        ['Ekberth Mandaku', '', 'Non Pegawai / Umum', 'Prodi PSDKU Keperawatan Bogor', '', '', 'Admin Prodi', 'Peserta', 'ekberth@example.test', '081255566677', ''],
    ];

    private const HEADER_ALIASES = [
        'nama' => 'nama',
        'name' => 'nama',
        'nip_nis_nik' => 'identity_number',
        'nip' => 'identity_number',
        'nis' => 'identity_number',
        'nik' => 'identity_number',
        'nim' => 'identity_number',
        'no_identitas' => 'identity_number',
        'nomor_identitas' => 'identity_number',
        'kategori' => 'participant_category',
        'kategori_peserta' => 'participant_category',
        'participant_category' => 'participant_category',
        'jenis_peserta' => 'participant_category',
        'status_peserta' => 'participant_category',
        'unit_kerja' => 'instansi',
        'unit_kerja_instansi' => 'instansi',
        'instansi' => 'instansi',
        'asal_instansi' => 'instansi',
        'tempat_praktik' => 'tempat_praktik',
        'lokasi_praktik' => 'tempat_praktik',
        'nama_pembimbing' => 'nama_pembimbing',
        'pembimbing' => 'nama_pembimbing',
        'pembimbing_lahan' => 'nama_pembimbing',
        'jabatan' => 'jabatan',
        'posisi' => 'jabatan',
        'peran' => 'peran',
        'role' => 'peran',
        'email' => 'email',
        'no_hp' => 'no_hp',
        'nomor_hp' => 'no_hp',
        'no_wa' => 'no_hp',
        'nomor_wa' => 'no_hp',
        'whatsapp' => 'no_hp',
        'catatan' => 'catatan',
        'notes' => 'catatan',
        'keterangan' => 'catatan',
    ];

    public function createTemplateFile(string $eventTitle): string
    {
        $spreadsheet = new Spreadsheet();
        $templateSheet = $spreadsheet->getActiveSheet();
        $templateSheet->setTitle('Template Import');

        $this->configureTemplateSheet($templateSheet, $eventTitle);

        $exampleSheet = new Worksheet($spreadsheet, 'Contoh Format');
        $spreadsheet->addSheet($exampleSheet);
        $this->configureExampleSheet($exampleSheet, $eventTitle);

        $spreadsheet->setActiveSheetIndex(0);

        $path = tempnam(sys_get_temp_dir(), 'unit-event-import-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    public function readImportRows(UploadedFile $file): array
    {
        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());

            if (method_exists($reader, 'setReadDataOnly')) {
                $reader->setReadDataOnly(true);
            }

            $spreadsheet = $reader->load($file->getRealPath());
        } catch (Throwable $exception) {
            throw ValidationException::withMessages([
                'participants_file' => 'File Excel tidak dapat dibaca. Pastikan format file sesuai template.',
            ]);
        }

        $rows = $spreadsheet->getSheet(0)->toArray('', true, true, true);
        $spreadsheet->disconnectWorksheets();

        if (! isset($rows[1]) || $this->rowIsEmpty($rows[1])) {
            throw ValidationException::withMessages([
                'participants_file' => 'Template impor tidak memiliki baris header yang valid.',
            ]);
        }

        $headerMap = $this->resolveHeaderMap($rows[1]);

        if (! in_array('nama', $headerMap, true)) {
            throw ValidationException::withMessages([
                'participants_file' => 'Kolom `Nama` wajib ada pada file impor.',
            ]);
        }

        $parsedRows = [];

        foreach (array_slice($rows, 1, null, true) as $rowNumber => $row) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $payload = [
                'nama' => '',
                'identity_number' => '',
                'participant_category' => '',
                'instansi' => '',
                'tempat_praktik' => '',
                'nama_pembimbing' => '',
                'jabatan' => '',
                'peran' => 'participant',
                'email' => '',
                'no_hp' => '',
                'catatan' => '',
            ];

            foreach ($headerMap as $column => $key) {
                $payload[$key] = $this->cleanCellValue($row[$column] ?? '');
            }

            if ($payload['nama'] === '') {
                continue;
            }

            // Skip template instruction rows that may appear at the bottom of the spreadsheet
            if ($this->isTemplateInstructionRow($payload['nama'])) {
                continue;
            }

            $payload['peran'] = $this->normalizePeran($payload['peran'], (int) $rowNumber);
            $payload['identity_number'] = $this->cleanIdentityNumber($payload['identity_number']);
            $payload['participant_category'] = $this->normalizeParticipantCategory($payload['participant_category'], (int) $rowNumber);

            if ($payload['email'] !== '' && ! filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'participants_file' => 'Email pada baris ' . $rowNumber . ' tidak valid.',
                ]);
            }

            $parsedRows[] = $payload;
        }

        return $parsedRows;
    }

    private function configureTemplateSheet(Worksheet $sheet, string $eventTitle): void
    {
        $headers = array_column(self::TEMPLATE_COLUMNS, 'header');
        $lastColumn = $this->lastColumnLetter();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $lastColumn . '200');
        $sheet->getDefaultRowDimension()->setRowHeight(22);

        foreach (self::TEMPLATE_COLUMNS as $index => $column) {
            $columnLetter = chr(65 + $index);
            $sheet->getColumnDimension($columnLetter)->setWidth($column['width']);
        }

        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '5B3F92'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        $sheet->getStyle('B2:B200')->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('H2:H200')->getNumberFormat()->setFormatCode('@');

        $categoryValidation = new DataValidation();
        $categoryValidation->setType(DataValidation::TYPE_LIST);
        $categoryValidation->setErrorStyle(DataValidation::STYLE_STOP);
        $categoryValidation->setAllowBlank(true);
        $categoryValidation->setShowDropDown(true);
        $categoryValidation->setShowErrorMessage(true);
        $categoryValidation->setErrorTitle('Kategori peserta tidak valid');
        $categoryValidation->setError('Gunakan salah satu nilai berikut: Pegawai Poltekkes Bandung, Mahasiswa, atau Non Pegawai / Umum.');
        $categoryValidation->setFormula1('"Pegawai Poltekkes Bandung,Mahasiswa,Non Pegawai / Umum"');

        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowDropDown(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Peran tidak valid');
        $validation->setError('Gunakan salah satu nilai berikut: Peserta, Narasumber, Panitia, Pembimbing Institusi, Preseptor, Koordinator Matakuliah, atau Kaprodi.');
        $validation->setFormula1('"Peserta,Narasumber,Panitia,Pembimbing Institusi,Preseptor,Koordinator Matakuliah,Kaprodi"');

        for ($row = 2; $row <= 200; $row++) {
            $sheet->getCell('C' . $row)->setDataValidation(clone $categoryValidation);
            $sheet->getCell('H' . $row)->setDataValidation(clone $validation);
        }

        $sheet->setSelectedCell('A2');
        $sheet->setCellValue('A203', 'Gunakan sheet ini untuk diisi lalu diimpor kembali ke sistem.');
        $sheet->setCellValue('A204', 'Kolom minimal yang dibaca sistem adalah Nama. Isi kategori peserta, serta NIP/NIS/NIK jika peserta sudah punya akun agar sistem bisa memakai akun lama saat membuka daftar hadir.');
        $sheet->mergeCells('A203:' . $lastColumn . '203');
        $sheet->mergeCells('A204:' . $lastColumn . '204');
        $sheet->getStyle('A203:' . $lastColumn . '204')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A203:' . $lastColumn . '204')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A203:' . $lastColumn . '204')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');

        $sheet->setCellValue('A206', 'Template impor peserta event: ' . $eventTitle);
        $sheet->mergeCells('A206:' . $lastColumn . '206');
        $sheet->getStyle('A206:' . $lastColumn . '206')->getFont()->setBold(true)->setSize(10);
    }

    private function configureExampleSheet(Worksheet $sheet, string $eventTitle): void
    {
        $headers = array_column(self::TEMPLATE_COLUMNS, 'header');
        $lastColumn = $this->lastColumnLetter();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray(self::SAMPLE_ROWS, null, 'A2');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $lastColumn . (count(self::SAMPLE_ROWS) + 1));

        foreach (self::TEMPLATE_COLUMNS as $index => $column) {
            $columnLetter = chr(65 + $index);
            $sheet->getColumnDimension($columnLetter)->setWidth($column['width']);
        }

        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '5B3F92'],
            ],
        ]);

        $sheet->getStyle('A1:' . $lastColumn . (count(self::SAMPLE_ROWS) + 1))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E5E7EB'],
                ],
            ],
        ]);

        foreach (range(2, count(self::SAMPLE_ROWS) + 1) as $row) {
            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':' . $lastColumn . $row)
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('F8FAFC');
            }
        }

        $sheet->setCellValue('A9', 'Contoh format tabel seperti daftar pada gambar referensi.');
        $sheet->setCellValue('A10', 'Saat impor, sistem hanya membaca sheet pertama: `Template Import`.');
        $sheet->setCellValue('A11', 'Kategori Peserta dapat diisi dengan Pegawai Poltekkes Bandung, Mahasiswa, atau Non Pegawai / Umum. Jika kosong, sistem akan menginfer dari akun yang terhubung lalu default ke umum.');
        $sheet->setCellValue('A12', 'Peran dapat diisi dengan Peserta, Narasumber, Panitia, Pembimbing Institusi, Preseptor, Koordinator Matakuliah, Kaprodi, atau dibiarkan kosong untuk default Peserta.');
        $sheet->setCellValue('A13', 'Kolom NIP/NIS/NIK dipakai untuk menghubungkan peserta ke akun aplikasi yang sudah ada, terutama akun mahasiswa dengan NIM.');
        $sheet->mergeCells('A9:' . $lastColumn . '9');
        $sheet->mergeCells('A10:' . $lastColumn . '10');
        $sheet->mergeCells('A11:' . $lastColumn . '11');
        $sheet->mergeCells('A12:' . $lastColumn . '12');
        $sheet->mergeCells('A13:' . $lastColumn . '13');
        $sheet->getStyle('A9:' . $lastColumn . '13')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A9:' . $lastColumn . '13')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A9:' . $lastColumn . '13')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEFCE8');
        $sheet->getStyle('A9:' . $lastColumn . '13')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('FDE68A');

        $sheet->setCellValue('A15', 'Contoh impor peserta event: ' . $eventTitle);
        $sheet->mergeCells('A15:' . $lastColumn . '15');
        $sheet->getStyle('A15:' . $lastColumn . '15')->getFont()->setBold(true)->setSize(10);
    }

    private function resolveHeaderMap(array $headerRow): array
    {
        $headerMap = [];

        foreach ($headerRow as $column => $value) {
            $normalized = $this->normalizeHeader((string) $value);
            $key = self::HEADER_ALIASES[$normalized] ?? null;

            if ($key && ! in_array($key, $headerMap, true)) {
                $headerMap[$column] = $key;
            }
        }

        return $headerMap;
    }

    private function normalizeHeader(string $value): string
    {
        return (string) Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_');
    }

    private function normalizePeran(string $value, int $rowNumber): string
    {
        $normalized = $this->normalizeHeader($value);

        return match ($normalized) {
            '', 'peserta', 'participant' => 'participant',
            'narasumber', 'speaker', 'pemateri' => 'speaker',
            'panitia', 'committee' => 'committee',
            'pembimbing_institusi', 'pembimbing', 'pembimbing_lahan', 'dosen_pembimbing' => 'pembimbing_institusi',
            'preseptor' => 'preseptor',
            'koordinator_matakuliah', 'koordinator', 'kordinator' => 'koordinator_matakuliah',
            'kaprodi' => 'kaprodi',
            default => throw ValidationException::withMessages([
                'participants_file' => 'Peran pada baris ' . $rowNumber . ' tidak dikenali. Gunakan Peserta, Narasumber, Panitia, Pembimbing Institusi, Preseptor, Koordinator Matakuliah, atau Kaprodi.',
            ]),
        };
    }

    private function normalizeParticipantCategory(string $value, int $rowNumber): string
    {
        $normalized = $this->normalizeHeader($value);

        return match ($normalized) {
            '' => '',
            'pegawai', 'pegawai_polkesban', 'employee', 'staff', 'internal' => 'employee',
            'mahasiswa', 'student' => 'student',
            'umum', 'public', 'non_pegawai', 'non_pegawai_umum', 'non_pegawai_polkesban', 'eksternal', 'external' => 'public',
            default => throw ValidationException::withMessages([
                'participants_file' => 'Kategori peserta pada baris ' . $rowNumber . ' tidak dikenali. Gunakan Pegawai Poltekkes Bandung, Mahasiswa, atau Non Pegawai / Umum.',
            ]),
        };
    }

    private function cleanCellValue(mixed $value): string
    {
        return trim((string) $value);
    }

    private function cleanIdentityNumber(mixed $value): string
    {
        return Str::upper((string) preg_replace('/[^A-Za-z0-9]+/', '', trim((string) $value)));
    }

    private function lastColumnLetter(): string
    {
        return chr(64 + count(self::TEMPLATE_COLUMNS));
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->cleanCellValue($value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Detect template instruction/footer rows that should not be imported as participants.
     * These rows appear at the bottom of the template spreadsheet and contain instructions
     * for users, not actual participant data.
     */
    private function isTemplateInstructionRow(string $nama): bool
    {
        // Names longer than 100 characters are almost certainly not real person names
        if (mb_strlen($nama) > 100) {
            return true;
        }

        $instructionPrefixes = [
            'Gunakan sheet ini',
            'Kolom minimal yang dibaca',
            'Template impor peserta event',
            'Contoh format tabel',
            'Saat impor, sistem hanya',
            'Kategori Peserta dapat diisi',
            'Peran dapat diisi',
            'Kolom NIP/NIS/NIK dipakai',
            'Contoh impor peserta event',
        ];

        foreach ($instructionPrefixes as $prefix) {
            if (Str::startsWith($nama, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public static function resolveProdiIdFromInstansi(?string $instansi): ?int
    {
        if (blank($instansi)) {
            return null;
        }

        $instansiLower = strtolower(trim($instansi));

        $map = [
            'diii keperawatan (kampus bandung)' => 12,
            'diii keperawatan bandung' => 12,
            'keperawatan bandung' => 12,

            'diii keperawatan (kampus bogor)' => 13,
            'diii keperawatan bogor' => 13,
            'keperawatan bogor' => 13,

            'diii kebidanan (kampus bandung)' => 14,
            'diii kebidanan bandung' => 14,
            'kebidanan bandung' => 14,

            'diii kebidanan (kampus bogor)' => 16,
            'diii kebidanan bogor' => 16,
            'kebidanan bogor' => 16,

            'diii kebidanan (kampus karawang)' => 15,
            'diii kebidanan karawang' => 15,
            'kebidanan karawang' => 15,

            'diii gizi' => 22,
            'gizi d3' => 22,
            'd3 gizi' => 22,
            'gizi' => 22,

            'diii sanitasi' => 18,
            'd3 sanitasi' => 18,
            'sanitasi' => 18,
            'sanitasi & kesehatan lingkungan' => 18,

            'diii teknologi laboratorium medis' => 6,
            'd3 analis kesehatan' => 6,
            'analis kesehatan' => 6,
            'd3 tlm' => 6,

            'str keperawatan' => 1,
            'd4 keperawatan' => 1,

            'str kebidanan' => 2,
            'd4 kebidanan' => 2,

            'str gizi' => 3,
            'd4 gizi' => 3,

            'str sanitasi lingkungan' => 5,
            'd4 kesehatan lingkungan' => 5,
            'd4 sanitasi lingkungan' => 5,

            'str teknologi laboratorium medis' => 10,
            'sarjana terapan tlm' => 10,
            'd4 tlm' => 10,

            'str terapi gigi' => 11,
            'terapi gigi' => 11,
            'd4 terapi gigi' => 11,

            'diii kesehatan gigi' => 17,
            'd3 kesehatan gigi' => 17,
            'kesehatan gigi' => 17,

            'profesi ners' => 19,
            'ners' => 19,

            'profesi bidan' => 20,
            'bidan profesi' => 20,

            'profesi dietisien' => 21,
            'dietisien' => 21,
        ];

        if (isset($map[$instansiLower])) {
            return $map[$instansiLower];
        }

        foreach ($map as $key => $id) {
            if (str_contains($instansiLower, $key)) {
                return $id;
            }
        }

        return null;
    }

    public function createExportFile(\App\Models\UnitEvent $unitEvent, \Illuminate\Support\Collection $participants): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar Undangan');

        // Set Headers
        $headers = [
            'No',
            'ID Registrasi',
            'Nama Undangan (Orang Tua/Wali)',
            'Nama Wisudawan',
            'NIM Wisudawan',
            'Program Studi',
            'No Kursi',
            'No HP',
            'Email',
            'Link Undangan Digital',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $rowNumber = 2;
        foreach ($participants as $index => $p) {
            $mahasiswa = $p->linkedMahasiswa;
            if (!$mahasiswa && $p->identity_number) {
                $mahasiswa = \App\Models\Mahasiswa::where('nim', $p->identity_number)->first();
            }
            
            $prodiName = '—';
            if ($mahasiswa?->programStudi?->nama) {
                $prodiName = $mahasiswa->programStudi->nama . ' (' . $mahasiswa->programStudi->jenjang . ')';
            } else {
                $resolvedProdiId = self::resolveProdiIdFromInstansi($p->instansi);
                if ($resolvedProdiId) {
                    $prodi = \App\Models\ProgramStudi::find($resolvedProdiId);
                    if ($prodi) {
                        $prodiName = $prodi->nama . ' (' . $prodi->jenjang . ')';
                    }
                }
            }

            $link = route('unit.events.access', [
                'unitSlug' => $unitEvent->page->slug,
                'eventSlug' => $unitEvent->slug,
                'token' => $p->access_token
            ]);

            $rowData = [
                $index + 1,
                $p->kode_registrasi,
                $p->nama,
                $p->meta['nama_wisudawan'] ?? '—',
                $p->identity_number ?: ($p->linkedMahasiswa?->nim ?: '—'),
                $prodiName,
                $p->meta['nomor_kursi'] ?? '—',
                $p->no_hp ?: '—',
                $p->email ?: '—',
                $link,
            ];

            $sheet->fromArray($rowData, null, 'A' . $rowNumber);
            $rowNumber++;
        }

        // Auto width for columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Header style
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F766E'], // Teal
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'unit-event-export-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    public function exportPraktikRecap(\App\Models\UnitEvent $unitEvent, \Illuminate\Support\Collection $participants): string
    {
        $templatePath = '/www/wwwroot/polkesban.ac.id/Daftar Hadir KDKK.xlsx';
        $spreadsheet = IOFactory::load($templatePath);
        
        // Base sheet to clone
        $baseSheet = $spreadsheet->getSheet(0);

        // Separate students and pembimbing
        $studentsAll = $participants->whereNotIn('peran', ['speaker', 'committee'])->values();
        $pembimbingsAll = $participants->whereIn('peran', ['speaker', 'committee'])->values();

        // Group students by their tempat_praktik
        $groupedStudents = $studentsAll->groupBy(function ($item) {
            return $item->tempat_praktik ?: 'Lainnya';
        });

        // Group pembimbing by their tempat_praktik
        $groupedPembimbings = $pembimbingsAll->groupBy(function ($item) {
            return $item->tempat_praktik ?: 'Lainnya';
        });

        // Get unique locations
        $locations = $groupedStudents->keys();
        if ($locations->isEmpty()) {
            $locations = collect(['Rekap Kehadiran']);
        }

        // Chunk dates into weekly groups (7 days max per sheet)
        $allDates = $unitEvent->attendanceDates();
        $weeklyDateChunks = $allDates->chunk(7)->values();
        $totalWeeks = max($weeklyDateChunks->count(), 1);

        // Track active sheet titles
        $activeSheetTitles = [];
        $sheetCreated = false;

        // We will create sheets for each unique location × week combination
        foreach ($locations as $index => $loc) {
            // Get students and pembimbings for this location
            $students = $groupedStudents->get($loc, collect())->values();
            $pembimbings = $groupedPembimbings->get($loc, collect())->values();
            
            if ($pembimbings->isEmpty() && $loc !== 'Lainnya') {
                $pembimbings = $groupedPembimbings->get('Lainnya', collect())->values();
            }

            // Derive pembimbing names from students' field if no explicit pembimbing participants
            $derivedPembimbingNames = collect();
            if ($pembimbings->isEmpty()) {
                $derivedPembimbingNames = $students->pluck('nama_pembimbing')
                    ->filter(fn($n) => filled($n))
                    ->unique()
                    ->values();
            }

            foreach ($weeklyDateChunks as $weekIdx => $weekDates) {
                $weekDates = $weekDates->values();

                if (!$sheetCreated) {
                    $sheet = $baseSheet;
                    $sheetCreated = true;
                } else {
                    $sheet = clone $baseSheet;
                    $spreadsheet->addSheet($sheet);
                }

                // Rename sheet (Excel sheets limit name length to 31 characters)
                $cleanTitle = str_replace(['*', ':', '?', '/', '\\', '[', ']'], '', $loc);
                $weekSuffix = $totalWeeks > 1 ? ' MG' . ($weekIdx + 1) : '';
                $sheetTitle = substr(trim($cleanTitle), 0, 31 - strlen($weekSuffix)) . $weekSuffix;
                $sheet->setTitle($sheetTitle);
                $activeSheetTitles[] = $sheetTitle;

                // Update headers
                $sheet->setCellValue('C10', $unitEvent->judul);
                $waktu = $unitEvent->mulai_at ? $unitEvent->mulai_at->translatedFormat('d F Y') : '';
                if ($unitEvent->selesai_at) {
                    $waktu .= ' s.d ' . $unitEvent->selesai_at->translatedFormat('d F Y');
                }
                $sheet->setCellValue('C11', $waktu);
                $locLabel = $loc;
                if ($totalWeeks > 1) {
                    $locLabel .= ' (Minggu ' . ($weekIdx + 1) . '/' . $totalWeeks . ')';
                }
                $sheet->setCellValue('C12', $locLabel);
                $sheet->setCellValue('C13', $unitEvent->semester ?? '—');
                
                $prodiName = $unitEvent->page->programStudi->nama ?? '—';
                $sheet->setCellValue('V10', $prodiName);
                $sheet->setCellValue('V11', $unitEvent->tahun_akademik ?? '—');
                $sheet->setCellValue('V12', $unitEvent->tingkat ?? '—');

                // Update date headers on row 16 using this week's dates
                foreach ($weekDates as $d => $date) {
                    $colIdx = 3 + $d * 4; // cols: C, G, K, O, S, W, AA...
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                    $sheet->setCellValue($colLetter . '16', $date->translatedFormat('l, d F Y'));
                }
                
                // Hide unused day columns
                $weekDayCount = min($weekDates->count(), 7);
                $startUnusedCol = 3 + $weekDayCount * 4;
                for ($col = $startUnusedCol; $col <= 30; $col++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $sheet->getColumnDimension($colLetter)->setVisible(false);
                }

                // Template has 9 student rows (19 to 27). Let's adjust rows.
                $targetStudentCount = $students->count();
                if ($targetStudentCount > 9) {
                    $sheet->insertNewRowBefore(20, $targetStudentCount - 9);
                } elseif ($targetStudentCount < 9 && $targetStudentCount > 0) {
                    $sheet->removeRow(19 + $targetStudentCount, 9 - $targetStudentCount);
                } elseif ($targetStudentCount === 0) {
                    $sheet->removeRow(19, 8);
                }

                // Write student data starting at row 19
                foreach ($students as $idx => $student) {
                    $row = 19 + $idx;
                    $sheet->setCellValue('A' . $row, $idx + 1);
                    $sheet->setCellValue('B' . $row, $student->nama);
                    
                    // Fill attendances for each day in this week
                    foreach ($weekDates as $d => $date) {
                        $colIdx = 3 + $d * 4;
                        $dateStr = $date->toDateString();
                        
                        $att = $student->attendances->first(fn($a) => $a->attendance_date->toDateString() === $dateStr);
                        
                        $c1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx) . $row;
                        $c2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . $row;
                        $c3 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 2) . $row;
                        $c4 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 3) . $row;
                        
                        if ($att && $att->signed_at) {
                            $sheet->setCellValue($c1, $att->signed_at->format('H.i'));
                            $sheet->setCellValue($c2, 'H');
                        } else {
                            $sheet->setCellValue($c1, 'L');
                            $sheet->setCellValue($c2, 'L');
                        }
                        
                        if ($att && $att->check_out_at) {
                            $sheet->setCellValue($c3, $att->check_out_at->format('H.i'));
                            $sheet->setCellValue($c4, 'H');
                        } else {
                            $sheet->setCellValue($c3, 'L');
                            $sheet->setCellValue($c4, 'L');
                        }
                    }
                }

                // Find PEMBIMBING row dynamically
                $pembimbingRow = null;
                $highestRow = $sheet->getHighestRow();
                for ($r = 19; $r <= $highestRow; $r++) {
                    $val = $sheet->getCell('A' . $r)->getValue();
                    if ($val !== null && trim((string) $val) === 'PEMBIMBING') {
                        $pembimbingRow = $r;
                        break;
                    }
                }

                if ($pembimbingRow) {
                    $pRowStart = $pembimbingRow + 3;

                    if ($pembimbings->isNotEmpty()) {
                        // Use participant-based pembimbing
                        $targetPembimbingCount = $pembimbings->count();
                        if ($targetPembimbingCount > 2) {
                            $sheet->insertNewRowBefore($pRowStart + 1, $targetPembimbingCount - 2);
                        }
                        
                        foreach ($pembimbings as $idx => $pb) {
                            $row = $pRowStart + $idx;
                            $sheet->setCellValue('A' . $row, $idx + 1);
                            $sheet->setCellValue('B' . $row, $pb->nama);
                            
                            // Write pembimbing presence for this week's dates
                            foreach ($weekDates as $d => $date) {
                                $colIdx = 3 + $d * 4;
                                $dateStr = $date->toDateString();
                                
                                $att = $pb->attendances->first(fn($a) => $a->attendance_date->toDateString() === $dateStr);
                                
                                $c1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx) . $row;
                                $c2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . $row;
                                $c3 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 2) . $row;
                                $c4 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 3) . $row;
                                
                                if ($att && $att->signed_at) {
                                    $sheet->setCellValue($c1, '');
                                    $sheet->setCellValue($c2, 'H');
                                } else {
                                    $sheet->setCellValue($c1, '');
                                    $sheet->setCellValue($c2, '');
                                }
                                
                                if ($att && $att->check_out_at) {
                                    $sheet->setCellValue($c3, '');
                                    $sheet->setCellValue($c4, 'H');
                                } else {
                                    $sheet->setCellValue($c3, '');
                                    $sheet->setCellValue($c4, '');
                                }
                            }
                        }
                    } elseif ($derivedPembimbingNames->isNotEmpty()) {
                        // Use derived pembimbing names from student records (name only)
                        $targetPembimbingCount = $derivedPembimbingNames->count();
                        if ($targetPembimbingCount > 2) {
                            $sheet->insertNewRowBefore($pRowStart + 1, $targetPembimbingCount - 2);
                        }
                        
                        foreach ($derivedPembimbingNames as $idx => $pbName) {
                            $row = $pRowStart + $idx;
                            $sheet->setCellValue('A' . $row, $idx + 1);
                            $sheet->setCellValue('B' . $row, $pbName);
                        }
                    }
                }

                // Fill signature block (Mengetahui)
                $mengetahuiRow = null;
                $highestRow = $sheet->getHighestRow();
                for ($r = ($pembimbingRow ?? 30); $r <= $highestRow; $r++) {
                    for ($c = 1; $c <= 10; $c++) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                        $val = $sheet->getCell($colLetter . $r)->getValue();
                        if ($val !== null && str_contains(trim((string) $val), 'Mengetahui')) {
                            $mengetahuiRow = $r;
                            break 2;
                        }
                    }
                }

                if ($mengetahuiRow && ($unitEvent->penandatangan_nama || $unitEvent->penandatangan_jabatan)) {
                    for ($r = $mengetahuiRow; $r <= $highestRow; $r++) {
                        for ($c = 1; $c <= 30; $c++) {
                            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                            $val = $sheet->getCell($colLetter . $r)->getValue();
                            if ($val !== null) {
                                $strVal = trim((string) $val);
                                if (str_contains($strVal, 'Ketua Jurusan') || str_contains($strVal, 'Ketua Program Studi') || str_contains($strVal, 'Koordinator')) {
                                    if ($unitEvent->penandatangan_jabatan) {
                                        $sheet->setCellValue($colLetter . $r, $unitEvent->penandatangan_jabatan);
                                    }
                                    if ($unitEvent->penandatangan_nama) {
                                        $sheet->setCellValue($colLetter . ($r + 5), $unitEvent->penandatangan_nama);
                                    }
                                    if ($unitEvent->penandatangan_nip) {
                                        $sheet->setCellValue($colLetter . ($r + 6), 'NIP. ' . $unitEvent->penandatangan_nip);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // Clean up unneeded worksheets from template
        foreach ($spreadsheet->getSheetNames() as $name) {
            if (!in_array($name, $activeSheetTitles, true)) {
                $sheetToRemove = $spreadsheet->getSheetByName($name);
                if ($sheetToRemove) {
                    $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($sheetToRemove));
                }
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'unit-event-export-praktik-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        
        return $path;
    }

    public function exportGabunganRecap(\App\Models\UnitEvent $unitEvent, \Illuminate\Support\Collection $participants): string
    {
        $templatePath = '/www/wwwroot/polkesban.ac.id/DAFTAR HADIR.xlsx';
        $spreadsheet = IOFactory::load($templatePath);
        
        // Base sheet to clone
        $baseSheet = $spreadsheet->getSheet(0);

        // Separate students and pembimbing
        $studentsAll = $participants->where('peran', 'participant')->values();
        $pembimbingsAll = $participants->whereNotIn('peran', ['participant'])->values();

        // Group students by their tempat_praktik
        $groupedStudents = $studentsAll->groupBy(function ($item) {
            return $item->tempat_praktik ?: 'Lainnya';
        });

        // Group pembimbing by their tempat_praktik
        $groupedPembimbings = $pembimbingsAll->groupBy(function ($item) {
            return $item->tempat_praktik ?: 'Lainnya';
        });

        // Get unique locations
        $locations = $groupedStudents->keys()->values();
        if ($locations->isEmpty()) {
            $locations = collect(['Rekap Kehadiran']);
        }

        // Each sheet contains 3 locations. Chunk locations by 3.
        $locationChunks = $locations->chunk(3)->values();

        // Chunk dates into weekly groups (7 days max per sheet)
        $allDates = $unitEvent->attendanceDates();
        $weeklyDateChunks = $allDates->chunk(7)->values();
        $totalWeeks = max($weeklyDateChunks->count(), 1);

        // Active sheet titles to keep
        $activeSheetTitles = [];

        foreach ($locationChunks as $chunkIdx => $chunkLocs) {
            $chunkLocs = $chunkLocs->values();

            foreach ($weeklyDateChunks as $weekIdx => $weekDates) {
                $weekDates = $weekDates->values();
                $weekSuffix = $totalWeeks > 1 ? ' MG' . ($weekIdx + 1) : '';
                $sheetTitle = 'KEL ' . ($chunkIdx + 1) . $weekSuffix;
                // Excel sheet names max 31 chars
                $sheetTitle = substr($sheetTitle, 0, 31);
                $activeSheetTitles[] = $sheetTitle;

                if (count($activeSheetTitles) === 1) {
                    $sheet = $baseSheet;
                    $sheet->setTitle($sheetTitle);
                } else {
                    $sheet = clone $baseSheet;
                    $sheet->setTitle($sheetTitle);
                    $spreadsheet->addSheet($sheet);
                }

                // Set main headers
                $sheet->setCellValue('D10', $unitEvent->judul);
                
                $waktu = $unitEvent->mulai_at ? $unitEvent->mulai_at->translatedFormat('d F Y') : '';
                if ($unitEvent->selesai_at) {
                    $waktu .= ' s.d ' . $unitEvent->selesai_at->translatedFormat('d F Y');
                }
                $sheet->setCellValue('D11', $waktu);
                $locNames = $chunkLocs->filter()->values()->all();
                $locsString = implode(', ', $locNames);
                $sheet->setCellValue('D12', \Illuminate\Support\Str::upper($locsString));
                $sheet->setCellValue('D13', $unitEvent->semester ?? '—');
                
                $prodiName = $unitEvent->page->programStudi->nama ?? '—';
                $sheet->setCellValue('W10', $prodiName);
                $sheet->setCellValue('W11', $unitEvent->tahun_akademik ?? '—');

                // Populating each of the 3 sections in this sheet
                for ($i = 0; $i < 3; $i++) {
                    $locName = $chunkLocs->get($i);

                    $locRow = 14 + $i * 22;
                    $dateRow = 16 + $i * 22;
                    $studentStartRow = 19 + $i * 22;
                    $pembimbingStartRow = 32 + $i * 22;

                    if ($locName !== null) {
                        // Populate location name
                        $locLabel = 'TEMPAT PRAKTIK: ' . \Illuminate\Support\Str::upper($locName);
                        if ($totalWeeks > 1) {
                            $locLabel .= ' (Minggu ' . ($weekIdx + 1) . '/' . $totalWeeks . ')';
                        }
                        $sheet->setCellValue('B' . $locRow, $locLabel);
                        $sheet->getStyle('B' . $locRow)->getFont()->setBold(true);

                        // Update date headers using this week's dates
                        foreach ($weekDates as $d => $date) {
                            if ($d >= 7) break;
                            $colIdx = 3 + $d * 4; // C, G, K, O, S, W, AA
                            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                            $sheet->setCellValue($colLetter . $dateRow, $date->translatedFormat('l, d F Y'));
                        }

                        // Get students and pembimbings for this location
                        $students = $groupedStudents->get($locName, collect())->values();
                        $pembimbings = $groupedPembimbings->get($locName, collect())->values();

                        if ($pembimbings->isEmpty() && $locName !== 'Lainnya') {
                            $pembimbings = $groupedPembimbings->get('Lainnya', collect())->values();
                        }

                        // Derive pembimbing names from students' field if no explicit pembimbing participants
                        $derivedPembimbingNames = collect();
                        if ($pembimbings->isEmpty()) {
                            $derivedPembimbingNames = $students->pluck('nama_pembimbing')
                                ->filter(fn($n) => filled($n))
                                ->unique()
                                ->values();
                        }

                        // Populate students (maximum 10 rows in the gabungan template layout)
                        for ($sIdx = 0; $sIdx < 10; $sIdx++) {
                            $row = $studentStartRow + $sIdx;
                            $student = $students->get($sIdx);

                            if ($student !== null) {
                                $sheet->setCellValue('A' . $row, $sIdx + 1);
                                $sheet->setCellValue('B' . $row, $student->nama);

                                // Fill attendance using this week's dates
                                foreach ($weekDates as $d => $date) {
                                    if ($d >= 7) break;
                                    $colIdx = 3 + $d * 4;
                                    $dateStr = $date->toDateString();

                                    $att = $student->attendances->first(fn($a) => $a->attendance_date->toDateString() === $dateStr);

                                    $c1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx) . $row;
                                    $c2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . $row;
                                    $c3 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 2) . $row;
                                    $c4 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 3) . $row;

                                    if ($att && $att->signed_at) {
                                        $sheet->setCellValue($c1, $att->signed_at->format('H.i'));
                                        $sheet->setCellValue($c2, 'H');
                                    } else {
                                        $sheet->setCellValue($c1, 'L');
                                        $sheet->setCellValue($c2, 'L');
                                    }

                                    if ($att && $att->check_out_at) {
                                        $sheet->setCellValue($c3, $att->check_out_at->format('H.i'));
                                        $sheet->setCellValue($c4, 'H');
                                    } else {
                                        $sheet->setCellValue($c3, 'L');
                                        $sheet->setCellValue($c4, 'L');
                                    }
                                }
                            } else {
                                // Clear unused row numbers and names to keep the sheet clean
                                $sheet->setCellValue('A' . $row, null);
                                $sheet->setCellValue('B' . $row, null);
                            }
                        }

                        // Populate pembimbing (maximum 2 rows in the gabungan template layout)
                        for ($pIdx = 0; $pIdx < 2; $pIdx++) {
                            $row = $pembimbingStartRow + $pIdx;

                            if ($pembimbings->isNotEmpty()) {
                                // Use participant-based pembimbing
                                $pb = $pembimbings->get($pIdx);
                                if ($pb !== null) {
                                    $sheet->setCellValue('A' . $row, $pIdx + 1);
                                    $sheet->setCellValue('B' . $row, $pb->nama);

                                    foreach ($weekDates as $d => $date) {
                                        if ($d >= 7) break;
                                        $colIdx = 3 + $d * 4;
                                        $dateStr = $date->toDateString();

                                        $att = $pb->attendances->first(fn($a) => $a->attendance_date->toDateString() === $dateStr);

                                        $c1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx) . $row;
                                        $c2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . $row;
                                        $c3 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 2) . $row;
                                        $c4 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 3) . $row;

                                        if ($att && $att->signed_at) {
                                            $sheet->setCellValue($c1, '');
                                            $sheet->setCellValue($c2, 'H');
                                        } else {
                                            $sheet->setCellValue($c1, '');
                                            $sheet->setCellValue($c2, '');
                                        }

                                        if ($att && $att->check_out_at) {
                                            $sheet->setCellValue($c3, '');
                                            $sheet->setCellValue($c4, 'H');
                                        } else {
                                            $sheet->setCellValue($c3, '');
                                            $sheet->setCellValue($c4, '');
                                        }
                                    }
                                } else {
                                    $sheet->setCellValue('A' . $row, null);
                                    $sheet->setCellValue('B' . $row, null);
                                }
                            } elseif ($derivedPembimbingNames->isNotEmpty()) {
                                // Use derived pembimbing names from student records
                                $pbName = $derivedPembimbingNames->get($pIdx);
                                if ($pbName !== null) {
                                    $sheet->setCellValue('A' . $row, $pIdx + 1);
                                    $sheet->setCellValue('B' . $row, $pbName);
                                } else {
                                    $sheet->setCellValue('A' . $row, null);
                                    $sheet->setCellValue('B' . $row, null);
                                }
                            } else {
                                $sheet->setCellValue('A' . $row, null);
                                $sheet->setCellValue('B' . $row, null);
                            }
                        }
                    } else {
                        // Clear the entire unused section
                        $startClearRow = 14 + $i * 22;
                        $endClearRow = 35 + $i * 22;
                        for ($r = $startClearRow; $r <= $endClearRow; $r++) {
                            for ($c = 1; $c <= 30; $c++) {
                                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                                $sheet->setCellValue($colLetter . $r, null);
                            }
                        }
                    }
                }

                // Hide unused columns (greater than this week's days)
                $weekDayCount = min($weekDates->count(), 7);
                $startUnusedCol = 3 + $weekDayCount * 4;
                for ($col = $startUnusedCol; $col <= 30; $col++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $sheet->getColumnDimension($colLetter)->setVisible(false);
                }

                // Populate signatures
                $kaprodiName = $unitEvent->penandatangan_nama ?: 'Sri Mulyati, SST., M. Keb';
                $kaprodiNip = $unitEvent->penandatangan_nip ?: '198105092005012000';
                $koordinatorName = $unitEvent->koordinator_nama ?: 'Yulidar Yanti, SST., M. Keb';
                $koordinatorNip = $unitEvent->koordinator_nip ?: '197707112005012000';

                $sheet->setCellValue('L86', $kaprodiName);
                $sheet->setCellValue('L87', 'NIP. ' . $kaprodiNip);
                
                $sheet->setCellValue('S86', $koordinatorName);
                $sheet->setCellValue('S87', 'NIP. ' . $koordinatorNip);
            }
        }

        // Clean up unneeded worksheets from template
        foreach ($spreadsheet->getSheetNames() as $name) {
            if (!in_array($name, $activeSheetTitles, true)) {
                $sheetToRemove = $spreadsheet->getSheetByName($name);
                if ($sheetToRemove) {
                    $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($sheetToRemove));
                }
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'unit-event-export-gabungan-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        
        return $path;
    }
}