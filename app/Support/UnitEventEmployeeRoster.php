<?php

namespace App\Support;

use App\Models\UnitEvent;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

class UnitEventEmployeeRoster
{
    private const NAME_NOISE_TOKENS = [
        'APT',
        'DRA',
        'DR',
        'DRG',
        'DRS',
        'H',
        'HJ',
        'HJH',
        'IR',
        'M',
        'MARS',
        'MBA',
        'MKES',
        'MKM',
        'MM',
        'MPD',
        'MPH',
        'MSI',
        'NERS',
        'NS',
        'PROF',
        'S',
        'SE',
        'SKED',
        'SKEP',
        'SKG',
        'SKM',
        'SP',
        'SST',
        'ST',
        'KES',
        'KM',
    ];

    public function searchEmployees(?string $query, int $limit = 60): Collection
    {
        $query = trim((string) $query);

        if ($query === '') {
            return collect();
        }

        $normalizedIdentity = $this->normalizeIdentityNumber($query);
        $normalizedEmail = Str::lower($query);

        return User::query()
            ->where('role', User::ROLE_EMPLOYEE)
            ->where(function ($builder) use ($query, $normalizedIdentity, $normalizedEmail): void {
                $builder->where('name', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $normalizedEmail . '%');

                if ($normalizedIdentity) {
                    $builder->orWhere('identity_number', 'like', '%' . $normalizedIdentity . '%');
                }
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'email', 'identity_number', 'role']);
    }

    public function previewEventAssignmentLetter(UnitEvent $unitEvent): array
    {
        if (! $unitEvent->surat_tugas_event_path) {
            return [
                'status' => 'missing_file',
                'file_name' => null,
                'message' => 'Belum ada surat tugas event yang diupload. Upload dulu di form event, lalu klik Baca Surat Tugas.',
                'matches' => collect(),
                'matched_identity_count' => 0,
                'matched_name_count' => 0,
                'unmatched_identity_numbers' => [],
            ];
        }

        $absolutePath = MediaStorage::temporaryLocalPath($unitEvent->surat_tugas_event_path);
        $fileName = MediaStorage::fileName($unitEvent->surat_tugas_event_path) ?: basename((string) $unitEvent->surat_tugas_event_path);

        if (! $absolutePath || ! is_file($absolutePath)) {
            return [
                'status' => 'missing_file',
                'file_name' => $fileName,
                'message' => 'File surat tugas event tidak ditemukan di storage publik.',
                'matches' => collect(),
                'matched_identity_count' => 0,
                'matched_name_count' => 0,
                'unmatched_identity_numbers' => [],
            ];
        }

        $extension = Str::lower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        if ($extension !== 'pdf') {
            return [
                'status' => 'unsupported_file',
                'file_name' => $fileName,
                'message' => 'Surat tugas otomatis baru mendukung PDF digital berbasis teks. File gambar atau scan masih perlu OCR.',
                'matches' => collect(),
                'matched_identity_count' => 0,
                'matched_name_count' => 0,
                'unmatched_identity_numbers' => [],
            ];
        }

        $text = $this->extractPdfText($absolutePath);

        if ($text === '') {
            return [
                'status' => 'empty_text',
                'file_name' => $fileName,
                'message' => 'Teks PDF tidak terbaca. Biasanya ini terjadi jika PDF berasal dari scan gambar dan OCR belum aktif di server.',
                'matches' => collect(),
                'matched_identity_count' => 0,
                'matched_name_count' => 0,
                'unmatched_identity_numbers' => [],
            ];
        }

        $employees = User::query()
            ->where('role', User::ROLE_EMPLOYEE)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'identity_number', 'role']);

        $matches = [];
        $matchedUserIds = [];
        $matchedIdentityCount = 0;
        $matchedNameCount = 0;
        $unmatchedIdentityNumbers = [];

        $identityNumbers = $this->extractIdentityNumbers($text);
        $usersByIdentity = $employees
            ->filter(fn (User $user): bool => filled($user->identity_number))
            ->keyBy(fn (User $user): string => (string) $this->normalizeIdentityNumber($user->identity_number));

        foreach ($identityNumbers as $identityNumber) {
            $employee = $usersByIdentity->get($identityNumber);

            if (! $employee) {
                $unmatchedIdentityNumbers[] = $identityNumber;
                continue;
            }

            $matches[$employee->id] = [
                'user' => $employee,
                'match_type' => 'identity_number',
                'match_label' => 'NIP cocok dari surat tugas',
                'confidence_label' => 'Tinggi',
                'match_value' => $identityNumber,
                'sort_weight' => 0,
            ];
            $matchedUserIds[$employee->id] = true;
            $matchedIdentityCount++;
        }

        $normalizedText = ' ' . $this->normalizeText($text) . ' ';

        foreach ($employees as $employee) {
            if (isset($matchedUserIds[$employee->id])) {
                continue;
            }

            foreach ($this->normalizedNameVariants((string) $employee->name) as $variant) {
                if ($variant === '' || str_word_count($variant) < 2 || mb_strlen($variant) < 8) {
                    continue;
                }

                if (! str_contains($normalizedText, ' ' . $variant . ' ')) {
                    continue;
                }

                $matches[$employee->id] = [
                    'user' => $employee,
                    'match_type' => 'name',
                    'match_label' => 'Nama terdeteksi di surat tugas',
                    'confidence_label' => 'Menengah',
                    'match_value' => Str::title(Str::lower($variant)),
                    'sort_weight' => 1,
                ];
                $matchedUserIds[$employee->id] = true;
                $matchedNameCount++;
                break;
            }
        }

        $sortedMatches = collect($matches)
            ->sortBy([
                ['sort_weight', 'asc'],
                [fn (array $match): string => Str::lower((string) $match['user']->name), 'asc'],
            ])
            ->values();

        if ($sortedMatches->isEmpty()) {
            return [
                'status' => 'no_match',
                'file_name' => $fileName,
                'message' => 'Surat tugas berhasil dibaca, tetapi belum ditemukan kecocokan pegawai berdasarkan NIP atau nama. Gunakan pencarian manual di bawah.',
                'matches' => collect(),
                'matched_identity_count' => 0,
                'matched_name_count' => 0,
                'unmatched_identity_numbers' => array_values(array_unique($unmatchedIdentityNumbers)),
            ];
        }

        return [
            'status' => 'matched',
            'file_name' => $fileName,
            'message' => 'Surat tugas berhasil dibaca. Cocokkan hasilnya sebelum ditambahkan ke event.',
            'matches' => $sortedMatches,
            'matched_identity_count' => $matchedIdentityCount,
            'matched_name_count' => $matchedNameCount,
            'unmatched_identity_numbers' => array_values(array_unique($unmatchedIdentityNumbers)),
        ];
    }

    private function extractPdfText(string $path): string
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($path);
        $text = trim((string) $pdf->getText());

        return preg_replace('/\s{3,}/', "\n\n", $text) ?? '';
    }

    private function extractIdentityNumbers(string $text): array
    {
        preg_match_all('/(?:\d[\s\.]*){18}/', $text, $matches);

        $identityNumbers = [];

        foreach ($matches[0] ?? [] as $rawMatch) {
            $normalized = preg_replace('/\D+/', '', (string) $rawMatch);

            if (strlen((string) $normalized) !== 18) {
                continue;
            }

            $identityNumbers[] = $normalized;
        }

        return array_values(array_unique($identityNumbers));
    }

    private function normalizeIdentityNumber(?string $value): ?string
    {
        $normalized = preg_replace('/[^A-Za-z0-9]+/', '', trim((string) $value));
        $normalized = Str::upper((string) $normalized);

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeText(string $value): string
    {
        $normalized = Str::of($value)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', ' ')
            ->squish()
            ->toString();

        return $normalized;
    }

    private function normalizedNameVariants(string $name): array
    {
        $fullName = $this->normalizeText($name);

        if ($fullName === '') {
            return [];
        }

        $tokens = explode(' ', $fullName);
        $coreTokens = $tokens;

        while ($coreTokens !== [] && $this->isNameNoiseToken($coreTokens[0])) {
            array_shift($coreTokens);
        }

        while ($coreTokens !== [] && $this->isNameNoiseToken($coreTokens[array_key_last($coreTokens)])) {
            array_pop($coreTokens);
        }

        $variants = [$fullName];

        if ($coreTokens !== [] && $coreTokens !== $tokens) {
            $variants[] = implode(' ', $coreTokens);
        }

        return array_values(array_unique(array_filter($variants)));
    }

    private function isNameNoiseToken(string $token): bool
    {
        $normalizedToken = Str::upper(trim($token));

        if ($normalizedToken === '') {
            return true;
        }

        if (in_array($normalizedToken, self::NAME_NOISE_TOKENS, true)) {
            return true;
        }

        return mb_strlen($normalizedToken) <= 1;
    }
}