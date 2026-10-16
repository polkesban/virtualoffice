<?php

namespace App\Services\AI;

use App\Models\PbmMaterial;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;
use RuntimeException;

class QuestionGeneratorService
{
    protected AiClient $aiClient;

    public function __construct(AiClient $aiClient)
    {
        $this->aiClient = $aiClient;
    }

    /**
     * Ekstraksi teks dari berkas yang diunggah dosen.
     */
    public function extractFromUploadedFile(UploadedFile $file): string
    {
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '180');

        $ext = strtolower($file->getClientOriginalExtension());
        $realPath = $file->getRealPath();

        return match ($ext) {
            'pdf'  => $this->extractPdf($realPath),
            'docx' => $this->extractDocx($realPath),
            'pptx' => $this->extractPptx($realPath),
            'txt', 'md' => $this->extractTextFile($realPath),
            default => throw new RuntimeException("Format berkas .{$ext} tidak didukung. Harap gunakan PDF, PPTX, DOCX, atau TXT.")
        };
    }

    /**
     * Ekstraksi teks dari model PbmMaterial yang tersimpan di sesi/kelas.
     */
    public function extractFromMaterial(PbmMaterial $material): string
    {
        $text = "### Materi: {$material->title}\n";
        if ($material->description) {
            $text .= "Deskripsi: {$material->description}\n\n";
        }

        if ($material->url && str_starts_with($material->url, '/storage/')) {
            $relativePath = str_replace('/storage/', '', $material->url);
            $fullPath = Storage::disk('public')->path($relativePath);

            if (file_exists($fullPath)) {
                $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                try {
                    $fileContent = match ($ext) {
                        'pdf'  => $this->extractPdf($fullPath),
                        'docx' => $this->extractDocx($fullPath),
                        'pptx' => $this->extractPptx($fullPath),
                        'txt', 'md' => $this->extractTextFile($fullPath),
                        default => ''
                    };
                    $text .= $fileContent;
                } catch (\Throwable $e) {
                    Log::warning("[QuestionGeneratorService] Gagal ekstraksi material ID {$material->id}: " . $e->getMessage());
                }
            }
        } elseif ($material->url && (str_starts_with($material->url, 'http://') || str_starts_with($material->url, 'https://'))) {
            // Coba ambil jika berupa artikel / laman web publik
            try {
                $response = Http::timeout(5)->get($material->url);
                if ($response->successful()) {
                    $html = $response->body();
                    $cleaned = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
                    $cleaned = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $cleaned);
                    $plain = html_entity_decode(strip_tags($cleaned));
                    $text .= mb_substr(preg_replace('/\s+/', ' ', trim($plain)), 0, 5000);
                }
            } catch (\Throwable $e) {
                Log::info("[QuestionGeneratorService] Skip fetch URL material {$material->url}: " . $e->getMessage());
            }
        }

        return $text;
    }

    /**
     * Ekstraksi berkas PDF menggunakan pdftotext (kecepatan tinggi & hemat memori) atau fallback Smalot.
     */
    protected function extractPdf(string $filePath): string
    {
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '180');

        // 1. Prioritaskan utilitas pdftotext via exec (sangat cepat, streaming langsung, aman untuk berkas besar)
        if (function_exists('exec')) {
            try {
                $escapedPath = escapeshellarg($filePath);
                $output = [];
                $returnCode = 1;
                @exec("pdftotext {$escapedPath} - 2>/dev/null", $output, $returnCode);
                if ($returnCode === 0 && !empty($output)) {
                    $text = implode("\n", $output);
                    if (mb_strlen(trim($text)) > 0) {
                        return preg_replace('/\s{3,}/', "\n\n", trim($text));
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("[QuestionGeneratorService] pdftotext exec fallback: " . $e->getMessage());
            }
        }

        // 2. Fallback ke Smalot PdfParser dengan retainImageContent = false agar tidak mengonsumsi RAM untuk gambar
        try {
            $config = new \Smalot\PdfParser\Config();
            $config->setRetainImageContent(false);
            $parser = new PdfParser([], $config);
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            return preg_replace('/\s{3,}/', "\n\n", trim($text));
        } catch (\Throwable $e) {
            Log::error("[QuestionGeneratorService] Error ekstraksi PDF {$filePath}: " . $e->getMessage());
            throw new RuntimeException("Gagal membaca teks dari file PDF: " . $e->getMessage());
        }
    }

    /**
     * Ekstraksi berkas Word .docx menggunakan ZipArchive (native PHP).
     */
    protected function extractDocx(string $filePath): string
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) === true) {
            $xmlIndex = $zip->locateName('word/document.xml');
            if ($xmlIndex !== false) {
                $xmlData = $zip->getFromIndex($xmlIndex);
                $zip->close();

                // Ganti tag paragraf dan break menjadi baris baru
                $formatted = str_replace(
                    ['<w:p>', '</w:p>', '<w:br/>', '<w:cr/>'],
                    ["\n", "\n", "\n", "\n"],
                    $xmlData
                );
                $text = html_entity_decode(strip_tags($formatted), ENT_QUOTES | ENT_XML1, 'UTF-8');
                return preg_replace('/\s{3,}/', "\n\n", trim($text));
            }
            $zip->close();
        }

        throw new RuntimeException("Gagal membaca dokumen Word (.docx) atau berkas rusak.");
    }

    /**
     * Ekstraksi teks dari presentasi PowerPoint (.pptx) menggunakan ZipArchive.
     */
    protected function extractPptx(string $filePath): string
    {
        $zip = new ZipArchive();
        $text = '';
        if ($zip->open($filePath) === true) {
            $slideEntries = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                if (preg_match('#^ppt/slides/slide(\d+)\.xml$#i', $entryName, $matches)) {
                    $slideEntries[(int)$matches[1]] = $entryName;
                }
            }
            ksort($slideEntries);

            foreach ($slideEntries as $slideNum => $entryName) {
                $xmlData = $zip->getFromName($entryName);
                if ($xmlData) {
                    $formatted = str_replace(
                        ['<a:p>', '</a:p>', '<a:br/>', '<a:br />'],
                        ["\n", "\n", "\n", "\n"],
                        $xmlData
                    );
                    $slideText = trim(strip_tags($formatted));
                    if (!empty($slideText)) {
                        $text .= "\n--- Slide {$slideNum} ---\n" . $slideText . "\n";
                    }
                }
            }
            $zip->close();
            return trim($text);
        }

        throw new RuntimeException("Gagal membaca presentasi PowerPoint (.pptx) atau berkas rusak.");
    }

    /**
     * Ekstraksi teks biasa.
     */
    protected function extractTextFile(string $filePath): string
    {
        $content = file_get_contents($filePath);
        return mb_convert_encoding(trim($content), 'UTF-8', 'UTF-8');
    }

    /**
     * Generate kumpulan soal CBT berbasis AI.
     *
     * @param array{
     *    context_text: string,
     *    question_count: int,
     *    difficulty: string,
     *    format_type: string,
     *    option_count: int,
     *    additional_instructions?: string|null
     * } $params
     * @return array<int, array{
     *    vignette: string|null,
     *    question_text: string,
     *    points: int,
     *    options: array<int, array{text: string, is_correct: bool, rationale: string}>
     * }>
     */
    public function generateQuestions(array $params): array
    {
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '180');

        $contextText = trim($params['context_text'] ?? '');
        $count = max(1, min(15, (int) ($params['question_count'] ?? 5)));
        $difficulty = $params['difficulty'] ?? 'c3_c4';
        $formatType = $params['format_type'] ?? 'vignette_case';
        $optionCount = max(4, min(5, (int) ($params['option_count'] ?? 5)));
        $instructions = trim($params['additional_instructions'] ?? '');

        // Batasi panjang konteks agar tidak melebihi batas token model (maksimal ~25.000 karakter)
        if (mb_strlen($contextText) > 25000) {
            $contextText = mb_substr($contextText, 0, 25000) . "\n... [teks dipotong untuk efisiensi context window]";
        }

        // Mapping tingkat kesulitan Taksonomi Bloom
        $bloomDesc = match ($difficulty) {
            'c1_c2' => 'Taksonomi Bloom C1-C2 (Mengingat dan Memahami konsep dasar, definisi, terminologi, atau prinsip dasar).',
            'c5_c6' => 'Taksonomi Bloom C5-C6 (Evaluasi, penalaran kritis tingkat lanjut, sintesis, dan pemecahan masalah kompleks).',
            default => 'Taksonomi Bloom C3-C4 (Aplikasi klinis dan Analisis kasus, standar Uji Kompetensi / CBT Perawat/Bidan/Nakes Poltekkes).'
        };

        $formatDesc = match ($formatType) {
            'conceptual' => 'Format Soal Pilihan Ganda Konseptual/Teori (pertanyaan langsung tanpa skenario kasus panjang, namun tetap mendalam dan analitis).',
            default      => 'Format Soal CBT Berbasis Kasus/Vignette Klinis (WAJIB menyertakan narasi kasus pasien: usia, jenis kelamin, keluhan utama, riwayat, hasil TTV atau pemeriksaan fisik/penunjang, kemudian diikuti lead-in pertanyaan yang spesifik).'
        };

        $systemPrompt = <<<PROMPT
Anda adalah Pakar Penyusun Soal CBT (Computer-Based Testing) dan Dosen Senior di Poltekkes Kemenkes Bandung.
Tugas Anda adalah menyusun {$count} butir soal pilihan ganda berkualitas tinggi berdasarkan RPS (Rencana Pembelajaran Semester) dan bahan ajar yang diberikan.

STANDAR KUALITAS SOAL:
1. Target Kesulitan: {$bloomDesc}
2. Gaya & Format: {$formatDesc}
3. Setiap soal harus memiliki {$optionCount} pilihan opsi jawaban (Opsi A sampai E atau A sampai D).
4. Opsi jawaban harus homogen, logis, tidak ambigu, dan hanya ada SATU kunci jawaban yang paling tepat (single best answer). Opsi pengecoh (distraktor) harus masuk akal.
5. HINDARI pilihan seperti "Semua benar", "Bukan salah satu di atas", atau opsi negatif ganda.
6. WAJIB menyertakan RASIONAL / PEMBAHASAN untuk SETIAP OPSI jawaban:
   - Tuliskan rasional secara padat, ringkas, dan fokus ilmiah (1-2 kalimat per opsi agar efisien).
   - Untuk opsi yang BENAR: jelaskan alasan ilmiah/klinis mengapa opsi tersebut adalah pilihan terbaik.
   - Untuk opsi yang SALAH: jelaskan mengapa opsi tersebut kurang tepat, kontraindikasi, atau bukan prioritas.
7. Kasus/Vignette dan Pertanyaan harus ditulis dalam Bahasa Indonesia baku yang jelas dan akademis.

FORMAT OUTPUT WAJIB:
Anda HARUS mengembalikan data HANYA dalam format JSON murni tanpa markdown pembungkus di luar JSON, dengan struktur berikut:
{
  "questions": [
    {
      "vignette": "Narasi skenario kasus/vignette (berikan tag <p>...</p> jika ada paragraf). Kosongkan jika tipe konseptual murni.",
      "question_text": "Kalimat pertanyaan/lead-in yang jelas dan fokus?",
      "points": 1,
      "options": [
        {
          "text": "Teks opsi jawaban",
          "is_correct": true,
          "rationale": "Penjelasan mengapa opsi ini benar."
        },
        {
          "text": "Teks opsi jawaban lain",
          "is_correct": false,
          "rationale": "Penjelasan mengapa opsi ini salah/kurang tepat."
        }
      ]
    }
  ]
}
PROMPT;

        $userPrompt = "Berikut adalah materi acuan / RPS perkuliahan untuk membuat soal:\n\n";
        $userPrompt .= "=== DOKUMEN MATERI & RPS ===\n{$contextText}\n=== AKHIR MATERI ===\n\n";
        if (!empty($instructions)) {
            $userPrompt .= "INSTRUKSI KHUSUS TAMBAHAN DARI DOSEN:\n{$instructions}\n\n";
        }
        $userPrompt .= "Buatkan persis {$count} butir soal pilihan ganda dengan {$optionCount} opsi per soal sesuai instruksi di atas dalam format JSON.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        // Panggil AiClient dengan temperature terkontrol, batas 8192 token, dan jsonMode aktif
        $rawResponse = $this->aiClient->chat($messages, 0.35, 8192, true);

        return $this->parseAndValidateJson($rawResponse);
    }

    /**
     * Parsing dan sanitasi respon JSON dari model AI dengan mekanisme recovery jika terpotong.
     *
     * @return array<int, array{
     *    vignette: string|null,
     *    question_text: string,
     *    points: int,
     *    options: array<int, array{text: string, is_correct: bool, rationale: string}>
     * }>
     */
    protected function parseAndValidateJson(string $raw): array
    {
        $clean = trim($raw);

        // Hapus penanda blok kode markdown (```json ... ```) jika model menyertakannya
        if (str_starts_with($clean, '```')) {
            $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
            $clean = preg_replace('/\s*```$/', '', $clean);
            $clean = trim($clean);
        }

        // Cari kurung kurawal pertama dan terakhir untuk mengekstrak objek JSON
        $startPos = strpos($clean, '{');
        $endPos = strrpos($clean, '}');
        if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
            $clean = substr($clean, $startPos, $endPos - $startPos + 1);
        }

        $decoded = json_decode($clean, true);

        // Jika json_decode langsung gagal, jalankan recovery untuk menyelamatkan butir soal yang sudah lengkap
        if (!is_array($decoded) || empty($decoded['questions']) || !is_array($decoded['questions'])) {
            $decoded = $this->recoverTruncatedQuestions($raw);
        }

        if (!is_array($decoded) || empty($decoded['questions']) || !is_array($decoded['questions'])) {
            Log::error("[QuestionGeneratorService] Gagal parse JSON AI. Respon mentah:\n" . $raw);
            throw new RuntimeException("AI tidak menghasilkan format soal yang valid. Silakan coba kembali atau sesuaikan materi.");
        }

        $validatedQuestions = [];

        foreach ($decoded['questions'] as $q) {
            if (empty($q['question_text']) || empty($q['options']) || !is_array($q['options'])) {
                continue;
            }

            $validatedOptions = [];
            $hasCorrect = false;

            foreach ($q['options'] as $idx => $opt) {
                $isCorrect = !empty($opt['is_correct']);
                if ($isCorrect && !$hasCorrect) {
                    $hasCorrect = true;
                } elseif ($isCorrect && $hasCorrect) {
                    // Hanya izinkan 1 jawaban benar
                    $isCorrect = false;
                }

                $validatedOptions[] = [
                    'text' => trim($opt['text'] ?? ''),
                    'is_correct' => $isCorrect,
                    'rationale' => trim($opt['rationale'] ?? '')
                ];
            }

            // Jika belum ada yang ditandai benar, jadikan opsi pertama benar
            if (!$hasCorrect && count($validatedOptions) > 0) {
                $validatedOptions[0]['is_correct'] = true;
            }

            if (count($validatedOptions) >= 2) {
                $validatedQuestions[] = [
                    'vignette' => !empty($q['vignette']) ? trim($q['vignette']) : null,
                    'question_text' => trim($q['question_text']),
                    'points' => (int) ($q['points'] ?? 1),
                    'options' => $validatedOptions
                ];
            }
        }

        if (empty($validatedQuestions)) {
            throw new RuntimeException("Tidak ada butir soal valid yang berhasil disusun dari respon AI.");
        }

        return $validatedQuestions;
    }

    /**
     * Coba selamatkan butir soal yang sudah lengkap jika JSON terpotong di akhir oleh batas token.
     */
    protected function recoverTruncatedQuestions(string $raw): ?array
    {
        $clean = trim($raw);
        if (str_starts_with($clean, '```')) {
            $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
            $clean = preg_replace('/\s*```$/', '', $clean);
            $clean = trim($clean);
        }

        $startPos = strpos($clean, '{');
        if ($startPos !== false) {
            $clean = substr($clean, $startPos);
        }

        $searchString = $clean;
        $maxAttempts = 20;

        while ($maxAttempts-- > 0) {
            $pos = strrpos($searchString, '}');
            if ($pos === false || $pos < 30) {
                break;
            }

            $candidate = substr($searchString, 0, $pos + 1);

            $attempts = [
                $candidate . "\n  ]\n}",
                $candidate . "\n}\n  ]\n}",
            ];

            foreach ($attempts as $testJson) {
                $decoded = json_decode($testJson, true);
                if (is_array($decoded) && !empty($decoded['questions']) && is_array($decoded['questions'])) {
                    $validQuestions = [];
                    foreach ($decoded['questions'] as $q) {
                        if (!empty($q['question_text']) && !empty($q['options']) && is_array($q['options'])) {
                            $validQuestions[] = $q;
                        }
                    }
                    if (!empty($validQuestions)) {
                        Log::info("[QuestionGeneratorService] Berhasil menyelamatkan " . count($validQuestions) . " butir soal dari JSON yang terpotong.");
                        return ['questions' => $validQuestions];
                    }
                }
            }

            $searchString = substr($searchString, 0, $pos);
        }

        return null;
    }
}
