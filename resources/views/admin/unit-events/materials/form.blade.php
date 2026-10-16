@extends('admin.layouts.admin')
@section('title', $material->exists ? 'Edit Materi Event' : 'Tambah Materi Event')
@section('page-title', $material->exists ? 'Edit Materi Event' : 'Tambah Materi Event')
@section('page-subtitle', 'File unduhan, tautan eksternal, atau embed materi')

@section('content')
<div class="p-6 max-w-4xl mx-auto" x-data="{ tipe: '{{ old('tipe', $material->tipe ?: 'file') }}' }">
    @include('admin.unit-events._nav', ['unitPage' => $unitPage, 'unitEvent' => $unitEvent])

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300">
            <ul class="list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $material->exists ? route('admin.unit-events.materials.update', [$unitPage, $unitEvent, $material]) : route('admin.unit-events.materials.store', [$unitPage, $unitEvent]) }}"
          enctype="multipart/form-data"
          class="space-y-5">
        @csrf
        @if($material->exists)
            @method('PUT')
        @endif

        <div class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 space-y-4">
            <div>
                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Judul Materi <span class="text-red-500">*</span></label>
                <input type="text" name="judul" value="{{ old('judul', $material->judul) }}" required class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
            </div>
            <div>
                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Deskripsi</label>
                <textarea name="deskripsi" rows="3" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">{{ old('deskripsi', $material->deskripsi) }}</textarea>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Tipe Materi <span class="text-red-500">*</span></label>
                    <select name="tipe" x-model="tipe" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                        @foreach(\App\Models\UnitEventMaterial::$tipeLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Urutan</label>
                    <input type="number" name="urutan" min="0" max="9999" value="{{ old('urutan', $material->urutan ?? 0) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                </div>
                <div class="space-y-3 pt-6 text-sm text-gray-700 dark:text-gray-300">
                    <label class="flex items-center gap-3"><input type="checkbox" name="publik" value="1" @checked(old('publik', $material->publik ?? true)) class="rounded"> Materi publik</label>
                    <label class="flex items-center gap-3"><input type="checkbox" name="downloadable" value="1" @checked(old('downloadable', $material->downloadable ?? true)) class="rounded"> Bisa diunduh</label>
                </div>
            </div>

            <div x-show="tipe === 'file'" class="space-y-2">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Upload File Materi</label>
                <input type="file" name="file_upload" class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                @if($material->file_path)
                    <a href="{{ \App\Support\MediaStorage::url($material->file_path) }}" target="_blank" class="inline-flex text-xs font-semibold text-brand-600 hover:underline">File saat ini</a>
                @endif
            </div>

            <div x-show="tipe === 'link'" class="space-y-2">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Tautan Materi</label>
                <input type="url" name="link_url" value="{{ old('link_url', $material->link_url) }}" placeholder="https://..." class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
            </div>

            <div x-show="tipe === 'embed'" class="space-y-2">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Embed URL</label>
                <input type="url" name="embed_url" value="{{ old('embed_url', $material->embed_url) }}" placeholder="https://www.youtube.com/embed/..." class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-gray-400">Gunakan URL embed langsung agar materi bisa disematkan di halaman event.</p>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.unit-events.materials.index', [$unitPage, $unitEvent]) }}" class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">Batal</a>
            <button type="submit" class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                {{ $material->exists ? 'Simpan Perubahan' : 'Tambah Materi' }}
            </button>
        </div>
    </form>
</div>
@endsection