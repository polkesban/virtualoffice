<?php

namespace App\Support;

use App\Models\AccreditationEvidence;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

class AccreditationEvidenceTextPreview
{
    private const MAX_CHARACTERS = 18000;

    public function __construct(private readonly AccreditationEvidencePdfOcr $ocr) {}

    public function build(AccreditationEvidence $evidence): array
    {
        if (! $evidence->hasUploadedFile()) {
            return [
                'status' => 'missing_file',
                'title' => 'Teks belum tersedia',
                'message' => 'Eviden ini belum memiliki file privat yang bisa dibaca teksnya.',
                'content' => null,
                'is_truncated' => false,
                'character_count' => 0,
            ];
        }

        if (! MediaStorage::exists($evidence->file_path)) {
            return [
                'status' => 'missing_file',
                'title' => 'File tidak ditemukan',
                'message' => 'File privat eviden tidak ditemukan di storage aplikasi.',
                'content' => null,
                'is_truncated' => false,
                'character_count' => 0,
            ];
        }

        $absolutePath = MediaStorage::temporaryLocalPath($evidence->file_path);
        if (! $absolutePath) {
            return [
                'status' => 'missing_file',
                'title' => 'File tidak ditemukan',
                'message' => 'File privat eviden tidak dapat disalin sementara untuk ekstraksi teks.',
                'content' => null,
                'is_truncated' => false,
                'character_count' => 0,
            ];
        }

        $extension = Str::lower(pathinfo($evidence->file_name ?: $absolutePath, PATHINFO_EXTENSION));
        $cacheKey = 'accreditation-evidence-text-preview:' . md5(implode('|', [
            $evidence->id,
            (string) $evidence->file_path,
            (string) $evidence->file_name,
            (string) $evidence->size_bytes,
            (string) $evidence->updated_at,
        ]));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($absolutePath, $extension) {
            return $this->extract($absolutePath, $extension);
        });
    }

    private function extract(string $absolutePath, string $extension): array
    {
        if ($extension === '') {
            return [
                'status' => 'unsupported',
                'title' => 'Format file belum dikenali',
                'message' => 'Ekstraksi teks otomatis belum bisa dijalankan karena ekstensi file tidak dikenali.',
                'content' => null,
                'is_truncated' => false,
                'character_count' => 0,
                'method' => null,
                'method_label' => null,
            ];
        }

        try {
            return match ($extension) {
                'pdf' => $this->extractPdfPayload($absolutePath),
                'txt', 'md', 'csv', 'json', 'xml', 'log', 'html', 'htm' => $this->buildReadyPayload(
                    $this->extractTextFile($absolutePath),
                    'native',
                    'File Teks',
                    'Teks diambil langsung dari file teks privat yang diunggah.'
                ),
                default => [
                    'status' => 'unsupported',
                    'title' => 'Format file belum didukung',
                    'message' => 'Preview teks otomatis saat ini mendukung PDF digital serta file teks seperti TXT, MD, CSV, JSON, XML, dan LOG.',
                    'content' => null,
                    'is_truncated' => false,
                    'character_count' => 0,
                    'method' => null,
                    'method_label' => null,
                ],
            };
        } catch (\Throwable $exception) {
            return [
                'status' => 'error',
                'title' => 'Ekstraksi teks gagal',
                'message' => 'Teks file tidak bisa dibaca saat ini: ' . $exception->getMessage(),
                'content' => null,
                'is_truncated' => false,
                'character_count' => 0,
                'method' => null,
                'method_label' => null,
            ];
        }
    }

    private function extractPdfPayload(string $absolutePath): array
    {
        $text = $this->extractPdfText($absolutePath);

        if ($text !== '') {
            return $this->buildReadyPayload(
                $text,
                'pdf_text',
                'PDF Digital',
                'Teks diambil dari lapisan teks PDF digital.'
            );
        }

        if (! $this->ocr->isAvailable()) {
            return [
                'status' => 'empty_text',
                'title' => 'Teks belum terbaca',
                'message' => 'Teks file tidak terbaca. Dokumen kemungkinan berasal dari scan gambar, sedangkan OCR opsional belum tersedia di server ini.',
                'content' => null,
                'is_truncated' => false,
                'character_count' => 0,
                'method' => null,
                'method_label' => null,
            ];
        }

        $ocrText = trim((string) ($this->ocr->extractFromPdf($absolutePath) ?? ''));

        if ($ocrText === '') {
            return [
                'status' => 'empty_text',
                'title' => 'Teks belum terbaca',
                'message' => 'OCR opsional sudah dicoba, tetapi teks PDF scan masih belum bisa dibaca dengan baik.',
                'content' => null,
                'is_truncated' => false,
                'character_count' => 0,
                'method' => 'ocr',
                'method_label' => 'OCR',
            ];
        }

        return $this->buildReadyPayload(
            $ocrText,
            'ocr',
            'OCR',
            'Teks diambil dari PDF scan menggunakan OCR opsional server.'
        );
    }

    private function buildReadyPayload(string $text, string $method, string $methodLabel, ?string $message): array
    {
        if ($text === '') {
            return [
                'status' => 'empty_text',
                'title' => 'Teks belum terbaca',
                'message' => 'Teks file tidak terbaca.',
                'content' => null,
                'is_truncated' => false,
                'character_count' => 0,
                'method' => $method,
                'method_label' => $methodLabel,
            ];
        }

        $normalized = preg_replace("/\r\n?/", "\n", $text) ?? $text;
        $normalized = trim((string) $normalized);
        $characterCount = mb_strlen($normalized);
        $content = mb_substr($normalized, 0, self::MAX_CHARACTERS);

        return [
            'status' => 'ready',
            'title' => 'Teks terbaca',
            'message' => $message,
            'content' => $content,
            'is_truncated' => $characterCount > self::MAX_CHARACTERS,
            'character_count' => $characterCount,
            'method' => $method,
            'method_label' => $methodLabel,
        ];
    }

    protected function extractPdfText(string $absolutePath): string
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($absolutePath);
        $text = $pdf->getText();

        return preg_replace('/\s{3,}/', "\n\n", trim($text)) ?? '';
    }

    protected function extractTextFile(string $absolutePath): string
    {
        return trim((string) file_get_contents($absolutePath));
    }
}