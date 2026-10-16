@extends('admin.layouts.admin')
@section('title', isset($item->id) ? 'Edit Publikasi' : 'Tambah Publikasi')
@section('content')
<div class="p-6 max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.publikasi.index') }}" class="text-sm text-brand-600 hover:underline">← Kembali</a>
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white mt-2">
            {{ isset($item->id) ? 'Edit Publikasi' : 'Tambah Publikasi' }}
        </h1>
    </div>

    <form method="POST"
          action="{{ isset($item->id) ? route('admin.publikasi.update', $item) : route('admin.publikasi.store') }}"
          class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 space-y-5">
        @csrf
        @if(isset($item->id)) @method('PUT') @endif

        @if($errors->any())
        <div class="rounded-xl bg-red-50 border border-red-200 p-3 text-sm text-red-700">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Judul <span class="text-red-500">*</span></label>
            <input type="text" name="judul" value="{{ old('judul', $item->judul) }}" required
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Penulis <span class="text-red-500">*</span></label>
            <input type="text" name="penulis" value="{{ old('penulis', $item->penulis) }}" required
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                   placeholder="e.g. John Doe, Jane Smith">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Institusi / Afiliasi</label>
                <input type="text" name="institusi" value="{{ old('institusi', $item->institusi) }}"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Jenis <span class="text-red-500">*</span></label>
                <select name="jenis" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                    @foreach(\App\Models\Publikasi::$jenisLabels as $k => $v)
                    <option value="{{ $k }}" @selected(old('jenis', $item->jenis)===$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Tahun</label>
                <input type="number" name="tahun" value="{{ old('tahun', $item->tahun) }}" min="1990" max="2099"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Volume</label>
                <input type="text" name="volume" value="{{ old('volume', $item->volume) }}"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Nomor / Edisi</label>
                <input type="text" name="nomor" value="{{ old('nomor', $item->nomor) }}"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Halaman</label>
                <input type="text" name="halaman" value="{{ old('halaman', $item->halaman) }}" placeholder="e.g. 12-25"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">DOI</label>
            <input type="text" name="doi" value="{{ old('doi', $item->doi) }}" placeholder="10.1234/example"
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-mono">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Link URL (jurnal/repositori)</label>
            <input type="url" name="link_url" value="{{ old('link_url', $item->link_url) }}" placeholder="https://..."
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">URL File PDF / Download</label>
            <input type="url" name="file_url" value="{{ old('file_url', $item->file_url) }}" placeholder="https://..."
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Abstrak</label>
            <textarea name="abstrak" rows="4"
                      class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">{{ old('abstrak', $item->abstrak) }}</textarea>
        </div>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="aktif" value="0">
            <input type="checkbox" name="aktif" value="1" class="rounded" @checked(old('aktif', $item->aktif ?? true))>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Tampilkan di website (aktif)</span>
        </label>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
                {{ isset($item->id) ? 'Simpan Perubahan' : 'Tambahkan' }}
            </button>
            <a href="{{ route('admin.publikasi.index') }}" class="px-6 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300">Batal</a>
        </div>
    </form>
</div>
@endsection
