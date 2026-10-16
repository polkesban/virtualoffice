@extends('admin.layouts.admin')
@section('title', $layanan->exists ? 'Edit Layanan Lab' : 'Tambah Layanan Lab')

@section('content')
<div class="p-6 max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.layanan-lab.index') }}" class="text-gray-400 hover:text-gray-600">←</a>
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">
            {{ $layanan->exists ? 'Edit: ' . $layanan->nama : 'Tambah Layanan Lab' }}
        </h1>
    </div>

    <form method="POST"
          action="{{ $layanan->exists ? route('admin.layanan-lab.update', $layanan) : route('admin.layanan-lab.store') }}"
          class="space-y-5">
        @csrf
        @if($layanan->exists) @method('PUT') @endif

        @if($errors->any())
        <div class="rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 space-y-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Layanan <span class="text-red-500">*</span></label>
                <input type="text" name="nama" value="{{ old('nama', $layanan->nama) }}" required
                       placeholder="Pemeriksaan Hematologi Lengkap"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Kategori <span class="text-red-500">*</span></label>
                <select name="kategori" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                    @foreach(\App\Models\LayananLab::$kategoriLabels as $k => $v)
                    <option value="{{ $k }}" @selected(old('kategori',$layanan->kategori??'umum')===$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Deskripsi</label>
                <textarea name="deskripsi" rows="3"
                          class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">{{ old('deskripsi', $layanan->deskripsi) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Tarif (Rp)</label>
                    <input type="number" name="tarif" value="{{ old('tarif', $layanan->tarif) }}"
                           min="0" step="500" placeholder="Kosongkan jika fleksibel"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Satuan</label>
                    <input type="text" name="satuan" value="{{ old('satuan', $layanan->satuan) }}"
                           placeholder="per sampel / per pemeriksaan"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Waktu Pengerjaan</label>
                <input type="text" name="lama_pengerjaan" value="{{ old('lama_pengerjaan', $layanan->lama_pengerjaan) }}"
                       placeholder="1-2 hari kerja"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Syarat / Persyaratan</label>
                <textarea name="syarat" rows="3" placeholder="Bawa sampel minimal 3 mL, form permintaan dari dokter, dll."
                          class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">{{ old('syarat', $layanan->syarat) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Urutan Tampil</label>
                    <input type="number" name="urutan" value="{{ old('urutan', $layanan->urutan ?? 0) }}"
                           min="0" max="127"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div class="flex items-end pb-1">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="aktif" id="aktif" value="1"
                               {{ old('aktif', $layanan->aktif ?? true) ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-brand-600">
                        <label for="aktif" class="text-sm font-medium text-gray-700 dark:text-gray-300">Aktif</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <a href="{{ route('admin.layanan-lab.index') }}"
               class="px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium">Batal</a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
                {{ $layanan->exists ? 'Simpan Perubahan' : 'Tambah Layanan' }}
            </button>
        </div>
    </form>
</div>
@endsection
