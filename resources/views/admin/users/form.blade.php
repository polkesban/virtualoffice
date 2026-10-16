@extends('admin.layouts.admin')
@section('title', $user->exists ? 'Edit Pengguna' : 'Tambah Pengguna')

@section('content')
<div class="p-6 max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-gray-400 hover:text-gray-600">←</a>
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">
            {{ $user->exists ? 'Edit: ' . $user->name : 'Tambah Pengguna' }}
        </h1>
    </div>

    <form method="POST"
          action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}"
          class="space-y-5"
          id="user-form">
        @csrf
        @if($user->exists) @method('PUT') @endif

        @if($errors->any())
        <div class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 p-4 text-sm text-red-700 dark:text-red-400">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        {{-- Basic info --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 space-y-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Email / Username (NIP) <span class="text-red-500">*</span></label>
                <input type="text" name="email" value="{{ old('email', $user->email) }}" required
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">NIP / NIS / NIK / NIM</label>
                <input type="text" name="identity_number" value="{{ old('identity_number', $user->identity_number) }}"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm uppercase">
                <p class="mt-1 text-xs text-gray-400">Isi NIP untuk akun pegawai agar peserta event bisa terhubung otomatis ke akun ini.</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">
                    Password {{ $user->exists ? '(kosongkan jika tidak diubah)' : '' }}
                    @unless($user->exists)<span class="text-red-500">*</span>@endunless
                </label>
                <div class="relative" data-password-toggle>
                    <input type="password" name="password" autocomplete="new-password" data-password-toggle-input
                           {{ $user->exists ? '' : 'required' }}
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 pr-24 text-sm">
                    <button type="button" data-password-toggle-button
                            class="absolute inset-y-0 right-3 inline-flex items-center text-xs font-semibold text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400 dark:hover:text-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-300 rounded-lg">
                        Lihat
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Konfirmasi Password</label>
                <div class="relative" data-password-toggle>
                    <input type="password" name="password_confirmation" autocomplete="new-password" data-password-toggle-input
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 pr-24 text-sm">
                    <button type="button" data-password-toggle-button
                            class="absolute inset-y-0 right-3 inline-flex items-center text-xs font-semibold text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400 dark:hover:text-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-300 rounded-lg">
                        Lihat
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Role / Jabatan <span class="text-red-500">*</span></label>
                <select name="role" id="role_select"
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                    @foreach($roleLabels as $k => $v)
                    <option value="{{ $k }}" @selected(old('role',$user->role)===$k)>{{ $v }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Role menentukan hak akses default. Role baru bisa dikelola di <a href="{{ route('admin.roles.index') }}" class="font-medium text-brand-600 hover:underline">Kelola Role</a>.</p>
            </div>

            @php
                $currentRole = old('role', $user->role);
                $showProdi = $currentRole === 'kaprodi';
                $showJurusan = in_array($currentRole, ['kajur', 'sekjur', 'admin_jurusan']);
                $showManagedPage = in_array($currentRole, ['page_admin', 'kajur', 'sekjur', 'admin_jurusan']);
            @endphp

            {{-- Kaprodi only --}}
            <div id="prodi_section" class="{{ $showProdi ? '' : 'hidden' }}">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Program Studi (wajib untuk Kaprodi)</label>
                <select name="program_studi_id"
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                    <option value="">— Pilih Program Studi —</option>
                    @foreach($prodiList as $p)
                    <option value="{{ $p->id }}" @selected(old('program_studi_id',$user->program_studi_id)===$p->id)>
                        {{ $p->nama }} ({{ $p->jenjang }})
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Kajur / Sekjur only: Jurusan select --}}
            <div id="jurusan_section" class="{{ $showJurusan ? '' : 'hidden' }}">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Jurusan <span class="text-red-500">*</span></label>
                <select name="jurusan_id" id="jurusan_select"
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                    <option value="">— Pilih Jurusan —</option>
                    @foreach($jurusanList as $j)
                    <option value="{{ $j->id }}" @selected(old('jurusan_id', $user->jurusan_id)===$j->id)>
                        {{ $j->nama }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Kajur / Sekjur only: D3, D4, Profesi level selectors --}}
            <div id="prodi_levels_section" class="{{ $showJurusan ? '' : 'hidden' }} pl-4 border-l-2 border-brand-200 dark:border-brand-800 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Prodi D3 di Jurusan ini</label>
                    <select name="prodi_d3_id" id="prodi_d3_select"
                            class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                        <option value="">— Pilih Prodi D3 —</option>
                        @foreach($prodiList->where('jenjang', 'D3') as $p)
                        <option value="{{ $p->id }}" data-jurusan="{{ $p->jurusan_id }}" @selected(old('prodi_d3_id', $p->jurusan_id == $user->jurusan_id ? $p->id : null) == $p->id)>
                            {{ $p->nama }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Prodi D4 / Sarjana Terapan di Jurusan ini</label>
                    <select name="prodi_d4_id" id="prodi_d4_select"
                            class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                        <option value="">— Pilih Prodi D4/ST —</option>
                        @foreach($prodiList->where('jenjang', 'D4') as $p)
                        <option value="{{ $p->id }}" data-jurusan="{{ $p->jurusan_id }}" @selected(old('prodi_d4_id', $p->jurusan_id == $user->jurusan_id ? $p->id : null) == $p->id)>
                            {{ $p->nama }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Prodi Profesi di Jurusan ini</label>
                    <select name="prodi_profesi_id" id="prodi_profesi_select"
                            class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                        <option value="">— Pilih Prodi Profesi —</option>
                        @foreach($prodiList->where('jenjang', 'Profesi') as $p)
                        <option value="{{ $p->id }}" data-jurusan="{{ $p->jurusan_id }}" @selected(old('prodi_profesi_id', $p->jurusan_id == $user->jurusan_id ? $p->id : null) == $p->id)>
                            {{ $p->nama }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="managed_page_section" class="{{ $showManagedPage ? '' : 'hidden' }}">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Halaman Publik yang Dikelola (Halaman Jurusan untuk Kajur/Sekjur)</label>
                <select name="managed_unit_page_id"
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                    <option value="">— Pilih Halaman Publik —</option>
                    @foreach($managedPageList as $page)
                    <option value="{{ $page->id }}" @selected((string) old('managed_unit_page_id', $user->managed_unit_page_id) === (string) $page->id)>
                        {{ $page->judul }} ({{ $page->slug }})
                    </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Gunakan role ini untuk admin BEM, HIMA, atau halaman publik khusus lain yang dikelola satu akun.</p>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_admin" id="is_admin" value="1"
                       {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}
                       class="w-4 h-4 rounded text-brand-600">
                <label for="is_admin" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Berikan akses login ke panel admin
                </label>
            </div>
        </div>

        {{-- Menu Access --}}
        <div id="menu_access_section" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-sm font-extrabold text-gray-900 dark:text-white">Hak Akses Menu</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Kustomisasi item sidebar yang dapat diakses pengguna ini</p>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="use_role_defaults" id="use_role_defaults" value="1"
                           class="w-4 h-4 rounded text-brand-600"
                           {{ $user->custom_menus === null ? 'checked' : '' }}>
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Gunakan default role</span>
                </label>
            </div>

            <div id="custom_menu_grid" class="{{ $user->custom_menus === null ? 'opacity-40 pointer-events-none' : '' }} space-y-4">
                @php
                    // Effective menus: custom if set, else role defaults
                    $effectiveMenus = $user->custom_menus
                        ?? (\App\Models\User::$roleMenus[old('role', $user->role)] ?? []);
                @endphp

                @foreach($allMenus as $group => $items)
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">{{ $group }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                        @foreach($items as $menuKey => $menuLabel)
                        <label class="flex items-center gap-2 cursor-pointer px-3 py-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800">
                            <input type="checkbox" name="custom_menus[]" value="{{ $menuKey }}"
                                   class="w-4 h-4 rounded text-brand-600 menu-checkbox"
                                   data-key="{{ $menuKey }}"
                                   {{ in_array($menuKey, $effectiveMenus) ? 'checked' : '' }}>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $menuLabel }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>

            <p id="super_admin_note" class="hidden text-sm text-brand-600 font-medium mt-2">
                Super Admin memiliki akses ke semua menu — tidak perlu diatur.
            </p>
        </div>

        <div class="flex gap-3 justify-end">
            <a href="{{ route('admin.users.index') }}"
               class="px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium">
                Batal
            </a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
                {{ $user->exists ? 'Simpan Perubahan' : 'Tambah Pengguna' }}
            </button>
        </div>
    </form>
</div>

<script>
const roleMenus = @json($roleMenus);

const roleSelect       = document.getElementById('role_select');
const prodiSection     = document.getElementById('prodi_section');
const jurusanSection   = document.getElementById('jurusan_section');
const prodiLevelsSection = document.getElementById('prodi_levels_section');
const jurusanSelect    = document.getElementById('jurusan_select');
const managedPageSection = document.getElementById('managed_page_section');
const useDefaultsChk   = document.getElementById('use_role_defaults');
const customMenuGrid   = document.getElementById('custom_menu_grid');
const superAdminNote   = document.getElementById('super_admin_note');
const menuAccessSection= document.getElementById('menu_access_section');

function applyRoleMenus(role) {
    const defaults = roleMenus[role] ?? [];
    document.querySelectorAll('.menu-checkbox').forEach(chk => {
        chk.checked = defaults.includes('*') || defaults.includes(chk.dataset.key);
    });
}

function toggleDefaultsMode() {
    const isDefault = useDefaultsChk.checked;
    customMenuGrid.classList.toggle('opacity-40', isDefault);
    customMenuGrid.classList.toggle('pointer-events-none', isDefault);
    if (isDefault) applyRoleMenus(roleSelect.value);
}

function toggleSuperAdmin() {
    const isSuper = roleSelect.value === 'super_admin';
    menuAccessSection.classList.toggle('hidden', isSuper);
}

function filterProdisByJurusan() {
    const selectedJurusan = jurusanSelect.value;
    ['prodi_d3_select', 'prodi_d4_select', 'prodi_profesi_select'].forEach(selectId => {
        const select = document.getElementById(selectId);
        if (!select) return;
        const options = select.querySelectorAll('option');
        options.forEach(opt => {
            if (opt.value === "") {
                opt.disabled = false;
                opt.hidden = false;
                return;
            }
            if (opt.getAttribute('data-jurusan') == selectedJurusan) {
                opt.disabled = false;
                opt.hidden = false;
            } else {
                opt.disabled = true;
                opt.hidden = true;
                if (select.value === opt.value) {
                    select.value = "";
                }
            }
        });
    });
}

roleSelect.addEventListener('change', function() {
    const isKaprodi = this.value === 'kaprodi';
    const isKajurSekjur = ['kajur', 'sekjur', 'admin_jurusan'].includes(this.value);
    const isPageAdminOrKajurSekjur = ['page_admin', 'kajur', 'sekjur', 'admin_jurusan'].includes(this.value);
    
    prodiSection.classList.toggle('hidden', !isKaprodi);
    jurusanSection.classList.toggle('hidden', !isKajurSekjur);
    prodiLevelsSection.classList.toggle('hidden', !isKajurSekjur);
    managedPageSection.classList.toggle('hidden', !isPageAdminOrKajurSekjur);
    
    toggleSuperAdmin();
    if (useDefaultsChk.checked) applyRoleMenus(this.value);
    if (isKajurSekjur) filterProdisByJurusan();
});

jurusanSelect.addEventListener('change', filterProdisByJurusan);

useDefaultsChk.addEventListener('change', toggleDefaultsMode);

// Init
toggleSuperAdmin();
toggleDefaultsMode();
filterProdisByJurusan();
</script>
@endsection
