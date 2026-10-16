<?php

namespace App\Support;

use App\Models\HalamanStatis;

class SiteAppearance
{
    public const HOME_KEY = 'beranda';
    public const HEADER_KEY = 'site_header';
    public const FOOTER_KEY = 'site_footer';

    /** @var array<string, HalamanStatis|null>|null */
    private static ?array $pages = null;

    public static function home(): array
    {
        return self::mergeDefaults(self::defaultHome(), self::payload(self::HOME_KEY));
    }

    public static function header(): array
    {
        return self::mergeDefaults(self::defaultHeader(), self::payload(self::HEADER_KEY));
    }

    public static function footer(): array
    {
        return self::mergeDefaults(self::defaultFooter(), self::payload(self::FOOTER_KEY));
    }

    public static function save(string $key, string $title, array $data, ?int $updatedBy = null): HalamanStatis
    {
        $page = HalamanStatis::firstOrNew(['kunci' => $key]);

        $page->fill([
            'judul' => $title,
            'menu_label' => $title,
            'menu_icon' => match ($key) {
                self::HOME_KEY => '🏠',
                self::HEADER_KEY => '🧭',
                self::FOOTER_KEY => '🦶',
                default => '📄',
            },
            'konten' => $page->konten,
            'data_ekstra' => $data,
            'updated_by' => $updatedBy,
        ]);

        $page->save();

        if (self::$pages === null) {
            self::$pages = [];
        }

        self::$pages[$key] = $page;

        return $page;
    }

    public static function storageUrl(?string $path, string $fallbackAssetPath): string
    {
        if (! $path) {
            return asset($fallbackAssetPath);
        }

        return MediaStorage::url($path) ?: asset($fallbackAssetPath);
    }

    private static function payload(string $key): array
    {
        $page = self::pages()[$key] ?? null;

        return is_array($page?->data_ekstra) ? $page->data_ekstra : [];
    }

    /** @return array<string, HalamanStatis|null> */
    private static function pages(): array
    {
        if (self::$pages !== null) {
            return self::$pages;
        }

        $keys = [self::HOME_KEY, self::HEADER_KEY, self::FOOTER_KEY];

        $pages = HalamanStatis::query()
            ->whereIn('kunci', $keys)
            ->get()
            ->keyBy('kunci');

        self::$pages = [];
        foreach ($keys as $key) {
            self::$pages[$key] = $pages->get($key);
        }

        return self::$pages;
    }

    private static function mergeDefaults(array $defaults, array $data): array
    {
        return array_replace_recursive($defaults, $data);
    }

    private static function defaultHeader(): array
    {
        return [
            'logo_path' => null,
            'logo_alt' => 'Logo Poltekkes Bandung',
            'address_text' => 'Jl. Pajajaran No. 56, Bandung 40171',
            'address_url' => 'https://maps.google.com/maps?q=Jl.+Pajajaran+No.+56+Bandung+Politeknik+Kesehatan+Kemenkes+Bandung',
            'socials' => [
                'instagram' => 'https://instagram.com/polkesban',
                'youtube' => 'https://youtube.com/@polkesban',
                'facebook' => 'https://facebook.com/polkesban',
            ],
        ];
    }

    private static function defaultFooter(): array
    {
        return [
            'map_title' => 'Map Poltekkes Bandung',
            'map_embed_url' => 'https://maps.google.com/maps?q=Jl.+Pajajaran+No.+56+Bandung+Politeknik+Kesehatan+Kemenkes+Bandung&output=embed&z=16',
            'address_text' => 'Jl. Pajajaran No. 56, Bandung 40171',
            'phone_text' => '(022) 4231627',
            'phone_href' => 'tel:+62224231627',
            'email_text' => 'info@polkesban.ac.id',
            'email_href' => 'mailto:info@polkesban.ac.id',
            'map_link_label' => 'Buka di Google Maps',
            'map_link_url' => 'https://maps.google.com/maps?q=Jl.+Pajajaran+No.+56+Bandung+Politeknik+Kesehatan+Kemenkes+Bandung',
            'quick_links_title' => 'Tautan Cepat',
            'quick_links' => [
                ['label' => 'Program Studi', 'href' => '/program-studi', 'external' => false],
                ['label' => 'Pengumuman', 'href' => '/pengumuman', 'external' => false],
                ['label' => 'Portal Mahasiswa', 'href' => '/portal-mahasiswa', 'external' => false],
                ['label' => 'Pojok Alumni', 'href' => '/pojok-alumni', 'external' => false],
                ['label' => 'Penerimaan Mahasiswa', 'href' => 'https://spmb.poltekkesbandung.ac.id/', 'external' => true],
                ['label' => 'SIAKAD', 'href' => 'https://siak.poltekkesbandung.ac.id/', 'external' => true],
                ['label' => 'Perpustakaan', 'href' => 'https://library.poltekkesbandung.ac.id', 'external' => true],
                ['label' => 'Helpdesk', 'href' => 'https://helpdesk.poltekkesbandung.ac.id/', 'external' => true],
            ],
            'about_links_title' => 'Tentang',
            'about_links' => [
                ['label' => 'Sejarah', 'href' => '/profil/sejarah', 'external' => false],
                ['label' => 'Visi & Misi', 'href' => '/profil/visi-misi', 'external' => false],
                ['label' => 'Struktur Organisasi', 'href' => '/profil/struktur', 'external' => false],
                ['label' => 'Akreditasi', 'href' => '/profil/akreditasi', 'external' => false],
                ['label' => 'Kerjasama', 'href' => '/kerjasama', 'external' => false],
            ],
            'newsletter_title' => 'Newsletter',
            'newsletter_body' => 'Dapatkan informasi pengumuman, agenda, dan program kampus terbaru langsung ke email Anda.',
            'newsletter_placeholder' => 'email@anda.com',
            'newsletter_button_label' => 'Langganan',
            'bottom_text' => '© {year} Politeknik Kesehatan Kemenkes Bandung. Hak cipta dilindungi.',
            'policy_links' => [
                ['label' => 'Kebijakan Privasi', 'href' => '/kebijakan-privasi', 'external' => false],
                ['label' => 'Syarat & Ketentuan', 'href' => '/syarat-ketentuan', 'external' => false],
                ['label' => 'Sitemap', 'href' => '/sitemap.xml', 'external' => false],
            ],
        ];
    }

    private static function defaultHome(): array
    {
        return [
            'description' => 'Politeknik Kesehatan Kemenkes Bandung — 20 program studi D3, D4, dan Profesi terakreditasi Unggul di bidang Keperawatan, Kebidanan, Gizi, Farmasi, TLM, dan lainnya.',
            'hero' => [
                'video_path' => null,
                'poster_path' => null,
                'badge_text' => 'Penerimaan {year}/{next_year} Dibuka',
                'title_line_1' => 'Wujudkan Impian',
                'title_highlight' => 'Nakes',
                'title_line_2' => 'Profesional',
                'body' => 'Program D3 & D4 terakreditasi Unggul. Bergabung bersama 15.000+ alumni mengabdi di seluruh Indonesia.',
                'primary_label' => 'Daftar Sekarang',
                'primary_href' => '/penerimaan',
                'primary_new_tab' => true,
                'secondary_label' => 'Lihat Prodi',
                'secondary_href' => '/program-studi',
                'secondary_new_tab' => false,
                'scroll_hint' => 'Scroll untuk explore',
            ],
            'statistics' => [
                'eyebrow' => 'Statistik Kampus',
                'title' => 'Dalam Angka',
                'items' => [
                    ['icon' => '🎓', 'target' => 15000, 'suffix' => '+', 'label' => 'Alumni Aktif'],
                    ['icon' => '📚', 'target' => 20, 'suffix' => '', 'label' => 'Program Studi'],
                    ['icon' => '🏛️', 'target' => 25, 'suffix' => '+', 'label' => 'Tahun Berdiri'],
                    ['icon' => '✅', 'target' => 98, 'suffix' => '%', 'label' => 'Kelulusan'],
                ],
            ],
            'quiz' => [
                'title' => 'Cek Jurusan',
                'subtitle' => 'Prodi cocok untuk kamu',
                'result_title' => '2 Pilihan Prodi Untukmu',
                'result_note' => 'Kamu boleh mendaftar 2 prodi sekaligus di SPMB',
                'default_result_key' => HomeQuizDefaults::defaultResultKey(),
                'questions' => HomeQuizDefaults::questions(),
                'map' => HomeQuizDefaults::map(),
                'map2' => HomeQuizDefaults::map2(),
                'all_programs_label' => 'Semua Prodi',
                'reset_label' => 'Coba Lagi',
                'desktop_total_label' => 'Program Studi',
                'desktop_badges' => ['D3', 'D4 Sarjana Terapan', 'Profesi'],
                'desktop_description' => 'Temukan program yang sesuai dengan minat dan impianmu sebagai tenaga kesehatan profesional',
                'desktop_link_label' => 'Lihat Semua Prodi →',
            ],
            'news' => [
                'title' => 'Berita Terbaru',
                'subtitle' => 'Info & kegiatan kampus',
                'link_label' => 'Semua',
            ],
            'announcements' => [
                'title' => 'Pengumuman',
                'link_label' => 'Semua →',
                'empty_text' => 'Belum ada pengumuman.',
            ],
            'achievements' => [
                'title' => 'Mahasiswa Berprestasi',
                'subtitle' => 'Apresiasi pilihan mahasiswa dengan capaian terbaik yang sedang ditayangkan.',
                'link_label' => 'Portal Mahasiswa →',
                'empty_cta' => 'Daftarkan diri & unggah portofoliomu untuk berpeluang terpilih',
            ],
            'cta' => [
                'badge_text' => 'TA {year}/{next_year}',
                'title_line_1' => 'Siap Jadi',
                'title_line_2' => 'Nakes Hebat?',
                'body' => 'Daftarkan diri sebelum kuota penuh!',
                'primary_label' => 'Daftar Online',
                'primary_href' => '/penerimaan',
                'primary_new_tab' => true,
                'secondary_label' => 'Tanya Info',
                'secondary_href' => '/kontak',
                'secondary_new_tab' => false,
            ],
            'programs' => [
                'eyebrow' => 'Pilih Jalanmu',
                'title' => 'Program Studi',
                'desktop_link_label' => 'Semua 20 Prodi',
                'mobile_link_label' => 'Lihat semua 20 program studi →',
            ],
            'gallery' => [
                'eyebrow' => 'Aktivitas Kampus',
                'title' => 'Galeri',
                'link_label' => 'Lihat semua foto',
                'lightbox_label' => 'Perbesar',
            ],
            'newsletter' => [
                'eyebrow' => 'Stay Updated',
                'title' => 'Dapatkan Info Terbaru Poltekkes Bandung',
                'body' => 'Berita, pengumuman & jadwal PPDB langsung di inbox.',
                'placeholder' => 'emailkamu@gmail.com',
                'button_label' => 'Subscribe',
                'success_message' => 'Berhasil! Cek inbox kamu.',
            ],
        ];
    }
}