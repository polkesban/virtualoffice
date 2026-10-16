@extends('admin.layouts.admin')
@section('title', isset($item->id) ? 'Edit Fasilitas Asrama' : 'Tambah Fasilitas Asrama')
@section('content')
<div class="p-6 max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.fasilitas-asrama.index') }}" class="text-sm text-brand-600 hover:underline">← Kembali</a>
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white mt-2">
            {{ isset($item->id) ? 'Edit: '.$item->nama : 'Tambah Kamar / Fasilitas' }}
        </h1>
    </div>

    <form method="POST"
          action="{{ isset($item->id) ? route('admin.fasilitas-asrama.update', $item) : route('admin.fasilitas-asrama.store') }}"
          enctype="multipart/form-data"
          class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 space-y-5">
        @csrf
        @if(isset($item->id)) @method('PUT') @endif

        @if($errors->any())
        <div class="rounded-xl bg-red-50 border border-red-200 p-3 text-sm text-red-700">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Nama / Tipe Kamar <span class="text-red-500">*</span></label>
                <input type="text" name="nama" value="{{ old('nama', $item->nama) }}" required
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                       placeholder="e.g. Kamar Tipe A – 4 orang">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Jenis <span class="text-red-500">*</span></label>
                <select name="jenis" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                    @foreach(\App\Models\FasilitasAsrama::$jenisLabels as $k => $v)
                    <option value="{{ $k }}" @selected(old('jenis', $item->jenis)===$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Lantai</label>
                <input type="number" name="lantai" value="{{ old('lantai', $item->lantai ?? 1) }}" min="1" max="20"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Kapasitas (orang) <span class="text-red-500">*</span></label>
                <input type="number" name="kapasitas" value="{{ old('kapasitas', $item->kapasitas ?? 4) }}" min="1" required
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Terisi (orang)</label>
                <input type="number" name="terisi" value="{{ old('terisi', $item->terisi ?? 0) }}" min="0"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Harga / Bulan (Rp)</label>
                <input type="number" name="harga_per_bulan" value="{{ old('harga_per_bulan', $item->harga_per_bulan) }}" min="0" step="1000"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                       placeholder="0 = Hubungi kami">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Harga / Semester (Rp)</label>
                <input type="number" name="harga_per_semester" value="{{ old('harga_per_semester', $item->harga_per_semester) }}" min="0" step="1000"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                       placeholder="0 = Hubungi kami">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Fasilitas (pisahkan dengan koma)</label>
            <input type="text" name="fasilitas" value="{{ old('fasilitas', $item->fasilitas) }}"
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                   placeholder="e.g. AC, Lemari, Kasur, Kamar mandi dalam, WiFi">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Keterangan</label>
            <textarea name="keterangan" rows="3"
                      class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">{{ old('keterangan', $item->keterangan) }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Foto</label>
            @if(isset($item->id) && $item->foto)
            <img src="{{ \App\Support\MediaStorage::url($item->foto) }}" class="h-24 rounded-xl mb-2 object-cover">
            @endif
            <input type="file" name="foto" accept="image/*"
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Urutan</label>
                <input type="number" name="urutan" value="{{ old('urutan', $item->urutan ?? 0) }}" min="0"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
            <div class="flex items-end pb-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="aktif" value="0">
                    <input type="checkbox" name="aktif" value="1" class="rounded" @checked(old('aktif', $item->aktif ?? true))>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Tampilkan (aktif)</span>
                </label>
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
                {{ isset($item->id) ? 'Simpan Perubahan' : 'Tambahkan' }}
            </button>
            <a href="{{ route('admin.fasilitas-asrama.index') }}" class="px-6 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300">Batal</a>
        </div>
    </form>
</div>
@endsection
