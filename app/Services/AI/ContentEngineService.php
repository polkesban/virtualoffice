<?php

namespace App\Services\AI;

use App\Models\Berita;
use App\Models\ContentJob;
use App\Support\AiProviderModelResolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AI Content Engine Service.
 *
 * Pipeline:
 *   1. Vision API describes the uploaded photo/video thumbnail
 *   2. Article generator produces a structured Bahasa Indonesia news article
 *   3. Caption generator produces an Instagram-ready caption + hashtags
 *   4. Results saved back to the content job record
 */
class ContentEngineService
{
    public function __construct(private readonly AiClient $ai) {}

    // ── Main pipeline ──────────────────────────────────────────────────────────

    public function process(ContentJob $job): void
    {
        $job->update(['status' => 'processing', 'error_message' => null]);

        try {
            $description = '';

            // Step 1 — Describe the image with vision AI
            if ($job->media_type === 'image') {
                $imagePath = storage_path('app/' . $job->media_path);
                if (file_exists($imagePath)) {
                    $description = $this->describeImage($imagePath, $job->metadata);
                }
            } else {
                // For video, use the admin-provided metadata as description
                $meta = $job->metadata;
                $description = "Video acara: {$meta['subject']} di {$meta['place']} pada {$meta['date']}.";
            }

            // Steps 2 & 3 — Generate article and caption concurrently
            // (sequential here; swap for async dispatch if needed)
            $article = $this->generateArticle($job, $description);
            $caption = $this->generateCaption($job, $description, $article['title']);
            $model = AiProviderModelResolver::chatModel(AiProviderModelResolver::activeProvider());

            $job->update([
                'status' => 'done',
                'ai_image_description' => $description,
                'generated_title' => $article['title'],
                'generated_article' => $article['body'],
                'generated_caption' => $caption['caption'],
                'generated_hashtags' => $caption['hashtags'],
                'model_used' => $model,
            ]);
        } catch (\Throwable $e) {
            $job->update(['status' => 'error', 'error_message' => $e->getMessage()]);
            Log::error('[ContentEngine] process failed', ['job' => $job->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Generate raw HTML content from a free-form prompt (used by AI block type in page builder).
     */
    public function generateRawHtml(string $prompt): string
    {
        return $this->ai->chat([
            ['role' => 'system', 'content' => 'Anda adalah asisten penulis konten website untuk institusi pendidikan tinggi. Tulis dalam Bahasa Indonesia, profesional dan informatif.'],
            ['role' => 'user', 'content' => $prompt],
        ], 0.7, 1500);
    }

    /**
     * Generate structured draft content for page builders.
     *
     * @param array{acara: string, tempat?: string, tanggal?: string, catatan?: string, context_title?: string} $meta
     * @return array{heading?: string, sub?: string, konten?: string}
     */
    public function generateBuilderDraft(string $type, array $meta, ?string $mediaPath = null): array
    {
        $type = trim($type) !== '' ? trim($type) : 'ai_teks';

        $visualContext = $this->buildBuilderVisualContext($meta, $mediaPath);
        $contextTitle = !empty($meta['context_title'])
            ? "\nKonteks halaman/seksi: {$meta['context_title']}"
            : '';

        if (in_array($type, ['statistik', 'tabel', 'tim', 'formulir'], true)) {
            $prompt = $this->buildStructuredBuilderPrompt($type, $meta, $contextTitle, $visualContext);

            $raw = $this->ai->chat([
                ['role' => 'system', 'content' => 'Anda adalah perancang konten website institusi pendidikan tinggi. Balas hanya dengan JSON valid sesuai format yang diminta, tanpa markdown dan tanpa teks tambahan.'],
                ['role' => 'user', 'content' => $prompt],
            ], 0.45, 1800);

            $parsed = $this->parseJsonBlock($raw);

            return $this->normalizeStructuredBuilderDraft($type, $parsed);
        }

        if ($type === 'hero') {
            $prompt = sprintf(
                "Buatkan copy hero website institusi pendidikan tinggi dalam Bahasa Indonesia.\n\nTopik/Subjek: %s\n%s%s%s%s%s\n\nBalas HANYA dalam JSON valid dengan format {\"heading\":\"...\",\"sub\":\"...\"}. Ketentuan: heading maksimal 10 kata, kuat dan formal; sub 1-2 kalimat, maksimal 45 kata; jangan gunakan markdown.",
                $meta['acara'] ?? '',
                !empty($meta['tempat']) ? "Tempat: {$meta['tempat']}\n" : '',
                !empty($meta['tanggal']) ? "Tanggal: {$meta['tanggal']}\n" : '',
                !empty($meta['catatan']) ? "Catatan: {$meta['catatan']}\n" : '',
                $contextTitle,
                $visualContext
            );

            $raw = $this->ai->chat([
                ['role' => 'system', 'content' => 'Anda adalah asisten copywriter website kampus. Tulis singkat, kuat, profesional, dan natural dalam Bahasa Indonesia.'],
                ['role' => 'user', 'content' => $prompt],
            ], 0.7, 600);

            $parsed = $this->parseJsonBlock($raw);
            $fallback = Str::of(strip_tags($raw))->squish()->toString();

            return [
                'heading' => trim((string) ($parsed['heading'] ?? $parsed['judul'] ?? Str::limit($fallback, 60, ''))),
                'sub' => trim((string) ($parsed['sub'] ?? $parsed['deskripsi'] ?? $fallback)),
            ];
        }

        $prompt = sprintf(
            "Buatkan draft HTML profesional untuk halaman website institusi pendidikan tinggi dalam Bahasa Indonesia.\n\nJenis konten: %s\nTopik/Subjek: %s\n%s%s%s%s%s\n\nBalas HANYA dalam JSON valid dengan format {\"konten\":\"<p>...</p>\"}. Gunakan HTML rapi dengan tag <p>, <h3>, <ul>, <li> bila perlu. Tidak boleh ada markdown atau penjelasan tambahan. Maksimal 500 kata.",
            $type,
            $meta['acara'] ?? '',
            !empty($meta['tempat']) ? "Tempat: {$meta['tempat']}\n" : '',
            !empty($meta['tanggal']) ? "Tanggal: {$meta['tanggal']}\n" : '',
            !empty($meta['catatan']) ? "Catatan: {$meta['catatan']}\n" : '',
            $contextTitle,
            $visualContext
        );

        $raw = $this->ai->chat([
            ['role' => 'system', 'content' => 'Anda adalah asisten penulis konten website untuk institusi pendidikan tinggi. Tulis dalam Bahasa Indonesia, profesional, informatif, dan mudah dipindai.'],
            ['role' => 'user', 'content' => $prompt],
        ], 0.7, 1500);

        $parsed = $this->parseJsonBlock($raw);

        return [
            'konten' => trim((string) ($parsed['konten'] ?? $parsed['html'] ?? $raw)),
        ];
    }

    /**
     * Generate HTML block content, optionally using a media file for vision context.
     * Used by the AI block type in the page builder section form.
     *
     * @param array{acara: string, tempat?: string, tanggal?: string, catatan?: string} $meta
     * @param string|null $mediaPath Absolute path to a temporary uploaded image/video
     */
    public function generateBlockHtml(array $meta, ?string $mediaPath = null): string
    {
        return $this->generateBuilderDraft('ai_teks', $meta, $mediaPath)['konten'] ?? '';
    }

    /**
     * Publish a completed ContentJob as a Berita record.
     */
    public function publish(ContentJob $job): Berita
    {
        $metadata = $job->metadata;

        $slug = Str::slug($job->generated_title) . '-' . $job->id;

        $berita = Berita::create([
            'user_id' => $job->user_id,
            'judul' => $job->generated_title,
            'slug' => $slug,
            'ringkasan' => Str::limit(strip_tags($job->generated_article), 200),
            'isi' => $job->generated_article,
            'kategori' => $metadata['kategori'] ?? 'berita',
            'tags' => $job->generated_hashtags ?? [],
            'status' => 'published',
            'published_at' => now(),
            'gambar_sampul' => $this->copyMediaToPublicStorage($job),
            'meta_description' => Str::limit(strip_tags($job->generated_article), 155),
        ]);

        $job->update(['status' => 'published', 'published_at' => now()]);

        return $berita;
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function describeImage(string $imagePath, array $metadata): string
    {
        $prompt = $this->loadPrompt('content-vision');

        $contextLines = array_filter([
            isset($metadata['place']) ? "Lokasi: {$metadata['place']}" : null,
            isset($metadata['date']) ? "Tanggal: {$metadata['date']}" : null,
            isset($metadata['subject']) ? "Acara/Subjek: {$metadata['subject']}" : null,
            isset($metadata['notes']) && $metadata['notes'] ? "Catatan: {$metadata['notes']}" : null,
        ]);

        $contextBlock = $contextLines
            ? "\n\nKonteks tambahan dari admin:\n" . implode("\n", $contextLines)
            : '';

        return $this->ai->vision($imagePath, $prompt . $contextBlock, 0.4);
    }

    private function buildBuilderVisualContext(array $meta, ?string $mediaPath = null): string
    {
        if (! ($mediaPath && file_exists($mediaPath))) {
            return '';
        }

        $visionPrompt = 'Deskripsikan isi foto ini secara detail untuk keperluan penulisan konten website institusi pendidikan tinggi. Fokus pada siapa yang ada di foto, kegiatan yang dilakukan, suasana, dan elemen visual yang relevan.';

        $contextLines = array_filter([
            !empty($meta['tempat']) ? "Lokasi: {$meta['tempat']}" : null,
            !empty($meta['tanggal']) ? "Tanggal: {$meta['tanggal']}" : null,
            !empty($meta['acara']) ? "Acara/Subjek: {$meta['acara']}" : null,
            !empty($meta['catatan']) ? "Catatan: {$meta['catatan']}" : null,
        ]);

        if ($contextLines) {
            $visionPrompt .= "\n\nKonteks dari admin:\n" . implode("\n", $contextLines);
        }

        return "\nDeskripsi visual media yang diunggah: " . $this->ai->vision($mediaPath, $visionPrompt, 0.4);
    }

    private function buildStructuredBuilderPrompt(string $type, array $meta, string $contextTitle, string $visualContext): string
    {
        $context = sprintf(
            "Topik/Subjek: %s\n%s%s%s%s%s",
            $meta['acara'] ?? '',
            !empty($meta['tempat']) ? "Tempat/Unit: {$meta['tempat']}\n" : '',
            !empty($meta['tanggal']) ? "Tanggal/Periode: {$meta['tanggal']}\n" : '',
            !empty($meta['catatan']) ? "Arahan Tambahan: {$meta['catatan']}\n" : '',
            $contextTitle,
            $visualContext
        );

        return match ($type) {
            'statistik' => "Susun blok statistik untuk halaman website institusi pendidikan tinggi.\n\n{$context}\n\nBalas HANYA JSON valid dengan format {\"items\":[{\"nilai\":\"120+\",\"label\":\"Mahasiswa aktif\",\"ikon\":\"📊\",\"warna\":\"green\"}]}. Ketentuan: buat 3 sampai 6 item, setiap label maksimal 4 kata, nilai singkat dan mudah dipindai, ikon emoji relevan, warna hanya boleh blue, green, purple, red, orange, atau teal.",
            'tabel' => "Susun draft tabel informasi untuk halaman website institusi pendidikan tinggi.\n\n{$context}\n\nBalas HANYA JSON valid dengan format {\"header\":[\"Kolom 1\",\"Kolom 2\"],\"baris\":[[\"A\",\"B\"],[\"C\",\"D\"]],\"dengan_header\":true,\"bergaris\":true}. Ketentuan: 3 sampai 6 kolom, 3 sampai 8 baris, isi setiap sel berupa string pendek, jangan gunakan markdown table.",
            'tim' => "Susun draft blok tim/personil untuk halaman website institusi pendidikan tinggi.\n\n{$context}\n\nBalas HANYA JSON valid dengan format {\"items\":[{\"nama\":\"Koordinator Layanan\",\"jabatan\":\"Ketua Tim\",\"foto\":\"\",\"bio\":\"...\"}]}. Ketentuan: buat 2 sampai 6 item. Jika nama asli tidak diketahui, gunakan nama berbasis peran atau posisi, bukan nama orang fiktif yang terlalu spesifik. Kolom foto boleh dikosongkan.",
            'formulir' => "Susun draft blok formulir untuk halaman website institusi pendidikan tinggi.\n\n{$context}\n\nBalas HANYA JSON valid dengan format {\"fields\":[{\"nama\":\"Nama Lengkap\",\"tipe\":\"text\",\"wajib\":true}],\"email_tujuan\":\"\",\"pesan_sukses\":\"Terima kasih, data Anda telah kami terima.\"}. Ketentuan: buat 3 sampai 7 field yang relevan dengan topik, tipe hanya boleh text, email, number, textarea, select, atau tel. Jika email tujuan tidak diketahui, kosongkan saja.",
            default => $context,
        };
    }

    private function normalizeStructuredBuilderDraft(string $type, array $payload): array
    {
        return match ($type) {
            'statistik' => $this->normalizeStatistikDraft($payload),
            'tabel' => $this->normalizeTableDraft($payload),
            'tim' => $this->normalizeTeamDraft($payload),
            'formulir' => $this->normalizeFormDraft($payload),
            default => [],
        };
    }

    private function normalizeStatistikDraft(array $payload): array
    {
        $allowedColors = ['blue', 'green', 'purple', 'red', 'orange', 'teal'];
        $items = [];

        foreach (($payload['items'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $nilai = trim((string) ($item['nilai'] ?? ''));
            $label = trim((string) ($item['label'] ?? ''));

            if ($nilai === '' || $label === '') {
                continue;
            }

            $warna = trim((string) ($item['warna'] ?? 'green'));
            $items[] = [
                'nilai' => Str::limit($nilai, 24, ''),
                'label' => Str::limit($label, 36, ''),
                'ikon' => trim((string) ($item['ikon'] ?? '📊')) ?: '📊',
                'warna' => in_array($warna, $allowedColors, true) ? $warna : 'green',
            ];

            if (count($items) >= 6) {
                break;
            }
        }

        if ($items === []) {
            $items = [
                ['nilai' => '0', 'label' => 'Indikator', 'ikon' => '📊', 'warna' => 'green'],
            ];
        }

        return ['items' => $items];
    }

    private function normalizeTableDraft(array $payload): array
    {
        $header = [];

        foreach (($payload['header'] ?? []) as $column) {
            $column = trim((string) $column);
            if ($column === '') {
                continue;
            }

            $header[] = Str::limit($column, 40, '');

            if (count($header) >= 6) {
                break;
            }
        }

        if ($header === []) {
            $header = ['Kolom 1', 'Kolom 2', 'Kolom 3'];
        }

        $columnCount = count($header);
        $rows = [];

        foreach (($payload['baris'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $cells = array_values($row);
            $normalized = [];

            for ($index = 0; $index < $columnCount; $index++) {
                $normalized[] = Str::limit(trim((string) ($cells[$index] ?? '')), 60, '');
            }

            if (implode('', $normalized) === '') {
                continue;
            }

            $rows[] = $normalized;

            if (count($rows) >= 8) {
                break;
            }
        }

        if ($rows === []) {
            $rows = [array_fill(0, $columnCount, '')];
        }

        return [
            'header' => $header,
            'baris' => $rows,
            'dengan_header' => (bool) ($payload['dengan_header'] ?? true),
            'bergaris' => (bool) ($payload['bergaris'] ?? true),
        ];
    }

    private function normalizeTeamDraft(array $payload): array
    {
        $items = [];

        foreach (($payload['items'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $nama = trim((string) ($item['nama'] ?? ''));
            $jabatan = trim((string) ($item['jabatan'] ?? ''));
            $bio = trim((string) ($item['bio'] ?? ''));
            $foto = trim((string) ($item['foto'] ?? ''));

            if ($nama === '' && $jabatan === '') {
                continue;
            }

            $items[] = [
                'nama' => Str::limit($nama ?: 'Personil', 60, ''),
                'jabatan' => Str::limit($jabatan ?: 'Tim', 60, ''),
                'foto' => filter_var($foto, FILTER_VALIDATE_URL) ? $foto : '',
                'bio' => Str::limit($bio, 220, ''),
            ];

            if (count($items) >= 6) {
                break;
            }
        }

        if ($items === []) {
            $items = [
                ['nama' => 'Koordinator Layanan', 'jabatan' => 'Ketua Tim', 'foto' => '', 'bio' => 'Ubah nama dan deskripsi sesuai personil yang sebenarnya.'],
            ];
        }

        return ['items' => $items];
    }

    private function normalizeFormDraft(array $payload): array
    {
        $allowedTypes = ['text', 'email', 'number', 'textarea', 'select', 'tel'];
        $fields = [];

        foreach (($payload['fields'] ?? []) as $field) {
            if (! is_array($field)) {
                continue;
            }

            $nama = trim((string) ($field['nama'] ?? ''));
            if ($nama === '') {
                continue;
            }

            $tipe = trim((string) ($field['tipe'] ?? 'text'));
            $fields[] = [
                'nama' => Str::limit($nama, 60, ''),
                'tipe' => in_array($tipe, $allowedTypes, true) ? $tipe : 'text',
                'wajib' => (bool) ($field['wajib'] ?? false),
            ];

            if (count($fields) >= 7) {
                break;
            }
        }

        if ($fields === []) {
            $fields = [
                ['nama' => 'Nama Lengkap', 'tipe' => 'text', 'wajib' => true],
                ['nama' => 'Email', 'tipe' => 'email', 'wajib' => true],
                ['nama' => 'Pesan', 'tipe' => 'textarea', 'wajib' => true],
            ];
        }

        $emailTujuan = trim((string) ($payload['email_tujuan'] ?? ''));

        return [
            'fields' => $fields,
            'email_tujuan' => filter_var($emailTujuan, FILTER_VALIDATE_EMAIL) ? $emailTujuan : '',
            'pesan_sukses' => trim((string) ($payload['pesan_sukses'] ?? 'Terima kasih, data Anda telah kami terima.')),
        ];
    }

    private function generateArticle(ContentJob $job, string $description): array
    {
        $meta = $job->metadata;
        $prompt = $this->loadPrompt('content-article');

        $userMsg = <<<MSG
        DESKRIPSI VISUAL FOTO/VIDEO:
        {$description}

        METADATA ACARA:
        - Tempat   : {$meta['place']}
        - Tanggal  : {$meta['date']}
        - Acara    : {$meta['subject']}
        - Kategori : {$meta['kategori']}
        - Catatan  : {$meta['notes']}

        Judul yang disarankan admin (boleh diabaikan): {$job->title}
        MSG;

        $raw = $this->ai->chat(
            [
                ['role' => 'system', 'content' => $prompt],
                ['role' => 'user', 'content' => $userMsg],
            ],
            config('ai.content_engine.temperature', 0.8),
            config('ai.content_engine.max_tokens', 2000),
        );

        $parsed = $this->parseJsonBlock($raw);

        return [
            'title' => $parsed['judul'] ?? ($job->title ?? 'Artikel Poltekkes Bandung'),
            'body' => $parsed['artikel'] ?? $raw,
        ];
    }

    private function generateCaption(ContentJob $job, string $description, string $articleTitle): array
    {
        $meta = $job->metadata;
        $prompt = $this->loadPrompt('content-caption');

        $userMsg = <<<MSG
        JUDUL ARTIKEL  : {$articleTitle}
        ACARA/SUBJEK   : {$meta['subject']}
        TEMPAT         : {$meta['place']}
        TANGGAL        : {$meta['date']}
        DESKRIPSI FOTO : {$description}
        TAGS TAMBAHAN  : {$meta['tags']}
        MSG;

        $raw = $this->ai->chat(
            [
                ['role' => 'system', 'content' => $prompt],
                ['role' => 'user', 'content' => $userMsg],
            ],
            config('ai.content_engine.temperature', 0.9),
            600,
        );

        $parsed = $this->parseJsonBlock($raw);

        return [
            'caption' => $parsed['caption'] ?? $raw,
            'hashtags' => $parsed['hashtags'] ?? [],
        ];
    }

    /**
     * Try to extract a JSON object from the AI response,
     * which may be wrapped in ```json code fences.
     */
    private function parseJsonBlock(string $raw): array
    {
        // Strip ```json ... ``` fences
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $raw, $m)) {
            $raw = $m[1];
        } elseif (preg_match('/(\{.*\})/s', $raw, $m)) {
            $raw = $m[1];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Copy the job's media file into public storage so Berita can serve it.
     */
    private function copyMediaToPublicStorage(ContentJob $job): ?string
    {
        $src = storage_path('app/' . $job->media_path);
        $ext = pathinfo($src, PATHINFO_EXTENSION);
        $dest = 'berita/ce-' . $job->id . '.' . $ext;
        $full = storage_path('app/public/' . $dest);

        if (file_exists($src)) {
            @mkdir(dirname($full), 0755, true);
            copy($src, $full);
            return $dest;
        }

        return null;
    }

    private function loadPrompt(string $name): string
    {
        $path = resource_path("prompts/{$name}.md");
        return file_exists($path) ? file_get_contents($path) : '';
    }
}
