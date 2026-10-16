@extends('admin.layouts.admin')
@section('title', $role->exists ? 'Edit Role' : 'Tambah Role')

@section('content')
<div class="p-6 max-w-4xl mx-auto">
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.roles.index') }}" class="text-gray-400 hover:text-gray-600">←</a>
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">
            {{ $role->exists ? 'Edit Role: ' . $role->label : 'Tambah Role Baru' }}
        </h1>
    </div>

    <form method="POST"
          action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}"
          class="space-y-5">
        @csrf
        @if($role->exists) @method('PUT') @endif

        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Key Role <span class="text-red-500">*</span></label>
                    <input type="text" name="key" value="{{ old('key', $role->key) }}"
                           {{ $role->exists && $role->is_system ? 'readonly' : '' }}
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 font-mono text-sm dark:border-gray-700 dark:bg-gray-800 {{ $role->exists && $role->is_system ? 'cursor-not-allowed opacity-70' : '' }}"
                           placeholder="contoh: unit_bahasa_baru">
                    <p class="mt-1 text-xs text-gray-400">Gunakan huruf kecil, angka, dan underscore. Key dipakai sebagai identitas internal role.</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Label / Nama Jabatan <span class="text-red-500">*</span></label>
                    <input type="text" name="label" value="{{ old('label', $role->label) }}" required
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800"
                           placeholder="Contoh: Unit Bahasa Internasional">
                </div>
            </div>

            <div class="mt-4">
                <div class="flex items-center gap-2">
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $role->is_system ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' }}">
                        {{ $role->is_system ? 'Role Sistem' : 'Role Kustom' }}
                    </span>
                </div>
                <p class="mt-2 text-xs text-gray-400">
                    Role sistem tidak dapat dihapus. Role kustom bisa dipakai untuk unit atau jabatan baru sesuai kebutuhan operasional.
                </p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-4">
                <h2 class="text-sm font-extrabold text-gray-900 dark:text-white">Hak Akses Menu Default</h2>
                <p class="mt-0.5 text-xs text-gray-400">Hak akses ini akan dipakai sebagai default saat user menggunakan opsi role default.</p>
            </div>

            @if(($role->key ?? old('key')) === \App\Models\User::ROLE_SUPER_ADMIN)
                <div class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-700 dark:border-brand-800 dark:bg-brand-900/20 dark:text-brand-300">
                    Super Admin selalu memiliki semua akses. Pengaturan menu tidak perlu diubah.
                </div>
            @else
                <div class="space-y-4">
                    @php $selectedMenus = old('menus', $role->menus ?? []); @endphp
                    @foreach($allMenus as $group => $items)
                        <div>
                            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">{{ $group }}</p>
                            <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                                @foreach($items as $menuKey => $menuLabel)
                                    <label class="flex items-center gap-2 rounded-lg px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <input type="checkbox" name="menus[]" value="{{ $menuKey }}"
                                               class="h-4 w-4 rounded text-brand-600"
                                               {{ in_array($menuKey, $selectedMenus, true) ? 'checked' : '' }}>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $menuLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.roles.index') }}"
               class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium dark:border-gray-700">
                Batal
            </a>
            <button type="submit"
                    class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                {{ $role->exists ? 'Simpan Perubahan' : 'Tambah Role' }}
            </button>
        </div>
    </form>
</div>
@endsection