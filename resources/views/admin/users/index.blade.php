@extends('admin.layouts.admin')
@section('title', 'Kelola Pengguna')

@section('content')
<div x-data="{
    viewMode: localStorage.getItem('admin_users_view_mode') || 'table',
    switchViewMode(mode) {
        this.viewMode = mode;
        localStorage.setItem('admin_users_view_mode', mode);
    }
}" class="p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">👥 Pengelola / Users</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Atur akun dan hak akses per jabatan</p>
        </div>
        <div class="flex items-center gap-3">
            @if(auth()->user()?->isSuperAdmin())
            <a href="{{ route('admin.users.employee-credentials') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2 text-sm font-bold text-gray-700 transition-colors hover:border-brand-200 hover:bg-brand-50 hover:text-brand-600 dark:border-gray-700 dark:text-gray-300 dark:hover:border-brand-700 dark:hover:bg-brand-900/20 dark:hover:text-brand-300">
                Kredensial Pegawai
            </a>
            @endif
            <a href="{{ route('admin.roles.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2 text-sm font-bold text-gray-700 transition-colors hover:border-brand-200 hover:bg-brand-50 hover:text-brand-600 dark:border-gray-700 dark:text-gray-300 dark:hover:border-brand-700 dark:hover:bg-brand-900/20 dark:hover:text-brand-300">
                Kelola Role
            </a>
            <a href="{{ route('admin.users.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
                + Tambah Pengguna
            </a>
        </div>
    </div>

    <!-- Search + View Switcher Toolbar -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="w-full sm:max-w-md">
            <div class="relative">
                <input type="text" id="search-input" placeholder="🔍 Cari nama, NIP, email, atau jabatan..."
                    class="w-full rounded-xl border border-gray-250 bg-white pl-10 pr-4 py-2.5 text-sm dark:bg-gray-900 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all">
                <span class="absolute left-3 top-3.5 text-gray-400 dark:text-gray-500">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1.5 shrink-0 bg-white dark:bg-gray-900 p-1.5 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm self-end sm:self-auto">
            <button type="button" 
                    @click="switchViewMode('table')"
                    :class="viewMode === 'table' ? 'bg-brand-600 text-white font-extrabold shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 font-semibold'"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs transition-all cursor-pointer">
                <span>📋</span> Tabel
            </button>
            <button type="button" 
                    @click="switchViewMode('grid')"
                    :class="viewMode === 'grid' ? 'bg-brand-600 text-white font-extrabold shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 font-semibold'"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs transition-all cursor-pointer">
                <span>🧱</span> Kartu
            </button>
        </div>
    </div>

    <!-- TABLE VIEW MODE -->
    <div x-show="viewMode === 'table'" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Nama</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Email</th>
                    <th class="px-4 py-3 text-left">Role / Jabatan</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Program Studi</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($users as $u)
                <tr class="user-row hover:bg-gray-50 dark:hover:bg-gray-800/50"
                    data-name="{{ strtolower($u->name) }}"
                    data-email="{{ strtolower($u->email) }}"
                    data-nip="{{ strtolower($u->identity_number) }}"
                    data-role="{{ strtolower($u->role_label) }}"
                    data-prodi="{{ strtolower($u->programStudi?->singkatan ?? '') }}">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                {{ mb_substr($u->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $u->name }}</p>
                                @if($u->identity_number)
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">ID: {{ $u->identity_number }}</p>
                                @endif
                                @if($u->is_admin)
                                <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400">Admin</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-500 hidden md:table-cell text-xs">{{ $u->email }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold
                            {{ $u->role === 'super_admin' ? 'bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $u->role_label }}
                        </span>
                        @if($u->managedUnitPage)
                        <p class="mt-1 text-[11px] text-gray-500">Kelola: {{ $u->managedUnitPage->judul }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-400 text-xs hidden lg:table-cell">
                        {{ $u->programStudi?->singkatan ?? ($u->isKaprodi() ? '—' : '') }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.users.edit', $u) }}"
                               class="px-3 py-1 rounded-lg text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-brand-50 hover:text-brand-600 transition-colors">
                                Edit
                            </a>
                            @if(auth()->user()->isSuperAdmin() && $u->id !== auth()->id() && !$u->isSuperAdmin())
                            <form method="POST" action="{{ route('admin.users.login-as', $u) }}">
                                @csrf
                                <button type="submit"
                                        class="px-3 py-1 rounded-lg text-xs font-bold bg-sky-50 dark:bg-sky-900/30 text-sky-600 hover:bg-sky-100 transition-colors"
                                        title="Login sebagai {{ $u->name }}">
                                    Login As
                                </button>
                            </form>
                            @endif
                            @if($u->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.destroy', $u) }}"
                                  onsubmit="return confirm('Hapus pengguna {{ $u->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="px-3 py-1 rounded-lg text-xs font-bold bg-red-50 dark:bg-red-900/30 text-red-600 hover:bg-red-100 transition-colors">
                                    Hapus
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-gray-400">Belum ada pengguna lain.</td>
                </tr>
                @endforelse
                <tr id="empty-search-row" style="display: none;">
                    <td colspan="5" class="px-4 py-12 text-center text-gray-400">Pengguna tidak ditemukan.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- GRID VIEW MODE -->
    <div x-show="viewMode === 'grid'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($users as $u)
        <div class="user-card bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 flex flex-col justify-between hover:border-brand-200 transition-colors"
             data-name="{{ strtolower($u->name) }}"
             data-email="{{ strtolower($u->email) }}"
             data-nip="{{ strtolower($u->identity_number) }}"
             data-role="{{ strtolower($u->role_label) }}"
             data-prodi="{{ strtolower($u->programStudi?->singkatan ?? '') }}">
            <div>
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-brand-600 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                            {{ mb_substr($u->name, 0, 1) }}
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm">{{ $u->name }}</h3>
                            <p class="text-xs text-gray-400 truncate max-w-[180px]">{{ $u->email }}</p>
                        </div>
                    </div>
                    @if($u->is_admin)
                    <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-2 py-0.5 rounded-full">Admin</span>
                    @endif
                </div>

                <div class="space-y-1.5 mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">Jabatan/Role:</span>
                        <span class="font-bold text-gray-700 dark:text-gray-300">{{ $u->role_label }}</span>
                    </div>
                    @if($u->identity_number)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">NIP/ID:</span>
                        <span class="font-mono text-gray-600 dark:text-gray-400">{{ $u->identity_number }}</span>
                    </div>
                    @endif
                    @if($u->managedUnitPage)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">Kelola Unit:</span>
                        <span class="font-semibold text-brand-600 truncate max-w-[160px]">{{ $u->managedUnitPage->judul }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2">
                <a href="{{ route('admin.users.edit', $u) }}"
                   class="px-3 py-1 rounded-lg text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-brand-50 hover:text-brand-600 transition-colors">
                    Edit
                </a>
                @if(auth()->user()->isSuperAdmin() && $u->id !== auth()->id() && !$u->isSuperAdmin())
                <form method="POST" action="{{ route('admin.users.login-as', $u) }}">
                    @csrf
                    <button type="submit"
                            class="px-3 py-1 rounded-lg text-xs font-bold bg-sky-50 dark:bg-sky-900/30 text-sky-600 hover:bg-sky-100 transition-colors">
                        Login As
                    </button>
                </form>
                @endif
                @if($u->id !== auth()->id())
                <form method="POST" action="{{ route('admin.users.destroy', $u) }}"
                      onsubmit="return confirm('Hapus pengguna {{ $u->name }}?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="px-3 py-1 rounded-lg text-xs font-bold bg-red-50 dark:bg-red-900/30 text-red-600 hover:bg-red-100 transition-colors">
                        Hapus
                    </button>
                </form>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <script>
        document.getElementById('search-input').addEventListener('input', function (e) {
            const query = e.target.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.user-row, .user-card');
            const emptyRow = document.getElementById('empty-search-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const email = row.getAttribute('data-email') || '';
                const nip = row.getAttribute('data-nip') || '';
                const role = row.getAttribute('data-role') || '';
                const prodi = row.getAttribute('data-prodi') || '';

                if (name.includes(query) || email.includes(query) || nip.includes(query) || role.includes(query) || prodi.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (emptyRow) {
                emptyRow.style.display = visibleCount === 0 ? '' : 'none';
            }
        });
    </script>

    {{-- Daftar role & aksesnya --}}
    <div class="mt-8">
        <h2 class="text-sm font-extrabold text-gray-700 dark:text-gray-300 mb-4">Panduan Role & Hak Akses</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach(\App\Models\User::$roleLabels as $role => $label)
            @php $menus = \App\Models\User::$roleMenus[$role] ?? []; @endphp
            <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
                <p class="text-xs font-bold text-gray-900 dark:text-white mb-1.5">{{ $label }}</p>
                <p class="text-[10px] text-gray-500 font-mono">
                    {{ implode(', ', $menus === ['*'] ? ['Semua akses'] : $menus) ?: '— Tidak ada akses edit' }}
                </p>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
