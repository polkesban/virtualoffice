<?php

namespace App\Services\AI;

use App\Models\Agenda;
use App\Models\Berita;
use App\Models\HalamanStatis;
use App\Models\PageSection;
use App\Models\Pengumuman;
use App\Models\ProgramStudi;
use App\Models\UnitPage;
use App\Support\SiteAppearance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PollyKnowledgeSnapshot
{
    public function build(): array
    {
        $version = $this->version();
        $cacheKey = 'polly:live-knowledge:' . $version;

        $context = Cache::remember($cacheKey, now()->addMinutes(30), fn () => $this->renderSnapshot());

        return [
            'context' => $context,
            'source' => 'Snapshot data aplikasi Poltekkes Bandung',
            'version' => $version,
        ];
    }

    private function version(): string
    {
        $fingerprint = [
            'program_studi_count' => ProgramStudi::count(),
            'program_studi_updated_at' => (string) (ProgramStudi::max('updated_at') ?? ''),
            'berita_count' => Berita::count(),
            'berita_updated_at' => (string) (Berita::max('updated_at') ?? ''),
            'pengumuman_count' => Pengumuman::count(),
            'pengumuman_updated_at' => (string) (Pengumuman::max('updated_at') ?? ''),
            'agenda_count' => Agenda::count(),
            'agenda_updated_at' => (string) (Agenda::max('updated_at') ?? ''),
            'halaman_statis_count' => HalamanStatis::count(),
            'halaman_statis_updated_at' => (string) (HalamanStatis::max('updated_at') ?? ''),
            'unit_page_count' => UnitPage::count(),
            'unit_page_updated_at' => (string) (UnitPage::max('updated_at') ?? ''),
            'page_section_count' => PageSection::count(),
            'page_section_updated_at' => (string) (PageSection::max('updated_at') ?? ''),
            'prompt_mtime' => (string) (@filemtime(resource_path('prompts/concierge-system.md')) ?: 0),
            'routes_mtime' => (string) (@filemtime(base_path('routes/web.php')) ?: 0),
            'snapshot_mtime' => (string) (@filemtime(__FILE__) ?: 0),
        ];

        return sha1(json_encode($fingerprint, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'polly-live-knowledge');
    }

    private function renderSnapshot(): string
    {
        $unitSections = PageSection::query()
            ->where('aktif', true)
            ->whereNotNull('unit_page_id')
            ->orderBy('urutan')
            ->get()
            ->groupBy('unit_page_id');

        $staticSections = PageSection::query()
            ->where('aktif', true)
            ->whereNotNull('halaman_statis_id')
            ->orderBy('urutan')
            ->get()
            ->groupBy('halaman_statis_id');

        $sections = [
            '# Snapshot Pengetahuan Langsung Poltekkes Bandung',
            'Snapshot ini dibuat otomatis dari data aplikasi Poltekkes Bandung yang aktif saat ini. Prioritaskan data snapshot ini jika ada perbedaan dengan dokumen lama.',
            $this->renderSiteIdentity(),
            $this->renderProgramStudi(),
            $this->renderAnnouncements(),
            $this->renderNews(),
            $this->renderAgenda(),
            $this->renderStaticPages($staticSections),
            $this->renderUnitPages($unitSections),
        ];

        return collect($sections)
            ->map(fn ($section) => trim((string) $section))
            ->filter()
            ->implode("\n\n");
    }

    private function renderSiteIdentity(): string
    {
        $header = SiteAppearance::header();
        $footer = SiteAppearance::footer();
        $home = SiteAppearance::home();

        $quickLinks = collect($footer['quick_links'] ?? [])
            ->take(8)
            ->map(function (array $link) {
                $label = $this->cleanText($link['label'] ?? '', 80);
                $href = $this->normalizeHref($link['href'] ?? null);

                return $label !== '' && $href !== null ? $label . ': ' . $href : null;
            })
            ->filter()
            ->implode('; ');

        $socials = collect($header['socials'] ?? [])
            ->map(function ($url, $platform) {
                $label = $this->cleanText((string) $platform, 40);
                $href = $this->normalizeHref(is_string($url) ? $url : null);

                return $label !== '' && $href !== null ? $label . ': ' . $href : null;
            })
            ->filter()
            ->implode('; ');

        $lines = array_filter([
            '- Nama institusi: Politeknik Kesehatan Kemenkes Bandung (Poltekkes Bandung)',
            '- Deskripsi singkat: ' . $this->cleanText($home['description'] ?? '', 240),
            '- Alamat utama: ' . $this->cleanText($footer['address_text'] ?? $header['address_text'] ?? '', 180),
            '- Telepon utama: ' . $this->cleanText($footer['phone_text'] ?? '', 120),
            '- Email utama: ' . $this->cleanText($footer['email_text'] ?? '', 120),
            '- Google Maps: ' . ($this->normalizeHref($footer['map_link_url'] ?? $header['address_url'] ?? null) ?? '-'),
            $quickLinks !== '' ? '- Tautan cepat utama: ' . $quickLinks : null,
            $socials !== '' ? '- Akun sosial: ' . $socials : null,
        ]);

        return "## Identitas dan Kontak\n" . implode("\n", $lines);
    }

    private function renderProgramStudi(): string
    {
        $prodis = ProgramStudi::aktif()
            ->get(['nama', 'jenjang', 'slug', 'akreditasi', 'durasi_semester', 'kuota', 'deskripsi_singkat']);

        if ($prodis->isEmpty()) {
            return '';
        }

        $lines = $prodis->map(function (ProgramStudi $prodi) {
            $segments = [
                '[' . $prodi->jenjang . '] ' . $prodi->nama,
                'URL: ' . url('/program-studi/' . $prodi->slug),
            ];

            if (! empty($prodi->akreditasi)) {
                $segments[] = 'Akreditasi: ' . $this->cleanText((string) $prodi->akreditasi, 80);
            }

            if (! empty($prodi->durasi_semester)) {
                $segments[] = 'Durasi: ' . (int) $prodi->durasi_semester . ' semester';
            }

            if (! empty($prodi->kuota)) {
                $segments[] = 'Kuota: ' . (int) $prodi->kuota;
            }

            $summary = $this->cleanText((string) $prodi->deskripsi_singkat, 160);

            if ($summary !== '') {
                $segments[] = 'Ringkasan: ' . $summary;
            }

            return '- ' . implode(' | ', $segments);
        })->all();

        return "## Program Studi Aktif\n" . implode("\n", $lines);
    }

    private function renderAnnouncements(): string
    {
        $announcements = Pengumuman::aktif()
            ->limit(10)
            ->get(['judul', 'slug', 'isi', 'prioritas', 'tanggal_mulai', 'tanggal_selesai']);

        if ($announcements->isEmpty()) {
            return '';
        }

        $lines = $announcements->map(function (Pengumuman $item) {
            $schedule = $item->tanggal_mulai?->format('Y-m-d') ?? '-';
            if ($item->tanggal_selesai) {
                $schedule .= ' s.d. ' . $item->tanggal_selesai->format('Y-m-d');
            }

            $segments = [
                $item->judul,
                'URL: ' . url('/pengumuman'),
                'Periode: ' . $schedule,
                'Prioritas: ' . $this->cleanText((string) $item->prioritas, 40),
            ];

            $summary = $this->cleanText((string) $item->isi, 180);
            if ($summary !== '') {
                $segments[] = 'Isi singkat: ' . $summary;
            }

            return '- ' . implode(' | ', $segments);
        })->all();

        return "## Pengumuman Aktif\n" . implode("\n", $lines);
    }

    private function renderNews(): string
    {
        $news = Berita::published()
            ->terbaru()
            ->limit(10)
            ->get(['judul', 'slug', 'ringkasan', 'published_at']);

        if ($news->isEmpty()) {
            return '';
        }

        $lines = $news->map(function (Berita $item) {
            $segments = [
                $item->judul,
                'URL: ' . url('/berita/' . $item->slug),
                'Terbit: ' . ($item->published_at?->format('Y-m-d') ?? '-'),
            ];

            $summary = $this->cleanText((string) $item->ringkasan, 180);
            if ($summary !== '') {
                $segments[] = 'Ringkasan: ' . $summary;
            }

            return '- ' . implode(' | ', $segments);
        })->all();

        return "## Berita Terbaru\n" . implode("\n", $lines);
    }

    private function renderAgenda(): string
    {
        $agenda = Agenda::publik()
            ->akan()
            ->orderBy('tanggal_mulai')
            ->limit(10)
            ->get(['judul', 'deskripsi', 'tanggal_mulai', 'tanggal_selesai', 'waktu_mulai', 'waktu_selesai', 'lokasi', 'kategori']);

        if ($agenda->isEmpty()) {
            return '';
        }

        $lines = $agenda->map(function (Agenda $item) {
            $dateLabel = $item->tanggal_mulai?->format('Y-m-d') ?? '-';
            if ($item->tanggal_selesai && $item->tanggal_selesai->format('Y-m-d') !== $dateLabel) {
                $dateLabel .= ' s.d. ' . $item->tanggal_selesai->format('Y-m-d');
            }

            $timeLabel = trim(implode(' - ', array_filter([
                $this->cleanText((string) $item->waktu_mulai, 20),
                $this->cleanText((string) $item->waktu_selesai, 20),
            ])));

            $segments = [
                $item->judul,
                'URL: ' . url('/agenda'),
                'Tanggal: ' . $dateLabel,
            ];

            if ($timeLabel !== '') {
                $segments[] = 'Waktu: ' . $timeLabel;
            }

            if (! empty($item->lokasi)) {
                $segments[] = 'Lokasi: ' . $this->cleanText((string) $item->lokasi, 80);
            }

            if (! empty($item->kategori)) {
                $segments[] = 'Kategori: ' . (Agenda::$kategoriLabels[$item->kategori] ?? $item->kategori);
            }

            $summary = $this->cleanText((string) $item->deskripsi, 160);
            if ($summary !== '') {
                $segments[] = 'Ringkasan: ' . $summary;
            }

            return '- ' . implode(' | ', $segments);
        })->all();

        return "## Agenda Publik Mendatang\n" . implode("\n", $lines);
    }

    private function renderStaticPages(Collection $staticSections): string
    {
        $pages = HalamanStatis::query()
            ->whereNotIn('kunci', HalamanStatis::systemKeys())
            ->orderBy('judul')
            ->get(['id', 'kunci', 'judul', 'konten']);

        if ($pages->isEmpty()) {
            return '';
        }

        $lines = $pages->map(function (HalamanStatis $page) use ($staticSections) {
            $segments = [$page->judul];

            $url = $this->staticPageUrl($page->kunci);
            if ($url !== null) {
                $segments[] = 'URL: ' . $url;
            }

            $content = $this->cleanText((string) $page->konten, 180);
            if ($content !== '') {
                $segments[] = 'Ringkasan: ' . $content;
            }

            $sectionSummary = $this->summarizeSectionCollection($staticSections->get($page->id, collect()));
            if ($sectionSummary !== '') {
                $segments[] = 'Blok aktif: ' . $sectionSummary;
            }

            return '- ' . implode(' | ', $segments);
        })->all();

        return "## Halaman Statis Publik\n" . implode("\n", $lines);
    }

    private function renderUnitPages(Collection $unitSections): string
    {
        $pages = UnitPage::query()
            ->where('aktif', true)
            ->orderBy('judul')
            ->get(['id', 'role', 'slug', 'judul', 'sub_judul']);

        if ($pages->isEmpty()) {
            return '';
        }

        $lines = $pages->map(function (UnitPage $page) use ($unitSections) {
            $segments = [
                $page->judul,
                'URL: ' . $page->publicUrl(),
            ];

            $subtitle = $this->cleanText((string) $page->sub_judul, 160);
            if ($subtitle !== '') {
                $segments[] = 'Subjudul: ' . $subtitle;
            }

            $sectionSummary = $this->summarizeSectionCollection($unitSections->get($page->id, collect()));
            if ($sectionSummary !== '') {
                $segments[] = 'Blok aktif: ' . $sectionSummary;
            }

            return '- ' . implode(' | ', $segments);
        })->all();

        return "## Halaman Unit dan Portal Aktif\n" . implode("\n", $lines);
    }

    private function summarizeSectionCollection(Collection $sections): string
    {
        return $sections
            ->take(3)
            ->map(fn (PageSection $section) => $this->summarizeSection($section))
            ->filter()
            ->implode(' ; ');
    }

    private function summarizeSection(PageSection $section): ?string
    {
        $config = is_array($section->konfigurasi) ? $section->konfigurasi : [];
        $fragments = [];

        if (! empty($section->judul)) {
            $fragments[] = $section->judul;
        }

        $typeFragments = match ($section->tipe) {
            'hero' => [
                $config['heading'] ?? null,
                $config['sub'] ?? null,
            ],
            'teks', 'ai_teks' => [
                $config['konten'] ?? null,
            ],
            'statistik' => [
                collect($config['items'] ?? [])
                    ->take(4)
                    ->map(function (array $item) {
                        $label = $this->cleanText($item['label'] ?? '', 60);
                        $value = $this->cleanText((string) ($item['nilai'] ?? $item['target'] ?? ''), 30);

                        return trim($label . ($value !== '' ? ' ' . $value : ''));
                    })
                    ->filter()
                    ->implode('; '),
            ],
            'tabel' => [collect($config['header'] ?? [])->map(fn ($item) => $this->cleanText((string) $item, 40))->filter()->implode(', ')],
            'galeri_foto' => [
                collect($config['items'] ?? [])
                    ->take(3)
                    ->map(fn (array $item) => $this->cleanText((string) ($item['judul'] ?? $item['caption'] ?? ''), 80))
                    ->filter()
                    ->implode('; '),
            ],
            'produk' => [
                collect($config['items'] ?? [])
                    ->take(3)
                    ->map(function (array $item) {
                        $name = $this->cleanText((string) ($item['nama'] ?? ''), 80);
                        $description = $this->cleanText((string) ($item['deskripsi'] ?? ''), 100);

                        return trim($name . ($description !== '' ? ': ' . $description : ''));
                    })
                    ->filter()
                    ->implode('; '),
            ],
            'kuis' => [
                collect($config['pertanyaan'] ?? [])
                    ->take(2)
                    ->map(fn (array $item) => $this->cleanText((string) ($item['soal'] ?? ''), 100))
                    ->filter()
                    ->implode('; '),
            ],
            'polling' => [
                $config['pertanyaan'] ?? null,
            ],
            'formulir' => [
                collect($config['fields'] ?? [])
                    ->take(5)
                    ->map(fn (array $item) => $this->cleanText((string) ($item['nama'] ?? ''), 40))
                    ->filter()
                    ->implode(', '),
            ],
            'unduhan' => [
                collect($config['items'] ?? [])
                    ->take(4)
                    ->map(fn (array $item) => $this->cleanText((string) ($item['nama'] ?? ''), 60))
                    ->filter()
                    ->implode('; '),
            ],
            'google_drive' => [
                $config['judul'] ?? null,
                $config['deskripsi'] ?? null,
            ],
            'kontak' => [
                implode('; ', array_filter([
                    $this->cleanText((string) ($config['alamat'] ?? ''), 80),
                    $this->cleanText((string) ($config['telepon'] ?? ''), 60),
                    $this->cleanText((string) ($config['email'] ?? ''), 80),
                    $this->cleanText((string) ($config['jam_kerja'] ?? ''), 80),
                ])),
            ],
            'embed' => [
                $config['judul'] ?? null,
            ],
            'tim' => [
                collect($config['items'] ?? [])
                    ->take(4)
                    ->map(function (array $item) {
                        $name = $this->cleanText((string) ($item['nama'] ?? ''), 60);
                        $position = $this->cleanText((string) ($item['jabatan'] ?? ''), 60);

                        return trim($name . ($position !== '' ? ' - ' . $position : ''));
                    })
                    ->filter()
                    ->implode('; '),
            ],
            default => [],
        };

        foreach ($typeFragments as $fragment) {
            $text = $this->cleanText(is_string($fragment) ? $fragment : '', 180);
            if ($text !== '') {
                $fragments[] = $text;
            }
        }

        $summary = collect($fragments)
            ->filter()
            ->unique()
            ->implode(' — ');

        if ($summary === '') {
            return null;
        }

        return '[' . $section->tipe . '] ' . Str::limit($summary, 240);
    }

    private function staticPageUrl(string $key): ?string
    {
        return match ($key) {
            'sejarah' => url('/profil/sejarah'),
            'visi_misi' => url('/profil/visi-misi'),
            'akreditasi' => url('/profil/akreditasi'),
            'kerjasama' => url('/kerjasama'),
            'perpustakaan' => url('/perpustakaan'),
            'penelitian' => url('/penelitian'),
            'penerimaan' => url('/penerimaan'),
            default => null,
        };
    }

    private function normalizeHref(?string $href): ?string
    {
        $href = trim((string) $href);

        if ($href === '') {
            return null;
        }

        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }

        if (str_starts_with($href, '/')) {
            return url($href);
        }

        return $href;
    }

    private function cleanText(?string $text, int $limit = 220): string
    {
        $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/https?:\/\/\S+/i', '', $text) ?? '';
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';

        if ($text === '' || preg_match('/^#[0-9a-f]{3,8}$/i', $text)) {
            return '';
        }

        return Str::limit($text, $limit);
    }
}