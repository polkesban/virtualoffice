@extends('admin.layouts.admin')
@section('title', $pengumuman->exists ? 'Edit Pengumuman' : 'Tambah Pengumuman')

@section('content')
<div class="p-6 max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.pengumuman.index') }}" class="text-gray-400 hover:text-gray-600">←</a>
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">
            {{ $pengumuman->exists ? 'Edit Pengumuman' : 'Tambah Pengumuman' }}
        </h1>
    </div>

    <form method="POST"
          action="{{ $pengumuman->exists ? route('admin.pengumuman.update', $pengumuman) : route('admin.pengumuman.store') }}"
          enctype="multipart/form-data"
          class="space-y-5">
        @csrf
        @if($pengumuman->exists) @method('PUT') @endif

        @if($errors->any())
        <div class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 p-4 text-sm text-red-700 dark:text-red-400">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 space-y-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Judul <span class="text-red-500">*</span></label>
                <input type="text" name="judul" value="{{ old('judul', $pengumuman->judul) }}" required
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Prioritas</label>
                    <select name="prioritas" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                        <option value="normal"  @selected(old('prioritas',$pengumuman->prioritas)==='normal')>🟢 Normal</option>
                        <option value="penting" @selected(old('prioritas',$pengumuman->prioritas)==='penting')>🟡 Penting</option>
                        <option value="urgent"  @selected(old('prioritas',$pengumuman->prioritas)==='urgent')>🔴 Urgent</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <div class="flex items-center gap-2 mt-2">
                        <input type="checkbox" name="aktif" id="aktif" value="1"
                               {{ old('aktif', $pengumuman->aktif ?? true) ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-brand-600">
                        <label for="aktif" class="text-sm font-medium text-gray-700 dark:text-gray-300">Aktif / Tampilkan</label>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Tanggal Mulai <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal_mulai"
                           value="{{ old('tanggal_mulai', $pengumuman->tanggal_mulai?->format('Y-m-d')) }}" required
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai"
                           value="{{ old('tanggal_selesai', $pengumuman->tanggal_selesai?->format('Y-m-d')) }}"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                    <p class="text-xs text-gray-400 mt-1">Kosongkan = tidak ada batas waktu</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Isi Pengumuman <span class="text-red-500">*</span></label>
                <textarea name="isi" rows="8" required
                          class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">{{ old('isi', $pengumuman->isi) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">File Lampiran (PDF/Word)</label>
                @if($pengumuman->file_lampiran)
                <p class="text-xs text-brand-600 dark:text-brand-400 mb-1">
                    Lampiran: <a href="{{ \App\Support\MediaStorage::url($pengumuman->file_lampiran) }}" target="_blank" class="hover:underline">Lihat file</a>
                </p>
                @endif
                <input type="file" name="file_lampiran" accept=".pdf,.doc,.docx"
                       class="text-sm text-gray-500 dark:text-gray-400">
            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <a href="{{ route('admin.pengumuman.index') }}"
               class="px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium">
                Batal
            </a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
                {{ $pengumuman->exists ? 'Simpan Perubahan' : 'Tambah Pengumuman' }}
            </button>
        </div>
    </form>
</div>
@endsection
