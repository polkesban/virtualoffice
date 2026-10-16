<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageSection;
use App\Support\PageFormBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageSectionResponseController extends Controller
{
    public function show(Request $request, PageSection $section)
    {
        abort_unless($section->tipe === 'formulir', 404);

        $this->authorizeSection($section);

        [$backUrl, $backLabel] = $this->backLink($section);
        $filters = $this->validatedFilters($request);

        return view('admin.page-sections.form-responses', [
            'section' => $section,
            'summaryPayload' => PageFormBlock::summary($section, 12, $filters),
            'summaryUrl' => route('admin.page-sections.responses.summary', array_filter(['section' => $section, ...$filters])),
            'exportUrl' => route('admin.page-sections.responses.export', array_filter(['section' => $section, ...$filters])),
            'editUrl' => $section->page
                ? route('admin.halaman-unit.section.edit', $section)
                : route('admin.halaman-statis.section.edit', $section),
            'backUrl' => $backUrl,
            'backLabel' => $backLabel,
            'contextTitle' => $section->page?->judul ?? $section->halamanStatis?->judul ?? 'Halaman',
            'filters' => $filters,
        ]);
    }

    public function summary(Request $request, PageSection $section): JsonResponse
    {
        abort_unless($section->tipe === 'formulir', 404);

        $this->authorizeSection($section);

        return response()->json(PageFormBlock::summary($section, 12, $this->validatedFilters($request)));
    }

    public function export(Request $request, PageSection $section)
    {
        abort_unless($section->tipe === 'formulir', 404);

        $this->authorizeSection($section);

        $filters = $this->validatedFilters($request);
        $config = PageFormBlock::normalizeConfig($section->konfigurasi ?? []);
        $fields = $config['fields'] ?? [];
        $submissions = PageFormBlock::submissions($section, $filters);

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, PageFormBlock::exportHeadingRow($fields, $config));

        foreach (PageFormBlock::exportDataRows($fields, $submissions, $config) as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);
        $csv = stream_get_contents($stream) ?: '';
        fclose($stream);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="form-responses-' . $section->id . '-' . now()->format('Ymd-His') . '.csv"',
        ]);
    }

    private function authorizeSection(PageSection $section): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($section->page) {
            $page = $section->page;

            if ($user->managed_unit_page_id && (int) $user->managed_unit_page_id === (int) $page->id) {
                return;
            }

            if ($user->isKaprodi()) {
                $linkedProdi = $page->programStudi;

                if ($linkedProdi && $linkedProdi->id === $user->program_studi_id) {
                    return;
                }

                abort(403, 'Anda tidak memiliki akses ke halaman unit ini.');
            }

            if ($user->isJurusanAdmin()) {
                $linkedProdi = $page->programStudi;

                if ($linkedProdi && $linkedProdi->jurusan_id === $user->jurusan_id) {
                    return;
                }

                abort(403, 'Anda tidak memiliki akses ke halaman unit ini.');
            }

            if ($page->role === $user->role) {
                return;
            }

            abort(403, 'Anda tidak memiliki akses ke halaman unit ini.');
        }

        if ($section->halamanStatis) {
            abort_unless(
                $user->hasRoleAccess('halaman-statis:' . $section->halamanStatis->kunci),
                403,
                'Anda tidak memiliki izin untuk melihat rekap formulir ini.'
            );

            return;
        }

        abort(404);
    }

    private function backLink(PageSection $section): array
    {
        if ($section->page) {
            return [route('admin.halaman-unit.manage', $section->page), 'Kembali ke Halaman Unit'];
        }

        if ($section->halamanStatis) {
            return [route('admin.halaman-statis.edit', $section->halamanStatis->kunci), 'Kembali ke Halaman Statis'];
        }

        return [route('admin.halaman-unit.index'), 'Kembali'];
    }

    private function validatedFilters(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'until' => ['nullable', 'date'],
        ]);

        return array_filter([
            'from' => isset($data['from']) && is_string($data['from']) && trim($data['from']) !== '' ? $data['from'] : null,
            'until' => isset($data['until']) && is_string($data['until']) && trim($data['until']) !== '' ? $data['until'] : null,
        ]);
    }
}