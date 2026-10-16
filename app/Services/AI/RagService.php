<?php

namespace App\Services\AI;

use App\Models\DocumentChunk;
use App\Models\KnowledgeDocument;
use App\Models\ProgramStudi;
use App\Support\AiProviderModelResolver;
use App\Support\LakipDocumentCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * RAG (Retrieval-Augmented Generation) Service.
 *
 * Handles:
 *  - Document indexing: extract text → chunk → embed → store
 *  - Query answering: embed question → cosine similarity → retrieve → generate
 */
class RagService
{
    public function __construct(
        private readonly AiClient $ai,
        private readonly PollyKnowledgeSnapshot $snapshot,
    ) {}

    // ── Indexing ───────────────────────────────────────────────────────────────

    /**
     * Index a knowledge document. Called from IndexDocumentJob.
     */
    public function indexDocument(KnowledgeDocument $doc): void
    {
        @ini_set('memory_limit', '512M');
        $doc->update(['status' => 'indexing', 'error_message' => null]);

        try {
            $text   = $this->extractText($doc);
            $chunks = $this->chunkText($text);

            // Remove old chunks before re-indexing
            $doc->chunks()->delete();

            $total = count($chunks);
            $count = 0;
            foreach ($chunks as $i => $chunk) {
                $doc->update(['status' => 'indexing:' . ($i + 1) . '/' . $total]);

                $embedding = $this->ai->embed($chunk);

                DocumentChunk::create([
                    'document_id'    => $doc->id,
                    'chunk_index'    => $i,
                    'content'        => $chunk,
                    'embedding'      => json_encode($embedding),
                    'token_estimate' => (int) (mb_strlen($chunk) / 4),
                ]);

                $count++;

                // Throttle: pause every 10 embeddings to avoid rate limits
                if ($i > 0 && $i % 10 === 0) {
                    usleep(300_000);
                }
            }

            $doc->update([
                'status'      => 'indexed',
                'chunk_count' => $count,
                'indexed_at'  => now(),
            ]);

        } catch (\Throwable $e) {
            $doc->update(['status' => 'error', 'error_message' => $e->getMessage()]);
            Log::error('[RAG] indexDocument failed', ['doc' => $doc->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    // ── Query ──────────────────────────────────────────────────────────────────

    /**
     * Answer a question using RAG.
     *
     * @param  string $question
     * @param  array  $history  OpenAI-format previous turns for multi-turn support
     * @param  array{path?: string, title?: string} $pageContext
     * @return array{answer: string, sources: list<string>, model: string}
     */
    public function query(string $question, array $history = [], string $language = 'id', array $pageContext = []): array
    {
        $language = $this->normalizeLanguage($language);

        if ($structured = $this->answerFromWebsiteData($question, $history, $language, $pageContext)) {
            return $structured;
        }

        $liveSnapshot = $this->snapshot->build();

        // 1. Build a contextual search query for RAG retrieval (prevents generic queries from retrieving random chunks)
        $searchQuery = $question;
        if (mb_strlen($question) < 70 && ! empty($history)) {
            $lastUserMessage = collect($history)
                ->filter(fn ($msg) => ($msg['role'] ?? null) === 'user')
                ->last();
            if ($lastUserMessage) {
                $searchQuery = trim($lastUserMessage['content']) . ' ' . $question;
            }
        }

        $queryEmbedding = $this->ai->embed($searchQuery);

        // 2. Retrieve top-k relevant chunks by cosine similarity
        $topChunks = $this->findSimilarChunks(
            $queryEmbedding,
            config('ai.rag.top_k', 5),
            config('ai.rag.min_score', 0.30),
            $searchQuery
        );

        // 3. Build context string from retrieved chunks
        $context = $topChunks->map(fn ($c) => "---\n{$c->content}")->join("\n\n");

        // 4. Build message array
        $systemPrompt = $this->loadPrompt('concierge-system');
        $messages     = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'system', 'content' => $this->languageSystemInstruction($language)],
        ];

        if (! empty($liveSnapshot['context'])) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->liveSnapshotSystemMessage($liveSnapshot['context'], $language),
            ];
        }

        if ($context) {
            $messages[] = [
                'role'    => 'system',
                'content' => "DOKUMEN REFERENSI POLTEKKES BANDUNG:\n{$context}",
            ];
        } else {
            $messages[] = [
                'role'    => 'system',
                'content' => $this->isEnglish($language)
                    ? 'There is no relevant Poltekkes Bandung reference for this question. Do not use general knowledge or assumptions. If the data is unavailable, answer honestly that the information is not yet available in our records.'
                    : 'Tidak ada referensi Poltekkes Bandung yang relevan untuk pertanyaan ini. Jangan gunakan pengetahuan umum atau asumsi. Jika data tidak tersedia, jawab dengan jujur bahwa informasi belum tersedia dalam basis data kami.',
            ];
        }

        // Append recent conversation history
        $maxHistory = config('ai.concierge.max_history', 6);
        $messages   = array_merge($messages, array_slice($history, -($maxHistory * 2)));

        // Append current question
        $messages[] = ['role' => 'user', 'content' => $question];

        // 5. Generate answer
        $answer = $this->ai->chat(
            $messages,
            config('ai.concierge.temperature', 0.4),
            config('ai.concierge.max_tokens', 1024),
        );

        // 6. Collect source document names (deduplicated)
        $sources = $topChunks
            ->map(fn ($c) => $c->document?->name)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (! empty($liveSnapshot['source']) && ! in_array($liveSnapshot['source'], $sources, true)) {
            array_unshift($sources, $liveSnapshot['source']);
        }

        return [
            'answer'  => $answer,
            'sources' => $sources,
            'model'   => AiProviderModelResolver::chatModel(AiProviderModelResolver::activeProvider()),
        ];
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function extractText(KnowledgeDocument $doc): string
    {
        if ($doc->file_type === 'url') {
            return $this->extractUrl($doc->file_path);
        }

        $path = \App\Support\MediaStorage::temporaryLocalPath($doc->file_path);

        if (! $path || ! file_exists($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }

        $text = match ($doc->file_type) {
            'pdf'   => $this->extractPdf($path),
            'md',
            'txt'   => file_get_contents($path),
            default => throw new \InvalidArgumentException("Unsupported file type: {$doc->file_type}"),
        };

        return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    }

    private function extractUrl(string $url): string
    {
        $response = \Illuminate\Support\Facades\Http::get($url);
        if (! $response->successful()) {
            throw new \RuntimeException("Gagal mengambil URL: {$url} (Status: {$response->status()})");
        }
        $html = $response->body();

        // Hapus elemen script dan style
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);

        // Bersihkan HTML tag dan decode entitas HTML
        $text = html_entity_decode(strip_tags($html));

        // Normalisasi whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\R{2,}/', "\n\n", $text);

        return mb_convert_encoding(trim($text), 'UTF-8', 'UTF-8');
    }

    private function extractPdf(string $path): string
    {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile($path);
        $text   = $pdf->getText();

        // Normalize excessive whitespace
        return preg_replace('/\s{3,}/', "\n\n", trim($text));
    }

    /**
     * Split text into overlapping chunks targeting $chunkSize characters.
     *
     * @return list<string>
     */
    private function chunkText(string $text): array
    {
        $chunkSize = config('ai.rag.chunk_size', 500);
        $overlap   = config('ai.rag.chunk_overlap', 80);
        $chunks    = [];

        // Split on paragraph boundaries first, then merge/split to target size
        $paragraphs = preg_split('/\n{2,}/', trim($text));
        $current    = '';

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if ($para === '') {
                continue;
            }

            if ($current !== '' && mb_strlen($current) + mb_strlen($para) > $chunkSize) {
                $chunks[] = trim($current);
                // Carry overlap from the end of the previous chunk
                $current = mb_substr($current, -$overlap) . "\n\n" . $para;
            } else {
                $current .= ($current ? "\n\n" : '') . $para;
            }
        }

        if (trim($current) !== '') {
            $chunks[] = trim($current);
        }

        return array_filter($chunks, fn ($c) => mb_strlen($c) > 20);
    }

    /**
     * Cosine similarity between two float vectors.
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b) || empty($a)) {
            return 0.0;
        }

        $dot   = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $va) {
            $vb    = $b[$i];
            $dot   += $va * $vb;
            $normA += $va * $va;
            $normB += $vb * $vb;
        }

        $denom = sqrt($normA) * sqrt($normB);

        return $denom > 0.0 ? (float) ($dot / $denom) : 0.0;
    }

    /**
     * Load all indexed chunks, score by cosine similarity, return top-k.
     */
    private function findSimilarChunks(array $queryEmbedding, int $topK, float $minScore, string $searchQuery = ''): Collection
    {
        if (empty($queryEmbedding)) {
            return collect();
        }

        $all = DocumentChunk::with('document')
            ->whereNotNull('embedding')
            ->whereHas('document', fn ($q) => $q->where('status', 'indexed'))
            ->get();

        $scored = $all
            ->map(function (DocumentChunk $chunk) use ($queryEmbedding) {
                $embedding     = $chunk->getEmbeddingArray();
                $chunk->_score = $this->cosineSimilarity($queryEmbedding, $embedding);
                return $chunk;
            })
            ->filter(fn ($c) => $c->_score >= $minScore)
            ->sortByDesc('_score');

        // Explicitly boost/force chunks containing key sections if query mentions them
        $forcedChunks = collect();
        if ($searchQuery !== '') {
            $searchQueryLower = strtolower($searchQuery);
            $forcedIndices = [];
            
            if (str_contains($searchQueryLower, 'visi')) {
                $forcedIndices[] = 26;
            }
            if (str_contains($searchQueryLower, 'misi')) {
                $forcedIndices[] = 28;
            }
            if (str_contains($searchQueryLower, 'tujuan')) {
                $forcedIndices[] = 30;
            }
            if (str_contains($searchQueryLower, 'strategi') || str_contains($searchQueryLower, 'strategis')) {
                $forcedIndices[] = 32;
                $forcedIndices[] = 127;
            }
            
            if (!empty($forcedIndices)) {
                $forcedChunks = DocumentChunk::with('document')
                    ->where('document_id', 3)
                    ->whereIn('chunk_index', $forcedIndices)
                    ->get()
                    ->map(function ($chunk) {
                        $chunk->_score = 2.0; // Ensure they are ranked at the very top
                        return $chunk;
                    });
            }
        }

        // Merge forced chunks with similarity scored chunks
        if ($forcedChunks->isNotEmpty()) {
            $scored = $forcedChunks->concat($scored)
                ->unique(fn ($c) => $c->document_id . '_' . $c->chunk_index);
        }

        return $scored
            ->sortByDesc('_score')
            ->take($topK)
            ->values();
    }

    private function loadPrompt(string $name): string
    {
        $path = resource_path("prompts/{$name}.md");

        if (file_exists($path)) {
            return file_get_contents($path);
        }

        // Hardcoded fallback so the concierge works even before prompts are loaded
        return <<<'PROMPT'
Kamu adalah asisten virtual resmi Politeknik Kesehatan Kemenkes Bandung (Poltekkes Bandung).
Jawab pertanyaan pengunjung dengan ramah, informatif, dan akurat berdasarkan DOKUMEN REFERENSI POLTEKKES BANDUNG yang disediakan.
DOKUMEN REFERENSI POLTEKKES BANDUNG adalah satu-satunya sumber kebenaran (source of truth). Jika ada perbedaan atau kontradiksi antara informasi di riwayat percakapan sebelumnya dengan DOKUMEN REFERENSI POLTEKKES BANDUNG, Anda wajib mengabaikan informasi salah di riwayat percakapan tersebut dan memberikan jawaban yang benar berdasarkan DOKUMEN REFERENSI POLTEKKES BANDUNG. Jangan pernah mengulang atau melanjutkan informasi yang tidak akurat dari riwayat percakapan sebelumnya.
Semua informasi mengenai visi, misi, tujuan, dan strategi Poltekkes Bandung wajib merujuk secara persis dan faktual pada DOKUMEN REFERENSI POLTEKKES BANDUNG yang disediakan. Dilarang keras mengarang, membuat analogi/contoh fiktif, atau mengubah teks visi/misi resmi Poltekkes Bandung meskipun sedang menyederhanakan bahasa atau penjelasan.
Jika informasi tidak tersedia dalam dokumen, katakan dengan jujur dan sarankan menghubungi info@polkesban.ac.id.
Ikuti bahasa yang diminta pengguna secara konsisten. Gunakan Bahasa Indonesia sebagai default. Format jawaban dengan poin-poin bila membantu.
PROMPT;
    }

    /**
     * Answer high-confidence questions from structured website data.
     *
     * This prevents hallucinations for public facts that already exist in the
     * application database, especially program study and jenjang questions.
     *
     * @param array<int, array{role: string, content: string}> $history
     * @param array{path?: string, title?: string} $pageContext
     * @return array{answer: string, sources: list<string>, model: string}|null
     */
    private function answerFromWebsiteData(string $question, array $history, string $language, array $pageContext = []): ?array
    {
        return $this->answerLakipQuestion($question, $history, $language, $pageContext)
            ?? $this->answerProgramStudiQuestion($question, $history, $language);
    }

    /**
     * @param array<int, array{role: string, content: string}> $history
     * @param array{path?: string, title?: string} $pageContext
     * @return array{answer: string, sources: list<string>, model: string}|null
     */
    private function answerLakipQuestion(string $question, array $history, string $language, array $pageContext): ?array
    {
        $intentText = $this->normalizeForMatching($question);

        $pagePath = $this->normalizeForMatching((string) ($pageContext['path'] ?? ''));
        $pageTitle = $this->normalizeForMatching((string) ($pageContext['title'] ?? ''));
        $asksAboutLakip = (bool) preg_match('/\blakip\b|\blkj\b|laporan kinerja instansi pemerintah|laporan kinerja/', $intentText);
        $asksAboutCurrentPage = (bool) preg_match('/halaman ini|page ini|this page|current page|dokumen ini|this document/', $intentText);
        $asksForExplanation = (bool) preg_match('/jelaskan|ringkas|apa isi|isi utama|apa itu|what is|tell me about|summary|summarize|tentang/', $intentText);
        $pageIndicatesLakip = str_contains($pagePath, 'lakip')
            || str_contains($pageTitle, 'lakip')
            || str_contains($pageTitle, 'laporan kinerja');

        if (! $asksAboutLakip && ! ($pageIndicatesLakip && $asksAboutCurrentPage && $asksForExplanation)) {
            return null;
        }

        $currentYear = $this->detectLakipYearFromPageContext($pagePath);

        if ($currentYear !== null) {
            return $this->buildLakipPageAnswer($currentYear, $language);
        }

        return $this->buildLakipGeneralAnswer($language);
    }

    /**
     * @param array<int, array{role: string, content: string}> $history
     * @return array{answer: string, sources: list<string>, model: string}|null
     */
    private function answerProgramStudiQuestion(string $question, array $history, string $language): ?array
    {
        $intentText = $this->normalizeForMatching($question);

        if (! preg_match('/program studi|prodi|study program|study programs|major|majors|degree|degrees|jenjang|d3|d4|profesi|professional program|sarjana terapan|applied bachelor|applied bachelors|diploma three|diploma four|keperawatan|nursing|kebidanan|midwifery|farmasi|pharmacy|fisioterapi|physiotherapy|rekam medis|medical records|tlm|teknologi laboratorium|laboratory|gizi|nutrition|dietisien|dietitian|sanitasi|kesehatan lingkungan|environmental health|promosi kesehatan|health promotion|terapi gigi|dental therapy|kesehatan gigi|dental health/', $intentText)) {
            return null;
        }

        $prodis = ProgramStudi::aktif()
            ->get(['nama', 'jenjang', 'slug'])
            ->values();

        if ($prodis->isEmpty()) {
            return null;
        }

        $topics = $this->detectProgramStudiTopics($intentText);
        $requestedJenjang = $this->extractRequestedJenjang($intentText);

        if (count($topics) === 1) {
            return $this->buildSpecificProgramStudiAnswer($prodis, $topics[0], $requestedJenjang, $language);
        }

        if (count($topics) > 1) {
            return $this->buildMultiProgramStudiAnswer($prodis, $topics, $requestedJenjang, $language);
        }

        return $this->buildProgramStudiListingAnswer($prodis, $requestedJenjang, $language);
    }

    /**
     * @param Collection<int, ProgramStudi> $prodis
     * @param list<string> $requestedJenjang
     * @return array{answer: string, sources: list<string>, model: string}
     */
    private function buildSpecificProgramStudiAnswer(Collection $prodis, string $topic, array $requestedJenjang, string $language): array
    {
        $label = $this->programStudiTopicLabel($topic, $language);

        $matches = $prodis
            ->filter(fn (ProgramStudi $prodi) => $this->matchesProgramStudiTopic($prodi->nama, $topic))
            ->values();

        if ($matches->isEmpty()) {
            return [
                'answer' => $this->isEnglish($language)
                    ? "Based on the active study program list on the Poltekkes Bandung website, {$label} is not currently listed as an active study program. For the latest confirmation, please contact info@polkesban.ac.id or (022) 2032672."
                    : "Berdasarkan daftar program studi aktif di website Poltekkes Bandung, {$label} saat ini belum tercantum sebagai program studi aktif. Jika Anda membutuhkan konfirmasi terbaru, silakan hubungi kami di info@polkesban.ac.id atau (022) 2032672.",
                'sources' => [$this->websiteDataSourceLabel($language)],
                'model' => 'website-data',
            ];
        }

        $byJenjang = $matches
            ->groupBy('jenjang')
            ->map(fn (Collection $items) => $items->pluck('nama')->values()->all());

        if (! empty($requestedJenjang)) {
            $missing = array_values(array_filter($requestedJenjang, fn ($jenjang) => ! $byJenjang->has($jenjang)));
            $availableSummary = collect(['D3', 'D4', 'Profesi', 'S1', 'S2'])
                ->filter(fn (string $jenjang) => $byJenjang->has($jenjang))
                ->map(function (string $jenjang) use ($byJenjang) {
                    return "{$jenjang} " . implode(', ', $byJenjang->get($jenjang, []));
                })
                ->implode('; ');

            $parts = [];

            if ($availableSummary !== '') {
                $parts[] = $this->isEnglish($language)
                    ? 'currently listed: ' . $availableSummary
                    : 'Yang tercantum saat ini adalah ' . $availableSummary;
            }

            if (! empty($missing)) {
                $parts[] = $this->isEnglish($language)
                    ? 'no ' . $label . ' entry is listed under ' . implode(', ', $missing)
                    : 'Tidak ada ' . implode(', ', $missing) . " {$label} di daftar program studi aktif";
            }

            return [
                'answer' => $this->isEnglish($language)
                    ? 'Based on the active study program list on the Poltekkes Bandung website, for ' . $label . ': ' . implode('. ', $parts) . '.'
                    : 'Berdasarkan daftar program studi aktif di website Poltekkes Bandung, untuk ' . $label . ' saat ini: ' . implode('. ', $parts) . '.',
                'sources' => [$this->websiteDataSourceLabel($language)],
                'model' => 'website-data',
            ];
        }

        $lines = $this->formatJenjangLines($byJenjang, ['D3', 'D4', 'Profesi', 'S1', 'S2']);

        return [
            'answer' => $this->isEnglish($language)
                ? "Based on the active study program list on the Poltekkes Bandung website, {$label} currently has:\n\n" . implode("\n", $lines)
                : "Berdasarkan daftar program studi aktif di website Poltekkes Bandung, untuk {$label} rinciannya adalah:\n\n" . implode("\n", $lines),
            'sources' => [$this->websiteDataSourceLabel($language)],
            'model' => 'website-data',
        ];
    }

    /**
     * @param Collection<int, ProgramStudi> $prodis
     * @param list<string> $requestedJenjang
     * @return array{answer: string, sources: list<string>, model: string}
     */
    private function buildProgramStudiListingAnswer(Collection $prodis, array $requestedJenjang, string $language): array
    {
        $order = empty($requestedJenjang) ? ['D3', 'D4', 'Profesi', 'S1', 'S2'] : $requestedJenjang;

        $byJenjang = $prodis
            ->groupBy('jenjang')
            ->map(fn (Collection $items) => $items->pluck('nama')->values()->all());

        $lines = $this->formatJenjangLines($byJenjang, $order);

        if ($this->isEnglish($language)) {
            $intro = empty($requestedJenjang)
                ? 'Based on the active study program list on the Poltekkes Bandung website, the degree breakdown is:'
                : 'Based on the active study program list on the Poltekkes Bandung website, the requested degree levels are:';
        } else {
            $intro = empty($requestedJenjang)
                ? 'Berdasarkan daftar program studi aktif di website Poltekkes Bandung, rincian jenjangnya adalah:'
                : 'Berdasarkan daftar program studi aktif di website Poltekkes Bandung, jenjang yang diminta adalah:';
        }

        return [
            'answer' => $intro . "\n\n" . implode("\n", $lines),
            'sources' => [$this->websiteDataSourceLabel($language)],
            'model' => 'website-data',
        ];
    }

    /**
     * @param Collection<int, ProgramStudi> $prodis
     * @param list<string> $topics
     * @param list<string> $requestedJenjang
     * @return array{answer: string, sources: list<string>, model: string}
     */
    private function buildMultiProgramStudiAnswer(Collection $prodis, array $topics, array $requestedJenjang, string $language): array
    {
        $lines = [];

        foreach ($topics as $topic) {
            $label = $this->programStudiTopicLabel($topic, $language);
            $matches = $prodis
                ->filter(fn (ProgramStudi $prodi) => $this->matchesProgramStudiTopic($prodi->nama, $topic))
                ->values();

            if ($matches->isEmpty()) {
                $lines[] = $this->isEnglish($language)
                    ? '- **' . $label . '**: not currently listed as an active study program on the website.'
                    : '- **' . $label . '**: belum tercantum sebagai program studi aktif di website.';
                continue;
            }

            $byJenjang = $matches
                ->groupBy('jenjang')
                ->map(fn (Collection $items) => $items->pluck('nama')->values()->all());

            if (! empty($requestedJenjang)) {
                $present = array_values(array_filter($requestedJenjang, fn ($jenjang) => $byJenjang->has($jenjang)));
                $missing = array_values(array_filter($requestedJenjang, fn ($jenjang) => ! $byJenjang->has($jenjang)));
                $availableSummary = collect(['D3', 'D4', 'Profesi', 'S1', 'S2'])
                    ->filter(fn (string $jenjang) => $byJenjang->has($jenjang))
                    ->map(fn (string $jenjang) => $jenjang . ' ' . implode(', ', $byJenjang->get($jenjang, [])))
                    ->implode('; ');
                $segments = [];

                if ($availableSummary !== '') {
                    $segments[] = $this->isEnglish($language)
                        ? 'currently listed: ' . $availableSummary
                        : 'yang tercantum: ' . $availableSummary;
                }

                if (! empty($missing)) {
                    $segments[] = $this->isEnglish($language)
                        ? 'no entry under ' . implode(', ', $missing)
                        : 'tidak ada ' . implode(', ', $missing);
                }

                $lines[] = '- **' . $label . '**: ' . implode('; ', $segments) . '.';
                continue;
            }

            $segments = collect(['D3', 'D4', 'Profesi', 'S1', 'S2'])
                ->filter(fn (string $jenjang) => $byJenjang->has($jenjang))
                ->map(fn (string $jenjang) => $jenjang . ' ' . implode(', ', $byJenjang->get($jenjang, [])))
                ->values()
                ->all();

            $lines[] = '- **' . $label . '**: ' . implode('; ', $segments) . '.';
        }

        return [
            'answer' => ($this->isEnglish($language)
                ? 'Based on the active study program list on the Poltekkes Bandung website, the details are:'
                : 'Berdasarkan daftar program studi aktif di website Poltekkes Bandung, rinciannya adalah:') . "\n\n" . implode("\n", $lines),
            'sources' => [$this->websiteDataSourceLabel($language)],
            'model' => 'website-data',
        ];
    }

    /**
     * @param Collection<string, list<string>> $byJenjang
     * @param list<string> $order
     * @return list<string>
     */
    private function formatJenjangLines(Collection $byJenjang, array $order): array
    {
        $lines = [];

        foreach ($order as $jenjang) {
            $items = $byJenjang->get($jenjang, []);
            if (! empty($items)) {
                $lines[] = '- **' . $jenjang . '**: ' . implode(', ', $items);
            }
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function extractRequestedJenjang(string $text): array
    {
        $requested = [];

        if (str_contains($text, 'd3') || str_contains($text, 'd-iii')) {
            $requested[] = 'D3';
        }

        if (str_contains($text, 'd4') || str_contains($text, 'd-iv') || str_contains($text, 'sarjana terapan')) {
            $requested[] = 'D4';
        }

        if (str_contains($text, 'diploma three')) {
            $requested[] = 'D3';
        }

        if (str_contains($text, 'diploma four') || str_contains($text, 'applied bachelor')) {
            $requested[] = 'D4';
        }

        if (str_contains($text, 'profesi')) {
            $requested[] = 'Profesi';
        }

        if (str_contains($text, 'professional program')) {
            $requested[] = 'Profesi';
        }

        return array_values(array_unique($requested));
    }

    /**
     * @return list<string>
     */
    private function detectProgramStudiTopics(string $text): array
    {
        $topics = [
            'farmasi' => ['farmasi', 'pharmacy'],
            'fisioterapi' => ['fisioterapi', 'physiotherapy'],
            'rekam-medis' => ['rekam medis', 'rekam medis informasi kesehatan', 'rmik', 'medical records'],
            'tlm' => ['teknologi laboratorium medis', 'tlm', 'analis kesehatan', 'medical laboratory', 'laboratory technology'],
            'gizi' => ['gizi', 'dietisien', 'nutrition', 'dietitian'],
            'sanitasi' => ['sanitasi', 'kesehatan lingkungan', 'environmental health'],
            'kebidanan' => ['kebidanan', 'midwifery'],
            'keperawatan' => ['keperawatan', 'ners', 'nursing'],
            'promkes' => ['promosi kesehatan', 'health promotion'],
            'terapi-gigi' => ['terapi gigi', 'dental therapy'],
            'kesehatan-gigi' => ['kesehatan gigi', 'dental health'],
        ];

        $matches = [];

        foreach ($topics as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $matches[] = $topic;
                    break;
                }
            }
        }

        return array_values(array_unique($matches));
    }

    private function matchesProgramStudiTopic(string $name, string $topic): bool
    {
        $name = $this->normalizeForMatching($name);

        return match ($topic) {
            'farmasi' => str_contains($name, 'farmasi'),
            'fisioterapi' => str_contains($name, 'fisioterapi'),
            'rekam-medis' => str_contains($name, 'rekam medis'),
            'tlm' => str_contains($name, 'teknologi laboratorium') || str_contains($name, ' tlm'),
            'gizi' => str_contains($name, 'gizi') || str_contains($name, 'dietisien'),
            'sanitasi' => str_contains($name, 'sanitasi') || str_contains($name, 'kesehatan lingkungan'),
            'kebidanan' => str_contains($name, 'kebidanan') || str_contains($name, 'bidan'),
            'keperawatan' => str_contains($name, 'keperawatan') || str_contains($name, 'ners'),
            'promkes' => str_contains($name, 'promosi kesehatan'),
            'terapi-gigi' => str_contains($name, 'terapi gigi'),
            'kesehatan-gigi' => str_contains($name, 'kesehatan gigi'),
            default => false,
        };
    }

    private function programStudiTopicLabel(string $topic, string $language = 'id'): string
    {
        if ($this->isEnglish($language)) {
            return match ($topic) {
                'farmasi' => 'Pharmacy',
                'fisioterapi' => 'Physiotherapy',
                'rekam-medis' => 'Medical Records',
                'tlm' => 'Medical Laboratory Technology',
                'gizi' => 'Nutrition',
                'sanitasi' => 'Sanitation / Environmental Health',
                'kebidanan' => 'Midwifery',
                'keperawatan' => 'Nursing',
                'promkes' => 'Health Promotion',
                'terapi-gigi' => 'Dental Therapy',
                'kesehatan-gigi' => 'Dental Health',
                default => 'that study program',
            };
        }

        return match ($topic) {
            'farmasi' => 'Farmasi',
            'fisioterapi' => 'Fisioterapi',
            'rekam-medis' => 'Rekam Medis',
            'tlm' => 'Teknologi Laboratorium Medis',
            'gizi' => 'Gizi',
            'sanitasi' => 'Sanitasi / Kesehatan Lingkungan',
            'kebidanan' => 'Kebidanan',
            'keperawatan' => 'Keperawatan',
            'promkes' => 'Promosi Kesehatan',
            'terapi-gigi' => 'Terapi Gigi',
            'kesehatan-gigi' => 'Kesehatan Gigi',
            default => 'program studi tersebut',
        };
    }

    private function normalizeLanguage(string $language): string
    {
        return $language === 'en' ? 'en' : 'id';
    }

    private function detectLakipYearFromPageContext(string $pagePath): ?string
    {
        $pagePath = trim($pagePath);
        if ($pagePath === '') {
            return null;
        }

        foreach (LakipDocumentCatalog::documents() as $year => $document) {
            $routePath = $this->normalizeForMatching(route($document['main_route_name'], [], false));
            if ($routePath !== '' && $pagePath === $routePath) {
                return $year;
            }
        }

        if (str_contains($pagePath, 'lakip-2024')) {
            return '2024';
        }

        if (str_contains($pagePath, 'lakip-2023')) {
            return '2023';
        }

        if (str_contains($pagePath, 'lakip')) {
            return '2025';
        }

        return null;
    }

    /**
     * @return array<string, array{headline: string, summary: string, highlights: list<string>}>
     */
    private function lakipSummaries(): array
    {
        return [
            '2025' => [
                'headline' => 'LAKIP 2025 Poltekkes Kemenkes Bandung dalam format HTML.',
                'summary' => 'Halaman ini merangkum pertanggungjawaban kinerja Direktur dan seluruh jajaran dalam satu tahun anggaran, dengan tren capaian yang secara umum berada di atas target.',
                'highlights' => [
                    'Dokumen menonjolkan penguatan keuangan BLU, akademik, SDM, penelitian, pengabdian masyarakat, dan kelembagaan.',
                    'Halaman web menuliskan ulang isi utama dokumen resmi agar lebih mudah dibaca tanpa membuka PDF.',
                ],
            ],
            '2024' => [
                'headline' => 'LAKIP 2024 Poltekkes Kemenkes Bandung dalam format HTML.',
                'summary' => 'Halaman ini menjelaskan pelaksanaan program, capaian indikator kinerja utama, dan evaluasi hasil institusi selama tahun 2024.',
                'highlights' => [
                    'Capaian agregat yang ditonjolkan di halaman adalah 219,79%.',
                    'Sebanyak 16 indikator melampaui target, sementara realisasi anggaran menjadi fokus pembenahan periode berikutnya.',
                ],
            ],
            '2023' => [
                'headline' => 'LAKIP 2023 Poltekkes Kemenkes Bandung dalam format HTML.',
                'summary' => 'Halaman ini menampilkan laporan pertanggungjawaban tahunan atas pelaksanaan tugas dan fungsi institusi selama tahun 2023.',
                'highlights' => [
                    'Sebanyak 18 dari 19 indikator mencapai atau melampaui target.',
                    'Sorotan kuat ada pada pendapatan BLU, modernisasi layanan, kelulusan UKOM, riset prioritas, dan prestasi mahasiswa.',
                ],
            ],
        ];
    }

    /**
     * @return array{answer: string, sources: list<string>, model: string}
     */
    private function buildLakipPageAnswer(string $year, string $language): array
    {
        $document = LakipDocumentCatalog::forYear($year);
        $summary = $this->lakipSummaries()[$year] ?? null;

        if ($summary === null) {
            return $this->buildLakipGeneralAnswer($language);
        }

        $path = route($document['main_route_name'], [], false);

        if ($this->isEnglish($language)) {
            $answer = implode(' ', [
                "This page is {$summary['headline']}",
                $summary['summary'],
                'Key highlights: ' . implode(' ', $summary['highlights']),
                "The page path is {$path} if you want to revisit this LAKIP year specifically.",
            ]);
        } else {
            $answer = implode(' ', [
                "Halaman ini memuat {$summary['headline']}",
                $summary['summary'],
                'Sorotan utamanya: ' . implode(' ', $summary['highlights']),
                "Jika ingin membuka lagi dokumen tahun ini, rutenya adalah {$path}.",
            ]);
        }

        return [
            'answer' => $answer,
            'sources' => [$this->lakipSourceLabel($language, $year)],
            'model' => 'website-data',
        ];
    }

    /**
     * @return array{answer: string, sources: list<string>, model: string}
     */
    private function buildLakipGeneralAnswer(string $language): array
    {
        $documents = collect(LakipDocumentCatalog::documents())
            ->sortKeysDesc()
            ->map(function (array $document, string $year) {
                return $document['label'] . ' (' . route($document['main_route_name'], [], false) . ')';
            })
            ->values()
            ->all();

        if ($this->isEnglish($language)) {
            $answer = 'LAKIP is the Government Agency Performance Accountability Report. On the Poltekkes Bandung website, LAKIP pages summarize institutional performance, key indicators, and follow-up actions for each year. Available pages: ' . implode('; ', $documents) . '.';
        } else {
            $answer = 'LAKIP adalah Laporan Kinerja Instansi Pemerintah. Di website Poltekkes Bandung, halaman LAKIP merangkum akuntabilitas kinerja, indikator utama, capaian, dan tindak lanjut tiap tahun. Halaman yang tersedia saat ini: ' . implode('; ', $documents) . '.';
        }

        return [
            'answer' => $answer,
            'sources' => [$this->lakipSourceLabel($language)],
            'model' => 'website-data',
        ];
    }

    private function isEnglish(string $language): bool
    {
        return $this->normalizeLanguage($language) === 'en';
    }

    private function languageSystemInstruction(string $language): string
    {
        return $this->isEnglish($language)
            ? 'Answer fully in English. Keep the tone warm, professional, and concise. Do not switch back to Indonesian unless the user asks for it.'
            : 'Jawab sepenuhnya dalam Bahasa Indonesia. Gunakan nada yang hangat, profesional, dan ringkas. Jangan beralih ke English kecuali pengguna memintanya.';
    }

    private function websiteDataSourceLabel(string $language): string
    {
        return $this->isEnglish($language)
            ? 'Live website study program data'
            : 'Daftar program studi aktif website';
    }

    private function lakipSourceLabel(string $language, ?string $year = null): string
    {
        if ($this->isEnglish($language)) {
            return $year ? "Poltekkes Bandung LAKIP {$year} page" : 'Poltekkes Bandung LAKIP pages';
        }

        return $year ? "Halaman LAKIP {$year} Poltekkes Bandung" : 'Halaman LAKIP Poltekkes Bandung';
    }

    private function liveSnapshotSystemMessage(string $context, string $language): string
    {
        return $this->isEnglish($language)
            ? "LIVE POLTEKKES BANDUNG APPLICATION SNAPSHOT (this is generated directly from the current application state and must be treated as the newest source if it conflicts with older documents):\n{$context}"
            : "SNAPSHOT APLIKASI POLTEKKES BANDUNG TERKINI (dibuat langsung dari kondisi aplikasi saat ini dan harus diprioritaskan jika bertentangan dengan dokumen yang lebih lama):\n{$context}";
    }

    private function normalizeForMatching(string $text): string
    {
        $text = mb_strtolower($text);
        $text = str_replace(['&', '/', '-', '_'], ' ', $text);
        return preg_replace('/\s+/', ' ', trim($text)) ?? '';
    }
}
