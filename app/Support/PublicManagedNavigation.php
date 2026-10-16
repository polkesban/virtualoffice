<?php

namespace App\Support;

use App\Models\AdminNavConfig;
use App\Models\NavMenu;
use App\Models\ProgramStudi;
use App\Models\UnitPage;
use Illuminate\Database\Eloquent\Collection;

class PublicManagedNavigation
{
    public static function enrichManualTree(Collection $items): Collection
    {
        $enrichedItems = $items->map(function (NavMenu $item) {
            $children = $item->relationLoaded('childrenRecursive')
                ? $item->getRelation('childrenRecursive')
                : new Collection();

            $children = self::mergeChildren(
                self::enrichManualTree($children),
                self::generatedChildrenFor($item)
            );

            $item->setRelation('childrenRecursive', $children);

            return $item;
        })->all();

        return new Collection($enrichedItems);
    }

    public static function generatedTopLevelItems(Collection $existingItems): Collection
    {
        $config = AdminNavConfig::getAllKeyed();
        $existingHrefs = $existingItems
            ->map(fn (NavMenu $item) => self::normalizeHref($item->href))
            ->filter()
            ->all();

        $items = collect();

        foreach (self::definitions() as $definition) {
            $href = self::normalizeHref($definition['href']);

            if (in_array($href, $existingHrefs, true)) {
                continue;
            }

            $cfg = $config[$definition['menu_key']] ?? [];

            if (($cfg['globally_hidden'] ?? false) === true) {
                continue;
            }

            $children = match ($definition['menu_key']) {
                'halaman-unit' => self::unitPageChildren(),
                'portal-mahasiswa' => self::portalMahasiswaChildren(),
                default => new Collection(),
            };

            if (in_array($definition['menu_key'], ['halaman-unit', 'portal-mahasiswa'], true) && $children->isEmpty()) {
                continue;
            }

            $item = new NavMenu([
                'label' => ($cfg['custom_label'] ?? null) ?: $definition['label'],
                'href' => $definition['href'],
                'icon' => ($cfg['custom_icon'] ?? null) ?: $definition['icon'],
                'parent_id' => null,
                'urutan' => (int) ($cfg['urutan'] ?? $definition['urutan']),
                'aktif' => true,
                'target' => '_self',
            ]);

            $item->exists = false;
            $item->setRelation('childrenRecursive', $children);

            $items->push($item);
        }

        return new Collection($items->all());
    }

    private static function definitions(): array
    {
        return [
            ['menu_key' => 'portal-mahasiswa', 'label' => 'Portal Mahasiswa', 'icon' => '🎓', 'href' => '/portal-mahasiswa', 'urutan' => 4],
            ['menu_key' => 'pojok-alumni', 'label' => 'Pojok Alumni', 'icon' => '🧾', 'href' => '/pojok-alumni', 'urutan' => 5],
            ['menu_key' => 'galeri', 'label' => 'Galeri', 'icon' => '🖼️', 'href' => '/galeri', 'urutan' => 60],
            ['menu_key' => 'agenda', 'label' => 'Agenda & Kalender', 'icon' => '📅', 'href' => '/agenda', 'urutan' => 70],
            ['menu_key' => 'berprestasi', 'label' => 'Mahasiswa Berprestasi', 'icon' => '🏆', 'href' => '/mahasiswa-berprestasi', 'urutan' => 90],
            ['menu_key' => 'halaman-unit', 'label' => 'Layanan', 'icon' => '🗂️', 'href' => '/unit', 'urutan' => 180],
        ];
    }

    private static function generatedChildrenFor(NavMenu $item): Collection
    {
        return match (self::normalizeHref($item->href)) {
            '/program-studi' => self::programStudiChildren(),
            default => new Collection(),
        };
    }

    private static function programStudiChildren(): Collection
    {
        $programs = ProgramStudi::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get(['nama', 'slug', 'urutan'])
            ->keyBy('slug');

        $usedSlugs = [];
        $items = collect();

        foreach (CampusStructure::programStudyGroups() as $group) {
            $groupPrograms = collect($group['prodi_slugs'])
                ->map(fn (string $slug) => $programs->get($slug))
                ->filter();

            if ($groupPrograms->isEmpty()) {
                continue;
            }

            foreach ($groupPrograms as $program) {
                $usedSlugs[] = $program->slug;
            }

            if ($group['direct']) {
                foreach ($groupPrograms as $program) {
                    $items->push(self::makeVirtualItem(
                        label: $program->nama,
                        href: '/program-studi/' . trim($program->slug, '/'),
                        urutan: (int) ($group['order'] ?? 999)
                    ));
                }

                continue;
            }

            $children = $groupPrograms
                ->map(function (ProgramStudi $program, int $index) {
                    return self::makeVirtualItem(
                        label: $program->nama,
                        href: '/program-studi/' . trim($program->slug, '/'),
                        urutan: (int) ($program->urutan ?? ($index + 1))
                    );
                })
                ->values();

            $items->push(self::makeVirtualItem(
                label: $group['label'],
                href: null,
                urutan: (int) ($group['order'] ?? 999),
                children: new Collection($children->all())
            ));
        }

        $remainingPrograms = $programs
            ->reject(fn (ProgramStudi $program) => in_array($program->slug, $usedSlugs, true))
            ->values();

        foreach ($remainingPrograms as $index => $program) {
            $items->push(self::makeVirtualItem(
                label: $program->nama,
                href: '/program-studi/' . trim($program->slug, '/'),
                urutan: 900 + $index
            ));
        }

        return new Collection(
            $items
                ->sortBy(fn (NavMenu $item) => sprintf('%05d-%s', (int) ($item->urutan ?? 999), mb_strtolower($item->label)))
                ->values()
                ->all()
        );
    }

    private static function portalMahasiswaChildren(): Collection
    {
        $pages = UnitPage::query()
            ->portalMahasiswa()
            ->where('aktif', true)
            ->get()
            ->keyBy('role');

        $items = collect();

        if ($pages->has('org_bem_km')) {
            $page = $pages->get('org_bem_km');
            $items->push(self::makeVirtualItem(
                label: 'BEM KM Poltekkes Bandung',
                href: $page->publicPath(),
                urutan: 1
            ));
        }

        $himaChildren = collect(CampusStructure::programStudyGroups())
            ->map(function (array $group) use ($pages) {
                $page = $pages->get('org_hima_' . $group['key']);

                return $page ? self::makeVirtualItem(
                    label: 'HIMA ' . $group['label'],
                    href: $page->publicPath(),
                    urutan: (int) ($group['order'] ?? 999)
                ) : null;
            })
            ->filter()
            ->values();

        if ($himaChildren->isNotEmpty()) {
            $items->push(self::makeVirtualItem(
                label: 'HIMA',
                href: null,
                urutan: 2,
                children: new Collection($himaChildren->all())
            ));
        }

        return new Collection($items->all());
    }

    private static function unitPageChildren(): Collection
    {
        $children = UnitPage::query()
            ->layananRoot()
            ->where('aktif', true)
            ->get()
            ->sortBy(fn (UnitPage $page) => sprintf('%05d-%s', $page->layananMenuOrder(), mb_strtolower($page->judul)))
            ->values()
            ->map(function (UnitPage $page, int $index) {
                return self::makeVirtualItem(
                    label: $page->judul,
                    href: $page->publicPath(),
                    urutan: $page->layananMenuOrder() ?: ($index + 1),
                    children: self::layananChildrenFor($page)
                );
            })
            ->all();

        return new Collection($children);
    }

    private static function layananChildrenFor(UnitPage $page): Collection
    {
        $items = collect();

        foreach (CampusStructure::layananChildLinks()[$page->role] ?? [] as $index => $child) {
            $items->push(self::makeVirtualItem(
                label: $child['label'],
                href: $child['href'],
                urutan: $child['urutan'] ?? ($index + 1)
            ));
        }

        return new Collection($items->all());
    }

    private static function mergeChildren(Collection $existingChildren, Collection $generatedChildren): Collection
    {
        $existingHrefs = $existingChildren
            ->map(fn (NavMenu $child) => self::normalizeHref($child->href))
            ->filter()
            ->all();

        $merged = $existingChildren
            ->concat(
                $generatedChildren->reject(function (NavMenu $child) use ($existingHrefs) {
                    return in_array(self::normalizeHref($child->href), $existingHrefs, true);
                })
            )
            ->sortBy(fn (NavMenu $child) => sprintf('%05d-%s', (int) ($child->urutan ?? 999), mb_strtolower($child->label)))
            ->values()
            ->all();

        return new Collection($merged);
    }

    private static function makeVirtualItem(string $label, ?string $href, int $urutan, ?string $icon = null, ?Collection $children = null): NavMenu
    {
        $item = new NavMenu([
            'label' => $label,
            'href' => $href,
            'icon' => $icon,
            'parent_id' => null,
            'urutan' => $urutan,
            'aktif' => true,
            'target' => '_self',
        ]);

        $item->exists = false;
        $item->setRelation('childrenRecursive', $children ?? new Collection());

        return $item;
    }

    private static function normalizeHref(?string $href): ?string
    {
        if (! $href) {
            return null;
        }

        $normalized = '/' . trim($href, '/');

        return $normalized === '/' ? '/' : rtrim($normalized, '/');
    }
}