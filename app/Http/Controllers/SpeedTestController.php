<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpeedTestController extends Controller
{
    private const STATIC_DOWNLOAD_SAMPLE = 'speedtest-download-25mb.bin';
    private const MIN_DOWNLOAD_BYTES = 1_000_000;
    private const DEFAULT_DOWNLOAD_BYTES = 12_000_000;
    private const MAX_DOWNLOAD_BYTES = 25_000_000;
    private const MAX_UPLOAD_BYTES = 1_000_000;

    public function index(): View
    {
        $downloadBytes = $this->downloadSampleBytes();

        return view('speedtest.index', [
            'downloadBytes' => $downloadBytes,
            'downloadUrl' => asset(self::STATIC_DOWNLOAD_SAMPLE),
            'uploadChunkBytes' => 500_000,
            'uploadRounds' => 4,
            'uploadParallel' => 2,
            'pingIterations' => 5,
            'maxUploadKb' => (int) floor(self::MAX_UPLOAD_BYTES / 1_000),
        ]);
    }

    public function ping(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'server_time' => now()->toIso8601String(),
            'unix_ms' => (int) round(microtime(true) * 1000),
        ], 200, $this->noStoreHeaders());
    }

    public function download(Request $request): StreamedResponse
    {
        $byteCount = $this->normalizeDownloadBytes((int) $request->query('bytes', self::DEFAULT_DOWNLOAD_BYTES));
        $chunkSize = 250_000;
        $chunk = random_bytes($chunkSize);

        return response()->stream(function () use ($byteCount, $chunkSize, $chunk): void {
            ignore_user_abort(true);
            set_time_limit(0);

            $remaining = $byteCount;

            while ($remaining > 0) {
                $length = min($remaining, $chunkSize);
                echo $length === $chunkSize ? $chunk : substr($chunk, 0, $length);

                $remaining -= $length;

                if (ob_get_level() > 0) {
                    @ob_flush();
                }

                flush();
            }
        }, 200, $this->noStoreHeaders([
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => (string) $byteCount,
            'Content-Disposition' => 'inline; filename="speedtest.bin"',
            'X-Accel-Buffering' => 'no',
        ]));
    }

    public function upload(Request $request): JsonResponse
    {
        $declaredLength = (int) $request->server('CONTENT_LENGTH', 0);

        if ($declaredLength > self::MAX_UPLOAD_BYTES) {
            abort(413, 'Payload speedtest terlalu besar.');
        }

        $payload = (string) $request->getContent();
        $bytesReceived = strlen($payload);

        if ($bytesReceived > self::MAX_UPLOAD_BYTES) {
            abort(413, 'Payload speedtest terlalu besar.');
        }

        return response()->json([
            'ok' => true,
            'bytes_received' => $bytesReceived,
            'server_time' => now()->toIso8601String(),
        ], 200, $this->noStoreHeaders());
    }

    private function normalizeDownloadBytes(int $requestedBytes): int
    {
        if ($requestedBytes <= 0) {
            return self::DEFAULT_DOWNLOAD_BYTES;
        }

        return min(self::MAX_DOWNLOAD_BYTES, max(self::MIN_DOWNLOAD_BYTES, $requestedBytes));
    }

    private function downloadSampleBytes(): int
    {
        $path = public_path(self::STATIC_DOWNLOAD_SAMPLE);

        if (! is_file($path)) {
            return self::MAX_DOWNLOAD_BYTES;
        }

        return (int) filesize($path);
    }

    private function noStoreHeaders(array $headers = []): array
    {
        return array_merge([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ], $headers);
    }
}