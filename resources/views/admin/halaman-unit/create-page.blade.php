@extends('admin.layouts.admin')
@section('title', 'Buat Halaman Unit Baru')

@section('content')
<div class="p-6 max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.halaman-unit.index') }}" class="text-gray-400 hover:text-gray-600">← Kembali</a>
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">➕ Buat Halaman Unit Baru</h1>
    </div>

    @if($errors->any())
    <div class="mb-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 p-4 text-sm text-red-700">
        <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.halaman-unit.store-page') }}" class="space-y-5">
        @csrf

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 space-y-4">

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">
                    Judul Halaman <span class="text-red-500">*</span>
                </label>
                <input type="text" name="judul" value="{{ old('judul') }}" required autofocus
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm"
                       placeholder="mis: Unit Penelitian dan Pengabdian Masyarakat">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Sub Judul</label>
                <input type="text" name="sub_judul" value="{{ old('sub_judul') }}"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm"
                       placeholder="Deskripsi singkat halaman…">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">
                        Slug (URL) <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden bg-gray-50 dark:bg-gray-800">
                        <span class="px-3 text-xs text-gray-400 bg-gray-100 dark:bg-gray-700 border-r border-gray-200 dark:border-gray-700 h-full py-2.5">/unit/</span>
                        <input type="text" name="slug" value="{{ old('slug') }}" required
                               pattern="[a-z0-9\-]+"
                               title="Hanya huruf kecil, angka, dan tanda minus"
                               class="flex-1 px-3 py-2.5 text-sm bg-transparent outline-none font-mono"
                               placeholder="nama-unit">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Hanya huruf kecil, angka, dan tanda minus (-).</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">
                        Kunci Role <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="role" value="{{ old('role') }}" required
                           pattern="[a-z0-9_\-]+"
                           title="Identifier unik untuk role/unit ini"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm font-mono"
                           placeholder="unit_penelitian">
                    <p class="text-xs text-gray-400 mt-1">Identifier unik (tidak boleh sama dengan halaman lain).</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Warna Tema</label>
                <div class="flex items-center gap-3">
                    <input type="color" name="warna_tema" value="{{ old('warna_tema', '#1d4ed8') }}"
                           class="w-12 h-10 rounded-lg border border-gray-200 cursor-pointer">
                    <span class="text-xs text-gray-400">Warna header dan aksen halaman</span>
                </div>
            </div>

        </div>

        <div class="flex gap-3 justify-end">
            <a href="{{ route('admin.halaman-unit.index') }}"
               class="px-6 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300">Batal</a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
                ✅ Buat Halaman
            </button>
        </div>
    </form>
</div>
@endsection
