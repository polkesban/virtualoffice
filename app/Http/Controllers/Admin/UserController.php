<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramStudi;
use App\Models\UnitPage;
use App\Models\User;
use App\Support\AdminMenuCatalog;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['programStudi', 'managedUnitPage'])->orderBy('name')->get();
        return view('admin.users.index', compact('users'));
    }

    public function credentials()
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        return view('admin.users.credentials', $this->combinedCredentialViewData());
    }

    public function credentialsPrint()
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        return view('admin.users.credentials-print', $this->combinedCredentialViewData());
    }

    public function employeeCredentials(Request $request)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        return view('admin.users.employee-credentials', $this->employeeCredentialViewData($request->query('file')));
    }

    public function employeeCredentialsPrint(Request $request)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        return view('admin.users.employee-credentials-print', $this->employeeCredentialViewData($request->query('file')));
    }

    public function downloadEmployeeCredentials(Request $request)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $path = $this->employeeCredentialFilePath($request->query('file'));
        abort_if(! $path, 404, 'File kredensial pegawai belum tersedia.');

        return response()->download($path, basename($path));
    }

    public function loginAs(User $user)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        abort_if($user->isSuperAdmin(), 403, 'Tidak dapat berpindah ke akun Super Admin lain.');

        // Store original admin ID so we can return
        session(['login_as_original_admin_id' => auth()->id()]);
        auth()->login($user);

        if ($user->is_admin) {
            return redirect()->route('admin.dashboard')
                ->with('info', 'Anda sedang login sebagai ' . $user->name . '. Klik "Kembali ke Admin" untuk keluar.');
        }

        return redirect()->route('home')
            ->with('info', 'Anda sedang login sebagai ' . $user->name . '.');
    }

    public function returnToAdmin()
    {
        $originalId = session('login_as_original_admin_id');
        abort_if(empty($originalId), 403, 'Tidak ada sesi login-as aktif.');

        $original = User::findOrFail($originalId);
        abort_unless($original->isSuperAdmin(), 403);

        session()->forget('login_as_original_admin_id');
        auth()->login($original);

        return redirect()->route('admin.users.index')
            ->with('success', 'Berhasil kembali ke akun Super Admin.');
    }

    public function create()
    {
        User::refreshRoleConfig();
        $prodiList = ProgramStudi::aktif()->get();
        $jurusanList = \App\Models\Jurusan::orderBy('nama')->get();
        $managedPageList = UnitPage::orderBy('judul')->get();
        $allMenus = $this->buildAllMenus();
        $roleMenus = User::$roleMenus;
        $roleLabels = User::$roleLabels;
        return view('admin.users.form', [
            'user'      => new User(),
            'prodiList' => $prodiList,
            'jurusanList' => $jurusanList,
            'managedPageList' => $managedPageList,
            'allMenus'  => $allMenus,
            'roleLabels' => $roleLabels,
            'roleMenus' => $roleMenus,
        ]);
    }
    public function store(Request $request)
    {
        User::refreshRoleConfig();
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|string|unique:users,email',
            'identity_number'  => 'nullable|string|max:50|unique:users,identity_number',
            'password'         => 'required|string|min:8|confirmed',
            'role'             => 'required|in:' . implode(',', array_keys(User::$roleLabels)),
            'program_studi_id' => [Rule::requiredIf(fn () => $request->input('role') === User::ROLE_KAPRODI), 'nullable', 'exists:program_studi,id'],
            'jurusan_id'       => [Rule::requiredIf(fn () => in_array($request->input('role'), [User::ROLE_KAJUR, User::ROLE_SEKJUR, User::ROLE_ADMIN_JURUSAN])), 'nullable', 'exists:jurusan,id'],
            'managed_unit_page_id' => [Rule::requiredIf(fn () => in_array($request->input('role'), [User::ROLE_PAGE_ADMIN, User::ROLE_KAJUR, User::ROLE_SEKJUR, User::ROLE_ADMIN_JURUSAN])), 'nullable', 'exists:unit_pages,id'],
            'is_admin'         => 'boolean',
            'prodi_d3_id'      => 'nullable|exists:program_studi,id',
            'prodi_d4_id'      => 'nullable|exists:program_studi,id',
            'prodi_profesi_id' => 'nullable|exists:program_studi,id',
        ]);

        $data['password']     = Hash::make($data['password']);
        $data['identity_number'] = $this->normalizeIdentityNumber($data['identity_number'] ?? null);
        $data['is_admin']     = $request->boolean('is_admin');
        $data['custom_menus'] = $this->parseCustomMenus($request);
        $data['managed_unit_page_id'] = in_array($data['role'], [User::ROLE_PAGE_ADMIN, User::ROLE_KAJUR, User::ROLE_SEKJUR, User::ROLE_ADMIN_JURUSAN, User::ROLE_DOSEN, User::ROLE_EMPLOYEE], true) ? ($data['managed_unit_page_id'] ?? null) : null;

        if (in_array($data['role'], [User::ROLE_KAJUR, User::ROLE_SEKJUR, User::ROLE_ADMIN_JURUSAN])) {
            $data['jurusan_id'] = $request->input('jurusan_id');
            
            // Map the selected prodi pages to this Jurusan
            $selectedProdiIds = array_filter([
                $request->input('prodi_d3_id'),
                $request->input('prodi_d4_id'),
                $request->input('prodi_profesi_id'),
            ]);
            
            if ($data['jurusan_id']) {
                // Clear old prodi mappings for this jurusan
                ProgramStudi::where('jurusan_id', $data['jurusan_id'])->update(['jurusan_id' => null]);
                
                // Set new ones
                if (!empty($selectedProdiIds)) {
                    ProgramStudi::whereIn('id', $selectedProdiIds)->update(['jurusan_id' => $data['jurusan_id']]);
                }
            }
            
            $data['program_studi_id'] = reset($selectedProdiIds) ?: null;
        } else {
            $data['jurusan_id'] = null;
            $data['program_studi_id'] = $data['role'] === User::ROLE_KAPRODI ? $data['program_studi_id'] : null;
        }

        User::create($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        User::refreshRoleConfig();
        $prodiList = ProgramStudi::aktif()->get();
        $jurusanList = \App\Models\Jurusan::orderBy('nama')->get();
        $managedPageList = UnitPage::orderBy('judul')->get();
        $allMenus = $this->buildAllMenus();
        $roleMenus = User::$roleMenus;
        $roleLabels = User::$roleLabels;
        return view('admin.users.form', compact('user', 'prodiList', 'jurusanList', 'managedPageList', 'allMenus', 'roleLabels', 'roleMenus'));
    }

    public function update(Request $request, User $user)
    {
        User::refreshRoleConfig();
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => ['required', 'string', Rule::unique('users')->ignore($user->id)],
            'identity_number'  => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'password'         => 'nullable|string|min:8|confirmed',
            'role'             => 'required|in:' . implode(',', array_keys(User::$roleLabels)),
            'program_studi_id' => [Rule::requiredIf(fn () => $request->input('role') === User::ROLE_KAPRODI), 'nullable', 'exists:program_studi,id'],
            'jurusan_id'       => [Rule::requiredIf(fn () => in_array($request->input('role'), [User::ROLE_KAJUR, User::ROLE_SEKJUR, User::ROLE_ADMIN_JURUSAN])), 'nullable', 'exists:jurusan,id'],
            'managed_unit_page_id' => [Rule::requiredIf(fn () => in_array($request->input('role'), [User::ROLE_PAGE_ADMIN, User::ROLE_KAJUR, User::ROLE_SEKJUR, User::ROLE_ADMIN_JURUSAN])), 'nullable', 'exists:unit_pages,id'],
            'is_admin'         => 'boolean',
            'prodi_d3_id'      => 'nullable|exists:program_studi,id',
            'prodi_d4_id'      => 'nullable|exists:program_studi,id',
            'prodi_profesi_id' => 'nullable|exists:program_studi,id',
        ]);

        $data['identity_number'] = $this->normalizeIdentityNumber($data['identity_number'] ?? null);
        $data['is_admin']     = $request->boolean('is_admin');
        $data['custom_menus'] = $this->parseCustomMenus($request);
        $data['managed_unit_page_id'] = in_array($data['role'], [User::ROLE_PAGE_ADMIN, User::ROLE_KAJUR, User::ROLE_SEKJUR, User::ROLE_ADMIN_JURUSAN, User::ROLE_DOSEN, User::ROLE_EMPLOYEE], true) ? ($data['managed_unit_page_id'] ?? $user->managed_unit_page_id) : null;

        if (in_array($data['role'], [User::ROLE_KAJUR, User::ROLE_SEKJUR, User::ROLE_ADMIN_JURUSAN])) {
            $data['jurusan_id'] = $request->input('jurusan_id');
            
            // Map the selected prodi pages to this Jurusan
            $selectedProdiIds = array_filter([
                $request->input('prodi_d3_id'),
                $request->input('prodi_d4_id'),
                $request->input('prodi_profesi_id'),
            ]);
            
            if ($data['jurusan_id']) {
                // Clear old prodi mappings for this jurusan
                ProgramStudi::where('jurusan_id', $data['jurusan_id'])->update(['jurusan_id' => null]);
                
                // Set new ones
                if (!empty($selectedProdiIds)) {
                    ProgramStudi::whereIn('id', $selectedProdiIds)->update(['jurusan_id' => $data['jurusan_id']]);
                }
            }
            
            $data['program_studi_id'] = reset($selectedProdiIds) ?: null;
        } else {
            $data['jurusan_id'] = null;
            $data['program_studi_id'] = $data['role'] === User::ROLE_KAPRODI ? $data['program_studi_id'] : null;
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** All available non-super-admin menus grouped for the form UI */
    private function buildAllMenus(): array
    {
        return AdminMenuCatalog::grouped();
    }

    /**
     * Convert checkbox input into custom_menus array.
     * Returns null if super_admin (no restriction needed).
     */
    private function parseCustomMenus(Request $request): ?array
    {
        if ($request->input('role') === 'super_admin') {
            return null;
        }
        // If admin chose "use role defaults", return null to clear override
        if ($request->boolean('use_role_defaults')) {
            return null;
        }
        return $request->input('custom_menus', []) ?: [];
    }

    private function normalizeIdentityNumber(?string $value): ?string
    {
        $normalized = Str::upper((string) preg_replace('/[^A-Za-z0-9]+/', '', trim((string) $value)));

        return $normalized !== '' ? $normalized : null;
    }

    private function employeeCredentialFilePath(?string $selectedFilename = null): ?string
    {
        $files = glob(storage_path('app/private/imports/duk-pegawai-credentials-*.xlsx')) ?: [];

        if ($files === []) {
            return null;
        }

        usort($files, fn (string $left, string $right) => filemtime($right) <=> filemtime($left));

        if ($selectedFilename) {
            $target = storage_path('app/private/imports/' . basename($selectedFilename));
            if (in_array($target, $files)) {
                return $target;
            }
        }

        return $files[0] ?? null;
    }

    private function employeeCredentialViewData(?string $selectedFilename = null): array
    {
        $allFiles = glob(storage_path('app/private/imports/duk-pegawai-credentials-*.xlsx')) ?: [];
        usort($allFiles, fn (string $left, string $right) => filemtime($right) <=> filemtime($left));

        $path = $this->employeeCredentialFilePath($selectedFilename);

        $availableFiles = collect($allFiles)->map(function ($filePath) {
            $filename = basename($filePath);
            $timeLabel = '';
            if (preg_match('/duk-pegawai-credentials-(\d{8})-(\d{6})\.xlsx/', $filename, $matches)) {
                $timeLabel = \Carbon\Carbon::createFromFormat('Ymd-His', $matches[1] . '-' . $matches[2])->translatedFormat('d M Y H:i');
            } else {
                $timeLabel = date('d M Y H:i', filemtime($filePath));
            }
            return [
                'filename' => $filename,
                'label' => $timeLabel . ' (' . $filename . ')',
            ];
        });

        if (! $path) {
            return [
                'employeeCredentialRows' => collect(),
                'employeeCredentialSummary' => [
                    'total' => 0,
                    'email_sources' => [],
                ],
                'employeeCredentialFileName' => null,
                'employeeCredentialGeneratedAt' => null,
                'employeeCredentialSourceLabel' => null,
                'pegawaiLoginUrl' => route('pegawai.login'),
                'employeeCredentialDownloadUrl' => null,
                'availableFiles' => $availableFiles,
                'selectedFilename' => null,
            ];
        }

        $rows = $this->readEmployeeCredentialRows($path);
        $sourceCell = trim((string) IOFactory::load($path)->getSheet(0)->getCell('A2')->getFormattedValue());
        $sourceLabel = Str::of($sourceCell)->after('Sumber DUK: ')->trim()->toString();

        return [
            'employeeCredentialRows' => $rows,
            'employeeCredentialSummary' => [
                'total' => $rows->count(),
                'email_sources' => $rows->countBy('email_source')->sortKeys()->all(),
            ],
            'employeeCredentialFileName' => basename($path),
            'employeeCredentialGeneratedAt' => Carbon::createFromTimestamp((int) filemtime($path)),
            'employeeCredentialSourceLabel' => $sourceLabel !== '' ? $sourceLabel : null,
            'pegawaiLoginUrl' => route('pegawai.login'),
            'employeeCredentialDownloadUrl' => route('admin.users.employee-credentials.download', ['file' => basename($path)]),
            'availableFiles' => $availableFiles,
            'selectedFilename' => basename($path),
        ];
    }

    private function combinedCredentialViewData(): array
    {
        return array_merge(
            $this->credentialViewData(),
            $this->employeeCredentialViewData(request('file')),
        );
    }

    private function readEmployeeCredentialRows(string $path): Collection
    {
        $sheet = IOFactory::load($path)->getSheet(0);
        $highestRow = $sheet->getHighestDataRow();
        $rows = collect();

        for ($row = 6; $row <= $highestRow; $row++) {
            $identityNumber = trim((string) $sheet->getCell('C' . $row)->getFormattedValue());

            if ($identityNumber === '') {
                continue;
            }

            $rows->push([
                'no' => trim((string) $sheet->getCell('A' . $row)->getFormattedValue()),
                'name' => trim((string) $sheet->getCell('B' . $row)->getFormattedValue()),
                'identity_number' => $identityNumber,
                'login_identifier' => trim((string) $sheet->getCell('D' . $row)->getFormattedValue()),
                'email' => trim((string) $sheet->getCell('E' . $row)->getFormattedValue()),
                'password' => $identityNumber,
                'email_source' => trim((string) $sheet->getCell('G' . $row)->getFormattedValue()),
                'position' => trim((string) $sheet->getCell('H' . $row)->getFormattedValue()),
                'phone' => trim((string) $sheet->getCell('I' . $row)->getFormattedValue()),
            ]);
        }

        return $rows;
    }

    private function credentialViewData(): array
    {
        $struktural = [
                ['no' => 1,  'jabatan' => 'Super Admin / Admin IT',         'email' => 'admin@polkesban.ac.id',    'password' => '— (sudah ada) —',  'menu' => 'Semua fitur'],
                ['no' => 2,  'jabatan' => 'Wakil Direktur I (Akademik)',    'email' => 'wadir1@polkesban.ac.id',   'password' => 'Polkesban@Wadir1', 'menu' => 'Berita, Pengumuman, Program Studi, Agenda'],
            ['no' => 3,  'jabatan' => 'Wakil Direktur II (Administrasi Umum)', 'email' => 'wadir2@polkesban.ac.id', 'password' => 'Polkesban@Wadir2', 'menu' => 'Halaman Bidang Administrasi Umum'],
            ['no' => 4,  'jabatan' => 'Unit Humas dan Kerjasama',       'email' => 'humas.kerjasama@polkesban.ac.id', 'password' => 'Polkesban@Humas26', 'menu' => 'Berita, Pengumuman, Galeri, Agenda, Halaman Humas & Kerja Sama'],
            ['no' => 5,  'jabatan' => 'Wakil Direktur III (Kemahasiswaan)', 'email' => 'wadir3@polkesban.ac.id', 'password' => 'Polkesban@Wadir3', 'menu' => 'Berita, Pengumuman, Berprestasi, Agenda'],
            ['no' => 6,  'jabatan' => 'Pusat Penjaminan Mutu',          'email' => 'mutu@polkesban.ac.id',     'password' => 'Polkesban@Mutu24', 'menu' => 'Hal. Akreditasi, Visi & Misi, Dokumen Mutu'],
            ['no' => 7,  'jabatan' => 'Pusat Litmas & Pengabmas',       'email' => 'litmas@polkesban.ac.id',   'password' => 'Polkesban@Litmas', 'menu' => 'Berita, Pengumuman, Agenda, Publikasi & Penelitian'],
            ['no' => 8,  'jabatan' => 'Kepala Pusat Pengembangan Pendidikan',  'email' => 'p3@polkesban.ac.id',       'password' => 'Polkesban@P3ban',  'menu' => 'Berita, Pengumuman, Pengembangan Kurikulum, IPE, Pusat Unggulan IPTEK'],
            ['no' => 9,  'jabatan' => 'Unit Teknologi Informasi',       'email' => 'ti@polkesban.ac.id',       'password' => 'Polkesban@UnitTI', 'menu' => 'Layanan IT, Hal. Sejarah'],
            ['no' => 10, 'jabatan' => 'Unit Perpustakaan Terpadu',      'email' => 'perpus@polkesban.ac.id',   'password' => 'Polkesban@Perpus', 'menu' => 'Koleksi Perpustakaan, Hal. Perpustakaan, Berita'],
            ['no' => 11, 'jabatan' => 'Unit Laboratorium Terpadu',      'email' => 'lab@polkesban.ac.id',      'password' => 'Polkesban@UnitLab','menu' => 'Layanan & Tarif Lab, Berita, Hal. Lab'],
            ['no' => 12, 'jabatan' => 'Unit Bahasa',                    'email' => 'bahasa@polkesban.ac.id',   'password' => 'Polkesban@Bahasa', 'menu' => 'Halaman Unit Bahasa, Berita, Pengumuman, Agenda'],
            ['no' => 13, 'jabatan' => 'Unit Bisnis',                    'email' => 'bisnis@polkesban.ac.id',   'password' => 'Polkesban@Bisnis', 'menu' => 'Layanan & Produk, Fasilitas Asrama, Galeri, Berita, Hal. Kerjasama'],
            ['no' => 14, 'jabatan' => 'Unit Asrama',                    'email' => 'asrama@polkesban.ac.id',   'password' => 'Polkesban@Asrama', 'menu' => 'Berita, Hal. Asrama'],
            ['no' => 15, 'jabatan' => 'Satuan Pengawas Internal (SPI)', 'email' => 'spi@polkesban.ac.id',      'password' => 'Polkesban@SPI247', 'menu' => 'Login saja (tidak ada menu edit)'],
        ];

        $kaprodi = [
                ['no' => 1, 'prodi' => 'D4 Sarjana Terapan Keperawatan', 'email' => 'kaprodi.kep.d4@polkesban.ac.id', 'password' => 'Kaprodi@Kep01'],
                ['no' => 2, 'prodi' => 'D4 Sarjana Terapan Kebidanan', 'email' => 'kaprodi.keb.d4@polkesban.ac.id', 'password' => 'Kaprodi@Keb02'],
                ['no' => 3, 'prodi' => 'D4 Gizi & Dietetika', 'email' => 'kaprodi.gizi.d4@polkesban.ac.id', 'password' => 'Kaprodi@Giz03'],
                ['no' => 4, 'prodi' => 'D4 Fisioterapi', 'email' => 'kaprodi.fisio.d4@polkesban.ac.id', 'password' => 'Kaprodi@Fis04'],
                ['no' => 5, 'prodi' => 'D4 Sanitasi Lingkungan', 'email' => 'kaprodi.sl.d4@polkesban.ac.id', 'password' => 'Kaprodi@SL005'],
                ['no' => 6, 'prodi' => 'D3 Teknologi Laboratorium Medis', 'email' => 'kaprodi.tlm.d3@polkesban.ac.id', 'password' => 'Kaprodi@TLM06'],
                ['no' => 7, 'prodi' => 'D3 Farmasi', 'email' => 'kaprodi.farmasi@polkesban.ac.id', 'password' => 'Kaprodi@Far07'],
                ['no' => 8, 'prodi' => 'D3 Rekam Medis & Informasi Kesehatan', 'email' => 'kaprodi.rmik@polkesban.ac.id', 'password' => 'Kaprodi@RMIK08'],
                ['no' => 9, 'prodi' => 'D4 Promosi Kesehatan', 'email' => 'kaprodi.prokes@polkesban.ac.id', 'password' => 'Kaprodi@PK009'],
                ['no' => 10, 'prodi' => 'D4 Sarjana Terapan TLM', 'email' => 'kaprodi.tlm.d4@polkesban.ac.id', 'password' => 'Kaprodi@TLM10'],
                ['no' => 11, 'prodi' => 'D4 Terapi Gigi', 'email' => 'kaprodi.tg@polkesban.ac.id', 'password' => 'Kaprodi@TG011'],
                ['no' => 12, 'prodi' => 'D3 Keperawatan Bandung', 'email' => 'kaprodi.kep.bdg@polkesban.ac.id', 'password' => 'Kaprodi@KB012'],
                ['no' => 13, 'prodi' => 'D3 Keperawatan Bogor', 'email' => 'kaprodi.kep.bgr@polkesban.ac.id', 'password' => 'Kaprodi@KBg13'],
                ['no' => 14, 'prodi' => 'D3 Kebidanan Bandung', 'email' => 'kaprodi.keb.bdg@polkesban.ac.id', 'password' => 'Kaprodi@KB014'],
                ['no' => 15, 'prodi' => 'D3 Kebidanan Karawang', 'email' => 'kaprodi.keb.krw@polkesban.ac.id', 'password' => 'Kaprodi@KKr15'],
                ['no' => 16, 'prodi' => 'D3 Kebidanan Bogor', 'email' => 'kaprodi.keb.bgr@polkesban.ac.id', 'password' => 'Kaprodi@KBo16'],
                ['no' => 17, 'prodi' => 'D3 Kesehatan Gigi', 'email' => 'kaprodi.kg@polkesban.ac.id', 'password' => 'Kaprodi@KG017'],
                ['no' => 18, 'prodi' => 'D3 Sanitasi & Kesehatan Lingkungan', 'email' => 'kaprodi.sanit@polkesban.ac.id', 'password' => 'Kaprodi@San18'],
                ['no' => 19, 'prodi' => 'Profesi Ners', 'email' => 'kaprodi.ners@polkesban.ac.id', 'password' => 'Kaprodi@Ner19'],
                ['no' => 20, 'prodi' => 'Profesi Bidan', 'email' => 'kaprodi.bidan@polkesban.ac.id', 'password' => 'Kaprodi@Bid20'],
                ['no' => 21, 'prodi' => 'Profesi Dietisien', 'email' => 'kaprodi.dietisien@polkesban.ac.id', 'password' => 'Kaprodi@Die21'],
                ['no' => 22, 'prodi' => 'D3 Gizi', 'email' => 'kaprodi.gizi.d3@polkesban.ac.id', 'password' => 'Kaprodi@Giz22'],
        ];

        $pageAdmins = [
                ['no' => 1, 'halaman' => 'BEM KM Poltekkes Bandung', 'email' => 'admin.bemkm@polkesban.ac.id', 'password' => 'BemKm@Pol26!', 'menu' => 'Kelola halaman publik BEM KM Poltekkes Bandung'],
                ['no' => 2, 'halaman' => 'HIMA Keperawatan', 'email' => 'admin.hima.keperawatan@polkesban.ac.id', 'password' => 'HimaKep@Pol26!', 'menu' => 'Kelola halaman publik HIMA Keperawatan'],
                ['no' => 3, 'halaman' => 'HIMA Kebidanan', 'email' => 'admin.hima.kebidanan@polkesban.ac.id', 'password' => 'HimaKeb@Pol26!', 'menu' => 'Kelola halaman publik HIMA Kebidanan'],
                ['no' => 4, 'halaman' => 'HIMA Gizi', 'email' => 'admin.hima.gizi@polkesban.ac.id', 'password' => 'HimaGizi@Pol26!', 'menu' => 'Kelola halaman publik HIMA Gizi'],
                ['no' => 5, 'halaman' => 'HIMA Kesehatan Lingkungan', 'email' => 'admin.hima.kesling@polkesban.ac.id', 'password' => 'HimaKesl@Pol26!', 'menu' => 'Kelola halaman publik HIMA Kesehatan Lingkungan'],
                ['no' => 6, 'halaman' => 'HIMA Teknologi Laboratorium Medis', 'email' => 'admin.hima.tlm@polkesban.ac.id', 'password' => 'HimaTlm@Pol26!', 'menu' => 'Kelola halaman publik HIMA Teknologi Laboratorium Medis'],
                ['no' => 7, 'halaman' => 'HIMA Kesehatan Gigi', 'email' => 'admin.hima.kesgigi@polkesban.ac.id', 'password' => 'HimaKg@Pol26!', 'menu' => 'Kelola halaman publik HIMA Kesehatan Gigi'],
                ['no' => 8, 'halaman' => 'HIMA Farmasi', 'email' => 'admin.hima.farmasi@polkesban.ac.id', 'password' => 'HimaFarm@Pol26!', 'menu' => 'Kelola halaman publik HIMA Farmasi'],
                ['no' => 9, 'halaman' => 'HIMA Promosi Kesehatan', 'email' => 'admin.hima.promkes@polkesban.ac.id', 'password' => 'HimaProm@Pol26!', 'menu' => 'Kelola halaman publik HIMA Promosi Kesehatan'],
        ];

        [$dynamicUnits, $dynamicPageAdmins] = $this->dynamicManagedPageCredentials($struktural, $pageAdmins);

        return [
            'struktural' => $this->renumberCredentials(array_merge($struktural, $dynamicUnits)),
            'kaprodi' => $kaprodi,
            'pageAdmins' => $this->renumberCredentials(array_merge($pageAdmins, $dynamicPageAdmins)),
        ];
    }

    private function dynamicManagedPageCredentials(array $struktural, array $pageAdmins): array
    {
        $knownEmails = collect(array_merge($struktural, $pageAdmins))
            ->pluck('email')
            ->filter()
            ->map(fn (string $email) => Str::lower($email))
            ->all();

        $managedUsers = User::query()
            ->with('managedUnitPage')
            ->where('role', User::ROLE_PAGE_ADMIN)
            ->whereNotNull('managed_unit_page_id')
            ->get()
            ->filter(fn (User $user) => $user->managedUnitPage)
            ->reject(fn (User $user) => in_array(Str::lower($user->email), $knownEmails, true));

        $dynamicUnits = [];
        $dynamicPageAdmins = [];

        foreach ($managedUsers as $user) {
            $page = $user->managedUnitPage;

            $passwordLabel = 'Manual / tidak tersimpan';
            $unitMenu = 'Halaman Unit Saya (' . $page->judul . ')';
            $pageMenu = 'Kelola halaman publik ' . $page->judul;

            if (str_starts_with((string) $page->role, 'unit_')) {
                $dynamicUnits[] = [
                    'jabatan' => $page->judul,
                    'email' => $user->email,
                    'password' => $passwordLabel,
                    'menu' => $unitMenu,
                ];

                continue;
            }

            $dynamicPageAdmins[] = [
                'halaman' => $page->judul,
                'email' => $user->email,
                'password' => $passwordLabel,
                'menu' => $pageMenu,
            ];
        }

        return [$dynamicUnits, $dynamicPageAdmins];
    }

    private function renumberCredentials(array $rows): array
    {
        return array_values(array_map(function (array $row, int $index) {
            $row['no'] = $index + 1;

            return $row;
        }, $rows, array_keys($rows)));
    }

    public function showChangePasswordForm(): \Illuminate\View\View
    {
        return view('admin.users.change-password');
    }

    public function changePassword(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.']);
        }

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return redirect()->route('admin.ubah-password')->with('success', 'Password Anda berhasil diperbarui.');
    }
}
