@extends('admin.layouts.admin')
@section('title', 'Kelola Halaman Statis')

@section('content')
<div class="p-6 max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">Kelola Halaman Statis</h1>
    </div>

    @if(session('success'))
    <div class="rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 p-4 text-sm text-green-700 dark:text-green-400">
        {{ session('success') }}
    </div>
    @endif

    {{-- Add new static page --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6">
        <h2 class="text-sm font-extrabold text-gray-900 dark:text-white mb-4">➕ Tambah Halaman Statis Baru</h2>
        <form method="POST" action="{{ route('admin.halaman-statis.store-meta') }}" class="space-y-4">
            @csrf
            @if($errors->any())
            <div class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 p-3 text-sm text-red-700 dark:text-red-400">
                <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
            @endif
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Kunci (unik, a-z_) <span class="text-red-500">*</span></label>
                    <input type="text" name="kunci" value="{{ old('kunci') }}" placeholder="mis: kurikulum"
                           pattern="[a-z0-9_]+" title="Hanya huruf kecil, angka, dan underscore"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm font-mono" required>
                    <p class="text-[10px] text-gray-400 mt-0.5">Digunakan di URL: /admin/halaman-statis/<em>kunci</em>/edit</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Label Menu Sidebar <span class="text-red-500">*</span></label>
                    <input type="text" name="menu_label" value="{{ old('menu_label') }}" placeholder="mis: Kurikulum"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Icon Emoji</label>
                    <input type="text" name="menu_icon" value="{{ old('menu_icon', '📄') }}" maxlength="10"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm">
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit"
                        class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
                    Tambah Halaman
                </button>
            </div>
        </form>
    </div>

    {{-- Existing pages list --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                    <th class="text-left px-5 py-3 font-bold text-xs text-gray-500 uppercase tracking-wide">Kunci</th>
                    <th class="text-left px-5 py-3 font-bold text-xs text-gray-500 uppercase tracking-wide">Label Sidebar</th>
                    <th class="text-left px-5 py-3 font-bold text-xs text-gray-500 uppercase tracking-wide">Hak Akses</th>
                    <th class="text-left px-5 py-3 font-bold text-xs text-gray-500 uppercase tracking-wide">Terakhir Edit</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                @forelse($pages as $page)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                    <td class="px-5 py-3 font-mono text-xs text-gray-600 dark:text-gray-400">
                        {{ $page->menu_icon ?? '📄' }} {{ $page->kunci }}
                    </td>
                    <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">
                        {{ $page->menu_label ?: $page->judul }}
                    </td>
                    <td class="px-5 py-3">
                        <code class="text-xs bg-gray-100 dark:bg-gray-800 rounded px-1.5 py-0.5">halaman-statis:{{ $page->kunci }}</code>
                    </td>
                    <td class="px-5 py-3 text-xs text-gray-400">
                        {{ $page->updated_at ? $page->updated_at->diffForHumans() : '—' }}
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.halaman-statis.edit', $page->kunci) }}"
                               class="px-3 py-1 text-xs rounded-lg bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400 font-medium hover:bg-brand-100 transition-colors">
                                Edit Konten
                            </a>
                            <form method="POST" action="{{ route('admin.halaman-statis.destroy-meta', $page->kunci) }}"
                                  onsubmit="return confirm('Hapus halaman \'{{ $page->kunci }}\'? Konten akan ikut terhapus.')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="px-3 py-1 text-xs rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 font-medium hover:bg-red-100 transition-colors">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-400">Belum ada halaman statis.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 p-4 text-sm text-blue-700 dark:text-blue-300">
        <strong>Cara kerja:</strong> Setelah menambah halaman baru, beri hak akses <code>halaman-statis:<em>kunci</em></code>
        kepada pengguna yang berhak mengeditnya melalui menu <a href="{{ route('admin.users.index') }}" class="underline">Pengelola / Users</a>.
        Halaman akan muncul otomatis di sidebar mereka.
    </div>
</div>
@endsection
