<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\HomeQuizDefaults;
use App\Support\SiteAppearance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SiteAppearanceController extends Controller
{
    public function edit(): View
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        return view('admin.site-appearance.edit', [
            'header' => SiteAppearance::header(),
            'footer' => SiteAppearance::footer(),
            'home' => SiteAppearance::home(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'header.logo_alt' => 'required|string|max:255',
            'header.address_text' => 'required|string|max:255',
            'header.address_url' => 'nullable|string|max:1000',
            'header.socials.instagram' => 'nullable|string|max:1000',
            'header.socials.youtube' => 'nullable|string|max:1000',
            'header.socials.facebook' => 'nullable|string|max:1000',
            'header.use_default_logo' => 'nullable|boolean',
            'header_logo_file' => 'nullable|image|max:4096',

            'footer.map_title' => 'required|string|max:80',
            'footer.map_embed_url' => 'required|string|max:2000',
            'footer.address_text' => 'required|string|max:255',
            'footer.phone_text' => 'required|string|max:80',
            'footer.phone_href' => 'required|string|max:255',
            'footer.email_text' => 'required|string|max:255',
            'footer.email_href' => 'required|string|max:255',
            'footer.map_link_label' => 'required|string|max:80',
            'footer.map_link_url' => 'required|string|max:2000',
            'footer.quick_links_title' => 'required|string|max:80',
            'footer.quick_links' => 'required|array|size:8',
            'footer.quick_links.*.label' => 'required|string|max:100',
            'footer.quick_links.*.href' => 'required|string|max:1000',
            'footer.quick_links.*.external' => 'nullable|boolean',
            'footer.about_links_title' => 'required|string|max:80',
            'footer.about_links' => 'required|array|size:5',
            'footer.about_links.*.label' => 'required|string|max:100',
            'footer.about_links.*.href' => 'required|string|max:1000',
            'footer.about_links.*.external' => 'nullable|boolean',
            'footer.newsletter_title' => 'required|string|max:80',
            'footer.newsletter_body' => 'required|string|max:500',
            'footer.newsletter_placeholder' => 'required|string|max:100',
            'footer.newsletter_button_label' => 'required|string|max:40',
            'footer.bottom_text' => 'required|string|max:255',
            'footer.policy_links' => 'required|array|size:3',
            'footer.policy_links.*.label' => 'required|string|max:100',
            'footer.policy_links.*.href' => 'required|string|max:1000',
            'footer.policy_links.*.external' => 'nullable|boolean',

            'home.description' => 'required|string|max:500',
            'home.hero.badge_text' => 'required|string|max:120',
            'home.hero.title_line_1' => 'required|string|max:120',
            'home.hero.title_highlight' => 'required|string|max:60',
            'home.hero.title_line_2' => 'required|string|max:120',
            'home.hero.body' => 'required|string|max:500',
            'home.hero.primary_label' => 'required|string|max:60',
            'home.hero.primary_href' => 'required|string|max:1000',
            'home.hero.primary_new_tab' => 'nullable|boolean',
            'home.hero.secondary_label' => 'required|string|max:60',
            'home.hero.secondary_href' => 'required|string|max:1000',
            'home.hero.secondary_new_tab' => 'nullable|boolean',
            'home.hero.scroll_hint' => 'required|string|max:80',
            'home.hero.use_default_video' => 'nullable|boolean',
            'home.hero.use_default_poster' => 'nullable|boolean',
            'home_hero_video_file' => 'nullable|file|mimetypes:video/mp4,video/webm|max:51200',
            'home_hero_poster_file' => 'nullable|image|max:4096',

            'home.statistics.eyebrow' => 'required|string|max:80',
            'home.statistics.title' => 'required|string|max:80',
            'home.statistics.items' => 'required|array|size:4',
            'home.statistics.items.*.icon' => 'required|string|max:10',
            'home.statistics.items.*.target' => 'required|integer|min:0|max:100000000',
            'home.statistics.items.*.suffix' => 'nullable|string|max:10',
            'home.statistics.items.*.label' => 'required|string|max:60',

            'home.quiz.title' => 'required|string|max:80',
            'home.quiz.subtitle' => 'required|string|max:120',
            'home.quiz.result_title' => 'required|string|max:120',
            'home.quiz.result_note' => 'required|string|max:200',
            'home.quiz.all_programs_label' => 'required|string|max:60',
            'home.quiz.reset_label' => 'required|string|max:60',
            'home.quiz.default_result_key' => 'required|string|max:120',
            'home.quiz.desktop_total_label' => 'required|string|max:60',
            'home.quiz.desktop_badges' => 'required|array|size:3',
            'home.quiz.desktop_badges.*' => 'required|string|max:40',
            'home.quiz.desktop_description' => 'required|string|max:255',
            'home.quiz.desktop_link_label' => 'required|string|max:80',
            'home.quiz.questions_json' => 'required|string|max:250000',
            'home.quiz.map_json' => 'required|string|max:250000',
            'home.quiz.map2_json' => 'required|string|max:250000',

            'home.news.title' => 'required|string|max:80',
            'home.news.subtitle' => 'required|string|max:120',
            'home.news.link_label' => 'required|string|max:40',

            'home.announcements.title' => 'required|string|max:80',
            'home.announcements.link_label' => 'required|string|max:40',
            'home.announcements.empty_text' => 'required|string|max:120',

            'home.achievements.title' => 'required|string|max:80',
            'home.achievements.subtitle' => 'required|string|max:200',
            'home.achievements.link_label' => 'required|string|max:60',
            'home.achievements.empty_cta' => 'required|string|max:200',

            'home.cta.badge_text' => 'required|string|max:80',
            'home.cta.title_line_1' => 'required|string|max:80',
            'home.cta.title_line_2' => 'required|string|max:80',
            'home.cta.body' => 'required|string|max:160',
            'home.cta.primary_label' => 'required|string|max:60',
            'home.cta.primary_href' => 'required|string|max:1000',
            'home.cta.primary_new_tab' => 'nullable|boolean',
            'home.cta.secondary_label' => 'required|string|max:60',
            'home.cta.secondary_href' => 'required|string|max:1000',
            'home.cta.secondary_new_tab' => 'nullable|boolean',

            'home.programs.eyebrow' => 'required|string|max:80',
            'home.programs.title' => 'required|string|max:80',
            'home.programs.desktop_link_label' => 'required|string|max:80',
            'home.programs.mobile_link_label' => 'required|string|max:80',

            'home.gallery.eyebrow' => 'required|string|max:80',
            'home.gallery.title' => 'required|string|max:80',
            'home.gallery.link_label' => 'required|string|max:80',
            'home.gallery.lightbox_label' => 'required|string|max:40',

            'home.newsletter.eyebrow' => 'required|string|max:80',
            'home.newsletter.title' => 'required|string|max:120',
            'home.newsletter.body' => 'required|string|max:200',
            'home.newsletter.placeholder' => 'required|string|max:120',
            'home.newsletter.button_label' => 'required|string|max:40',
            'home.newsletter.success_message' => 'required|string|max:120',
        ]);

        $header = $this->buildHeaderData($request, $validated['header']);
        $footer = $this->buildFooterData($validated['footer']);
        $home = $this->buildHomeData($request, $validated['home']);

        SiteAppearance::save(SiteAppearance::HEADER_KEY, 'Header Website', $header, auth()->id());
        SiteAppearance::save(SiteAppearance::FOOTER_KEY, 'Footer Website', $footer, auth()->id());
        SiteAppearance::save(SiteAppearance::HOME_KEY, 'Beranda Website', $home, auth()->id());

        return back()->with('success', 'Editor situs berhasil disimpan.');
    }

    private function buildHeaderData(Request $request, array $header): array
    {
        $current = SiteAppearance::header();

        if ($request->boolean('header.use_default_logo')) {
            $this->deleteManagedAsset($current['logo_path'] ?? null);
            $header['logo_path'] = null;
        } else {
            $header['logo_path'] = $this->replaceManagedAsset(
                $request,
                'header_logo_file',
                'site-editor/header',
                $current['logo_path'] ?? null
            );
        }

        $header['socials'] = [
            'instagram' => $header['socials']['instagram'] ?? '',
            'youtube' => $header['socials']['youtube'] ?? '',
            'facebook' => $header['socials']['facebook'] ?? '',
        ];

        return $header;
    }

    private function buildFooterData(array $footer): array
    {
        $footer['quick_links'] = $this->normalizeLinks($footer['quick_links'] ?? []);
        $footer['about_links'] = $this->normalizeLinks($footer['about_links'] ?? []);
        $footer['policy_links'] = $this->normalizeLinks($footer['policy_links'] ?? []);

        return $footer;
    }

    private function buildHomeData(Request $request, array $home): array
    {
        $current = SiteAppearance::home();

        if ($request->boolean('home.hero.use_default_video')) {
            $this->deleteManagedAsset($current['hero']['video_path'] ?? null);
            $home['hero']['video_path'] = null;
        } else {
            $home['hero']['video_path'] = $this->replaceManagedAsset(
                $request,
                'home_hero_video_file',
                'site-editor/home',
                $current['hero']['video_path'] ?? null
            );
        }

        if ($request->boolean('home.hero.use_default_poster')) {
            $this->deleteManagedAsset($current['hero']['poster_path'] ?? null);
            $home['hero']['poster_path'] = null;
        } else {
            $home['hero']['poster_path'] = $this->replaceManagedAsset(
                $request,
                'home_hero_poster_file',
                'site-editor/home',
                $current['hero']['poster_path'] ?? null
            );
        }

        $home['hero']['primary_new_tab'] = (bool) ($home['hero']['primary_new_tab'] ?? false);
        $home['hero']['secondary_new_tab'] = (bool) ($home['hero']['secondary_new_tab'] ?? false);
        $home['cta']['primary_new_tab'] = (bool) ($home['cta']['primary_new_tab'] ?? false);
        $home['cta']['secondary_new_tab'] = (bool) ($home['cta']['secondary_new_tab'] ?? false);
        $home['quiz']['desktop_badges'] = array_values($home['quiz']['desktop_badges'] ?? []);
        $home['quiz']['default_result_key'] = trim((string) ($home['quiz']['default_result_key'] ?? HomeQuizDefaults::defaultResultKey()));
        $home['quiz']['questions'] = $this->decodeJsonArray(
            $home['quiz']['questions_json'] ?? '[]',
            'home.quiz.questions_json',
            'Pertanyaan quiz'
        );
        $home['quiz']['map'] = $this->decodeJsonArray(
            $home['quiz']['map_json'] ?? '{}',
            'home.quiz.map_json',
            'Mapping hasil utama quiz'
        );
        $home['quiz']['map2'] = $this->decodeJsonArray(
            $home['quiz']['map2_json'] ?? '{}',
            'home.quiz.map2_json',
            'Mapping hasil alternatif quiz'
        );
        $this->assertQuizQuestions($home['quiz']['questions']);
        $this->assertQuizResults($home['quiz']['map'], 'home.quiz.map_json', 'hasil utama');
        $this->assertQuizResults($home['quiz']['map2'], 'home.quiz.map2_json', 'hasil alternatif');

        if (! array_key_exists($home['quiz']['default_result_key'], $home['quiz']['map'])) {
            throw ValidationException::withMessages([
                'home.quiz.default_result_key' => 'Key hasil default harus tersedia di JSON mapping hasil utama.',
            ]);
        }

        unset(
            $home['quiz']['questions_json'],
            $home['quiz']['map_json'],
            $home['quiz']['map2_json']
        );

        $home['statistics']['items'] = array_map(function (array $item): array {
            return [
                'icon' => $item['icon'],
                'target' => (int) $item['target'],
                'suffix' => $item['suffix'] ?? '',
                'label' => $item['label'],
            ];
        }, $home['statistics']['items'] ?? []);

        return $home;
    }

    private function decodeJsonArray(string $json, string $field, string $label): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages([
                $field => $label . ' harus berupa JSON yang valid.',
            ]);
        }

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                $field => $label . ' harus berupa array/object JSON.',
            ]);
        }

        return $decoded;
    }

    private function assertQuizQuestions(array $questions): void
    {
        if ($questions === []) {
            throw ValidationException::withMessages([
                'home.quiz.questions_json' => 'Pertanyaan quiz tidak boleh kosong.',
            ]);
        }

        foreach ($questions as $index => $question) {
            if (! is_array($question)) {
                throw ValidationException::withMessages([
                    'home.quiz.questions_json' => 'Pertanyaan quiz pada item ' . ($index + 1) . ' harus berupa object JSON.',
                ]);
            }

            $prompt = trim((string) ($question['q'] ?? ''));
            $icon = trim((string) ($question['icon'] ?? ''));
            $options = $question['opts'] ?? null;

            if ($prompt === '' || $icon === '' || ! is_array($options) || $options === []) {
                throw ValidationException::withMessages([
                    'home.quiz.questions_json' => 'Setiap pertanyaan quiz wajib punya `q`, `icon`, dan `opts` yang terisi.',
                ]);
            }

            foreach ($options as $optionIndex => $option) {
                if (! is_array($option)) {
                    throw ValidationException::withMessages([
                        'home.quiz.questions_json' => 'Opsi pada pertanyaan ' . ($index + 1) . ' item ' . ($optionIndex + 1) . ' harus berupa object JSON.',
                    ]);
                }

                if (trim((string) ($option['label'] ?? '')) === '' || trim((string) ($option['val'] ?? '')) === '') {
                    throw ValidationException::withMessages([
                        'home.quiz.questions_json' => 'Setiap opsi quiz wajib punya `label` dan `val`.',
                    ]);
                }
            }
        }
    }

    private function assertQuizResults(array $results, string $field, string $label): void
    {
        foreach ($results as $key => $result) {
            if (! is_string($key) || trim($key) === '') {
                throw ValidationException::withMessages([
                    $field => 'Setiap key pada mapping ' . $label . ' harus berupa string non-kosong.',
                ]);
            }

            if (! is_array($result)) {
                throw ValidationException::withMessages([
                    $field => 'Setiap item pada mapping ' . $label . ' harus berupa object JSON.',
                ]);
            }

            if (
                trim((string) ($result['nama'] ?? '')) === ''
                || trim((string) ($result['emoji'] ?? '')) === ''
                || trim((string) ($result['slug'] ?? '')) === ''
            ) {
                throw ValidationException::withMessages([
                    $field => 'Setiap item pada mapping ' . $label . ' wajib punya `nama`, `emoji`, dan `slug`.',
                ]);
            }
        }
    }

    private function normalizeLinks(array $links): array
    {
        return array_map(function (array $link): array {
            return [
                'label' => $link['label'] ?? '',
                'href' => $link['href'] ?? '',
                'external' => (bool) ($link['external'] ?? false),
            ];
        }, array_values($links));
    }

    private function replaceManagedAsset(Request $request, string $field, string $directory, ?string $currentPath): ?string
    {
        if (! $request->hasFile($field)) {
            return $currentPath;
        }

        $this->deleteManagedAsset($currentPath);

        return \App\Support\MediaStorage::storeUploadedFile($request->file($field), $directory);
    }

    private function deleteManagedAsset(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'site-editor/')) {
            return;
        }

        \App\Support\MediaStorage::delete($path);
    }
}