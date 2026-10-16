<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class AccreditationEvidencePdfOcr
{
    private const MAX_PAGES = 5;
    private const PROCESS_TIMEOUT_SECONDS = 90;

    public function isAvailable(): bool
    {
        return $this->findExecutable('tesseract') !== null
            && $this->findExecutable('pdftoppm') !== null;
    }

    public function extractFromPdf(string $absolutePath): ?string
    {
        $tesseract = $this->findExecutable('tesseract');
        $pdftoppm = $this->findExecutable('pdftoppm');

        if (! $tesseract || ! $pdftoppm) {
            return null;
        }

        $tempDir = storage_path('app/tmp/accreditation-evidence-ocr/' . Str::uuid()->toString());
        File::ensureDirectoryExists($tempDir);

        try {
            $render = new Process([
                $pdftoppm,
                '-png',
                '-r',
                '180',
                '-f',
                '1',
                '-l',
                (string) self::MAX_PAGES,
                $absolutePath,
                $tempDir . DIRECTORY_SEPARATOR . 'page',
            ]);
            $render->setTimeout(self::PROCESS_TIMEOUT_SECONDS);
            $render->run();

            if (! $render->isSuccessful()) {
                return null;
            }

            $images = glob($tempDir . DIRECTORY_SEPARATOR . 'page-*.png') ?: [];

            if ($images === []) {
                return null;
            }

            natsort($images);

            $chunks = [];

            foreach ($images as $imagePath) {
                $ocr = new Process([$tesseract, $imagePath, 'stdout', '--psm', '1']);
                $ocr->setTimeout(self::PROCESS_TIMEOUT_SECONDS);
                $ocr->run();

                if ($ocr->isSuccessful()) {
                    $chunks[] = trim($ocr->getOutput());
                }
            }

            $text = trim(implode("\n\n", array_filter($chunks)));

            return $text !== ''
                ? (preg_replace('/\s{3,}/', "\n\n", $text) ?? $text)
                : null;
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    private function findExecutable(string $name): ?string
    {
        return (new ExecutableFinder())->find($name) ?: null;
    }
}