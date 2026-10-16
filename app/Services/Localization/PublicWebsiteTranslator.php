<?php

namespace App\Services\Localization;

use App\Models\TranslationCache;
use App\Services\AI\AiClient;
use App\Support\PublicLocale;
use DOMDocument;
use DOMElement;
use DOMText;
use DOMXPath;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublicWebsiteTranslator
{
    private const CACHE_PREFIX = 'public-website-translation:v2';

    public function __construct(private readonly AiClient $aiClient)
    {
    }

    public function translateHtml(string $html, string $targetLocale): string
    {
        $targetLocale = PublicLocale::normalize($targetLocale);

        if (! $this->shouldTranslate($targetLocale) || trim($html) === '') {
            return $html;
        }

        // Protect Alpine.js attributes starting with @ from being stripped by DOMDocument
        $html = preg_replace('/(\s)@([a-zA-Z0-9_\-\.]+)([\s=>])/i', '$1data-alpine-at-$2$3', $html);

        $dom = $this->loadHtml($html);

        if (! $dom) {
            return $html;
        }

        $texts = [];
        $seen = [];

        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//text()[normalize-space() != ""]') ?: [] as $node) {
            if (! $node instanceof DOMText) {
                continue;
            }

            if (! $this->isTranslatableTextNode($node)) {
                continue;
            }

            $text = $node->wholeText;

            if (! $this->shouldTranslateText($text) || isset($seen[$text])) {
                continue;
            }

            $texts[] = $text;
            $seen[$text] = true;
        }

        foreach ($xpath->query('//*') ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            foreach ($this->translatableAttributesFor($node) as $attribute) {
                if (! $node->hasAttribute($attribute)) {
                    continue;
                }

                $value = $node->getAttribute($attribute);

                if (! $this->shouldTranslateText($value) || isset($seen[$value])) {
                    continue;
                }

                $texts[] = $value;
                $seen[$value] = true;
            }
        }

        if ($texts === []) {
            return $html;
        }

        $translations = $this->translateTexts($texts, $targetLocale);

        foreach ($xpath->query('//text()[normalize-space() != ""]') ?: [] as $node) {
            if (! $node instanceof DOMText || ! $this->isTranslatableTextNode($node)) {
                continue;
            }

            $text = $node->wholeText;

            if (isset($translations[$text]) && $translations[$text] !== '') {
                $node->replaceData(0, mb_strlen($text), $translations[$text]);
            }
        }

        foreach ($xpath->query('//*') ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            foreach ($this->translatableAttributesFor($node) as $attribute) {
                if (! $node->hasAttribute($attribute)) {
                    continue;
                }

                $value = $node->getAttribute($attribute);

                if (isset($translations[$value]) && $translations[$value] !== '') {
                    $node->setAttribute($attribute, $translations[$value]);
                }
            }
        }

        $translatedHtml = $dom->saveHTML();

        if (! is_string($translatedHtml) || $translatedHtml === '') {
            return $html;
        }

        $translatedHtml = preg_replace('/^<\?xml encoding="UTF-8"\?>/i', '', $translatedHtml) ?? $translatedHtml;

        // Restore Alpine.js attributes
        return preg_replace('/(\s)data-alpine-at-([a-zA-Z0-9_\-\.]+)([\s=>])/i', '$1@$2$3', $translatedHtml) ?? $translatedHtml;
    }

    public function translateData(mixed $value, string $targetLocale): mixed
    {
        return $this->translateStructuredData($value, $targetLocale);
    }

    public function translateText(string $text, string $targetLocale): string
    {
        $targetLocale = PublicLocale::normalize($targetLocale);

        if (! $this->shouldTranslate($targetLocale)) {
            return $text;
        }

        $results = $this->translateTexts([$text], $targetLocale);

        return $results[$text] ?? $text;
    }

    private function shouldTranslate(string $targetLocale): bool
    {
        return (bool) config('ai.website_translation.enabled', true)
            && ! PublicLocale::isDefault($targetLocale)
            && array_key_exists($targetLocale, PublicLocale::supported());
    }

    private function loadHtml(string $html): ?DOMDocument
    {
        $internalErrors = libxml_use_internal_errors(true);

        try {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $loaded = $dom->loadHTML('<?xml encoding="UTF-8"?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_COMPACT);

            return $loaded ? $dom : null;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);
        }
    }

    private function isTranslatableTextNode(DOMText $node): bool
    {
        $parent = $node->parentNode;

        if (! $parent instanceof DOMElement) {
            return false;
        }

        if ($this->isExcludedElement($parent)) {
            return false;
        }

        return ! $this->hasExcludedAncestor($parent);
    }

    private function hasExcludedAncestor(DOMElement $element): bool
    {
        $current = $element;

        while ($current = $current->parentNode) {
            if (! $current instanceof DOMElement) {
                continue;
            }

            if ($this->isExcludedElement($current)) {
                return true;
            }
        }

        return false;
    }

    private function isExcludedElement(DOMElement $element): bool
    {
        $excludedTags = [
            'code',
            'iframe',
            'kbd',
            'math',
            'noscript',
            'pre',
            'script',
            'style',
            'svg',
            'textarea',
        ];

        if (in_array(strtolower($element->tagName), $excludedTags, true)) {
            return true;
        }

        return $element->hasAttribute('data-no-translate');
    }

    /**
     * @return array<int, string>
     */
    private function translatableAttributesFor(DOMElement $element): array
    {
        $attributes = ['placeholder', 'title', 'alt', 'aria-label'];

        if (strtolower($element->tagName) === 'meta') {
            $name = strtolower($element->getAttribute('name'));
            $property = strtolower($element->getAttribute('property'));

            if (in_array($name, ['description', 'keywords'], true)
                || in_array($property, ['og:title', 'og:description', 'twitter:title', 'twitter:description'], true)) {
                $attributes[] = 'content';
            }
        }

        if (strtolower($element->tagName) === 'input') {
            $type = strtolower($element->getAttribute('type'));

            if (in_array($type, ['button', 'reset', 'submit'], true)) {
                $attributes[] = 'value';
            }
        }

        return $attributes;
    }

    private function shouldTranslateText(string $text): bool
    {
        $trimmed = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        if ($trimmed === '' || mb_strlen($trimmed) > (int) config('ai.website_translation.max_text_length', 800)) {
            return false;
        }

        if (preg_match('/^[\p{N}\p{P}\p{S}\s]+$/u', $trimmed) === 1) {
            return false;
        }

        if (filter_var($trimmed, FILTER_VALIDATE_URL)) {
            return false;
        }

        if (filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (preg_match('#^[a-z0-9][a-z0-9_.\/-]*$#', $trimmed) === 1 && preg_match('#[-_\/]#', $trimmed) === 1) {
            return false;
        }

        return true;
    }

    private function translateStructuredData(mixed $value, string $targetLocale, int|string|null $contextKey = null): mixed
    {
        $targetLocale = PublicLocale::normalize($targetLocale);

        if (! $this->shouldTranslate($targetLocale)) {
            return $value;
        }

        if ($this->shouldBypassDataKey($contextKey)) {
            return $value;
        }

        $texts = [];
        $this->collectStructuredDataStrings($value, $texts, $contextKey);

        if ($texts === []) {
            return $value;
        }

        $translations = $this->translateTexts(array_values(array_unique($texts)), $targetLocale);

        return $this->replaceStructuredDataStrings($value, $translations, $contextKey);
    }

    /**
     * @param  array<int, string>  $texts
     */
    private function collectStructuredDataStrings(mixed $value, array &$texts, int|string|null $contextKey = null): void
    {
        if ($this->shouldBypassDataKey($contextKey)) {
            return;
        }

        if (is_string($value)) {
            if ($this->shouldTranslateText($value)) {
                $texts[] = $value;
            }

            return;
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $this->collectStructuredDataStrings($item, $texts, $key);
            }

            return;
        }

        if (is_object($value)) {
            foreach ($value as $key => $item) {
                $this->collectStructuredDataStrings($item, $texts, $key);
            }
        }
    }

    private function replaceStructuredDataStrings(mixed $value, array $translations, int|string|null $contextKey = null): mixed
    {
        if ($this->shouldBypassDataKey($contextKey)) {
            return $value;
        }

        if (is_string($value)) {
            return $translations[$value] ?? $value;
        }

        if (is_array($value)) {
            $translated = [];

            foreach ($value as $key => $item) {
                $translated[$key] = $this->replaceStructuredDataStrings($item, $translations, $key);
            }

            return $translated;
        }

        if (is_object($value)) {
            foreach ($value as $key => $item) {
                $value->{$key} = $this->replaceStructuredDataStrings($item, $translations, $key);
            }
        }

        return $value;
    }

    private function shouldBypassDataKey(int|string|null $key): bool
    {
        if (! is_string($key)) {
            return false;
        }

        return in_array(strtolower($key), [
            'code',
            'email',
            'email_href',
            'embed_url',
            'external',
            'file',
            'href',
            'icon',
            'id',
            'image',
            'image_url',
            'key',
            'kode',
            'map_embed_url',
            'path',
            'phone_href',
            'property',
            'qris_link',
            'slug',
            'src',
            'target',
            'target_blank',
            'type',
            'url',
            'value',
            'video',
        ], true);
    }

    /**
     * @param  array<int, string>  $texts
     * @return array<string, string>
     */
    private function translateTexts(array $texts, string $targetLocale): array
    {
        $results = [];
        $pending = [];
        // L1 cache: keep hot strings in memory for 1 year (DB is permanent source of truth)
        $ttl = now()->addYear();

        foreach (array_values(array_unique($texts)) as $text) {
            $glossary = $this->glossaryTranslation($text, $targetLocale);

            if ($glossary !== null) {
                $results[$text] = $glossary;
                $this->storeCachedTranslation($this->cacheKey($targetLocale, $text), $glossary, $ttl, $targetLocale, $text);
                continue;
            }

            $cacheKey = $this->cacheKey($targetLocale, $text);
            $cached = $this->getCachedTranslation($cacheKey, $targetLocale, $text);

            if ($cached !== null) {
                $results[$text] = $cached;
                continue;
            }

            $pending[] = $text;
        }

        if ($pending === []) {
            return $results;
        }

        foreach ($this->chunkTexts($pending) as $chunk) {
            $translatedChunk = $this->requestTranslations($chunk, $targetLocale);

            foreach ($chunk as $index => $sourceText) {
                $translatedText = $translatedChunk[$index] ?? $sourceText;

                if (trim($translatedText) === trim($sourceText) && $this->shouldRetryUnchangedItem($sourceText, $targetLocale)) {
                    $translatedText = $this->translateSingleText($sourceText, $targetLocale);
                }

                $results[$sourceText] = $translatedText;
                $this->storeCachedTranslation($this->cacheKey($targetLocale, $sourceText), $translatedText, $ttl, $targetLocale, $sourceText);
            }
        }

        return $results;
    }

    private function getCachedTranslation(string $cacheKey, string $targetLocale, string $sourceText): ?string
    {
        try {
            $glossary = $this->glossaryTranslation($sourceText, $targetLocale);

            // ── L1: Laravel cache (hot path) ──────────────────────────────
            $cached = Cache::get($cacheKey);

            if (is_string($cached) && $glossary !== null && trim($cached) !== trim($glossary)) {
                Cache::forget($cacheKey);
                $cached = null;
            }

            if (is_string($cached)
                && trim($cached) === trim($sourceText)
                && ($this->shouldRetryUnchangedItem($sourceText, $targetLocale)
                    || $glossary !== null)) {
                Cache::forget($cacheKey);
                $cached = null;
            }

            if (is_string($cached) && $cached !== '') {
                return $cached;
            }

            // ── L2: Permanent DB store ─────────────────────────────────────
            $dbTranslation = TranslationCache::findTranslation($targetLocale, $sourceText);

            if ($dbTranslation !== null) {
                // Warm L1 cache from DB so next hit is instant
                Cache::put($cacheKey, $dbTranslation, now()->addYear());
                return $dbTranslation;
            }

            return null;
        } catch (Throwable $e) {
            Log::warning('Public website translation cache read failed.', [
                'locale' => $targetLocale,
                'text'   => $sourceText,
                'error'  => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function storeCachedTranslation(mixed $cacheKey, string $translatedText, mixed $ttl, string $targetLocale, string $sourceText): void
    {
        try {
            // L1: Laravel cache
            Cache::put($cacheKey, $translatedText, $ttl);

            // L2: Permanent DB store (no expiry)
            TranslationCache::storeTranslation($targetLocale, $sourceText, $translatedText, 'ai');
        } catch (Throwable $e) {
            Log::warning('Public website translation cache write failed.', [
                'locale' => $targetLocale,
                'text'   => $sourceText,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    private function translateSingleTextFallback(string $text, string $targetLocale): string
    {
        $glossaryTranslation = $this->glossaryTranslation($text, $targetLocale);

        if ($glossaryTranslation !== null) {
            return $glossaryTranslation;
        }

        try {
            $language = $targetLocale === 'en' ? 'English' : (PublicLocale::supported()[$targetLocale] ?? $targetLocale);
            $response = $this->aiClient->chat([
                [
                    'role' => 'system',
                    'content' => implode("\n", [
                        'You translate short public website labels.',
                        'Translate the user text into ' . $language . '.',
                        'Return only the translated text with no quotes, JSON, or explanation.',
                        'Keep proper names, URLs, and email addresses unchanged when they appear.',
                    ]),
                ],
                [
                    'role' => 'user',
                    'content' => $text,
                ],
            ], 0.1, 120);

            $translated = trim($response, "\" \n\r\t");

            return $translated !== '' ? $translated : $text;
        } catch (Throwable) {
            return $text;
        }
    }

    private function glossaryTranslation(string $text, string $targetLocale): ?string
    {
        if ($targetLocale !== 'en') {
            return null;
        }

        $prefix = '';
        $suffix = '';

        if (preg_match('/^\s+/u', $text, $matches) === 1) {
            $prefix = $matches[0];
        }

        if (preg_match('/\s+$/u', $text, $matches) === 1) {
            $suffix = $matches[0];
        }

        $trimmed = trim($text);
        $punctuation = '';
        $core = $trimmed;

        if (preg_match('/^(.*?)([.!?:]+)$/u', $trimmed, $matches) === 1) {
            $core = trim($matches[1]);
            $punctuation = $matches[2];
        }

        $glossary = [
            'Hubungi Kami' => 'Contact Us',
            'Informasi Kontak' => 'Contact Information',
            'Alamat' => 'Address',
            'Telepon' => 'Phone',
            'Jam Operasional' => 'Operating Hours',
            'Kirim Pesan' => 'Send Message',
            'Nama Lengkap' => 'Full Name',
            'Topik' => 'Topic',
            'Subjek' => 'Subject',
            'Pesan' => 'Message',
            'Wajib diisi' => 'Required',
            'Layanan Online' => 'Online Services',
            'Buka di Google Maps' => 'Open in Google Maps',
            'Beranda' => 'Home',
            'Program Studi' => 'Study Programs',
            'Program Studi Lainnya' => 'Other Study Programs',
            'Lihat Semua Program →' => 'View All Programs →',
            'Lihat detail →' => 'View details →',
            'Portal Mahasiswa' => 'Student Portal',
            'Login Admin' => 'Admin Login',
            'Menu Utama' => 'Main Menu',
            'Tautan Cepat' => 'Quick Links',
            'Tentang' => 'About',
            'Kebijakan Privasi' => 'Privacy Policy',
            'Syarat & Ketentuan' => 'Terms & Conditions',
            'Berita Terbaru' => 'Latest News',
            'Pengumuman' => 'Announcements',
            'Mahasiswa Berprestasi' => 'Outstanding Students',
            'Buka panduan halaman' => 'Open page guide',
            'Tutup panduan' => 'Close guide',
            'Help halaman ini' => 'Help for this page',
            'Scroll untuk explore' => 'Scroll to explore',
            'Tampilan' => 'Display',
            'Klik kartu ini untuk toggling' => 'Click this card to toggle',
            'Cek Jurusan' => 'Check Study Programs',
            'Pertanyaan' => 'Question',
            'Semua' => 'All',
            'Belum dipilih' => 'Not selected yet',
            'Daftar Online' => 'Register Online',
            'Daftar Sekarang' => 'Register Now',
            'Tanya Info' => 'Ask for Information',
            'Pilih Jalanmu' => 'Choose Your Path',
            'Daftarkan diri & unggah portofoliomu untuk berpeluang terpilih' => 'Register and upload your portfolio for a chance to be selected',
            'Semua 20 Prodi' => 'All 20 Study Programs',

            // ── Homepage Statistics Block (SiteAppearance::defaultHome) ────
            'Statistik Kampus' => 'Campus Statistics',
            'STATISTIK KAMPUS' => 'CAMPUS STATISTICS',
            'Dalam Angka' => 'In Numbers',
            'Alumni Aktif' => 'Active Alumni',
            'Tahun Berdiri' => 'Years Founded',
            'Kelulusan' => 'Graduation Rate',
            'Program Studi' => 'Study Programs',

            // ── Homepage Hero ──────────────────────────────────────────────
            'Lihat Prodi' => 'View Programs',
            'Wujudkan Impian' => 'Realize Your Dream',
            'Profesional' => 'Professional',
            'Program D3 & D4 terakreditasi Unggul. Bergabung bersama 15.000+ alumni mengabdi di seluruh Indonesia.' => 'Accredited D3 & D4 programs. Join 15,000+ alumni serving across Indonesia.',
            'Penerimaan {year}/{next_year} Dibuka' => 'Admission {year}/{next_year} Open',
            'Scroll untuk explore' => 'Scroll to explore',

            // ── Homepage Quiz Block ────────────────────────────────────────
            'Prodi cocok untuk kamu' => 'Find Your Study Program',
            '2 Pilihan Prodi Untukmu' => '2 Recommended Programs for You',
            'Kamu boleh mendaftar 2 prodi sekaligus di SPMB' => 'You may apply to 2 programs simultaneously in SPMB',
            'Semua Prodi' => 'All Programs',
            'Coba Lagi' => 'Try Again',
            'Temukan program yang sesuai dengan minat dan impianmu sebagai tenaga kesehatan profesional' => 'Find a program that matches your interests and dreams as a health professional',
            'Lihat Semua Prodi →' => 'View All Programs →',
            // Quiz questions
            'Skenario kerja mana yang paling menggambarkan impianmu?' => 'Which work scenario best describes your dream?',
            'Pendekatan yang paling mencerminkan caramu bekerja:' => 'The approach that best reflects how you work:',
            'Lingkungan kerja yang paling kamu bayangkan:' => 'The work environment you envision most:',
            'Dari keempat area ini, mana yang paling menarik bagimu?' => 'Of these four areas, which interests you most?',
            // Quiz answer options
            '🏥 Merawat & mendampingi pasien langsung di RS/klinik' => '🏥 Caring for & assisting patients directly at hospitals/clinics',
            '🤱 Mendampingi ibu hamil, bersalin & tumbuh kembang bayi' => '🤱 Accompanying pregnant women, childbirth & infant development',
            '🔬 Menganalisis sampel lab, meramu obat, atau konseling gizi' => '🔬 Analyzing lab samples, compounding medicine, or nutrition counseling',
            '🌿 Menyehatkan masyarakat & menjaga lingkungan hidup' => '🌿 Promoting community health & protecting the environment',
            '💉 Prosedur & tindakan klinis langsung pada pasien' => '💉 Clinical procedures & direct patient interventions',
            '🧪 Analisis, diagnosis penunjang & riset di laboratorium' => '🧪 Analysis, supporting diagnostics & lab research',
            '📢 Edukasi, komunikasi & perubahan perilaku masyarakat' => '📢 Education, communication & community behavior change',
            '🦷 Spesialisasi mendalam: gigi, gizi klinis, atau farmasi' => '🦷 Deep specialization: dental, clinical nutrition, or pharmacy',
            '🏥 Rumah Sakit / Klinik / Puskesmas' => '🏥 Hospital / Clinic / Health Center',
            '🔬 Laboratorium / Apotek / Riset' => '🔬 Laboratory / Pharmacy / Research',
            '🌍 Komunitas / Lapangan / Posyandu' => '🌍 Community / Field / Posyandu',
            '🏫 Kampus / Pelatihan / Penyuluhan' => '🏫 Campus / Training / Health Education',
            '💊 Farmasi & Obat-obatan' => '💊 Pharmacy & Medicines',
            '🫀 Anatomi & Fisiologi Tubuh' => '🫀 Body Anatomy & Physiology',
            '🌱 Kesehatan Lingkungan & Gaya Hidup' => '🌱 Environmental Health & Lifestyle',
            '🧬 Mikrobiologi & Patologi Klinik' => '🧬 Microbiology & Clinical Pathology',

            // ── Homepage News & Announcements ─────────────────────────────
            'Info & kegiatan kampus' => 'Campus news & activities',
            'Berita Terbaru' => 'Latest News',
            'Belum ada pengumuman.' => 'No announcements yet.',
            'Apresiasi pilihan mahasiswa dengan capaian terbaik yang sedang ditayangkan.' => 'Recognizing students with outstanding achievements currently on display.',

            // ── Homepage CTA ───────────────────────────────────────────────
            'Siap Jadi' => 'Ready to Be',
            'Nakes Hebat?' => 'a Great Healthcare Professional?',
            'Daftarkan diri sebelum kuota penuh!' => 'Register before slots are full!',
            'TA {year}/{next_year}' => 'AY {year}/{next_year}',

            // ── Programs & Gallery ─────────────────────────────────────────
            'Pilih Jalanmu' => 'Choose Your Path',
            'Lihat semua 20 program studi →' => 'View all 20 study programs →',
            'Aktivitas Kampus' => 'Campus Activities',
            'Lihat semua foto' => 'View all photos',
            'Perbesar' => 'Enlarge',

            // ── Newsletter ─────────────────────────────────────────────────
            'Dapatkan Info Terbaru Poltekkes Bandung' => 'Get the Latest Poltekkes Bandung Updates',
            'Berita, pengumuman & jadwal PPDB langsung di inbox.' => 'News, announcements & admissions schedule straight to your inbox.',
            'Berhasil! Cek inbox kamu.' => 'Success! Check your inbox.',

            // ── Lihat Prodi description ────────────────────────────────────
            'Nakes' => 'Healthcare Professional',
            'Lihat semua' => 'View all',
            'D4 Sarjana Terapan' => 'D4 Applied Bachelor',
            'Profesi' => 'Professional Program',
            'Pojok Alumni' => 'Alumni Corner',
            'Penerimaan Mahasiswa' => 'Student Admissions',
            'Kebijakan Privasi' => 'Privacy Policy',
            'Syarat & Ketentuan' => 'Terms & Conditions',
            'Newsletter' => 'Newsletter',
            'Langganan' => 'Subscribe',
            'Dapatkan informasi pengumuman, agenda, dan program kampus terbaru langsung ke email Anda.' => 'Receive the latest announcements, agenda, and campus programs directly to your email.',

            // ── Navbar & Sidebar ──────────────────────────────────────────
            'Cari menu, jurusan, unit, HIMA...' => 'Search menus, departments, units, HIMA...',
            'Cari menu' => 'Search menu',
            'Buka navigasi situs' => 'Open site navigation',
            'Tutup navigasi situs' => 'Close site navigation',
            'Navigasi utama' => 'Main navigation',
            'Daftar seluruh menu situs' => 'All site menu items',
            'Ganti Tema Gelap/Terang' => 'Toggle Dark/Light Theme',
            'Ganti Tema' => 'Toggle Theme',
            'Bahasa' => 'Language',
            'Lompat ke konten utama' => 'Skip to main content',

            // ── Footer ───────────────────────────────────────────────────
            'Pengaduan / Kritik / Saran / Keluhan' => 'Complaints / Feedback / Suggestions',
            'Sampaikan masukan Anda kepada unit atau program studi terkait. Setiap laporan akan ditindaklanjuti.' => 'Share your feedback with the relevant unit or study program. Every report will be followed up.',
            'Kirim Sekarang' => 'Submit Now',

            // ── Complaint / Feedback Form (Public) ───────────────────────
            'Formulir Pengaduan / Kritik / Saran / Keluhan' => 'Complaint / Feedback / Suggestion Form',
            'Formulir Pengaduan / Kritik / Saran / Keluhan — Poltekkes Bandung' => 'Complaint / Feedback / Suggestion Form — Poltekkes Bandung',
            'Sampaikan pengaduan, kritik, saran, atau keluhan Anda kepada unit-unit di Politeknik Kesehatan Bandung.' => 'Submit your complaints, criticism, suggestions, or feedback to units at Poltekkes Bandung.',
            'Sampaikan pengaduan, kritik, saran, atau keluhan Anda kepada unit/bagian terkait di Poltekkes Bandung.' => 'Submit your complaints, criticism, suggestions, or feedback to the relevant unit/department at Poltekkes Bandung.',
            'Setiap masukan akan ditindaklanjuti sesuai prosedur.' => 'Every submission will be followed up according to procedure.',
            'Terdapat kesalahan pada formulir' => 'There are errors in the form',
            'Jenis Pesan' => 'Message Type',
            'Pengaduan' => 'Complaint',
            'Kritik' => 'Criticism',
            'Saran' => 'Suggestion',
            'Keluhan' => 'Grievance',
            'Ditujukan kepada' => 'Addressed to',
            '— Pilih Unit/Bagian/Prodi Tujuan —' => '— Select Destination Unit/Department/Program —',
            'Subjek / Judul' => 'Subject / Title',
            'Contoh: Keluhan layanan perpustakaan...' => 'Example: Library service complaint...',
            'Isi Pesan' => 'Message Content',
            'Tuliskan pengaduan/kritik/saran/keluhan Anda secara jelas dan detail...' => 'Write your complaint/criticism/suggestion/feedback clearly and in detail...',
            'Kirim sebagai Anonim' => 'Send Anonymously',
            'Identitas Anda tidak akan disimpan atau ditampilkan' => 'Your identity will not be saved or displayed',
            'Nomor Telepon' => 'Phone Number',
            '(opsional)' => '(optional)',
            '08xx-xxxx-xxxx' => '08xx-xxxx-xxxx',
            'Informasi Penting' => 'Important Information',
            'Pesan yang masuk akan ditinjau oleh unit terkait dalam 3 hari kerja.' => 'Messages will be reviewed by the relevant unit within 3 business days.',
            'Simpan Nomor Tiket yang diberikan setelah pengiriman untuk memantau status.' => 'Keep the Ticket Number provided after submission to track the status.',
            'Mohon sampaikan pesan dengan bahasa yang sopan dan konstruktif.' => 'Please communicate your message in polite and constructive language.',
            'Kirim Pengaduan / Saran' => 'Submit Complaint / Feedback',
            'Politeknik Kesehatan Bandung · Formulir ini terenkripsi dan aman' => 'Poltekkes Bandung · This form is encrypted and secure',

            // ── Destination Groups ────────────────────────────────────────
            'Pimpinan' => 'Leadership',
            'Bagian' => 'Division',
            'Unit Layanan' => 'Service Unit',
            'Unit Bisnis' => 'Business Unit',
            'Unit Bahasa' => 'Language Unit',
            'Unit Asrama' => 'Dormitory Unit',
            'Unit Perpustakaan Terpadu' => 'Integrated Library',
            'Unit Laboratorium Terpadu' => 'Integrated Laboratory',
            'Unit Teknologi Informasi' => 'Information Technology Unit',
            'Unit Humas dan Kerjasama' => 'Public Relations & Cooperation Unit',
            'Unit Pengembangan Kompetensi' => 'Competency Development Unit',
            'Bidang Akademik (Wadir I)' => 'Academic Division (Vice Director I)',
            'Bidang Administrasi Umum (Wadir II)' => 'General Administration Division (Vice Director II)',
            'Bidang Kemahasiswaan (Wadir III)' => 'Student Affairs Division (Vice Director III)',
            'Bagian Administrasi Umum' => 'General Administration Department',
            'Bidang OSDM' => 'HR & Organization Division',
            'Satuan Pengawas Internal (SPI)' => 'Internal Supervisory Unit (SPI)',
            'Pusat Penjaminan Mutu' => 'Quality Assurance Center',
            'Pusat Penelitian & Pengabmas' => 'Research & Community Service Center',
            'Pusat Pengembangan Pendidikan' => 'Education Development Center',

            // ── Success Page ──────────────────────────────────────────────
            'Pengaduan Berhasil Dikirim — Poltekkes Bandung' => 'Submission Successful — Poltekkes Bandung',
            'Terima Kasih!' => 'Thank You!',
            'Pengaduan / saran Anda telah kami terima dan akan segera ditindaklanjuti oleh unit terkait.' => 'Your complaint / suggestion has been received and will be followed up by the relevant unit.',
            'Nomor Tiket Anda' => 'Your Ticket Number',
            'Simpan nomor tiket ini untuk memantau status pengaduan Anda.' => 'Save this ticket number to track your complaint status.',
            'Kembali ke Beranda' => 'Back to Home',
            'Kirim Pengaduan Lain' => 'Submit Another',
            'Selanjutnya apa?' => 'What happens next?',
            'Unit terkait akan meninjau pengaduan Anda dalam 3 hari kerja.' => 'The relevant unit will review your complaint within 3 business days.',
            'Anda dapat menggunakan nomor tiket di atas untuk menanyakan status langsung ke unit terkait.' => 'You can use the ticket number above to inquire about the status directly with the relevant unit.',
            'Mohon simpan nomor tiket ini untuk referensi.' => 'Please keep this ticket number for reference.',

            // ── General Navigation & UI ────────────────────────────────────
            'Berita' => 'News',
            'Profil' => 'Profile',
            'Kontak' => 'Contact',
            'Sejarah' => 'History',
            'Visi & Misi' => 'Vision & Mission',
            'Struktur Organisasi' => 'Organizational Structure',
            'Akreditasi' => 'Accreditation',
            'Galeri' => 'Gallery',
            'Agenda & Kalender' => 'Agenda & Calendar',
            'Pojok Alumni' => 'Alumni Corner',
            'Layanan' => 'Services',
            'Publikasi dan Penelitian' => 'Publications & Research',
            'Pengabmas/Pengabdian kepada Masyarakat' => 'Community Service',
            'Layanan Produk dan Bisnis' => 'Business & Product Services',
            'Fasilitas Asrama' => 'Dormitory Facilities',
            'Koperasi' => 'Cooperative',
            'Layanan dan Tarif Lab' => 'Lab Services & Rates',
            'Koleksi Perpustakaan' => 'Library Collection',
            'Dokumen Mutu' => 'Quality Documents',
            'Layanan IT' => 'IT Services',
            'Program Pelatihan' => 'Training Programs',
            'SIPANDU' => 'SIPANDU',
            'Pengembangan Kurikulum' => 'Curriculum Development',
            'Interprofessional Education' => 'Interprofessional Education',
            'Pusat Unggulan IPTEK' => 'Center of Excellence in Science & Technology',
            'Baca selengkapnya' => 'Read more',
            'Lihat semua' => 'View all',
            'Kembali' => 'Back',
            'Simpan' => 'Save',
            'Batal' => 'Cancel',
            'Hapus' => 'Delete',
            'Edit' => 'Edit',
            'Tambah' => 'Add',
            'Cari' => 'Search',
            'Reset' => 'Reset',
            'Lihat' => 'View',
            'Tutup' => 'Close',
            'Buka' => 'Open',

            // ── Unit Pages ────────────────────────────────────────────────
            'Direktur' => 'Director',
            'Bidang Akademik' => 'Academic Division',
            'Bidang Administrasi Umum' => 'General Administration Division',
            'Bidang Kemahasiswaan' => 'Student Affairs Division',
            'Satuan Pengawas Internal' => 'Internal Supervisory Unit',
            'Pusat Litmas & Pengabmas' => 'Research & Community Service Center',
            'Pusat Penelitian & Pengabmas' => 'Research & Community Service Center',
            'Pusat Pengembangan Pendidikan' => 'Education Development Center',
            'Bidang OSDM' => 'HR & Organization Division',
            'BEM KM Poltekkes Bandung' => 'BEM KM Poltekkes Bandung',

            // ── Unit page common sections ──────────────────────────────────
            'Sub Unit' => 'Sub Unit',
            'Kegiatan Terbaru' => 'Latest Activities',
            'Informasi' => 'Information',
            'Kontak Unit' => 'Unit Contact',
            'Struktur' => 'Structure',
            'Program Kerja' => 'Work Programs',
            'Dokumen' => 'Documents',
            'Tautan' => 'Links',
            'Jadwal' => 'Schedule',
            'Pengumuman Unit' => 'Unit Announcements',

            // ── Concierge / AI ───────────────────────────────────────────
            'Asisten Virtual' => 'Virtual Assistant',
            'Ketik pertanyaan Anda...' => 'Type your question...',
            'Kirim' => 'Send',
            'Tanya AI' => 'Ask AI',

            // ── Common phrases ────────────────────────────────────────────
            'Tidak ada data' => 'No data available',
            'Memuat...' => 'Loading...',
            'Berhasil' => 'Successful',
            'Gagal' => 'Failed',
            'Silakan coba lagi' => 'Please try again',
            'Data berhasil disimpan' => 'Data saved successfully',
            'Data berhasil dihapus' => 'Data deleted successfully',
        ];

        $translated = $glossary[$trimmed] ?? $glossary[$core] ?? null;

        if ($translated === null) {
            return null;
        }

        return $prefix . $translated . $punctuation . $suffix;
    }

    /**
     * @param  array<int, string>  $texts
     * @return array<int, string>
     */
    private function retryShortLabelBatch(array $texts, string $targetLocale): array
    {
        try {
            $language = $targetLocale === 'en' ? 'English' : (PublicLocale::supported()[$targetLocale] ?? $targetLocale);
            $response = $this->aiClient->chat([
                [
                    'role' => 'system',
                    'content' => implode("\n", [
                        'You translate short public website labels.',
                        'Input is a JSON array of short labels or button texts.',
                        'Translate each item into ' . $language . '.',
                        'Return a JSON array with the same order and item count.',
                        'Do not add explanations or markdown fences.',
                    ]),
                ],
                [
                    'role' => 'user',
                    'content' => json_encode(array_values($texts), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ], 0.1, 600);

            $decoded = $this->decodeTranslationResponse($response);

            if (count($decoded) !== count($texts)) {
                return $texts;
            }

            return array_map(static function (mixed $item, int $index) use ($texts): string {
                return is_string($item) && trim($item) !== '' ? $item : $texts[$index];
            }, $decoded, array_keys($decoded));
        } catch (Throwable) {
            return $texts;
        }
    }

    /**
     * @param  array<int, string>  $texts
     * @return array<int, array<int, string>>
     */
    private function chunkTexts(array $texts): array
    {
        $maxItems = (int) config('ai.website_translation.batch_size', 20);
        $maxChars = (int) config('ai.website_translation.max_chars_per_batch', 2800);

        $chunks = [];
        $currentChunk = [];
        $currentLength = 0;

        foreach ($texts as $text) {
            $textLength = mb_strlen($text);

            if ($currentChunk !== [] && (count($currentChunk) >= $maxItems || ($currentLength + $textLength) > $maxChars)) {
                $chunks[] = $currentChunk;
                $currentChunk = [];
                $currentLength = 0;
            }

            $currentChunk[] = $text;
            $currentLength += $textLength;
        }

        if ($currentChunk !== []) {
            $chunks[] = $currentChunk;
        }

        return $chunks;
    }

    /**
     * @param  array<int, string>  $texts
     * @return array<int, string>
     */
    private function requestTranslations(array $texts, string $targetLocale): array
    {
        if (count($texts) === 1) {
            return [$this->translateSingleText($texts[0], $targetLocale)];
        }

        try {
            $response = $this->aiClient->chat([
                [
                    'role' => 'system',
                    'content' => $this->systemPrompt($targetLocale),
                ],
                [
                    'role' => 'user',
                    'content' => json_encode(array_values($texts), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ], 0.2, 2200);

            $decoded = $this->decodeTranslationResponse($response);

            if (count($decoded) !== count($texts)) {
                return $this->fallbackTranslationBatch($texts, $targetLocale, $response);
            }

            if (count($texts) > 1 && $this->looksUntranslatedBatch($texts, $decoded)) {
                return $this->splitAndTranslateBatch($texts, $targetLocale);
            }

            $resolved = array_map(static function (mixed $item, int $index) use ($texts): string {
                return is_string($item) && trim($item) !== '' ? $item : $texts[$index];
            }, $decoded, array_keys($decoded));

            if (count($texts) > 1) {
                $retryIndexes = [];
                $retryTexts = [];

                foreach ($resolved as $index => $translatedText) {
                    $sourceText = $texts[$index] ?? null;

                    if (! is_string($sourceText)) {
                        continue;
                    }

                    if (trim($translatedText) !== trim($sourceText) || ! $this->shouldRetryUnchangedItem($sourceText, $targetLocale)) {
                        continue;
                    }

                    $retryIndexes[] = $index;
                    $retryTexts[] = $sourceText;
                }

                if ($retryTexts !== []) {
                    $retriedTexts = count($retryTexts) > 1
                        ? $this->retryShortLabelBatch($retryTexts, $targetLocale)
                        : [$this->translateSingleText($retryTexts[0], $targetLocale)];

                    foreach ($retryIndexes as $offset => $index) {
                        $sourceText = $retryTexts[$offset];
                        $candidate = $retriedTexts[$offset] ?? $sourceText;

                        if (trim($candidate) === trim($sourceText)) {
                            $candidate = $this->translateSingleTextFallback($sourceText, $targetLocale);
                        }

                        $resolved[$index] = $candidate;
                    }
                }
            }

            return $resolved;
        } catch (Throwable $e) {
            if (count($texts) > 1) {
                return $this->splitAndTranslateBatch($texts, $targetLocale);
            }

            Log::warning('Public website translation failed.', [
                'locale' => $targetLocale,
                'error' => $e->getMessage(),
            ]);

            return $texts;
        }
    }

    /**
     * @return array<int, mixed>
     */
    private function decodeTranslationResponse(string $response): array
    {
        $trimmed = trim($response);

        if (preg_match('/```(?:json)?\s*(\[.*\])\s*```/su', $trimmed, $matches) === 1) {
            $trimmed = $matches[1];
        }

        $decoded = json_decode($trimmed, true);

        if (is_array($decoded)) {
            return array_values($decoded);
        }

        return [];
    }

    /**
     * @param  array<int, string>  $texts
     * @return array<int, string>
     */
    private function fallbackTranslationBatch(array $texts, string $targetLocale, string $response): array
    {
        if (count($texts) === 1) {
            $single = trim($response);

            if ($single === '') {
                return [$this->translateSingleText($texts[0], $targetLocale)];
            }

            $decoded = json_decode($single, true);

            if (is_string($decoded) && trim($decoded) !== '') {
                return [$decoded];
            }

            $trimmedSingle = trim($single, "\" \n\r\t");

            if ($trimmedSingle !== '' && $trimmedSingle !== $texts[0]) {
                return [$trimmedSingle];
            }

            return [$this->translateSingleText($texts[0], $targetLocale)];
        }

        return $this->splitAndTranslateBatch($texts, $targetLocale);
    }

    /**
     * @param  array<int, string>  $texts
     * @return array<int, string>
     */
    private function splitAndTranslateBatch(array $texts, string $targetLocale): array
    {
        if (count($texts) === 1) {
            return [$this->translateSingleText($texts[0], $targetLocale)];
        }

        $midpoint = max(1, intdiv(count($texts), 2));

        return [
            ...$this->requestTranslations(array_slice($texts, 0, $midpoint), $targetLocale),
            ...$this->requestTranslations(array_slice($texts, $midpoint), $targetLocale),
        ];
    }

    /**
     * @param  array<int, string>  $texts
     * @param  array<int, mixed>  $decoded
     */
    private function looksUntranslatedBatch(array $texts, array $decoded): bool
    {
        foreach ($texts as $index => $text) {
            if (! is_string($decoded[$index] ?? null)) {
                return false;
            }

            if (trim((string) $decoded[$index]) !== trim($text)) {
                return false;
            }
        }

        return true;
    }

    private function translateSingleText(string $text, string $targetLocale): string
    {
        try {
            $response = $this->aiClient->chat([
                [
                    'role' => 'system',
                    'content' => $this->singleTextSystemPrompt($targetLocale),
                ],
                [
                    'role' => 'user',
                    'content' => $text,
                ],
            ], 0.1, 300);

            $translated = trim($response);

            if (preg_match('/```(?:text)?\s*(.*?)\s*```/su', $translated, $matches) === 1) {
                $translated = trim($matches[1]);
            }

            $translated = trim($translated, "\" \n\r\t");

            if (($translated === '' || trim($translated) === trim($text)) && $this->shouldRetryUnchangedItem($text, $targetLocale)) {
                return $this->translateSingleTextFallback($text, $targetLocale);
            }

            return $translated !== '' ? $translated : $text;
        } catch (Throwable $e) {
            Log::warning('Public single-text translation failed.', [
                'locale' => $targetLocale,
                'text' => $text,
                'error' => $e->getMessage(),
            ]);

            return $this->translateSingleTextFallback($text, $targetLocale);
        }
    }

    private function shouldRetryUnchangedItem(string $text, string $targetLocale): bool
    {
        if ($targetLocale !== 'en' || ! $this->shouldTranslateText($text)) {
            return false;
        }

        $normalized = mb_strtolower(trim($text));

        if ($normalized === '') {
            return false;
        }

        $indonesianTokens = [
            'yang',
            'dan',
            'dengan',
            'dari',
            'pada',
            'merupakan',
            'adalah',
            'sebagai',
            'oleh',
            'dalam',
            'kesehatan',
            'kementerian',
            'sejarah',
            'visi',
            'misi',
            'struktur',
            'organisasi',
            'akreditasi',
            'berprestasi',
            'tahun',
            'tanggal',
            'keputusan',
            'menteri',
            'tentang',
            'akun',
            'alamat',
            'anda',
            'beranda',
            'belum',
            'berita',
            'buka',
            'coba',
            'daftar',
            'detail',
            'dipilih',
            'email',
            'fakultas',
            'form',
            'hasil',
            'halaman',
            'hubungi',
            'informasi',
            'jam',
            'jurusan',
            'kami',
            'kampus',
            'klik',
            'kontak',
            'kirim',
            'lihat',
            'mahasiswa',
            'operasional',
            'panduan',
            'pendaftaran',
            'pengumuman',
            'pesan',
            'pilih',
            'pilihan',
            'portofolio',
            'portofoliomu',
            'program',
            'prodi',
            'sekarang',
            'semua',
            'soal',
            'studi',
            'tampilan',
            'telepon',
            'terpilih',
            'terbaru',
            'ulang',
            'unggah',
            'untuk',
            'wajib',
        ];

        foreach ($indonesianTokens as $token) {
            if (preg_match('/(^|\b)' . preg_quote($token, '/') . '(\b|$)/u', $normalized) === 1) {
                return true;
            }
        }

        if (preg_match('/^\p{Lu}[\p{L}\p{M}\s.-]+$/u', $text) === 1 && ! str_contains($normalized, ' ')) {
            return false;
        }

        return false;
    }

    private function systemPrompt(string $targetLocale): string
    {
        $language = $targetLocale === 'en' ? 'English' : PublicLocale::supported()[$targetLocale];

        return implode("\n", [
            'You localize public website copy.',
            'Translate each string into ' . $language . '.',
            'Input is a JSON array of strings. Output must be a JSON array with the same item order and item count.',
            'Keep URLs, emails, phone numbers, codes, and product names unchanged unless they are ordinary descriptive words.',
            'Preserve placeholders, punctuation, emojis, and formatting intent.',
            'Do not wrap the JSON in markdown fences.',
        ]);
    }

    private function singleTextSystemPrompt(string $targetLocale): string
    {
        $language = $targetLocale === 'en' ? 'English' : PublicLocale::supported()[$targetLocale];

        return implode("\n", [
            'You translate one website UI text string.',
            'Translate the user text into ' . $language . '.',
            'Return only the translated text.',
            'Do not add quotes, JSON, notes, or explanations.',
            'Preserve product names, URLs, emails, and punctuation when they appear.',
        ]);
    }

    private function cacheKey(string $targetLocale, string $text): string
    {
        return implode(':', [
            self::CACHE_PREFIX,
            $targetLocale,
            sha1($text),
        ]);
    }
}