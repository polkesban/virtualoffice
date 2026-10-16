<?php

namespace App\Http\Controllers;

use App\Models\UnitPage;
use App\Support\CampusStructure;
use Illuminate\View\View;

class PortalMahasiswaController extends Controller
{
    public function index(): View
    {
        $pages = UnitPage::query()
            ->portalMahasiswa()
            ->where('aktif', true)
            ->get()
            ->keyBy('role');

        $bemPage = $pages->get('org_bem_km');

        $himaPages = collect(CampusStructure::programStudyGroups())
            ->map(function (array $group) use ($pages) {
                $page = $pages->get('org_hima_' . $group['key']);

                return $page ? [
                    'label' => 'HIMA ' . $group['label'],
                    'page' => $page,
                    'order' => $group['order'],
                ] : null;
            })
            ->filter()
            ->sortBy('order')
            ->values();

        return view('portal-mahasiswa.index', compact('bemPage', 'himaPages'));
    }

    public function show(string $slug): View
    {
        $page = UnitPage::query()
            ->portalMahasiswa()
            ->where('slug', $slug)
            ->where('aktif', true)
            ->firstOrFail();

        $sections = $page->sections()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get();

        return view('unit.show', compact('page', 'sections'));
    }
}