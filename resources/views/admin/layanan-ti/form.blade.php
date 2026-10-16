@extends('admin.layouts.admin')
@section('title', isset($item->id) ? 'Edit Layanan TI' : 'Tambah Layanan TI')
@section('content')
<div class="p-6 max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.layanan-ti.index') }}" class="text-sm text-brand-600 hover:underline">← Kembali</a>
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white mt-2">
            {{ isset($item->id) ? 'Edit: '.$item->nama : 'Tambah Layanan TI' }}
        </h1>
    </div>

    <form method="POST"
          action="{{ isset($item->id) ? route('admin.layanan-ti.update', $item) : route('admin.layanan-ti.store') }}"
          class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 space-y-5">
        @csrf
        @if(isset($item->id)) @method('PUT') @endif

        @if($errors->any())
        <div class="rounded-xl bg-red-50 border border-red-200 p-3 text-sm text-red-700">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Nama Layanan <span class="text-red-500">*</span></label>
            <input type="text" name="nama" value="{{ old('nama', $item->nama) }}" required
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                   placeholder="e.g. Permohonan Akun Email Institusi">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Kategori <span class="text-red-500">*</span></label>
                <select name="kategori" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                    @foreach(\App\Models\LayananTI::$kategoriLabels as $k => $v)
                    <option value="{{ $k }}" @selected(old('kategori', $item->kategori)===$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Kontak Penanggung Jawab</label>
                <input type="text" name="kontak" value="{{ old('kontak', $item->kontak) }}" placeholder="e.g. Ext. 120 / WA 08xxx"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Estimasi Waktu</label>
                <input type="text" name="estimasi_waktu" value="{{ old('estimasi_waktu', $item->estimasi_waktu) }}" placeholder="e.g. 1–3 hari kerja"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Urutan Tampil</label>
                <input type="number" name="urutan" value="{{ old('urutan', $item->urutan ?? 0) }}" min="0"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Deskripsi Layanan</label>
            <textarea name="deskripsi" rows="3"
                      class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                      placeholder="Jelaskan layanan ini secara singkat…">{{ old('deskripsi', $item->deskripsi) }}</textarea>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Prosedur / Langkah Pengajuan</label>
            <textarea name="prosedur" rows="4"
                      class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                      placeholder="1. …&#10;2. …&#10;3. …">{{ old('prosedur', $item->prosedur) }}</textarea>
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
            <a href="{{ route('admin.layanan-ti.index') }}" class="px-6 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300">Batal</a>
        </div>
    </form>
</div>
@endsection
