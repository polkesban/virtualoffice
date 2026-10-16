<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HalamanStatis;
use App\Models\PageSection;
use App\Models\ProgramStudi;
use App\Models\UnitPage;
use App\Models\User;
use App\Support\MediaStorage;
use App\Support\PageFormBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HalamanUnitController extends Controller
{
    // ─── Index: super admin sees all pages, others see their own page ──────────
    public function index()
    {
        $user = auth()->user();

        if ($user->role === User::ROLE_SUPER_ADMIN) {
            $pages = UnitPage::with(['programStudi'])->withCount('sections')->orderBy('judul')->get();
            return view('admin.halaman-unit.all', compact('pages'));
        }

        if ($user->isJurusanAdmin()) {
            $allowedPageIds = [];
            if ($user->managed_unit_page_id) {
                $allowedPageIds[] = $user->managed_unit_page_id;
            }
            if ($user->jurusan_id) {
                $prodis = ProgramStudi::where('jurusan_id', $user->jurusan_id)->get();
                foreach ($prodis as $prodi) {
                    $prodiPage = $this->getOrCreatePageForProdi($prodi);
                    $allowedPageIds[] = $prodiPage->id;
                }
            }

            $pages = UnitPage::whereIn('id', array_unique($allowedPageIds))->with(['programStudi'])->withCount('sections')->orderBy('judul')->get();
            return view('admin.halaman-unit.all', compact('pages'));
        }

        if ($user->managedUnitPage) {
            $page = $user->managedUnitPage;
            $sections = $page->sections()->orderBy('urutan')->get();

            return view('admin.halaman-unit.index', compact('page', 'sections'));
        }

        // Kaprodi: unit_page is per-prodi, not shared by role
        if ($user->isKaprodi()) {
            $prodi = $user->programStudi;
            if (!$prodi) abort(403, 'Akun kaprodi belum ditugaskan ke program studi manapun.');
            $page = $this->getOrCreatePageForProdi($prodi);
            $sections = $page->sections()->orderBy('urutan')->get();
            return view('admin.halaman-unit.index', compact('page', 'sections'));
        }

        // Dosen / Employee: individual unit page per user
        if ($user->isDosen() || $user->isEmployee()) {
            $page = $this->getOrCreatePageForUser($user);
            $sections = $page->sections()->orderBy('urutan')->get();
            return view('admin.halaman-unit.index', compact('page', 'sections'));
        }

        $page     = UnitPage::getOrCreateForRole($user->role);
        $sections = $page->sections()->orderBy('urutan')->get();

        return view('admin.halaman-unit.index', compact('page', 'sections'));
    }

    // ─── Get or auto-create unit_page for an individual user ────────────────────
    private function getOrCreatePageForUser(User $user): UnitPage
    {
        if ($user->managedUnitPage) {
            return $user->managedUnitPage;
        }

        $roleKey = 'user_' . $user->id;
        $rolePrefix = ($user->role === User::ROLE_DOSEN) ? 'dosen-' : 'pegawai-';
        $roleTitlePrefix = ($user->role === User::ROLE_DOSEN) ? 'Halaman Dosen' : 'Halaman Pegawai';

        $page = UnitPage::firstOrCreate(
            ['role' => $roleKey],
            [
                'slug'       => $rolePrefix . Str::slug($user->name) . '-' . $user->id,
                'judul'      => $user->name,
                'sub_judul'  => $roleTitlePrefix . ' — ' . $user->name,
                'warna_tema' => '#1d4ed8',
                'aktif'      => true,
            ]
        );

        if ((int) $user->managed_unit_page_id !== (int) $page->id) {
            $user->update(['managed_unit_page_id' => $page->id]);
        }

        return $page;
    }

    // ─── Get or auto-create unit_page for a specific program studi ─────────────
    private function getOrCreatePageForProdi(ProgramStudi $prodi): UnitPage
    {
        if ($prodi->unitPage) {
            return $prodi->unitPage;
        }

        $page = UnitPage::create([
            'role'       => 'kaprodi_' . $prodi->id,
            'slug'       => 'prodi-' . $prodi->slug,
            'judul'      => $prodi->nama,
            'aktif'      => true,
            'warna_tema' => '#1d4ed8',
        ]);
        $prodi->update(['unit_page_id' => $page->id]);

        return $page;
    }

    // ─── Super admin jumps to a specific page ──────────────────────────────────
    public function manage(UnitPage $unitPage)
    {
        $this->authorize_page($unitPage);
        $sections = $unitPage->sections()->orderBy('urutan')->get();
        return view('admin.halaman-unit.index', ['page' => $unitPage, 'sections' => $sections]);
    }

    // ─── Edit page metadata ─────────────────────────────────────────────────────
    public function edit(UnitPage $unitPage)
    {
        $this->authorize_page($unitPage);
        return view('admin.halaman-unit.edit-page', ['page' => $unitPage]);
    }

    public function update(Request $request, UnitPage $unitPage)
    {
        $this->authorize_page($unitPage);

        $data = $request->validate([
            'judul'      => 'required|string|max:200',
            'sub_judul'  => 'nullable|string|max:500',
            'warna_tema' => 'nullable|string|max:20',
            'cover_foto' => 'nullable|image|max:4096',
            'aktif'      => 'boolean',
        ]);

        $data['aktif'] = $request->boolean('aktif');

        if ($request->hasFile('cover_foto')) {
            MediaStorage::delete($unitPage->cover_foto);
            $data['cover_foto'] = MediaStorage::storeUploadedFile($request->file('cover_foto'), 'unit-pages');
        }

        $unitPage->update($data);

        return redirect()->route('admin.halaman-unit.manage', $unitPage)
            ->with('success', 'Pengaturan halaman berhasil disimpan.');
    }

    // ─── Add section ────────────────────────────────────────────────────────────
    public function createSection(UnitPage $unitPage)
    {
        $this->authorize_page($unitPage);
        return view('admin.halaman-unit.section-form', [
            'page'    => $unitPage,
            'section' => new PageSection(),
        ]);
    }

    public function storeSection(Request $request, UnitPage $unitPage)
    {
        $this->authorize_page($unitPage);

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

        $maxUrutan = $unitPage->sections()->max('urutan') ?? -1;

        PageSection::create([
            'unit_page_id' => $unitPage->id,
            'tipe'         => $request->tipe,
            'judul'        => $request->judul,
            'konfigurasi'  => $konfigurasi,
            'urutan'       => $maxUrutan + 1,
            'aktif'        => true,
        ]);

        return redirect()->route('admin.halaman-unit.manage', $unitPage)
            ->with('success', 'Blok berhasil ditambahkan.');
    }

    // ─── Edit section ────────────────────────────────────────────────────────────
    public function editSection(PageSection $section)
    {
        $this->authorize_page($section->page);
        return view('admin.halaman-unit.section-form', [
            'page'    => $section->page,
            'section' => $section,
        ]);
    }

    public function updateSection(Request $request, PageSection $section)
    {
        $this->authorize_page($section->page);

        $request->validate([
            'tipe'  => 'required|string|in:' . implode(',', array_keys(PageSection::$types)),
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

        $section->update([
            'tipe'        => $request->tipe,
            'judul'       => $request->judul,
            'konfigurasi' => $konfigurasi,
        ]);

        return redirect()->route('admin.halaman-unit.manage', $section->page)
            ->with('success', 'Blok berhasil diperbarui.');
    }

    // ─── Delete section ──────────────────────────────────────────────────────────
    public function destroySection(PageSection $section)
    {
        $page = $section->page;
        $this->authorize_page($page);
        $section->delete();
        return back()->with('success', 'Blok dihapus.');
    }

    // ─── Toggle section active ───────────────────────────────────────────────────
    public function toggleSection(PageSection $section)
    {
        $this->authorize_page($section->page);
        $section->update(['aktif' => !$section->aktif]);
        return back()->with('success', 'Status blok diubah.');
    }

    // ─── Reorder sections ────────────────────────────────────────────────────────
    public function moveSection(Request $request, PageSection $section)
    {
        $this->authorize_page($section->page);

        $direction = $request->input('direction'); // 'up' | 'down'
        $sibling   = PageSection::where('unit_page_id', $section->unit_page_id)
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

    private function authorize_page(UnitPage $page): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) return;

        if ($user->isWadir1() && $page->programStudi) {
            return;
        }

        if ($user->managed_unit_page_id && (int) $user->managed_unit_page_id === (int) $page->id) {
            return;
        }

        if ($page->role === 'user_' . $user->id) {
            return;
        }

        // Kaprodi: verify the page belongs to their assigned prodi
        if ($user->isKaprodi()) {
            $linkedProdi = $page->programStudi;
            if ($linkedProdi && $linkedProdi->id === $user->program_studi_id) return;
            abort(403, 'Anda tidak memiliki akses ke halaman unit ini.');
        }

        // Kajur / Sekjur / Admin Jurusan: verify the page is either their managed department page or under their department prodis
        if ($user->isJurusanAdmin()) {
            if ($user->managed_unit_page_id && (int) $user->managed_unit_page_id === (int) $page->id) {
                return;
            }
            if ($user->jurusan_id) {
                $linkedProdi = $page->programStudi;
                if ($linkedProdi && $linkedProdi->jurusan_id === $user->jurusan_id) {
                    return;
                }
            }
            abort(403, 'Anda tidak memiliki akses ke halaman unit ini.');
        }

        if ($page->role !== $user->role) {
            abort(403, 'Anda tidak memiliki akses ke halaman unit ini.');
        }
    }

    // ─── Super admin: create new unit page ─────────────────────────────────────
    public function createPage()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        return view('admin.halaman-unit.create-page');
    }

    public function storePage(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'judul'      => 'required|string|max:200',
            'sub_judul'  => 'nullable|string|max:500',
            'slug'       => 'required|string|max:200|unique:unit_pages,slug|regex:/^[a-z0-9\-]+$/',
            'role'       => 'required|string|max:80|unique:unit_pages,role',
            'warna_tema' => 'nullable|string|max:20',
        ]);

        UnitPage::create(array_merge($data, ['aktif' => true, 'warna_tema' => $data['warna_tema'] ?? '#1d4ed8']));

        return redirect()->route('admin.halaman-unit.index')
            ->with('success', 'Halaman unit "' . $data['judul'] . '" berhasil dibuat.');
    }

    public function destroyPage(UnitPage $unitPage)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        MediaStorage::delete($unitPage->cover_foto);
        $unitPage->sections()->delete();
        $unitPage->delete();

        return redirect()->route('admin.halaman-unit.index')
            ->with('success', 'Halaman unit dihapus.');
    }
}
