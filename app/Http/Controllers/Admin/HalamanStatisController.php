<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HalamanStatis;
use App\Models\PageSection;
use App\Support\PageFormBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class HalamanStatisController extends Controller
{
    // ── Super admin: list & manage pages ─────────────────────────────────────

    public function index()
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);
        $pages = HalamanStatis::whereNotIn('kunci', HalamanStatis::systemKeys())
            ->orderBy('kunci')
            ->get();
        return view('admin.halaman-statis.index', compact('pages'));
    }

    public function storeMeta(Request $request)
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'kunci'       => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/',
                              'unique:halaman_statis,kunci'],
            'menu_label'  => 'required|string|max:60',
            'menu_icon'   => 'nullable|string|max:10',
        ]);

        HalamanStatis::create([
            'kunci'      => $data['kunci'],
            'judul'      => $data['menu_label'],
            'menu_label' => $data['menu_label'],
            'menu_icon'  => $data['menu_icon'] ?: '📄',
            'konten'     => '',
        ]);

        return redirect()->route('admin.halaman-statis.index')
            ->with('success', 'Halaman "' . $data['menu_label'] . '" berhasil ditambahkan. Atur hak akses di menu Pengguna.');
    }

    public function destroyMeta(string $kunci)
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        $halaman = HalamanStatis::where('kunci', $kunci)->firstOrFail();
        $halaman->delete();

        return back()->with('success', 'Halaman statis dihapus.');
    }

    // ── Per-unit: edit content ────────────────────────────────────────────────

    private function authorizeKunci(string $kunci): void
    {
        $user = Auth::user();
        if ($user->isSuperAdmin()) return;
        if (!$user->hasRoleAccess('halaman-statis:' . $kunci)) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit halaman ini.');
        }
    }

    public function edit(string $kunci)
    {
        // Allow any kunci that exists in DB or that super admin can create
        $halaman = HalamanStatis::firstOrCreate(
            ['kunci' => $kunci],
            [
                'judul'      => HalamanStatis::$kunciLabels[$kunci] ?? ucwords(str_replace('_', ' ', $kunci)),
                'menu_label' => HalamanStatis::$kunciLabels[$kunci] ?? ucwords(str_replace('_', ' ', $kunci)),
                'menu_icon'  => '📄',
                'konten'     => '',
            ]
        );

        $this->authorizeKunci($kunci);

        $sections = PageSection::where('halaman_statis_id', $halaman->id)
            ->orderBy('urutan')->get();

        return view('admin.halaman-statis.edit', compact('halaman', 'sections'));
    }

    public function update(Request $request, string $kunci)
    {
        $halaman = HalamanStatis::where('kunci', $kunci)->firstOrFail();
        $this->authorizeKunci($kunci);

        $data = $request->validate([
            'judul'       => 'required|string|max:255',
            'konten'      => 'nullable|string',
            'data_ekstra' => 'nullable|json',
        ]);

        $data['updated_by'] = Auth::id();

        if (isset($data['data_ekstra']) && is_string($data['data_ekstra'])) {
            $data['data_ekstra'] = json_decode($data['data_ekstra'], true);
        }

        $halaman->update($data);

        return back()->with('success', 'Halaman berhasil disimpan.');
    }

    // ── Section (Block Builder) management ───────────────────────────────────

    public function createSection(string $kunci)
    {
        $halaman = HalamanStatis::where('kunci', $kunci)->firstOrFail();
        $this->authorizeKunci($kunci);
        return view('admin.halaman-unit.section-form', [
            'page'        => $halaman,
            'section'     => new PageSection(),
            'contextType' => 'statis',
        ]);
    }

    public function storeSection(Request $request, string $kunci)
    {
        $halaman = HalamanStatis::where('kunci', $kunci)->firstOrFail();
        $this->authorizeKunci($kunci);

        $request->validate([
            'tipe'  => 'required|string|in:' . implode(',', PageSection::creatableTypeKeys()),
            'judul' => 'nullable|string|max:200',
            'ai_draft_media' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif|max:10240',
        ]);

        $konfigurasi = json_decode($request->input('konfigurasi', '{}'), true) ?? [];
        $konfigurasi = PageFormBlock::normalizeConfigForStorage($konfigurasi, (string) $request->tipe);
        if (in_array((string) $request->tipe, ['ai_teks', 'hero'])) {
            $konfigurasi = PageFormBlock::attachAiTextUploadedMedia(
                $konfigurasi,
                $request->file('ai_draft_media'),
                trim((string) ($request->judul ?: ($konfigurasi['_prompt_acara'] ?? '')))
            );
        }
        $maxUrutan   = PageSection::where('halaman_statis_id', $halaman->id)->max('urutan') ?? -1;

        PageSection::create([
            'halaman_statis_id' => $halaman->id,
            'tipe'              => $request->tipe,
            'judul'             => $request->judul,
            'konfigurasi'       => $konfigurasi,
            'urutan'            => $maxUrutan + 1,
            'aktif'             => true,
        ]);

        return redirect()->route('admin.halaman-statis.edit', $kunci)
            ->with('success', 'Blok berhasil ditambahkan.');
    }

    public function editSection(PageSection $section)
    {
        $halaman = $section->halamanStatis;
        abort_unless($halaman !== null, 404);
        $this->authorizeKunci($halaman->kunci);
        return view('admin.halaman-unit.section-form', [
            'page'        => $halaman,
            'section'     => $section,
            'contextType' => 'statis',
        ]);
    }

    public function updateSection(Request $request, PageSection $section)
    {
        $halaman = $section->halamanStatis;
        abort_unless($halaman !== null, 404);
        $this->authorizeKunci($halaman->kunci);

        $request->validate([
            'tipe'  => 'required|string|in:' . implode(',', array_keys(PageSection::$types)),
            'judul' => 'nullable|string|max:200',
            'ai_draft_media' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif|max:10240',
        ]);

        $konfigurasi = PageFormBlock::normalizeConfigForStorage(
            json_decode($request->input('konfigurasi', '{}'), true) ?? [],
            (string) $request->tipe
        );
        if (in_array((string) $request->tipe, ['ai_teks', 'hero'])) {
            $konfigurasi = PageFormBlock::attachAiTextUploadedMedia(
                $konfigurasi,
                $request->file('ai_draft_media'),
                trim((string) ($request->judul ?: ($konfigurasi['_prompt_acara'] ?? '')))
            );
        }

        $section->update([
            'tipe'        => $request->tipe,
            'judul'       => $request->judul,
            'konfigurasi' => $konfigurasi,
        ]);

        return redirect()->route('admin.halaman-statis.edit', $halaman->kunci)
            ->with('success', 'Blok berhasil diperbarui.');
    }

    public function destroySection(PageSection $section)
    {
        $halaman = $section->halamanStatis;
        abort_unless($halaman !== null, 404);
        $this->authorizeKunci($halaman->kunci);
        $section->delete();
        return back()->with('success', 'Blok dihapus.');
    }

    public function toggleSection(PageSection $section)
    {
        $halaman = $section->halamanStatis;
        abort_unless($halaman !== null, 404);
        $this->authorizeKunci($halaman->kunci);
        $section->update(['aktif' => !$section->aktif]);
        return back();
    }

    public function moveSection(Request $request, PageSection $section)
    {
        $halaman = $section->halamanStatis;
        abort_unless($halaman !== null, 404);
        $this->authorizeKunci($halaman->kunci);

        $direction = $request->input('direction');
        $sibling   = PageSection::where('halaman_statis_id', $halaman->id)
            ->when($direction === 'up',
                fn ($q) => $q->where('urutan', '<', $section->urutan)->orderByDesc('urutan'),
                fn ($q) => $q->where('urutan', '>', $section->urutan)->orderBy('urutan')
            )->first();

        if ($sibling) {
            [$section->urutan, $sibling->urutan] = [$sibling->urutan, $section->urutan];
            $section->save();
            $sibling->save();
        }
        return back();
    }
}
