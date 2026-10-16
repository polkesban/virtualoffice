@extends('admin.layouts.admin')
@section('title', 'Pengaturan Halaman — ' . $page->judul)
@section('content')
<div class="p-6 max-w-xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.halaman-unit.manage', $page) }}" class="text-sm text-brand-600 hover:underline">← Kembali</a>
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white mt-2">Pengaturan Halaman Unit</h1>
    </div>

    <form method="POST" action="{{ route('admin.halaman-unit.update', $page) }}"
          enctype="multipart/form-data"
          class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 space-y-5">
        @csrf @method('PUT')

        @if($errors->any())
        <div class="rounded-xl bg-red-50 border border-red-200 p-3 text-sm text-red-700">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Judul Halaman <span class="text-red-500">*</span></label>
            <input type="text" name="judul" value="{{ old('judul', $page->judul) }}" required
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Sub-judul / Tagline</label>
            <input type="text" name="sub_judul" value="{{ old('sub_judul', $page->sub_judul) }}"
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Warna Tema</label>
            <div class="flex items-center gap-3">
                <input type="color" name="warna_tema" value="{{ old('warna_tema', $page->warna_tema) }}"
                       class="h-9 w-16 rounded-xl border border-gray-200 cursor-pointer">
                <input type="text" id="warna_text" value="{{ old('warna_tema', $page->warna_tema) }}"
                       class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-mono"
                       oninput="document.querySelector('[name=warna_tema]').value = this.value">
            </div>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Foto Cover (opsional)</label>
            @if($page->cover_foto)
            <img src="{{ \App\Support\MediaStorage::url($page->cover_foto) }}" class="h-20 rounded-xl mb-2 object-cover">
            @endif
            <input type="file" name="cover_foto" accept="image/*"
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
        </div>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="aktif" value="0">
            <input type="checkbox" name="aktif" value="1" class="rounded" @checked(old('aktif', $page->aktif))>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Tampilkan di website (aktif / live)</span>
        </label>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">Simpan</button>
            <a href="{{ route('admin.halaman-unit.manage', $page) }}" class="px-6 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300">Batal</a>
        </div>
    </form>
</div>
@endsection
