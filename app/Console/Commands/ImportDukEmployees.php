<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class ImportDukEmployees extends Command
{
    protected $signature = 'users:import-duk
                            {file=DUK November 2025.xlsx : Path file DUK Excel}
                            {--dry-run : Parse file tanpa menyimpan ke database}
                            {--reset-passwords : Regenerasi password sementara untuk akun yang sudah ada berdasarkan NIP}
                            {--export= : Path file XLSX untuk ekspor kredensial baru}';

    protected $description = 'Import akun pegawai Poltekkes Bandung dari file DUK Excel';

    private const PLACEHOLDER_EMAIL_DOMAIN = 'pegawai.polkesban.ac.id';

    public function handle(): int
    {
        $filePath = $this->resolvePath((string) $this->argument('file'));

        if (! is_file($filePath)) {
            $this->error("File DUK tidak ditemukan: {$filePath}");

            return self::FAILURE;
        }

        try {
            $employees = $this->loadEmployees($filePath);
        } catch (Throwable $e) {
            $this->error('Gagal membaca file DUK: ' . $e->getMessage());

            return self::FAILURE;
        }

        if ($employees->isEmpty()) {
            $this->warn('Tidak ada baris pegawai yang dapat diproses dari file DUK.');

            return self::SUCCESS;
        }

        $workbookEmailCounts = $this->buildWorkbookEmailCounts($employees);
        $claimedEmails = User::query()
            ->select('id', 'email')
            ->get()
            ->mapWithKeys(fn (User $user) => [Str::lower($user->email) => $user->id])
            ->all();

        $results = [
            'parsed' => $employees->count(),
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'admin_preserved' => 0,
            'email_sources' => [
                'kemenkes' => 0,
                'private' => 0,
                'existing' => 0,
                'placeholder' => 0,
            ],
            'credential_rows' => [],
        ];

        $runner = function () use ($employees, $workbookEmailCounts, &$claimedEmails, &$results): void {
            $bar = $this->output->createProgressBar($employees->count());
            $bar->start();

            foreach ($employees as $employee) {
                $existingUser = User::query()
                    ->where('identity_number', $employee['identity_number'])
                    ->first();

                $isAdminAccount = (bool) ($existingUser?->is_admin);

                $emailDecision = $isAdminAccount
                    ? [
                        'email' => Str::lower((string) $existingUser->email),
                        'source' => 'existing',
                    ]
                    : $this->resolveEmployeeEmail($employee, $existingUser, $workbookEmailCounts, $claimedEmails);

                $attributes = [
                    'name' => $employee['name'],
                    'identity_number' => $employee['identity_number'],
                ];

                if (! $isAdminAccount) {
                    $attributes += [
                        'email' => $emailDecision['email'],
                        'is_admin' => false,
                        'role' => User::ROLE_EMPLOYEE,
                        'program_studi_id' => null,
                        'managed_unit_page_id' => null,
                        'custom_menus' => null,
                    ];
                }

                $plainPassword = null;

                if (! $existingUser) {
                    $plainPassword = $this->generateTemporaryPassword();
                    $attributes['password'] = $plainPassword;
                } elseif ($this->option('reset-passwords') && ! $isAdminAccount) {
                    $plainPassword = $this->generateTemporaryPassword();
                    $attributes['password'] = $plainPassword;
                }

                $state = $this->determineState($existingUser, $attributes, $isAdminAccount);

                if ($this->option('dry-run')) {
                    $results[$state]++;
                    if ($isAdminAccount && $existingUser) {
                        $results['admin_preserved']++;
                    }
                } else {
                    if (! $existingUser) {
                        $existingUser = User::create($attributes);
                        $results['created']++;
                    } else {
                        $oldEmail = Str::lower((string) $existingUser->email);

                        $existingUser->fill($attributes);

                        if ($existingUser->isDirty()) {
                            $existingUser->save();
                            $results['updated']++;
                        } else {
                            $results['unchanged']++;
                        }

                        if (! $isAdminAccount && $oldEmail !== Str::lower((string) $existingUser->email) && (($claimedEmails[$oldEmail] ?? null) === $existingUser->id)) {
                            unset($claimedEmails[$oldEmail]);
                        }

                        if ($isAdminAccount) {
                            $results['admin_preserved']++;
                        }
                    }

                    if (! $isAdminAccount) {
                        $claimedEmails[Str::lower((string) $existingUser->email)] = $existingUser->id;
                    }
                }

                $results['email_sources'][$emailDecision['source']]++;

                if ($plainPassword !== null) {
                    $results['credential_rows'][] = [
                        'name' => $employee['name'],
                        'identity_number' => $employee['identity_number'],
                        'login_identifier' => $employee['identity_number'],
                        'email' => $isAdminAccount ? (string) $existingUser?->email : $emailDecision['email'],
                        'password' => $plainPassword,
                        'email_source' => $emailDecision['source'],
                        'position' => $employee['position'],
                        'phone' => $employee['phone'],
                    ];
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
        };

        if ($this->option('dry-run')) {
            $runner();
        } else {
            DB::transaction($runner);
        }

        $this->renderSummary($results, $filePath);

        if (! $this->option('dry-run') && $results['credential_rows'] !== []) {
            try {
                $exportPath = $this->resolveExportPath();
                $this->exportCredentialWorkbook($results['credential_rows'], $exportPath, basename($filePath));
                $this->info('File kredensial tersimpan di: ' . $exportPath);
            } catch (Throwable $e) {
                $this->error('Akun berhasil diproses, tetapi file kredensial gagal diekspor: ' . $e->getMessage());
                $this->warn('Jalankan ulang command dengan opsi --reset-passwords untuk membuat ulang password sementara dan menghasilkan file kredensial baru.');

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    private function loadEmployees(string $filePath): Collection
    {
        $sheet = IOFactory::load($filePath)->getSheet(0);
        $highestRow = $sheet->getHighestDataRow();
        $employees = [];

        for ($row = 9; $row <= $highestRow; $row++) {
            $nameBlock = trim((string) $sheet->getCell('B' . $row)->getFormattedValue());
            $identityNumber = $this->normalizeIdentityNumber((string) $sheet->getCell('D' . $row)->getFormattedValue());

            if ($nameBlock === '' || $identityNumber === null) {
                continue;
            }

            $employees[] = [
                'name' => $this->extractName($nameBlock),
                'identity_number' => $identityNumber,
                'position' => trim((string) $sheet->getCell('G' . $row)->getFormattedValue()),
                'phone' => trim((string) $sheet->getCell('X' . $row)->getFormattedValue()),
                'email_private' => $this->normalizeEmail((string) $sheet->getCell('Y' . $row)->getFormattedValue()),
                'email_kemenkes' => $this->normalizeEmail((string) $sheet->getCell('Z' . $row)->getFormattedValue()),
            ];
        }

        return collect($employees);
    }

    private function buildWorkbookEmailCounts(Collection $employees): array
    {
        $counts = [];

        foreach ($employees as $employee) {
            foreach (['email_kemenkes', 'email_private'] as $key) {
                $email = $employee[$key] ?? null;

                if (! $email) {
                    continue;
                }

                $counts[$email] = ($counts[$email] ?? 0) + 1;
            }
        }

        return $counts;
    }

    private function resolveEmployeeEmail(array $employee, ?User $existingUser, array $workbookEmailCounts, array $claimedEmails): array
    {
        foreach ([
            'kemenkes' => $employee['email_kemenkes'],
            'private' => $employee['email_private'],
        ] as $source => $candidate) {
            if (! $candidate) {
                continue;
            }

            if (($workbookEmailCounts[$candidate] ?? 0) > 1) {
                continue;
            }

            $ownerId = $claimedEmails[$candidate] ?? null;

            if ($ownerId !== null && (! $existingUser || $ownerId !== $existingUser->id)) {
                continue;
            }

            return [
                'email' => $candidate,
                'source' => $source,
            ];
        }

        if ($existingUser && filled($existingUser->email)) {
            return [
                'email' => Str::lower((string) $existingUser->email),
                'source' => 'existing',
            ];
        }

        return [
            'email' => $this->makePlaceholderEmail($employee['identity_number']),
            'source' => 'placeholder',
        ];
    }

    private function determineState(?User $existingUser, array $attributes, bool $isAdminAccount): string
    {
        if (! $existingUser) {
            return 'created';
        }

        $fields = ['name', 'identity_number'];

        if (! $isAdminAccount) {
            $fields = array_merge($fields, ['email', 'is_admin', 'role', 'program_studi_id', 'managed_unit_page_id']);
        }

        foreach ($fields as $field) {
            if (($existingUser->{$field} ?? null) !== ($attributes[$field] ?? null)) {
                return 'updated';
            }
        }

        if (array_key_exists('password', $attributes)) {
            return 'updated';
        }

        return 'unchanged';
    }

    private function exportCredentialWorkbook(array $rows, string $filePath, string $sourceFilename): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kredensial Pegawai');

        $sheet->setCellValue('A1', 'Kredensial Akun Pegawai Poltekkes Bandung');
        $sheet->setCellValue('A2', 'Sumber DUK: ' . $sourceFilename);
        $sheet->setCellValue('A3', 'Login utama menggunakan NIP Baru pada kolom "Username / NIP".');

        $headers = ['No', 'Nama', 'NIP Baru', 'Username / NIP', 'Email Akun', 'Password Sementara', 'Sumber Email', 'Jabatan DUK', 'Telepon'];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue($this->cell($index + 1, 5), $header);
        }

        $rowNumber = 6;

        foreach ($rows as $index => $row) {
            $sheet->setCellValue($this->cell(1, $rowNumber), $index + 1);
            $sheet->setCellValue($this->cell(2, $rowNumber), $row['name']);
            $sheet->setCellValueExplicit($this->cell(3, $rowNumber), $row['identity_number'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->cell(4, $rowNumber), $row['login_identifier'], DataType::TYPE_STRING);
            $sheet->setCellValue($this->cell(5, $rowNumber), $row['email']);
            $sheet->setCellValue($this->cell(6, $rowNumber), $row['password']);
            $sheet->setCellValue($this->cell(7, $rowNumber), $row['email_source']);
            $sheet->setCellValue($this->cell(8, $rowNumber), $row['position']);
            $sheet->setCellValue($this->cell(9, $rowNumber), $row['phone']);
            $rowNumber++;
        }

        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $directory = dirname($filePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        (new Xlsx($spreadsheet))->save($filePath);
    }

    private function renderSummary(array $results, string $filePath): void
    {
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['File sumber', $filePath],
                ['Baris pegawai diproses', $results['parsed']],
                ['Akun baru', $results['created']],
                ['Akun diperbarui', $results['updated']],
                ['Akun tidak berubah', $results['unchanged']],
                ['Akun admin dipertahankan', $results['admin_preserved']],
                ['Email Kemenkes terpakai', $results['email_sources']['kemenkes']],
                ['Email pribadi terpakai', $results['email_sources']['private']],
                ['Email lama dipertahankan', $results['email_sources']['existing']],
                ['Email placeholder', $results['email_sources']['placeholder']],
                ['Baris kredensial baru', count($results['credential_rows'])],
            ]
        );
    }

    private function extractName(string $nameBlock): string
    {
        $lines = preg_split('/\R+/', $nameBlock) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line !== '') {
                return $line;
            }
        }

        return trim($nameBlock);
    }

    private function normalizeEmail(?string $value): ?string
    {
        $normalized = Str::lower(trim((string) $value));

        return filter_var($normalized, FILTER_VALIDATE_EMAIL) ? $normalized : null;
    }

    private function normalizeIdentityNumber(?string $value): ?string
    {
        $normalized = Str::upper((string) preg_replace('/[^A-Za-z0-9]+/', '', trim((string) $value)));

        return $normalized !== '' ? $normalized : null;
    }

    private function makePlaceholderEmail(string $identityNumber): string
    {
        return Str::lower($identityNumber . '@' . self::PLACEHOLDER_EMAIL_DOMAIN);
    }

    private function generateTemporaryPassword(): string
    {
        return 'Poltekkes Bandung!' . random_int(100000, 999999);
    }

    private function resolveExportPath(): string
    {
        $custom = trim((string) $this->option('export'));

        if ($custom !== '') {
            return $this->resolvePath($custom);
        }

        return storage_path('app/private/imports/duk-pegawai-credentials-' . now()->format('Ymd-His') . '.xlsx');
    }

    private function resolvePath(string $path): string
    {
        if ($path === '') {
            return base_path();
        }

        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    private function cell(int $column, int $row): string
    {
        return Coordinate::stringFromColumnIndex($column) . $row;
    }
}