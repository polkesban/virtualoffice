<?php

namespace App\Http\Controllers;

use App\Models\UnitPage;
use App\Models\ProgramStudi;
use App\Support\LakipDocumentCatalog;

class UnitPageController extends Controller
{
    public function show(string $slug)
    {
        $page = UnitPage::with('programStudi')
            ->where('slug', $slug)
            ->where('aktif', 1)
            ->firstOrFail();

        // If this unit page is linked to a program studi, redirect there
        if ($page->programStudi) {
            return redirect()->route('program-studi.show', $page->programStudi->slug, 301);
        }

        $sections = $page->sections()->where('aktif', 1)->orderBy('urutan')->get();

        // Get related unit page IDs (include sub-units if any)
        $unitPageIds = [$page->id];
        $subUnitPages = collect();
        if ($page->role === 'pusat_p3') {
            $subUnitPages = UnitPage::where('role', 'like', 'sub_p3_%')->where('aktif', 1)->get();
            $unitPageIds = array_merge($unitPageIds, $subUnitPages->pluck('id')->toArray());
        }

        // Identify parent page if this is a sub-unit
        $parentPage = null;
        if (str_starts_with((string) $page->role, 'sub_p3_')) {
            $parentPage = UnitPage::where('role', 'pusat_p3')->where('aktif', 1)->first();
        }

        // Fetch events for this unit and sub-units
        $events = \App\Models\UnitEvent::with('page')
            ->whereIn('unit_page_id', $unitPageIds)
            ->published()
            ->orderByDesc('mulai_at')
            ->limit(6)
            ->get();

        return view('unit.show', compact('page', 'sections', 'events', 'subUnitPages', 'parentPage'));
    }

    public function showLakipAdministrasiUmum()
    {
        $page = $this->administrasiUmumPage();

        return view('unit.lakip-administrasi-umum', compact('page'));
    }

    public function showLakipAdministrasiUmum2024()
    {
        $page = $this->administrasiUmumPage();

        return view('unit.lakip-administrasi-umum-2024', compact('page'));
    }

    public function showLakipAdministrasiUmum2023()
    {
        $page = $this->administrasiUmumPage();

        return view('unit.lakip-administrasi-umum-2023', compact('page'));
    }

    public function redirectLakipAdministrasiUmum2025LegacySection(string $section)
    {
        $anchors = LakipDocumentCatalog::forYear('2025')['legacy_section_anchors'] ?? [];
        abort_unless(array_key_exists($section, $anchors), 404);

        return redirect()->to(route('unit.administrasi-umum.lakip') . $anchors[$section], 301);
    }

    public function redirectLakipAdministrasiUmum2024LegacySection(string $section)
    {
        $anchors = LakipDocumentCatalog::forYear('2024')['legacy_section_anchors'] ?? [];
        abort_unless(array_key_exists($section, $anchors), 404);

        return redirect()->to(route('unit.administrasi-umum.lakip.2024') . $anchors[$section], 301);
    }

    public function redirectLakipAdministrasiUmum2023LegacySection(string $section)
    {
        $anchors = LakipDocumentCatalog::forYear('2023')['legacy_section_anchors'] ?? [];
        abort_unless(array_key_exists($section, $anchors), 404);

        return redirect()->to(route('unit.administrasi-umum.lakip.2023') . $anchors[$section], 301);
    }

    private function administrasiUmumPage(): UnitPage
    {
        return UnitPage::query()
            ->where('slug', 'bidang-administrasi-umum')
            ->where('aktif', 1)
            ->firstOrFail();
    }
}
