@extends('admin.layouts.admin')
@section('title', $galeri->exists ? 'Edit Foto' : 'Upload Foto')

@section('content')
<div class="p-6 max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.galeri.index') }}" class="text-gray-400 hover:text-gray-600">←</a>
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">
            {{ $galeri->exists ? 'Edit Foto' : 'Upload Foto Galeri' }}
        </h1>
    </div>

    <form method="POST"
          action="{{ $galeri->exists ? route('admin.galeri.update', $galeri) : route('admin.galeri.store') }}"
          enctype="multipart/form-data"
          class="space-y-5">
        @csrf
        @if($galeri->exists) @method('PUT') @endif

        @if($errors->any())
        <div class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 p-4 text-sm text-red-700 dark:text-red-400">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 space-y-4">
            @if($galeri->exists)
            <div>
                <p class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Foto Saat Ini</p>
                <img src="{{ \App\Support\MediaStorage::url($galeri->file, 'galeri') }}" alt="{{ $galeri->alt }}"
                     class="h-40 w-auto rounded-xl object-cover">
            </div>
            @endif

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">
                    {{ $galeri->exists ? 'Ganti Foto (opsional)' : 'Foto' }} @unless($galeri->exists)<span class="text-red-500">*</span>@endunless
                </label>
                <input type="file" name="file" accept="image/*"
                       {{ $galeri->exists ? '' : 'required' }}
                       class="text-sm text-gray-500 dark:text-gray-400">
                <p class="text-xs text-gray-400 mt-1">Maks 4 MB. Format: JPG, PNG, WebP</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Judul <span class="text-red-500">*</span></label>
                <input type="text" name="judul" value="{{ old('judul', $galeri->judul) }}" required
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Alt Text</label>
                <input type="text" name="alt" value="{{ old('alt', $galeri->alt) }}"
                       placeholder="Deskripsi singkat untuk aksesibilitas"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Kategori</label>
                    <select name="kategori" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                        @foreach(\App\Models\Galeri::$kategoriLabels as $k=>$v)
                        <option value="{{ $k }}" @selected(old('kategori',$galeri->kategori)===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Urutan</label>
                    <input type="number" name="urutan" value="{{ old('urutan', $galeri->urutan ?? 0) }}"
                           min="0" max="255"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="tampil" id="tampil" value="1"
                       {{ old('tampil', $galeri->tampil ?? true) ? 'checked' : '' }}
                       class="w-4 h-4 rounded text-brand-600">
                <label for="tampil" class="text-sm font-medium text-gray-700 dark:text-gray-300">Tampilkan di beranda</label>
            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <a href="{{ route('admin.galeri.index') }}"
               class="px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium">
                Batal
            </a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
                {{ $galeri->exists ? 'Simpan Perubahan' : 'Upload Foto' }}
            </button>
        </div>
    </form>
</div>
@endsection
