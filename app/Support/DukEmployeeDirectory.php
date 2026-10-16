<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class DukEmployeeDirectory
{
    public const DEFAULT_DUK_FILE = 'DUK November 2025.xlsx';
    public const DEFAULT_INSTITUTION = 'Poltekkes Kemenkes Bandung';

    private ?Collection $profiles = null;

    public function all(): Collection
    {
        if ($this->profiles instanceof Collection) {
            return $this->profiles;
        }

        $filePath = base_path(self::DEFAULT_DUK_FILE);

        if (! is_file($filePath)) {
            return $this->profiles = collect();
        }

        try {
            $sheet = IOFactory::load($filePath)->getSheet(0);
        } catch (Throwable) {
            return $this->profiles = collect();
        }

        $profiles = [];

        for ($row = 9; $row <= $sheet->getHighestDataRow(); $row++) {
            $nameBlock = trim((string) $sheet->getCell('B' . $row)->getFormattedValue());
            $identityNumber = $this->normalizeIdentityNumber((string) $sheet->getCell('D' . $row)->getFormattedValue());

            if ($nameBlock === '' || $identityNumber === null) {
                continue;
            }

            $profiles[$identityNumber] = [
                'name' => $this->extractName($nameBlock),
                'identity_number' => $identityNumber,
                'position' => $this->normalizeText((string) $sheet->getCell('G' . $row)->getFormattedValue()),
                'institution' => self::DEFAULT_INSTITUTION,
            ];
        }

        return $this->profiles = collect($profiles);
    }

    public function find(?string $identityNumber): ?array
    {
        $normalizedIdentityNumber = $this->normalizeIdentityNumber((string) $identityNumber);

        if (! $normalizedIdentityNumber) {
            return null;
        }

        return $this->all()->get($normalizedIdentityNumber);
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

    private function normalizeIdentityNumber(string $value): ?string
    {
        $normalized = Str::upper((string) preg_replace('/[^A-Za-z0-9]+/', '', trim($value)));

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeText(string $value): ?string
    {
        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }
}
