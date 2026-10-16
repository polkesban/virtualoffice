@extends('admin.layouts.admin')
@section('title', 'Kelola Role')
@section('page-title', 'Kelola Role')
@section('page-subtitle', 'Tambah, edit, hapus, dan atur akses menu role admin dari database')

@section('content')
<div class="p-6 max-w-7xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">🪪 Role / Jabatan</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Role sistem tetap aman, role kustom bisa ditambah dan dihapus sesuai kebutuhan.</p>
        </div>
        <a href="{{ route('admin.roles.create') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500">
            + Tambah Role
        </a>
    </div>

    @if(session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white dark:border-gray-800 dark:bg-gray-900">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left">Role</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Key</th>
                    <th class="px-4 py-3 text-left">Akses Menu</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Dipakai</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($roles as $role)
                    @php
                        $menus = $role->menus ?? [];
                        $menuSummary = $role->key === \App\Models\User::ROLE_SUPER_ADMIN
                            ? 'Semua akses'
                            : (empty($menus) ? 'Tanpa akses menu default' : implode(', ', $menus));
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $role->label }}</span>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $role->is_system ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' }}">
                                    {{ $role->is_system ? 'Sistem' : 'Kustom' }}
                                </span>
                            </div>
                        </td>
                        <td class="hidden px-4 py-3 font-mono text-xs text-gray-500 md:table-cell">{{ $role->key }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $menuSummary }}</td>
                        <td class="hidden px-4 py-3 text-xs text-gray-500 lg:table-cell">
                            {{ $role->users_count }} user · {{ $role->unit_pages_count }} halaman unit
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.roles.edit', $role) }}"
                                   class="rounded-lg bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:bg-gray-700 dark:text-gray-300">
                                    Edit
                                </a>
                                @unless($role->is_system)
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                                          onsubmit="return confirm('Hapus role {{ $role->label }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="rounded-lg bg-red-50 px-3 py-1 text-xs font-bold text-red-600 transition-colors hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300">
                                            Hapus
                                        </button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-gray-400">Belum ada role tersedia.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection