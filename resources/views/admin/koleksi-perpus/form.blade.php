@extends('admin.layouts.admin')
@section('title', isset($item->id) ? 'Edit Koleksi' : 'Tambah Koleksi')
@section('content')
<div class="p-6 max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.koleksi-perpus.index') }}" class="text-sm text-brand-600 hover:underline">← Kembali</a>
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white mt-2">
            {{ isset($item->id) ? 'Edit Koleksi' : 'Tambah Koleksi Perpustakaan' }}
        </h1>
    </div>

    <form method="POST"
          action="{{ isset($item->id) ? route('admin.koleksi-perpus.update', $item) : route('admin.koleksi-perpus.store') }}"
          enctype="multipart/form-data"
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
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Penulis / Pengarang <span class="text-red-500">*</span></label>
            <input type="text" name="penulis" value="{{ old('penulis', $item->penulis) }}" required
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Penerbit</label>
                <input type="text" name="penerbit" value="{{ old('penerbit', $item->penerbit) }}"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Tahun Terbit</label>
                <input type="number" name="tahun_terbit" value="{{ old('tahun_terbit', $item->tahun_terbit) }}" min="1900" max="2099"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">ISBN</label>
                <input type="text" name="isbn" value="{{ old('isbn', $item->isbn) }}"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Kategori <span class="text-red-500">*</span></label>
                <select name="kategori" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                    @foreach(\App\Models\KoleksiPerpus::$kategoriLabels as $k => $v)
                    <option value="{{ $k }}" @selected(old('kategori', $item->kategori)===$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Stok Total</label>
                <input type="number" name="stok_total" value="{{ old('stok_total', $item->stok_total ?? 1) }}" min="0"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Stok Tersedia</label>
                <input type="number" name="stok_tersedia" value="{{ old('stok_tersedia', $item->stok_tersedia ?? 1) }}" min="0"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Lokasi Rak</label>
                <input type="text" name="lokasi_rak" value="{{ old('lokasi_rak', $item->lokasi_rak) }}" placeholder="e.g. R1-A3"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Link Digital (URL)</label>
            <input type="url" name="link_digital" value="{{ old('link_digital', $item->link_digital) }}" placeholder="https://..."
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Deskripsi / Abstrak</label>
            <textarea name="deskripsi" rows="3"
                      class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">{{ old('deskripsi', $item->deskripsi) }}</textarea>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Foto Cover</label>
            @if(isset($item->id) && $item->cover)
            <img src="{{ \App\Support\MediaStorage::url($item->cover) }}" class="h-24 rounded-xl mb-2 object-cover">
            @endif
            <input type="file" name="cover" accept="image/*"
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
        </div>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="aktif" value="0">
            <input type="checkbox" name="aktif" value="1" class="rounded" @checked(old('aktif', $item->aktif ?? true))>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Tampilkan di katalog (aktif)</span>
        </label>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
                {{ isset($item->id) ? 'Simpan Perubahan' : 'Tambahkan' }}
            </button>
            <a href="{{ route('admin.koleksi-perpus.index') }}" class="px-6 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300">Batal</a>
        </div>
    </form>
</div>
@endsection
