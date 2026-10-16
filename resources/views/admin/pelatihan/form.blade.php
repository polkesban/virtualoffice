@extends('admin.layouts.admin')
@section('title', isset($item->id) ? 'Edit Program Pelatihan' : 'Tambah Program Pelatihan')
@section('content')
<div class="p-6 max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.pelatihan.index') }}" class="text-sm text-brand-600 hover:underline">← Kembali</a>
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white mt-2">
            {{ isset($item->id) ? 'Edit Program' : 'Tambah Program Pelatihan' }}
        </h1>
    </div>

    <form method="POST"
          action="{{ isset($item->id) ? route('admin.pelatihan.update', $item) : route('admin.pelatihan.store') }}"
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
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Judul Program <span class="text-red-500">*</span></label>
            <input type="text" name="judul" value="{{ old('judul', $item->judul) }}" required
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                   placeholder="e.g. Workshop Penulisan Karya Ilmiah 2024">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Jenis <span class="text-red-500">*</span></label>
                <select name="jenis" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                    @foreach(\App\Models\Pelatihan::$jenisLabels as $k => $v)
                    <option value="{{ $k }}" @selected(old('jenis', $item->jenis)===$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Penyelenggara</label>
                <input type="text" name="penyelenggara" value="{{ old('penyelenggara', $item->penyelenggara) }}"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                       placeholder="e.g. Poltekkes Bandung">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai', optional($item->tanggal_mulai)->format('Y-m-d')) }}"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai', optional($item->tanggal_selesai)->format('Y-m-d')) }}"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Lokasi</label>
            <input type="text" name="lokasi" value="{{ old('lokasi', $item->lokasi) }}"
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                   placeholder="e.g. Aula Graha Utama, Poltekkes Bandung">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Deskripsi / Tujuan</label>
            <textarea name="deskripsi" rows="3"
                      class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">{{ old('deskripsi', $item->deskripsi) }}</textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Target Peserta</label>
                <input type="text" name="peserta_target" value="{{ old('peserta_target', $item->peserta_target) }}"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                       placeholder="e.g. Dosen & Tenaga Kependidikan">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Jumlah Peserta</label>
                <input type="number" name="jumlah_peserta" value="{{ old('jumlah_peserta', $item->jumlah_peserta) }}" min="0"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Hasil / Luaran Kegiatan</label>
            <textarea name="hasil" rows="3"
                      class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                      placeholder="Deskripsikan hasil atau luaran dari kegiatan ini…">{{ old('hasil', $item->hasil) }}</textarea>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Foto Kegiatan</label>
            @if(isset($item->id) && $item->foto)
            <img src="{{ \App\Support\MediaStorage::url($item->foto) }}" class="h-24 rounded-xl mb-2 object-cover">
            @endif
            <input type="file" name="foto" accept="image/*"
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
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
            <a href="{{ route('admin.pelatihan.index') }}" class="px-6 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300">Batal</a>
        </div>
    </form>
</div>
@endsection
